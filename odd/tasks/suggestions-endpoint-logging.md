# Feature: trazas detalladas del endpoint POST /api/suggestions/inbox

## Objective

Que **cada request** al endpoint de creacion de sugerencias de egresos
(`POST /api/suggestions/inbox`) deje una traza completa y greppable de **todas**
las etapas por las que pasa: desde que entra el body crudo hasta que se
responde. Hoy existen logs, pero hay puntos ciegos: cuando algo falla, el log
no dice **donde** fallo ni **por que**.

## Problem

`POST /api/suggestions/inbox` es el endpoint mas exposed del proyecto: lo
dispara MacroDroid desde el celular con cada notificacion del sistema. Su
cadena es larga y heterogenea:

```
ApiController::suggestions()
  -> resolveUserId()          (payload user_id | email | default)
  -> SuggestionIngest::ingest()
       -> NotificationPayload::parse()      (dump "|" de MacroDroid)
       -> hasAmount() / isDeclined()        (filtros previos a la IA)
       -> pendingEquivalent()               (dedupe contra pendientes)
       -> NotificationSuggestion::ingest()  (INSERT staging)
       -> AiExtractor::extract()
            -> catalogue()                  (3 SELECTs al catalogo)
            -> AiClient::chat()             (MiniMax -> Gemini fallback)
            -> parseJson() / fallback()
            -> resolveIds() / inferByKeywords()
       -> NotificationSuggestion::applyExtraction()  (UPDATE staging)
  -> respond()                (JSON)
```

Puntos ciegos actuales (revisados archivo por archivo):

1. **El cuerpo crudo se parsea sin dejar rastro.** Si el JSON viene roto, el
   log no dice cual fue el error de `json_decode`, ni si el payload vino como
   JSON, como formulario o como texto plano. `SuggestionIngest` termina
   usando el body crudo como si fuera la notificacion, y eso no se distingue
   de una notificacion legitima.
2. **`resolveUserId()` no deja rastro del origen.** Si la sugerencia se crea
   para el usuario equivocado (fallback a `SUGGESTION_USER_ID` porque el
   `user_email` del payload no existe), no hay forma de saberlo desde el log.
3. **El `catch (Throwable)` solo loguea el mensaje.** Sin archivo, sin linea,
   sin traza: el error mas comun ("no se pudo crear la sugerencia") queda
   como una frase sin contexto.
4. **`AiExtractor` no loguea NADA.** Es la etapa mas opaca: no se sabe que
   catalogo se cargo, cuan largo fue el prompt, si el modelo devolvio JSON
   utilizable, que nombres se resolvieron a ids, que campos se rellenaron por
   heuristica, ni **por que** la sugerencia quedo `needs_review`.
5. **`AiClient::fail()` escribe con `error_log` crudo**, fuera del formato
   `clave=valor` de `ProcessLog`: los errores de proveedor no se pueden
   correlacionar con el `req=` del request ni grepear por nivel.
6. **`SuggestionIngest` no dice de donde salio el texto** ni la referencia de
   deduplicacion. El `source_ref` puede venir de 3 sitios (payload, header
   `X-Notification-Id`, epoch de la notificacion) y cada uno cambia el
   comportamiento del dedupe.
7. **`NotificationSuggestion` no loguea el resultado de sus escrituras.** Un
   `INSERT` fallido y un dedupe por hash son el mismo `created: false` en el
   log, pero son fallos opuestos.
8. **No hay nivel de error.** Todas las lineas se ven igual: no se puede
   hacer `grep level=error` para ver solo lo que fallo.

## Why

- El endpoint se dispara por cada notificacion del celular. Cuando "no le
  llegan sugerencias", el diagnostico es adivinar, y el unico dato es un
  `skipped` sin contexto.
- La IA es el eslabon mas frágil (MiniMax se cae, se trunca, devuelve JSON
  invalido, agota saldo). Hoy un fallback a regex es **indistinguible** de un
  acierto de la IA en el log.
- Un dedupe mal calculado **tumba** una sugerencia legitima sin dejar rastro.
  Es el fallo mas caro porque es silencioso.

## Scope

**In scope**

- `ProcessLog`: nivel (`info`/`error`), secuencia numerica por request,
  helper de excepcion con archivo/linea/traza, limite de sanitizacion
  parametrizable.
- `ApiController::suggestions()`: rastro del parseo del body, del origen del
  usuario, y excepcion completa con traza.
- `SuggestionIngest`: origen del texto, origen del `source_ref`, origen del
  `source`, detalle de la extraccion, fallo de `applyExtraction`.
- `AiExtractor`: catalogo, prompt, respuesta, parseo del JSON, resolucion de
  ids, heuristicas aplicadas y **razones** de `needs_review`.
- `AiClient`: peticion/respuesta por proveedor y errores via `ProcessLog`.
- `NotificationSuggestion`: resultado de dedupe, insert y guardado de la
  extraccion.

**Out of scope**

- Cambiar el comportamiento del endpoint (mismos codigos HTTP, mismos
  `status`, misma logica de filtros y dedupe). Esto es **instrumentalizacion**.
