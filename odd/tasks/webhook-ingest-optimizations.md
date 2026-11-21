# Feature: optimizaciones del webhook de sugerencias

## Objective

Optimizar `POST /api/suggestions/inbox` para que cada notificacion que llega
del celular (Gmail, Google Wallet, apps) produzca **una sola sugerencia
correcta**: descartar lo que no es un egreso, no duplicar la misma compra que
llega por dos canales, dejar el formulario con la mejor preseleccion posible
y sin ruido en la descripcion.

## Problem

Hoy el webhook procesa **cualquier** notificacion del sistema:

- Se guarda una sugerencia por notificacion, incluso las que no son un egreso
  (YouTube Music, notificaciones genericas) -> la tabla se llena de ruido.
- El mismo evento que llega por dos canales (correo del banco + Google Wallet)
  genera **dos** sugerencias pendientes del mismo egreso.
- El texto crudo que manda MacroDroid viene como un **dump con separador `|`**
  (`titulo | subtitulo | texto | canal | paquete | ...`). Ese `|` se cuela en
  la descripcion y en el prompt.
- Cuando la notificacion no trae fecha, `event_date` queda `NULL` y el
  formulario aparece sin fecha.
- Cuando la notificacion no trae evidencia de deposito, el select queda vacio
  aunque haya un deposito "general" obvio.

## Why

- El webhook se invoca por **cada notificacion del telefono**, no solo por
  pagos. El costo por llamada de IA es real: cada llamada que se evita es una
  llamada menos a MiniMax/Gemini.
- Una sugerencia basura en la lista pendiente cuesta un clic del usuario para
  descartarla, y tapa las que si sirven.
- La compra duplicada (correo + Wallet) obliga a aprovar dos veces o a
  descartar a mano.

## Scope

**In scope**

- Parseo del payload `|` de MacroDroid a campos utiles (titulo, texto,
  timestamp de la notificacion) y descarte de los campos de metadatos
  (canal, paquete, api level, memoria).
- Pre-validacion **antes** de llamar a la IA y antes de crear el registro:
  - el texto tiene al menos un monto con formato de dinero,
  - el texto no es un pago **rechazado / declinado / fallido**.
- Deduplicacion contra las sugerencias **pendientes** del usuario: mismo
  monto + descripcion similar + ventana temporal.
- Los pendientes recientes se pasan al modelo como **valores previos** para
  que pueda cruzar una compra repetida por dos canales.
- Valores por defecto: fecha del evento y deposito, cuando el mensaje no da
  evidencia suficiente.
- Descripcion limpia: sin `|`, sin timestamp, solo contexto del egreso.
- El endpoint responde `skipped` (con motivo) en vez de `400`, para que el
  cliente no reintente.

**Out of scope**

- Cambiar el cliente Android / MacroDroid.
- Aprobar sugerencias automaticamente: sigue siendo decision del usuario.
- Unificar el calculo de saldo de los depositos.

## Constraints

- PHP 8 vanilla, sin Composer, sin framework, sin tests ni linter.
- La tabla `notification_suggestions` tiene `event_date date` (**no**
  `event_datetime`). No agregar columnas: el timestamp completo se guarda
  dentro de `parsed_json`.
- `notification_suggestions` y `porcents` se leen con el ORM del proyecto
  (`select`/`get`/`update` con la sintaxis `["col[=]" => valor, "AND"]`).
- `config.php` parsea `.env` con `explode("=")[1]`: los valores no pueden
  contener `=`.
- `short_open_tag = Off`: todo el PHP nuevo con `<?php`.
- La app corre en `America/Bogota`.
- No tocar `Router.php`, `Base.php`, `Authentication.php`, `.htaccess`.

## Decisiones tomadas

| Decision | Valor |
|---|---|
| Sin monto en el texto | **No se crea** el registro (se responde `skipped`) |
| Pago rechazado / declinado | **No se crea** el registro (se responde `skipped`) |
| Duplicado contra pendiente | No se crea; se responde `duplicate` con el id existente |
| Ventana de duplicado | +/- 6 horas sobre la fecha efectiva |
| Similitud de descripcion | Tokens significativos (6+ letras, no genericos): igualdad, contencion, prefijo de 8 chars o una palabra significativa compartida |
| Fecha sin evidencia | Fecha/hora del evento generado (ahora, `America/Bogota`) |
| Deposito por defecto | `Inversion` para el tipo de egreso `Inversion`; `Gastos en general` para el resto |
| Valores previos en el prompt | Los 5 pendientes mas recientes (monto, fecha, descripcion) |
| Respuesta de omitido | HTTP 200 con `{"status":"skipped","reason":"..."}` |

