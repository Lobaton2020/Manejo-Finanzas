<?php

require_once URL_APP . "services/NotificationPayload.php";
require_once URL_APP . "services/ProcessLog.php";

/**
 * Ingesta de notificaciones crudas (Gmail -> MacroDroid -> webhook).
 *
 * Acepta un payload heterogeno porque el cliente (MacroDroid) todavia no
 * tiene un template fijo: se busca el texto en las claves conocidas y, si no
 * hay ninguna, se usa el body crudo tal cual.
 *
 * REGLA DE ORO: la ingesta NO omite notificaciones. Todo lo que llega se
 * convierte en una sugerencia y se devuelve al cliente. Los filtros que
 * existian antes (ver odd/tasks/webhook-ingest-optimizations.md) se
 * desactivaron porque dejaban avisos validos sin registrar:
 *   1. "No tiene monto" -> YA NO omite. La IA puede ver un monto que el
 *      regex no vio; si tampoco lo ve, la sugerencia queda con
 *      needs_review=1 y el usuario completa el monto en el formulario.
 *   2. "Pago rechazado / declinado" -> sigue omitiendo: un pago rechazado NO
 *      es un egreso y manchar la lista de sugerencias seria peyor.
 *   3. "Ya hay una sugerencia pendiente equivalente" (la misma compra por
 *      otro canal) -> YA NO omite. Puede ser la misma compra vista desde
 *      dos canales o un falso positivo del comparador; se crea igual y el
 *      usuario decide en la UI.
 *   4. La misma notificacion reenviada (mismo dedupe_hash) -> se REUSA la
 *      sugerencia existente: no se crea una fila repetida, pero tampoco se
 *      omite (se devuelve con sus datos).
 *
 * Idempotente: la misma notificacion reenviada no crea una segunda
 * sugerencia (dedupe por sha256 de source_ref + texto normalizado).
 */
class SuggestionIngest
{
    /** Claves que traen el texto completo de la notificacion, por prioridad. */
    private const TEXT_KEYS = ["raw_text", "text", "notification", "body", "message", "content", "subject"];

    /** Claves que se combinan entre si (titulo + cuerpo de la notificacion). */
    private const TEXT_PARTS = ["not_title", "not_sub_text", "not_text_big"];

    /** Claves que pueden traer la referencia unica de la notificacion. */
    private const REF_KEYS = ["source_ref", "not_timestamp", "timestamp", "id"];

    private $model;
    private $extractor;

    public function __construct(NotificationSuggestion $model, AiExtractor $extractor)
    {
        $this->model = $model;
        $this->extractor = $extractor;
    }

