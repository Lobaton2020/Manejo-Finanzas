# Feature: notifications-sync-endpoint

## Objective

Backend PHP que recibe transacciones capturadas por `app-reader-notifications`
(Android) y las persiste en un inbox/staging para revisión posterior antes
de promoverlas a `outflows` (futura UI "pre-egreso").

## Problem

El Android actualmente manda a `https://httpbin.org/post` (echo mock).
Necesitamos un endpoint real que:

- Acepte el batch JSON del contrato existente (api-spec.md).
- Sea idempotente por UUID del cliente.
- NO requiera autenticación (uso personal, red privada).
- NO afecte el resto del flujo MVC existente.

## Why

Esta tabla `notifications_transactions` es staging/inbox, NO el registro
final. Próxima fase: UI pre-egreso donde el usuario revisa, completa
datos faltantes y confirma para crear un `outflow`.

## Scope

**In scope:**

- `POST /v1/notifications/transactions` (URL: `?url=v1/notifications/transactions`)
- Tabla nueva `notifications_transactions` con `CREATE TABLE IF NOT EXISTS` en bootstrap
- Validación estricta por campo, **fail-fast en todo el batch** (un row malo → 400 + 0 inserts)
- Idempotencia por PK = UUID cliente (`INSERT IGNORE` vía rowCount)
- Logging mínimo (error_log) para 400 y para batch exitoso
- Response shape: `{ accepted: [], duplicates: [], rejected: [] }`

**Out of scope (futuro):**

- UI pre-egreso
- Promover transaction → workflow (crear outflow desde inbox)
- Auth (Bearer token) — para cuando se exponga públicamente
- Rate limiting
- Multi-user partitioning

## Constraints

- Sin Composer, sin frameworks — vanilla PHP como el resto del proyecto.
- Sin tocar `Router.php`, `Base.php`, `Authentication.php`, `.htaccess`.
- El `.htaccess` ya redirige `/(.+)` → `index.php?url=$1`, no hace falta nada.
- DB ya existe en `192.168.20.240`, no agregar MariaDB local.

## Tasks

- [x] **T1 — Model**: `app/models/NotificationsTransaction.php` con `__construct("notifications_transactions")`, métodos `ensureSchema()`, `ingestBatch($batchId, $clientId, array $rows)`.
- [x] **T2 — Controller del recurso**: `app/controllers/NotificationsController.php` extends Controller (sin `authentication()`). Método `sync()` parsea JSON, valida fail-fast, llama al modelo, responde. Categorías hardcoded en `ALLOWED_CATEGORIES`.
- [x] **T3 — Orquestador**: `app/controllers/ApiController.php` extends Controller (sin `authentication()`). Método `notifications($subroute)` valida HTTP (POST only) y dispatcha a `NotificationsController::sync()` cuando `$subroute === 'transactions'`. **NO hace bootstrap de schema** — el operador crea la tabla a mano.
- [ ] **T6 — DDL manual**: crear la tabla `notifications_transactions` en `192.168.20.240` corriendo el SQL de la sección "Schema DDL" abajo.
- [x] **T4 — Validation**: validar TODOS los campos primero (fail-fast). Si uno falla → 400 con detalle `{ errors: [{index, id, field, reason}] }`. Si todo pasa → insertar.
- [ ] **T5 — Smoke test**: `php -l` en los 3 archivos. Levantar podman-compose y probar con `curl` un payload de muestra + un payload inválido + un re-envío (debe reportar duplicates).

## Architecture

```
URL: ?url=api/notifications/transactions

Router (core)                →  ApiController  (orquestador, sin auth)
                                   │
                                   ├─ constructor: ensureSchema() 1 vez
                                   ├─ notifications("transactions")
                                   │     └─ valida POST
                                   └─ instanciate → NotificationsController (Controller real)
                                                          │
                                                          ├─ parsea php://input
                                                          ├─ valida fail-fast
                                                          ├─ NotificationsTransaction::ingestBatch()
                                                          │     └─ SELECT ids existentes
                                                          │     └─ INSERT IGNORE por fila nueva
                                                          └─ responde JSON

Separación:
  ApiController            →  router/orquestador (delgado, sin lógica)
  NotificationsController  →  Controller real del recurso (extends Controller)
  NotificationsTransaction →  modelo de DB (extends Orm)
  NO existe app/handlers/  →  eliminado, no hacia falta
  Schema                    →  creado MANUALMENTE por el operador (no auto-bootstrap)

## Schema DDL (a correr en 192.168.20.240)

```sql
CREATE TABLE IF NOT EXISTS notifications_transactions (
    id              VARCHAR(36)  NOT NULL PRIMARY KEY,
    client_id       VARCHAR(64)  NOT NULL,
    batch_id        VARCHAR(128) NOT NULL,
    amount          DECIMAL(18, 2) NOT NULL,
    currency        CHAR(3)      NOT NULL,
    merchant        VARCHAR(255) NOT NULL,
    category        VARCHAR(64)  NOT NULL,
    bank            VARCHAR(64)  NOT NULL,
    card_brand      VARCHAR(64)  NULL,
    card_last_four  VARCHAR(4)   NULL,
    payment_method  VARCHAR(255) NOT NULL,
    datetime        BIGINT       NOT NULL,
    captured_at     BIGINT       NOT NULL,
    received_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sources         TEXT            NOT NULL,
    raw_text        TEXT            NOT NULL,
    INDEX idx_notif_tx_datetime (datetime DESC),
    INDEX idx_notif_tx_merchant  (merchant),
    INDEX idx_notif_tx_batch     (batch_id),
    INDEX idx_notif_tx_received  (received_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
```

## Verification

- `php -l app/controllers/ApiController.php` y `php -l app/handlers/NotificationsSyncHandler.php` y `php -l app/models/NotificationsTransaction.php` → sin errores.
- `podman-compose up -d --build` → container sano.
- `curl -X POST 'http://127.0.0.1:8080/?url=api/notifications/transactions' -H 'Content-Type: application/json' -H 'Idempotency-Key: test-batch-1' -d @samples/bbva_compra.json` → 200 con `accepted` poblado.
- Repetir el mismo curl → 200 con `duplicates` poblado.
- `curl -X POST ...` con amount vacío → 400 con `errors[]`.

## Authorized scope (the user explicitly OK'd)

- Crear `app/controllers/ApiController.php`, `app/handlers/NotificationsSyncHandler.php`, `app/models/NotificationsTransaction.php`
- Directorio nuevo `app/handlers/`
- Validación estricta fail-fast en batch
- Tabla `notifications_transactions` con `CREATE TABLE IF NOT EXISTS` en bootstrap
- Sin auth
- URL: `?url=api/notifications/transactions` (prefijo `api/`, no `v1/`)
- ApiController actúa como orquestador, dispatcha por recurso a handler dedicado