## Gotchas descubiertos (con evidencia real)

1. El payload real del celular es un **dump separado por `|`**:
   `titulo | subtitulo | texto_grande(+timestamp) | texto | canal | paquete | api | memoria`.
   Los campos 2 y 4 vienen vacios en la mayoria de las notificaciones.
2. El **timestamp epoch ms va pegado al final** del campo de texto
   (`... with Glim ••8890 1795270163703`). Si no se quita, parece un monto.
3. Los campos de **metadatos** (`tapandpay.transactions.low`,
   `com.google.android.apps.walletnfcrel`, `13`, `3.48GB`) no son informacion
   del egreso: contaminan la descripcion y el prompt.
4. **Wallet** usa formato USA (`COP 62,870.00`); **BBVA** usa formato Colombia
   (`$ 35.127,00`). El parser de montos tiene que cubrir los dos.
5. Una notificacion de pago **rechazado** trae monto y parece un egreso
   (`Pago rechazado`, `DECLINED - COP 11,000.00`, `Tu compra no se pudo
   completar`). Se esta guardando como sugerencia.
6. La tabla **no** tiene `event_datetime`: solo `event_date` (date) y
   `created_at` (timestamp).
7. `porcents` **es** la tabla de depositos (`id_porcent`, `name`, `status`).
   El usuario 1 tiene 11 depositos activos; el "caso general" es
   `Gastos en general` (`id_porcent = 4`) y el de inversion es `Inversion`
   (`id_porcent = 14`).
8. **`.env` local tenia `GEMINI_BASE_URL` con espacios en vez de la URL**
   (`GEMINI_BASE_URL=            `). Como `config.php` define la constante sin
   validar, la llamada a Gemini se armaba como `/v1beta/models/...` y cURL
   fallaba con `cURL error 3: URL rejected: No host part in the URL`. El
   fallback heuristico tapaba el sintoma. **Verificar siempre las constantes
   efectivas** (`php -r 'require "app/config/config.php"; echo GEMINI_BASE_URL;'`)
   antes de culpar al proveedor.
9. **El prefijo de 8 caracteres es insuficiente para comparar notificaciones
   de bancos**: TODAS las compras de BBVA empiezan con "Compra Exitosa", asi
   que dos compras distintas de igual monto se declaraban duplicadas. La
   comparacion va sobre palabras *significativas* (6+ letras, quitando
   genericas como "compra", "exitosa", "mastercard").
10. `GEMINI_TIMEOUT=40` se quedo corto en el primer request (el prompt con
    catalogo + valores previos es largo): `cURL error 28`. Subido a 120 s.

## Tasks

- [x] **T1 — Parseo del payload `|`**: servicio nuevo con funciones puras que
      separan los campos del dump, descartan los metadatos, quitan el
      timestamp epoch pegado y devuelven el texto limpio + el `source_ref`.
      → `app/services/NotificationPayload.php`
- [x] **T2 — Pre-check de monto**: si el texto limpio no tiene un monto con
      formato de dinero, no se crea el registro y no se llama a la IA.
- [x] **T3 — Pre-check de pago rechazado**: idem si el texto dice que el pago
      fue rechazado / declinado / no pudo completar.
- [x] **T4 — Dedupe contra pendientes**: consulta de sugerencias pendientes con
      monto igual, descripcion similar y fecha dentro de la ventana; si hay
      match, no se crea y se devuelve la existente.
- [x] **T5 — Valores previos en el prompt**: los pendientes recientes entran al
      prompt como contexto de referencia.
- [x] **T6 — Fecha por defecto**: `event_date` = fecha del evento (hoy) cuando
      el mensaje no trae fecha.
- [x] **T7 — Deposito por defecto**: deposito "general" cuando no hay
      evidencia en el texto.
- [x] **T8 — Descripcion limpia**: sin `|`, sin timestamp, solo contexto del
      egreso (max 120 caracteres).
- [x] **T9 — Respuesta del endpoint**: `skipped` con motivo, `duplicate` con
      el id de la sugerencia existente.
