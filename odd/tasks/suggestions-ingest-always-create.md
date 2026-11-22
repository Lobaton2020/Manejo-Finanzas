# Feature: la ingesta de sugerencias nunca omite notificaciones

## Objective

Que `POST /api/suggestions/inbox` **nunca descarte** una notificacion por
filtros heuristicos. Todo lo que llega del celular se convierte en una
sugerencia y se devuelve al cliente; el usuario decide en la UI cual es un
egreso y cual no.

## Problem

Los filtros introducidos en `odd/tasks/webhook-ingest-optimizations.md`
(commit `03a2f2c`) descartaban notificaciones **antes** de crear la
sugerencia:

| Filtro | Efecto |
|---|---|
| `no_amount` | Si el regex no veia un monto con forma de dinero, respondia `skipped` y **no creaba nada**. |
| `declined` | Si el texto decia "rechazado/declinado", respondia `skipped`. |
| `duplicate_pending` | Si existia una sugerencia pendiente con el mismo monto y descripcion similar, respondia `duplicate` y **no creaba nada**. |

Los tres son heuristicas sobre texto libre, y las tres fallan:

- El regex de monto solo reconoce formatos de dinero. Un `Compra por
  $45.000` con formato raro, o un monto pegado a otros digitos, se pierde.
- El comparador de descripciones (`NotificationPayload::isSimilar`) da
  falsos positivos: dos compras legitimas del mismo monto en la misma hora
  (por ejemplo, dos Cafés de 12.000) se confundian.
- El filtro de "declined" depende de una lista de palabras: un aviso de
  "pago no procesado" no esta en la lista.

El efecto agregado era **notificaciones validas que nunca llegaban a la
lista**, y el diagnostico era imposible: el endpoint respondia `skipped` con
un motivo, sin registro de lo que se habia perdido.

## Why

- El modulo "Suggested" es la via principal para capturar egresos sin
  escribir a mano. Si la captura falla en silencio, el usuario deja de
  confiar en ella y vuelve a cargar todo a mano.
- Es preferible una sugerencia de mas (que el usuario descarta con un clic)
  que una sugerencia que nunca existe.
- El costo del error es asimetrico: un falso negativo se pierde para
  siempre; un falso positivo cuesta un clic.

## Scope

**In scope**

- Quitar la omision por `no_amount`: la sugerencia se crea igual. Si la IA
  tampoco ve el monto, la sugerencia queda con `needs_review=1` y el
  usuario lo completa en el formulario.
- Quitar la omision por `duplicate_pending`: cada canal (correo, Wallet)
  genera su propia sugerencia.
- La misma notificacion reenviada (`dedupe_hash` igual) ya no se omite: se
  **reutiliza** la sugerencia existente y se devuelve con sus datos
  (`source: "existing"`), sin volver a llamar a la IA.
- Un `INSERT` fallido se reporta como error explicito (`insert_failed`,
  HTTP 400) en vez de disfrazarse de `duplicate`.

**Out of scope**

- El filtro `declined`: un pago rechazado no es un egreso. Se mantiene
  (responde `skipped`, HTTP 200). Si se quiere quitar tambien, es una
  linea en `SuggestionIngest::ingest()`.
- El filtro de texto vacio (`empty_text`): no hay nada que registrar.
- Cambiar el schema de `notification_suggestions`.
- La idempotencia por `dedupe_hash`: se mantiene, pero deja de ser una
  omision (se reutiliza en vez de descartarse).

## Decisions

| Decision | Valor |
|---|---|
| Filtro `no_amount` | Desactivado (la sugerencia se crea igual) |
| Filtro `duplicate_pending` | Desactivado (cada canal crea su propia) |
| Filtro `declined` | **Activo** (un pago rechazado no es un egreso) |
| Notificacion reenviada | Se reutiliza la sugerencia existente (`reason: existing`) |
| `INSERT` fallido | Error explicito (`reason: insert_failed`, HTTP 400) |

## Checks

- `php -l` en los archivos modificados.
- Harness de ingesta con stubs de BD e IA (7 casos, ver abajo).
- Trace manual del endpoint contra la BD real: una notificacion sin monto
  debe aparecer en la lista de sugerencias con monto vacio y
  `needs_review=1`.

## Progress

- [x] **T1** `SuggestionIngest::ingest()`: quitar `no_amount` y
      `duplicate_pending`; `duplicate` -> `existing`; `insert_failed`
      explicito.
- [x] **T2** `SuggestionIngest::extractionFromRow()`: reconstruir la
      extraccion desde la fila existente para que la respuesta sea honesta.
- [x] **T3** `NotificationSuggestion::ingest()`: devolver la fila existente
      (`existing`) para poder reutilizarla.
- [x] **T4** Documentacion: docblock de `SuggestionIngest` actualizado.

## Verificacion (harness con stubs de BD e IA)

| Caso | skipped | reason | id | ok |
|---|---|---|---|---|
| Compra con monto | false | - | 999 | true |
| Texto **sin monto** | **false** | - | 999 | **true** |
| Pago declinado | true | declined | - | false |
| **Duplicado pendiente** | **false** | - | 999 | **true** |
| **Misma notificacion reenviada** | **false** | **existing** | 77 | **true** |
| INSERT fallido | false | insert_failed | - | false |
| Payload vacio | false | empty_text | - | false |

En el caso "misma notificacion reenviada" la respuesta trae los datos reales
de la fila (`amount=35127`, `description="Compra en EDS ENGATIVA FR"`,
`source="existing"`) y **no** se vuelve a llamar a la IA.

## Relevant files

- `app/services/SuggestionIngest.php` — orquestacion de la ingesta
- `app/models/NotificationSuggestion.php` — staging (devuelve la fila previa)
- `app/controllers/ApiController.php` — contrato de la respuesta
