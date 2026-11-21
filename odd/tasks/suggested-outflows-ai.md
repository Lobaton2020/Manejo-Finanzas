# Feature: suggested-outflows (IA sobre notificaciones de pago)

## Objective

Botón **Suggested** dentro del módulo de Egresos. Las notificaciones de pago
(Gmail → MacroDroid → webhook) llegan como **texto no estructurado**; un LLM
(MiniMax-M3) las convierte a datos estructurados y el usuario **aprueba en un
clic** o **edita** antes de que se cree el egreso real.

## Problem

Hoy un egreso con tarjeta se registra a mano: abrir Egresos → llenar tipo,
categoría, depósito, monto, fecha. El dato ya existe, pero llega como texto de
un correo del banco y nadie lo estructura.

## Why

- El texto de la notificación tiene el monto, la fecha, el comercio y la
  cuenta origen: alcanza para prellenar el 80% del formulario.
- La app ya tiene el "plan → aprobar → egreso real" resuelto en
  `BudgetController::execList()/execOne()` → `OutflowServie::perform_egress()`.
  No hay que inventar reglas: hay que reusarlas.
- El egreso real nunca se crea sin que el usuario lo confirme.

## Scope

**In scope**

- Endpoint `POST ?url=api/suggestions/inbox` que acepta texto crudo
  (JSON con claves conocidas o texto plano) y devuelve la extracción.
- Tabla `notification_suggestions` (staging + estado de aprobación).
- `AiClient` (cliente HTTP de MiniMax) + `AiExtractor` (prompt, catálogo,
  parseo robusto, resolución de ids, fallback por regex).
- `?url=suggestion` → lista de sugerencias pendientes, con **Aprobar**
  (un clic) y **Editar** (abre el form de egreso prellenable).
- Prefill del form existente `outflow/create` + marcado de la sugerencia como
  aprobada cuando `outflow/store` inserta.
- Botón `Suggested` en la lista de Egresos.

**Out of scope**

- Ingresos (`inflows`): el módulo es de egresos. Una transferencia entra como
  egreso con el tipo de egreso que elija el usuario.
- Módulo de Presupuesto: el botón vive solo en Egresos (decisión del usuario).
- Auth del webhook: sin auth, igual que `api/notifications/transactions`
  (red privada). El usuario se resuelve por `SUGGESTION_USER_ID`.
- Reemplazo del cliente Android `app-reader-notifications` (decisión del
  usuario: el pipeline nuevo es el único activo).

## Constraints

- PHP 8 vanilla, **sin Composer**, sin frameworks, sin tests, sin linter.
- **No tocar** `Router.php`, `Base.php`, `Authentication.php`, `.htaccess`.
- Rutas por convención: `?url=controller/method`. No hay tabla de rutas.
- DDL **manual** (no hay migraciones en el repo).
- `outflows.amount` es `FLOAT` → heredar, no cambiar el schema.
- `perform_egress()` lanza `ErrorException` si el saldo no alcanza → la UI
  debe traducirla a mensaje, no a 500.
- `short_open_tag = Off` en php.ini → **todo el PHP nuevo con `<?php`**.
  (Bug preexistente: `create.php` usa `<? if (!$is_budget): ?>` → se imprime
  literal en el HTML. Se corrige de paso.)
- `config.php` parsea `.env` con `explode("=")[1]` → los valores no pueden
  contener `=`. La API key de MiniMax no lo tiene.

## Decisiones tomadas

| Decisión | Valor |
|---|---|
| Proveedor de IA | MiniMax, `MiniMax-M3`, `POST https://api.minimax.io/v1/chat/completions` |
| Pipeline de entrada | Solo el nuevo (Gmail → MacroDroid → webhook crudo) |
| Destino al aprobar | Egreso real directo (`perform_egress()`) |
| Depósito `id_porcent` | La IA lo sugiere por nombre, el usuario confirma |
| Zona horaria | `America/Bogota` (default de la app) |
| Botón | Solo en el módulo de Egresos |

## Diseño: qué se reusa (y por qué el estimate se achica)

1. **`outflow/create` + `outflow/store` ya existen.** El prefill entra por
   `$_GET` (igual que hoy hace `is_budget` e `id_temporal_budget`) → **no hay
   form nuevo, no hay view nueva de formulario**.
2. **`OutflowServie::perform_egress()`** → valida saldo, inserta, notifica
   `"egress"`, maneja el caso "inversion" con `id_group_investment`. No se
   duplica esa lógica.