    /**
     * @param array  $payload Payload ya decodificado (o vacio).
     * @param string $rawBody Body crudo de la peticion.
     * @param int    $idUser  Usuario destino.
     * @return array{
     *   ok: bool, error: ?string, skipped: bool, reason: ?string,
     *   duplicate: bool, id_suggestion: ?int, extraction: array
     * }
     */
    public function ingest(array $payload, string $rawBody, int $idUser): array
    {
        $textInfo = $this->extractText($payload, $rawBody);
        $text = $textInfo["text"];
        ProcessLog::event("ingest", "text", [
            "origin" => $textInfo["origin"],
            "chars"  => mb_strlen($text),
        ]);

        if ($text === "") {
            ProcessLog::event("ingest", "skip", ["reason" => "empty_text"]);
            return $this->result(false, "empty_text", false, null, false, null, []);
        }

        // El celular manda un dump separado por "|": se separa el contenido
        // real de los metadatos y se quita el epoch pegado al texto.
        $parsed = NotificationPayload::parse($text);
        $cleanText = $parsed["text"] !== "" ? $parsed["text"] : $text;

        $hasAmount = NotificationPayload::hasAmount($cleanText);
        $isDeclined = NotificationPayload::isDeclined($cleanText);

        ProcessLog::event("ingest", "parse", [
            "has_amount" => $hasAmount,
            "declined"   => $isDeclined,
            "amount"     => NotificationPayload::parseAmount($cleanText),
            "timestamp"  => $parsed["timestamp"],
            "package"    => $parsed["metadata"]["paquete"] ?? null,
            "channel"    => $parsed["metadata"]["canal"] ?? null,
            "text"       => ProcessLog::preview($cleanText),
        ]);

        // La falta de monto YA NO omite la notificacion. Se crea la sugerencia
        // igual: la IA puede ver un monto que el regex no vio, y si no, la
        // sugerencia queda con needs_review=1 para que el usuario lo
        // complete en el formulario.
        if (!$hasAmount) {
            ProcessLog::event("ingest", "no_amount", [
                "action" => "continue",
                "text"   => ProcessLog::preview($cleanText),
            ]);
        }

        // Un pago rechazado / declinado tampoco es un egreso.
        if ($isDeclined) {
            ProcessLog::event("ingest", "skip", ["reason" => "declined"]);
            return $this->result(false, "declined", true, "declined", false, null, []);
        }

        $amount = NotificationPayload::parseAmount($cleanText);
        $description = NotificationPayload::cleanDescription($cleanText);

        $sourceOrigin = !empty($payload["source"]) && is_string($payload["source"])
            ? "payload"
            : "default";
        $source = $sourceOrigin === "payload"
            ? substr(trim($payload["source"]), 0, 64)
            : "macrodroid";
        ProcessLog::event("ingest", "source", [
            "source" => $source,
            "origin" => $sourceOrigin,
        ]);

        $refInfo = $this->extractSourceRef($payload);
        $sourceRef = $refInfo["ref"];
        $refOrigin = $refInfo["origin"];
        if ($sourceRef === null && $parsed["timestamp"] !== null) {
            // El epoch de la notificacion es mejor clave de deduplicado que
            // el texto crudo: dos reenvios del mismo aviso comparten epoch.
            $sourceRef = (string) $parsed["timestamp"];
            $refOrigin = "epoch";
        }
        ProcessLog::event("ingest", "source_ref", [
            "origin" => $refOrigin,
            "ref"    => ProcessLog::preview($sourceRef, 40),
        ]);
        $hash = $this->model->dedupeHash($sourceRef, $text);

        // La compra pudo haber llegado antes por otro canal: se busca una
        // sugerencia pendiente equivalente para INFORMAR, pero no se omite
        // nada (ver el docblock de la clase).
        ProcessLog::event("ingest", "dedupe", [
            "phase"  => "before",
            "amount" => $amount,
            "text"   => ProcessLog::preview($description),
        ]);
        $equivalent = $this->model->pendingEquivalent(
            $idUser,
            $amount,
            $description,
            $this->extractEventDate($cleanText),
            6,
            [$cleanText]
        );
        ProcessLog::event("ingest", "dedupe", [
            "phase"            => "after",
            "duplicate_pending" => $equivalent !== null,
            "id_suggestion"    => $equivalent !== null ? (int) $equivalent["id_suggestion"] : null,
        ]);
        // Una sugerencia pendiente equivalente YA NO omite esta notificacion:
        // puede ser la misma compra vista desde otro canal (correo + Wallet) o
        // un falso positivo del comparador. Se crea igual y el usuario decide
        // en la UI.
        if ($equivalent !== null) {
            ProcessLog::event("ingest", "duplicate_pending", [
                "action"        => "continue",
                "id_suggestion" => (int) $equivalent["id_suggestion"],
            ]);
        }

        // Los pendientes recientes se le pasan al modelo como contexto.
        $previous = $this->model->pendingSummary($idUser, 5);
        ProcessLog::event("ingest", "previous", ["count" => count($previous)]);

        $ingest = $this->model->ingest($idUser, $source, $sourceRef, $text, $hash);
        ProcessLog::event("ingest", "insert", [
            "created"      => (bool) $ingest["created"],
            "id_suggestion" => $ingest["id_suggestion"],
            "source_ref"   => $sourceRef !== null ? ProcessLog::preview($sourceRef) : null,
            "hash"         => substr($hash, 0, 12),
        ]);
        if (!$ingest["created"]) {
            $idExisting = $ingest["id_suggestion"] !== null ? (int) $ingest["id_suggestion"] : null;
            if ($idExisting === null) {
                // El INSERT fallo de verdad: no hay sugerencia que devolver.
                ProcessLog::error("ingest", "insert_failed", ["hash" => substr($hash, 0, 12)]);
                return $this->result(false, "insert_failed", false, "insert_failed", false, null, []);
            }
            // La misma notificacion ya estaba: se REUSA la sugerencia existente
            // en vez de omitirla, y se devuelven los datos que ya tiene.
            ProcessLog::event("ingest", "reused", ["id_suggestion" => $idExisting]);
            return $this->result(
                true,
                null,
                false,
                "existing",
                false,
                $idExisting,
                $this->extractionFromRow($ingest["existing"] ?? null)
            );
        }

        $idSuggestion = (int) $ingest["id_suggestion"];
        // AiExtractor ya informa `ai_model` segun el proveedor que respondio.
        $extractStartedAt = microtime(true);
        $extraction = $this->extractor->extract($idUser, $cleanText, $previous);
        ProcessLog::event("ingest", "extract", [
            "latency_ms"     => round((microtime(true) - $extractStartedAt) * 1000, 1),
            "source"         => $extraction["source"] ?? null,
            "confidence"     => $extraction["data"]["confidence"] ?? null,
            "needs_review"   => $extraction["needs_review"] ?? null,
            "error"          => $extraction["error"] ?? null,
            "provider"       => $extraction["provider"] ?? null,
            "ai_model"       => $extraction["ai_model"] ?? null,
            "amount"         => $extraction["data"]["amount"] ?? null,
            "description"    => ProcessLog::preview($extraction["data"]["description"] ?? null),
            "id_outflow_type" => $extraction["ids"]["id_outflow_type"] ?? null,
            "id_category"     => $extraction["ids"]["id_category"] ?? null,
            "id_porcent"      => $extraction["ids"]["id_porcent"] ?? null,
        ]);

        try {
            $this->model->applyExtraction($idSuggestion, $extraction);
        } catch (Throwable $e) {
            ProcessLog::exception("ingest", "apply_failed", $e, ["id_suggestion" => $idSuggestion]);
            throw $e;
        }
        ProcessLog::event("ingest", "apply", ["id_suggestion" => $idSuggestion]);

        return $this->result(true, null, false, null, false, $idSuggestion, $extraction);
    }

