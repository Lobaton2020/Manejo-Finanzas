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
| Proveedor de IA (primario) | MiniMax, `MiniMax-M3`, `POST https://api.minimax.io/v1/chat/completions` |
| Proveedor de IA (fallback) | Gemini `gemini-3.5-flash` si MiniMax falla (402/caído/sin key) |
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

## Gotchas de Gemini (fallback, verificados contra la API real)

1. `gemini-2.5-flash` y `gemini-2.0-flash` responden **404 "no longer
   available to new users"** aunque aparezcan en `ListModels`. El modelo
   vigente verificado es **`gemini-3.5-flash`** (`gemini-3.8-flash` hizo
   timeout; `gemini-flash-latest` dio 503 por demanda).
2. Gemini 3.x **razona por defecto** y ese razonamiento consume
   `maxOutputTokens` y ensucia el `text` (en una prueba con 80 tokens solo
   salió `"{"`). Se apaga con
   `generationConfig.thinkingConfig.thinkingBudget = 0`.
3. `responseMimeType: "application/json"` fuerza JSON puro, pero igual se
   reutiliza `parseJson()` por robustez.
4. La API key va en el header **`x-goog-api-key`**, nunca en la query string,
   para no dejarla en logs.
5. La respuesta útil está en `candidates[0].content.parts[*].text` (los parts
   pueden traer `thoughtSignature`; se ignoran). `finishReason` distinto de
   `STOP` (`MAX_TOKENS`, `SAFETY`, `RECITATION`) se trata como fallo.

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
| `1571bf5` | Servidor embebido de PHP para probar en local |
| `47bfdb4` | Heurísticas: opción por defecto según contexto |
| `3565cce` | Fallback de IA a Gemini + heurísticas de contexto negativo |

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

### Estado de los checks de IA

**MiniMax sigue respondiendo `HTTP 402 insufficient_balance_error (1008)`**:
la cuenta no tiene saldo. Por eso se agregó **Gemini como fallback**.

- La vía con IA **quedó validada end-to-end con Gemini** (`gemini-3.5-flash`):
  `scripts/extract_probe.php` con los 4 textos de prueba devolvió `provider:
  gemini` y los datos correctos (ver tabla abajo). El flujo degradado por
  regex sigue existiendo si ambos proveedores fallan.
- Cuando MiniMax recupere saldo, `chat()` lo vuelve a intentar primero sin
  cambiar nada. `POST ?url=suggestion/reprocess/{id}` reprocesa pendientes.
- Evidencia (probe, `php -d extension=pdo_mysql -d opcache.enable=0`):

| Texto | Tipo | Categoría | Depósito | Monto |
|---|---|---|---|---|
| Bre-B / Nequi (original) | Gasto | Otros | — | 100 |
| Compra EDS ENGATIVA | Gasto | Gasolina moto | — | 35.127 |
| Compra de bitcoin | Inversion | Bitcoin (BTC) | Inversion | 500.000 |
| "Número de celular" (transferencia) | Gasto | Otros (no Tecnología) | — | — |

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

## Cómo probar en local

El container (`podman-compose`) **no levanta en esta máquina**: con
`userns_mode: keep-id` el root del container es el uid 1000 del host y Apache
no puede hacer bind del puerto 80 (`ip_unprivileged_port_start = 1024`, sin
`CAP_NET_BIND_SERVICE`). Además los volúmenes con `:U` fallaban con
"failed to chown recursively host path" sobre `/mnt/disco2`.

La vía local es el **servidor embebido de PHP** con un router que emula el
rewrite del `.htaccess`:

```bash
./scripts/dev-server.sh          # http://127.0.0.1:8124
./scripts/dev-server.sh 9000     # otro puerto
```

- Carga `pdo_mysql` si el PHP del sistema no lo trae (Arch: `php -d
  extension=pdo_mysql`).
- Desactiva opcache en CLI: si no, el server sirve código viejo durante 180 s
  (`opcache.revalidate_freq`) y los cambios no se ven.
- `scripts/dev-router.php` traduce `/outflow` → `index.php?url=outflow`, igual
  que el `.htaccess`, así que las URLs de la app funcionan igual que en
  producción.

Prueba del webhook:

```bash
curl -X POST 'http://127.0.0.1:8124/api/suggestions/inbox' \
  -H 'Content-Type: application/json' \
  -d '{"raw_text":"Compra Exitosa\nEDS ENGATIVA FR\n$ 35.127,00\n19/11/2026 18:30","source_ref":"demo-1"}'
```

Y para probar la UI sin contraseña (solo en desarrollo), se puede forjar una
sesión por CLI:

```bash
php -r 'session_id("sugtest"); session_start();
$_SESSION["activeSession"]=true;
$_SESSION["credentials"]=["id"=>1,"rol"=>["name"=>"Administrador"],"name"=>"Andres",
"email"=>"andrespipe021028@gmail.com","image"=>null,"document"=>"1"];
session_write_close();'
# luego: curl -b "PHPSESSID=sugtest" http://127.0.0.1:8124/suggestion
```

## Relevant files

- `app/services/AiClient.php` — cliente IA: MiniMax primario + Gemini fallback
- `app/services/AiExtractor.php` — prompt + parseo + resolve ids
- `app/models/NotificationSuggestion.php` — staging de sugerencias
- `app/controllers/SuggestionController.php` — UI listar/aprobar/descartar
- `app/controllers/ApiController.php` — ruta `api/suggestions/inbox`
- `app/controllers/OutflowController.php` — prefill + marcado de aprobado
- `app/views/outflows/suggested.php` — lista de pendientes
- `public/assets/js/outflow.js` — preselect de categoría