- [x] **T10 — Verificacion**: `php -l`, curls contra el servidor embebido y
      limpieza de las filas de prueba.

## Progress

### Ruta

Implementacion delegada a un unico escritor (`general`) porque toca 4 archivos
no triviales (`SuggestionIngest`, `AiExtractor`, `NotificationSuggestion` y el
servicio nuevo de parseo). El orquestador reviso el diff, corrigio la
comparacion de similitud, corrigio `.env` y verifico end-to-end.

### Evidencia de verificacion (2026-11-21)

`php -l` sin errores en los 5 archivos. Script de casos (`/tmp/opencode/verify_payload.php`):
15/15 OK (similitud cruzada de canales, no similitud entre "Compra Exitosa -
ALPHA" y "Compra Exitosa - BETA", montos USA y Colombia, epoch, memoria,
declinado, "sin saldo" que NO es declinado).

Curls contra `http://127.0.0.1:8124/api/suggestions/inbox`:

| Caso | Respuesta |
|---|---|
| Compra Wallet nueva | `{"status":"created","id_suggestion":39,...,"source":"ai","description":"OFICINA PRUEBA XYZ","event_date":"2026-11-21","deposit_name":"Gastos en general","confidence":0.85}` |
| Mismo aviso reenviado | `{"status":"duplicate","id_suggestion":39}` |
| Mismo gasto por otro canal (corro del banco, `$ 45.000,00`) | `{"status":"duplicate","id_suggestion":39}` |
| YouTube (sin monto) | `{"status":"skipped","reason":"no_amount"}` |
| Pago rechazado | `{"status":"skipped","reason":"declined"}` |

Filas de prueba borradas al final (39, 40 y los dos declinados 30/31 que
habian creado las pruebas del escritor con el pipeline viejo). La tabla
quedo con 14 filas, todas de notificaciones reales del telefono.

### Cambios fuera del repo

- `.env` local: `GEMINI_BASE_URL` corregido a
  `https://generativelanguage.googleapis.com` y `GEMINI_TIMEOUT` de 40 a 120.
  `.env` esta en `.gitignore`: **no viaja en el commit**.
- **Pendiente en produccion**: el `.env` del servidor no tiene
  `MINIMAX_API_KEY` ni `GEMINI_API_KEY`; hay que agregarlas y reiniciar el
  contenedor, o las notificaciones siguen entrando por el fallback
  heuristico.

## Acceptance criteria

- Una notificacion sin monto (YouTube Music, generica) **no** crea sugerencia:
  la respuesta es `{"status":"skipped","reason":"no_amount"}`.
- Una notificacion de pago rechazado **no** crea sugerencia:
  `{"status":"skipped","reason":"declined"}`.
- La misma compra que llega por dos canales produce **una** sugerencia: la
  segunda responde `{"status":"duplicate","id_suggestion":N}`.
- Una sugerencia sin fecha en el mensaje queda con `event_date` = hoy.
- Una sugerencia sin evidencia de deposito queda con `Gastos en general`
  preseleccionado (o `Inversion` si el tipo es `Inversion`).
- La descripcion no contiene `|`, ni el timestamp epoch, ni el nombre del
  paquete de la app.
- La UI de sugerencias muestra las pendientes ya con esos valores.

## Checks

- `php -l` en cada archivo nuevo o modificado.
- `curl -X POST http://127.0.0.1:8124/api/suggestions/inbox` con:
  - un dump de Wallet de compra real -> 1 sugerencia, descripcion limpia,
    fecha de hoy, deposito preseleccionado;
  - un dump de YouTube Music -> `skipped`, y la tabla no crece;
  - un dump de pago rechazado -> `skipped`, y la tabla no crece;
  - el mismo dump de compra dos veces -> la segunda `duplicate`.
- Conteo de filas de `notification_suggestions` antes y despues de cada
  prueba: las filas de prueba se borran al final.

## Relevant files

- `app/services/SuggestionIngest.php` — orquestacion de la ingesta
- `app/services/AiExtractor.php` — prompt, parseo, defaults, catalogo
- `app/models/NotificationSuggestion.php` — staging + consultas de pendientes
- `app/controllers/ApiController.php` — contrato de la respuesta
- `app/views/outflows/suggested.php` — UI de pendientes