- Enviar los logs a un servicio externo: se siguen escribiendo con
  `error_log` (=> stderr => `docker logs` / `kubectl logs`).
- Cambiar el schema de `notification_suggestions`.
- Loguear el texto crudo completo de la notificacion: es informacion
  financiera. Se mantiene el `preview()` acotado.

## Constraints

- **No loguear secretos**: keys de IA, credenciales de BD, ni el texto crudo
  completo. El `preview()` truncado se mantiene como unico mecanismo para
  reconocer la notificacion.
- **No loguear PII innecesaria**: el `user_email` del payload NO se escribe;
  solo el origen de la resolucion y si el lookup encontro o no al usuario.
- **Formato estable**: una linea por evento, `clave=valor`, con
  `[timestamp] [pid] [req] [seq] [scope] level=... stage=...`.
- **Sin loguear dentro de un `catch` que se traga la excepcion**: si se
  captura una excepcion para registrarla y despues se relanza, queda claro.
- Compatibilidad: `ProcessLog::event()` mantiene su firma actual; todo lo
  nuevo son metodos adicionales.

## Tasks

- [x] **T1** `ProcessLog`: `level`, `seq`, `error()`, `exception()`, traza,
      sanitizacion con limite variable. Actualizar el docblock del formato.
- [x] **T2** `ApiController::suggestions()`: log `payload` (origen del body:
      `json`/`form`/`unparsed`/`empty`, error de `json_decode`, claves
      recibidas), log `user` (origen: `payload_user_id`/`payload_email`/
      `default`, sin el email), excepcion con traza.
- [x] **T3** `SuggestionIngest`: log `text` (de que clave salio), log
      `source_ref` (de donde salio), log `source`, detalle de la extraccion
      (monto, descripcion, ids) y `apply_failed` con rethrow.
- [x] **T4** `AiExtractor`: logs `catalogue`, `prompt`, `ai_failed`,
      `parse_json`, `resolve_ids`, `heuristics`, `finish` y `review_reasons`.
- [x] **T5** `AiClient`: log `ai_request` (mensajes, bytes, timeout), bytes
      de respuesta en `ai_attempt`, y `fail()` via `ProcessLog`.
- [x] **T6** `NotificationSuggestion`: logs `dedupe_hit`, `insert_failed`,
      `extraction_saved`.
- [x] **T7** Verificacion: `php -l` en los 6 archivos + trace manual del
      endpoint.

> **Nota**: este trabajo se hizo junto con el cambio de comportamiento de la
> ingesta (`odd/tasks/suggestions-ingest-always-create.md`), que quita las
> omisiones por falta de monto y por duplicado. Los logs nuevos son lo que
> permite ver por que una notificacion llego o no a la lista.

## Acceptance criteria

- Un request que termina bien deja, en orden, la traza completa: `start`,
  `payload`, `user`, `request`, `ingest_call`, `text`, `parse`, `dedupe`,
  `insert`, `catalogue`, `prompt`, `ai_request`, `ai_attempt`, `ai_result`,
  `parse_json`, `resolve_ids`, `heuristics`, `review_reasons`, `extract`,
  `apply`, `ingest_result`, `respond`, `done`.
- Un fallo de la IA queda con `level=error` y se ve si fue de MiniMax o de
  Gemini, con su codigo (`minimax_1004`, `truncated`, `http_error`...).
- Un `catch` de excepcion loguea `exception`, `message`, `where` (archivo y
  linea) y `trace`.
- `grep level=error` devuelve **solo** fallos.
- `grep req=<id>` devuelve la traza completa de un request, de principio a fin.
- El fallback por regex (IA caida) queda distinguible de un acierto de la IA
  (`source=fallback` + `ai_failed` + `review_reasons`).
- El texto crudo completo de la notificacion **no** aparece en ninguna linea.

## Checks

- `php -l` en cada archivo modificado.
- Trace manual del endpoint (servidor local o el que este configurado):
  `curl -sS -X POST http://127.0.0.1:8124/api/suggestions/inbox -H
  'Content-Type: application/json' -d '{"raw_text":"..."}'` y revisar las
  lineas emitted con el mismo `req=`.
- Escenario de error: cuerpo JSON invalido -> debe quedar `payload
  source=unparsed` + el error de `json_decode`.
- Escenario de error: sin credenciales de IA -> debe quedar `level=error`
  con `code=minimax_api_key_missing` y el `fallback` activated.
- `grep level=error` sobre el log: no debe traer lineas de INFO.

## Relevant files

- `app/services/ProcessLog.php` — formato de la traza (se extiende)
- `app/controllers/ApiController.php` — `suggestions()`, borde del endpoint
- `app/services/SuggestionIngest.php` — orquestacion de la ingesta
- `app/services/AiExtractor.php` — extraccion con IA (hoy sin logs)
- `app/services/AiClient.php` — HTTP a MiniMax/Gemini
- `app/models/NotificationSuggestion.php` — staging en base de datos
