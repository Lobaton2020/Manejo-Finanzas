<?php

/**
 * Staging de sugerencias de egreso extraidas de notificaciones (IA).
 *
 * No es el registro final: la sugerencia se revisa y, al aprobarla, se crea
 * el egreso real en `outflows` via `OutflowServie::perform_egress()`.
 *
 * Idempotencia: `dedupe_hash` = sha256(source_ref | raw_text normalizado), con
 * indice unico por usuario. Reenvios de la misma notificacion no duplican.
 *
 * Ver odd/tasks/suggested-outflows-ai.md para el DDL y el contrato.
 */
class NotificationSuggestion extends Orm
{
    /** Estados de la sugerencia. */
    const STATUS_PENDING  = 0;
    const STATUS_APPROVED = 1;
    const STATUS_DISCARDED = 2;

    public function __construct()
    {
        parent::__construct("notification_suggestions");
    }

    /**
     * Hash de deduplicacion.
     *
     * Si hay `source_ref` (id de la notificacion) se usa solo ese: el mismo
     * aviso reenviado con texto ligeramente distinto sigue siendo el mismo.
     * Sin referencia, se usa el texto normalizado.
     */
    public function dedupeHash(?string $sourceRef, string $rawText): string
    {
        if ($sourceRef !== null && trim($sourceRef) !== "") {
            return hash("sha256", "ref|" . trim($sourceRef));
        }

        $normalized = preg_replace('/\s+/u', ' ', trim($rawText));
        return hash("sha256", "text|" . $normalized);
    }

    /**
     * Inserta la sugerencia si no existe todavia.
     *
     * @return array{created: bool, id_suggestion: ?int}
     */
    public function ingest(int $idUser, string $source, ?string $sourceRef, string $rawText, string $dedupeHash): array
    {
        $existing = $this->get("*", ["id_user[=]" => $idUser, "dedupe_hash[=]" => $dedupeHash, "AND"])->array();
        if (!empty($existing)) {
            return ["created" => false, "id_suggestion" => (int) $existing->id_suggestion];
        }

        $data = [
            "id_user"      => $idUser,
            "source"       => $source,
            "source_ref"   => $sourceRef,
            "dedupe_hash"  => $dedupeHash,
            "raw_text"     => $rawText,
            "needs_review" => 1,
            "status"       => self::STATUS_PENDING,
            "created_at"   => getCurrentDatetime(),
        ];

        if (!$this->insert($data)->array()) {
            return ["created" => false, "id_suggestion" => null];
        }

        return ["created" => true, "id_suggestion" => (int) $this->id()];
    }

    /**
     * Guarda el resultado de la extraccion sobre la sugerencia.
     */
    public function applyExtraction(int $idSuggestion, array $result): void
    {
        $data = $result["data"];
        $ids = $result["ids"];

        $update = [
            "parsed_json"     => json_encode($result, JSON_UNESCAPED_UNICODE),
            "amount"          => $data["amount"] ?? null,
            "currency"        => $data["currency"] ?? "COP",
            "description"     => $data["description"] ?? null,
            "event_date"      => $data["event_date"] ?? null,
            "id_outflow_type" => $ids["id_outflow_type"] ?? null,
            "id_category"     => $ids["id_category"] ?? null,
            "id_porcent"      => $ids["id_porcent"] ?? null,
            "confidence"      => $data["confidence"] ?? 0,
            "needs_review"    => $result["needs_review"] ? 1 : 0,
            "ai_model"        => $result["ai_model"] ?? null,
            "ai_error"        => $result["error"] ? mb_substr($result["error"], 0, 250) : null,
        ];

        $this->update($update, ["id_suggestion[=]" => $idSuggestion]);
    }

    /**
     * Sugerencias pendientes del usuario (las mas recientes primero).
     */
    public function pending(int $idUser, int $limit = 50): array
    {
        return $this->select(
            "*",
            ["id_user[=]" => $idUser, "status[=]" => self::STATUS_PENDING, "AND"],
            "created_at DESC",
            $limit
        )->array();
    }

    /**
     * Cantidad de sugerencias pendientes del usuario.
     */
    public function countPending(int $idUser): int
    {
        return (int) $this->count(
            ["id_user[=]" => $idUser, "status[=]" => self::STATUS_PENDING, "AND"]
        )->array();
    }

    /**
     * Sugerencia del usuario por id (null si no existe o no es suya).
     */
    public function findOwned(int $idUser, int $idSuggestion)
    {
        $row = $this->get("*", ["id_user[=]" => $idUser, "id_suggestion[=]" => $idSuggestion, "AND"])->array();
        return empty($row) ? null : $row;
    }

    /**
     * Marca la sugerencia como aprobada y la asocia al egreso creado.
     */
    public function markApproved(int $idSuggestion, int $idOutflow): void
    {
        $this->update(
            [
                "status"      => self::STATUS_APPROVED,
                "id_outflow"  => $idOutflow,
                "approved_at" => getCurrentDatetime(),
            ],
            ["id_suggestion[=]" => $idSuggestion]
        );
    }

    /**
     * Marca la sugerencia como descartada.
     */
    public function markDiscarded(int $idSuggestion): void
    {
        $this->update(
            ["status" => self::STATUS_DISCARDED],
            ["id_suggestion[=]" => $idSuggestion]
        );
    }
}