    /**
     * Reconstruye la extraccion desde una sugerencia YA guardada.
     *
     * Se usa cuando la misma notificacion vuelve a llegar: la sugerencia ya
     * existe, asi que se devuelve con sus datos (no se omite y no se vuelve a
     * llamar a la IA).
     *
     * @param  object|null $row Fila de `notification_suggestions` (null si no hay).
     * @return array Extraccion con la misma forma que devuelve la IA.
     */
    private function extractionFromRow($row): array
    {
        if (empty($row)) {
            return [];
        }

        return [
            "data" => [
                "amount"            => $row->amount !== null ? (float) $row->amount : null,
                "currency"          => !empty($row->currency) ? $row->currency : "COP",
                "description"       => $row->description,
                "event_datetime"    => null,
                "event_date"        => $row->event_date,
                "outflow_type_name" => null,
                "category_name"     => null,
                "deposit_name"      => null,
                "counterparty"      => null,
                "payment_method"    => null,
                "card_last_four"    => null,
                "confidence"        => $row->confidence !== null ? (float) $row->confidence : 0.0,
                "fields_missing"    => [],
            ],
            "ids" => [
                "id_outflow_type" => $row->id_outflow_type !== null ? (int) $row->id_outflow_type : null,
                "id_category"     => $row->id_category !== null ? (int) $row->id_category : null,
                "id_porcent"      => $row->id_porcent !== null ? (int) $row->id_porcent : null,
            ],
            "needs_review" => (bool) $row->needs_review,
            "source"       => "existing",
            "error"        => $row->ai_error,
            "provider"     => null,
            "ai_model"     => $row->ai_model,
        ];
    }

    /**
     * Saca el texto de la notificacion del payload o del body crudo.
     *
     * @return array{text: string, origin: string} Texto y de donde salio
     *         ("key:<clave>", "keys:<a>+<b>" o "raw_body").
     */
    private function extractText(array $payload, string $rawBody): array
    {
        foreach (self::TEXT_KEYS as $key) {
            if (!empty($payload[$key]) && is_string($payload[$key])) {
                $value = trim($payload[$key]);
                if ($value !== "") {
                    return ["text" => $value, "origin" => "key:" . $key];
                }
            }
        }

        $parts = [];
        $partKeys = [];
        foreach (self::TEXT_PARTS as $key) {
            if (!empty($payload[$key]) && is_string($payload[$key])) {
                $value = trim($payload[$key]);
                if ($value !== "") {
                    $parts[] = $value;
                    $partKeys[] = $key;
                }
            }
        }
        if (!empty($parts)) {
            return ["text" => implode("\n", $parts), "origin" => "keys:" . implode("+", $partKeys)];
        }

        return ["text" => trim($rawBody), "origin" => "raw_body"];
    }

    /**
     * Referencia unica de la notificacion (para deduplicar reenvios).
     *
     * @return array{ref: ?string, origin: string} Referencia y de donde salio
     *         ("payload_key:<clave>", "header" o "none").
     */
    private function extractSourceRef(array $payload): array
    {
        foreach (self::REF_KEYS as $key) {
            if (isset($payload[$key]) && (is_string($payload[$key]) || is_numeric($payload[$key]))) {
                $value = trim((string) $payload[$key]);
                if ($value !== "") {
                    return ["ref" => substr($value, 0, 255), "origin" => "payload_key:" . $key];
                }
            }
        }

        $header = $_SERVER["HTTP_X_NOTIFICATION_ID"] ?? null;
        if (!empty($header)) {
            return ["ref" => substr(trim((string) $header), 0, 255), "origin" => "header"];
        }

        return ["ref" => null, "origin" => "none"];
    }

    /**
     * Fecha del evento que trae el texto ("2026/11/19 21:46" o
     * "19/11/2026 21:46"), en formato "Y-m-d". Null si no hay fecha.
     */
    private function extractEventDate(string $text): ?string
    {
        if (preg_match('/(\d{4})\/(\d{2})\/(\d{2})/', $text, $matches)) {
            return "{$matches[1]}-{$matches[2]}-{$matches[3]}";
        }
        if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $text, $matches)) {
            return "{$matches[3]}-{$matches[2]}-{$matches[1]}";
        }
        return null;
    }

    /**
     * Respuesta uniforme de la ingesta.
     */
    private function result(
        bool $ok,
        ?string $error,
        bool $skipped,
        ?string $reason,
        bool $duplicate,
        ?int $idSuggestion,
        array $extraction
    ): array {
        return [
            "ok"           => $ok,
            "error"        => $error,
            "skipped"      => $skipped,
            "reason"       => $reason,
            "duplicate"    => $duplicate,
            "id_suggestion" => $idSuggestion,
            "extraction"   => $extraction,
        ];
    }
}