3. **`make_table`, `renderMessage`, `route`, `number_price`, `execute_post`,
   `is_correct`, `redirect()->with()`** → el listar y el aprobar son 40 líneas.
4. **La IA NO devuelve ids de DB, devuelve NOMBRES.** El backend los resuelve
   de forma determinística (normalizar: minúsculas, sin tildes, sin signos;
   coincidencia exacta → prefijo 8 chars, misma técnica que
   `TransactionMerger::isMerchantSimilar` del cliente Android). Si no hay
   match, el select queda vacío y lo elige el usuario.
5. **Fallback determinista por regex** si el LLM falla: la sugerencia nunca
   se pierde.

## Gotchas de MiniMax (verificados en la doc)

1. `response_format` / `json_schema` **solo existe en `MiniMax-Text-01`** →
   con M3 el JSON se pide por prompt y se parsea a mano (quitar fences
   ```json, quitar `<thinking>`, primer `{` … último `}`).
2. `temperature` y `top_p` **no hacen nada** en M3 (siempre 1 / 0.95).
3. `max_completion_tokens` **cuenta los tokens del thinking**: si queda corto →
   `finish_reason: "length"` y `content` **vacío**. Margen de 4096 + chequeo
   explícito de `content` vacío.
4. `reasoning_split: true` deja `content` limpio (el razonamiento va en
   `reasoning_content`).
5. `thinking: {"type":"disabled"}` es válido en M3 (en M3.1-Flash da 400).
6. Los errores vienen en `base_resp.status_code` (**no** en el status HTTP):
   1004 auth, 1002 rate limit, 1001 timeout, 1027 output inválido.
7. `input_sensitive` / `output_sensitive` → si el modelo marca el contenido
   como sensible, la sugerencia se guarda con `needs_review = 1`.

## Tasks

- [x] **T1 — DDL**: `scripts/ddl_notification_suggestions.sql` + aplicado en
      `192.168.20.240/finanzas`. Tabla `notification_suggestions` verificada.
- [x] **T2 — Config**: `.env` + `.env.example` + constantes en
      `app/config/config.php` (`MINIMAX_*`, `SUGGESTION_USER_ID`). El parser
      de `.env` ahora ignora comentarios y no trunca valores con `=`.
- [x] **T3 — `AiClient`**: `app/services/AiClient.php`. curl, `base_resp`,
      `finish_reason`, `content` vacío, timeout, sin filtrar la key a logs.
- [x] **T4 — `AiExtractor`**: `app/services/AiExtractor.php`. Catálogo real del
      usuario, prompt con schema JSON, parseo robusto, resolución de ids,
      fallback regex (monto `\d{1,3}(\.\d{3})*,\d{2}` y fecha `yyyy/MM/dd` y
      `dd/MM/yyyy`).
- [x] **T5 — `NotificationSuggestion`**: `app/models/NotificationSuggestion.php`.
      Ingest + dedupe por `source_ref` (o texto normalizado si no viene).
- [x] **T6 — Endpoint**: `ApiController::suggestions('inbox')`. Acepta JSON con
      claves conocidas o texto plano. Devuelve la extracción.
- [x] **T7 — Prefill**: `OutflowController::create()` lee `$_GET["suggestion"]`
      y precarga tipo / categoría / depósito / monto / descripción / fecha.
      `store()` marca la sugerencia como aprobada.
- [x] **T8 — Aprobar en 1 clic**: `SuggestionController::approve()` →
      `perform_egress()` + `status = 1`. `discard()` → `status = 2`.
      `reprocess()` reintenta la IA.
- [x] **T9 — UI**: `app/views/outflows/suggested.php` (lista) + botón
      `Suggested` con contador en `outflows/list.php` + preselect del radio de
      categoría en `create.php`.
- [x] **T10 — Corrección `short_open_tag`**: `create.php` usaba `<?` → se
      imprimía literal en el HTML. Corregido a `<?php`.
- [x] **T11 — Verificación**: `php -l` en todos los archivos; webhook probado
      por HTTP; flujo completo probado (crear → aprobar → egreso → saldo).

## Progress

Commits en `feature/suggested-outflows`:

| Commit | Alcance |
|---|---|
| `7060420` | T1-T5 + T2 (DDL, config, AiClient, AiExtractor, modelo) |
| `8e0dba1` | T6 (endpoint) |
| `bc8b929` | T7-T10 (UI, prefill, botón) |
| `fc8c809` | Feature doc |

Evidencia de las pruebas (servidor embebido PHP + `curl` con sesión):

| Prueba | Resultado |
|---|---|
| `POST api/suggestions/inbox` con texto BBVA | 200, sugerencia creada, `amount=100`, `event_date=2026-11-19` |
| Mismo `source_ref` reenviado | 200 `{"status":"duplicate"}` — no duplica |
| Texto plano (sin JSON) | 200, también extraído |
| `GET ?url=suggestion` | 200, lista con fecha, monto y badge "Revisar" |
| `GET ?url=outflow/create&suggestion=7` | 200, monto/descripción/fecha precargados |
| `POST suggestion/approve/7` | 200, egreso creado y sugerencia `status=1` |
| `POST suggestion/discard/8` | 200, sugerencia `status=2` |
| `POST outflow/store` con `id_suggestion` | 200, egreso creado + sugerencia aprobada |
| `php -l` en 11 archivos | sin errores |

### Checks pendientes (bloqueados por la cuenta de MiniMax)

**La API key de MiniMax responde `HTTP 402 insufficient_balance_error
(1008)`**: la cuenta no tiene saldo. Consecuencias:

- La vía con IA **no se pudo validar end-to-end**. El prompt, el parseo del
  JSON del modelo y la resolución de ids están escritos y revisados, pero
  nunca se ejecutaron contra una respuesta real de MiniMax.
- Lo que sí quedó verificado es la **vía degradada**: el fallback por regex
  extrae monto y fecha, y el flujo completo (sugerir → aprobar → egreso real)
  funciona con esos datos.
- Al topping la cuenta: `POST ?url=suggestion/reprocess/{id}` reprocesa las
  sugerencias pendientes, y `scripts/extract_probe.php` permite iterar el
  prompt sin levantar la app.

### Deuda técnica conocida

- `app/services/DepositBalanceService.php` no se creó: el saldo disponible de
  cada depósito no se le pasa a la IA (no lo necesita para elegir el
  depósito; el formulario ya lo muestra y `perform_egress` lo valida). El
  cálculo de saldo sigue duplicado entre `ReportController` y el futuro
  servicio, si algún día se unifica.
- `InflowController::store()` hace `return execute_post(...)` y por eso el
  `Router` intenta castear el objeto `Redirect` a string → error 500 al
  guardar un ingreso. Bug preexistente, no tocado por este feature (los
  controllers de egresos y presupuesto no hacen ese `return`).


## Acceptance criteria

- Un POST de texto crudo a `?url=api/suggestions/inbox` crea una sugerencia
  con monto, fecha, tipo, categoría y depósito resueltos.
- Reenviar el mismo texto **no** duplica la sugerencia.
- Con la API key inválida, la sugerencia igual se crea (fallback regex) con
  `needs_review = 1`.
- `?url=suggestion` lista las pendientes con monto, fecha y confianza.
- **Aprobar** crea el egreso real y descuenta el depósito.
- **Editar** abre `outflow/create` con todo precargado; al guardar, el egreso
  se crea y la sugerencia queda aprobada.
- `perform_egress` con saldo insuficiente → mensaje de error en la UI, no 500.

## Checks

- `php -l` en cada archivo nuevo y modificado.
- `curl -X POST 'http://<host>/?url=api/suggestions/inbox' -H 'Content-Type:
  application/json' -d '{"raw_text":"<texto BBVA>"}'`.
- `curl` directo a MiniMax con el texto de ejemplo → JSON con `amount` 100.00,
  `event_datetime` 2026-11-19T21:46.
- UI: `?url=suggestion` → aprobar → fila nueva en `?url=outflow` y saldo del
  depósito actualizado.

## Relevant files

- `app/services/AiClient.php` — cliente MiniMax
- `app/services/AiExtractor.php` — prompt + parseo + resolve ids
- `app/models/NotificationSuggestion.php` — staging de sugerencias
- `app/controllers/SuggestionController.php` — UI listar/aprobar/descartar
- `app/controllers/ApiController.php` — ruta `api/suggestions/inbox`
- `app/controllers/OutflowController.php` — prefill + marcado de aprobado
- `app/views/outflows/suggested.php` — lista de pendientes
- `public/assets/js/outflow.js` — preselect de categoría
