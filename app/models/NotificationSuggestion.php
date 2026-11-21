<?php

require_once URL_APP . "services/NotificationPayload.php";

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
     * Sugerencia pendiente equivalente a la que se quiere crear.
     *
     * La compra puede llegar por dos canales (correo del banco y Google
     * Wallet). Si ya hay una sugerencia PENDIENTE del usuario con el mismo
     * monto y una descripcion similar dentro de la ventana temporal, no se
     * crea una segunda: se devuelve la que ya existe.
     *
     * Tiempo efectivo: `event_date` cuando esta, si no `created_at`.
     * OJO: `event_date` es DATE (sin hora), asi que dos filas del mismo dia
     * se consideran dentro de la ventana (se compara el dia calendario).
     *
     * @param int       $idUser     Usuario dueño de las sugerencias.
     * @param float|null $amount    Monto de la notificacion entrante.
     * @param string    $description Descripcion de la notificacion entrante.
     * @param string|null $eventDate Fecha del evento entrante ("Y-m-d").
     * @param int       $windowHours Ventana +/- en horas.
     * @return array|null Fila existente (assoc) o null.
     */
    public function pendingEquivalent(
        int $idUser,
        ?float $amount,
        string $description,
        ?string $eventDate,
        int $windowHours = 6,
        array $extraCandidates = []
    ): ?array {
        if ($amount === null || trim($description) === "") {
            return null;
        }

        // Candidatos de la notificacion entrante: la descripcion corta y el
        // texto completo (que incluye el cuerpo con el comercio).
        $incoming = array_values(array_filter(array_merge([$description], $extraCandidates), function ($value) {
            return trim((string) $value) !== "";
        }));
        if (empty($incoming)) {
            return null;
        }

        $rows = $this->select(
            "id_suggestion, amount, description, raw_text, event_date, created_at",
            ["id_user[=]" => $idUser, "status[=]" => self::STATUS_PENDING, "AND"],
            "created_at DESC",
            200
        )->array();

        $incomingDate = ($eventDate !== null && trim($eventDate) !== "") ? trim($eventDate) : getCurrentDatetime();

        foreach ($rows as $row) {
            if ($row->amount === null) {
                continue;
            }
            if (abs((float) $row->amount - $amount) > 0.01) {
                continue;
            }

            // La fila pendiente se compara por su descripcion (la que se
            // mostro al usuario) y por su texto limpio: el comercio puede
            // estar en cualquiera de los dos. El texto crudo se limpia
            // antes porque trae metadatos del celular (paquete de android,
            // canal, memoria) que producirian falsos duplicados.
            $pending = array_values(array_filter([
                (string) ($row->description ?? ""),
                self::cleanText((string) ($row->raw_text ?? "")),
            ], function ($value) {
                return trim($value) !== "";
            }));
            if (empty($pending)) {
                continue;
            }

            $matches = false;
            foreach ($pending as $pendingText) {
                foreach ($incoming as $incomingText) {
                    if (NotificationPayload::isSimilar($incomingText, $pendingText)) {
                        $matches = true;
                        break 2;
                    }
                }
            }
            if (!$matches) {
                continue;
            }

            $rowTime = !empty($row->event_date) ? (string) $row->event_date : (string) $row->created_at;
            if (!$this->withinWindow($incomingDate, $rowTime, $windowHours)) {
                continue;
            }

            return (array) $row;
        }

        return null;
    }

    /**
     * Texto de una notificacion sin metadatos ni epoch.
     *
     * @return string Texto limpio o "" si no hay nada usable.
     */
    private static function cleanText(string $rawText): string
    {
        if (trim($rawText) === "") {
            return "";
        }
        $parsed = NotificationPayload::parse($rawText);

        return trim($parsed["text"]);
    }

    /**
     * Los pendientes recientes del usuario en formato compacto: son los
     * "valores previos" que se le pasan al modelo como contexto.
     *
     * @return array<int, array{amount: ?float, event_date: ?string, description: string}>
     */
    public function pendingSummary(int $idUser, int $limit = 5): array
    {
        $rows = $this->select(
            "amount, event_date, description",
            ["id_user[=]" => $idUser, "status[=]" => self::STATUS_PENDING, "AND"],
            "created_at DESC",
            $limit
        )->array();

        $summary = [];
        foreach ($rows as $row) {
            $summary[] = [
                "amount"      => $row->amount !== null ? (float) $row->amount : null,
                "event_date"  => !empty($row->event_date) ? (string) $row->event_date : null,
                "description" => trim((string) ($row->description ?? "")),
            ];
        }

        return $summary;
    }

    /**
     * Dos tiempos efectivos estan dentro de la ventana.
     *
     * `event_date` es DATE (sin hora): cuando alguno de los dos solo tiene
     * fecha se compara el dia calendario, asi dos filas del mismo dia
     * siempre coinciden.
     */
    private function withinWindow(string $a, string $b, int $windowHours): bool
    {
        $aHasTime = (bool) preg_match('/\d{1,2}:\d{2}/', $a);
        $bHasTime = (bool) preg_match('/\d{1,2}:\d{2}/', $b);

        if (!$aHasTime || !$bHasTime) {
            return substr(trim($a), 0, 10) === substr(trim($b), 0, 10);
        }

        $timeA = strtotime($a);
        $timeB = strtotime($b);
        if ($timeA === false || $timeB === false) {
            return false;
        }

        return abs($timeA - $timeB) <= $windowHours * 3600;
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
