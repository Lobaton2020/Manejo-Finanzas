-- Suggested outflows: staging de sugerencias de egreso extraidas con IA.
-- DDL manual (este proyecto no tiene migraciones).
-- Aplicar en: 192.168.20.240 / finanzas
--
-- status: 0 = pendiente, 1 = aprobado (genero outflow), 2 = descartado
-- needs_review: 1 = el LLM no pudo extraer todo o la API fallo (fallback regex)

CREATE TABLE IF NOT EXISTS notification_suggestions (
    id_suggestion   INT AUTO_INCREMENT PRIMARY KEY,
    id_user         INT            NOT NULL,
    source          VARCHAR(64)    NOT NULL DEFAULT 'macrodroid',
    source_ref      VARCHAR(255)   NULL,
    dedupe_hash     CHAR(64)       NOT NULL,
    raw_text        MEDIUMTEXT     NOT NULL,
    parsed_json     MEDIUMTEXT     NULL,
    amount          DECIMAL(18,2)  NULL,
    currency        CHAR(3)        NULL,
    description     VARCHAR(255)   NULL,
    event_date      DATE           NULL,
    id_outflow_type INT            NULL,
    id_category     INT            NULL,
    id_porcent      INT            NULL,
    confidence      DECIMAL(4,3)   NULL,
    needs_review    TINYINT(1)     NOT NULL DEFAULT 0,
    status          TINYINT(1)     NOT NULL DEFAULT 0,
    id_outflow      INT            NULL,
    ai_model        VARCHAR(64)    NULL,
    ai_error        VARCHAR(255)   NULL,
    created_at      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at     DATETIME       NULL,
    UNIQUE KEY uq_user_dedupe (id_user, dedupe_hash),
    INDEX idx_user_status (id_user, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
