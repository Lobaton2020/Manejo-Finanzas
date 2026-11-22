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
 * Antes de crear nada se hacen tres filtros (ver
 * odd/tasks/webhook-ingest-optimizations.md):
 *   1. El texto tiene un monto con forma de dinero? Si no, NO es un egreso
 *      (YouTube Music, notificaciones genericas) y se responde `skipped`.
 *   2. El texto dice que el pago fue rechazado / declinado? Entonces no se
 *      crea nada y se responde `skipped`.
 *   3. Ya existe una sugerencia PENDIENTE con el mismo monto y una
 *      descripcion similar (la misma compra por otro canal)? Entonces no se
 *      crea una segunda y se responde `duplicate`.
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
        $text = $this->extractText($payload, $rawBody);
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

        // Sin monto no hay egreso: no se crea el registro ni se llama a la IA.
        if (!$hasAmount) {
            ProcessLog::event("ingest", "skip", ["reason" => "no_amount"]);
            return $this->result(false, "no_amount", true, "no_amount", false, null, []);
        }

        // Un pago rechazado / declinado tampoco es un egreso.
        if ($isDeclined) {
            ProcessLog::event("ingest", "skip", ["reason" => "declined"]);
            return $this->result(false, "declined", true, "declined", false, null, []);
        }

        $amount = NotificationPayload::parseAmount($cleanText);
        $description = NotificationPayload::cleanDescription($cleanText);

        $source = !empty($payload["source"]) && is_string($payload["source"])
            ? substr(trim($payload["source"]), 0, 64)
            : "macrodroid";

        $sourceRef = $this->extractSourceRef($payload);
        if ($sourceRef === null && $parsed["timestamp"] !== null) {
            // El epoch de la notificacion es mejor clave de deduplicado que
            // el texto crudo: dos reenvios del mismo aviso comparten epoch.
            $sourceRef = (string) $parsed["timestamp"];
        }
        $hash = $this->model->dedupeHash($sourceRef, $text);

        // La compra pudo haber llegado antes por otro canal: si ya hay una
        // sugerencia pendiente equivalente, se devuelve esa.
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
        if ($equivalent !== null) {
            return $this->result(true, null, false, "duplicate_pending", true, (int) $equivalent["id_suggestion"], []);
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
            return $this->result(true, null, false, "duplicate", true, $ingest["id_suggestion"], []);
        }

        $idSuggestion = (int) $ingest["id_suggestion"];
        // AiExtractor ya informa `ai_model` segun el proveedor que respondio.
        $extractStartedAt = microtime(true);
        $extraction = $this->extractor->extract($idUser, $cleanText, $previous);
        ProcessLog::event("ingest", "extract", [
            "latency_ms"   => round((microtime(true) - $extractStartedAt) * 1000, 1),
            "source"       => $extraction["source"] ?? null,
            "confidence"   => $extraction["data"]["confidence"] ?? null,
            "needs_review" => $extraction["needs_review"] ?? null,
            "error"        => $extraction["error"] ?? null,
            "provider"     => $extraction["provider"] ?? null,
            "ai_model"     => $extraction["ai_model"] ?? null,
        ]);

        $this->model->applyExtraction($idSuggestion, $extraction);
        ProcessLog::event("ingest", "apply", ["id_suggestion" => $idSuggestion]);

        return $this->result(true, null, false, null, false, $idSuggestion, $extraction);
    }

    /**
     * Saca el texto de la notificacion del payload o del body crudo.
     */
    private function extractText(array $payload, string $rawBody): string
    {
        foreach (self::TEXT_KEYS as $key) {
            if (!empty($payload[$key]) && is_string($payload[$key])) {
                $value = trim($payload[$key]);
                if ($value !== "") {
                    return $value;
                }
            }
        }

        $parts = [];
        foreach (self::TEXT_PARTS as $key) {
            if (!empty($payload[$key]) && is_string($payload[$key])) {
                $value = trim($payload[$key]);
                if ($value !== "") {
                    $parts[] = $value;
                }
            }
        }
        if (!empty($parts)) {
            return implode("\n", $parts);
        }

        return trim($rawBody);
    }

    /**
     * Referencia unica de la notificacion (para deduplicar reenvios).
     */
    private function extractSourceRef(array $payload): ?string
    {
        foreach (self::REF_KEYS as $key) {
            if (isset($payload[$key]) && (is_string($payload[$key]) || is_numeric($payload[$key]))) {
                $value = trim((string) $payload[$key]);
                if ($value !== "") {
                    return substr($value, 0, 255);
                }
            }
        }

        $header = $_SERVER["HTTP_X_NOTIFICATION_ID"] ?? null;
        if (!empty($header)) {
            return substr(trim((string) $header), 0, 255);
        }

        return null;
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
