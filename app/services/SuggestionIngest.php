<?php

/**
 * Ingesta de notificaciones crudas (Gmail -> MacroDroid -> webhook).
 *
 * Acepta un payload heterogeno porque el cliente (MacroDroid) todavia no
 * tiene un template fijo: se busca el texto en las claves conocidas y, si no
 * hay ninguna, se usa el body crudo tal cual.
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
     * @return array{ok: bool, error: ?string, duplicate: bool, id_suggestion: ?int, extraction: array}
     */
    public function ingest(array $payload, string $rawBody, int $idUser): array
    {
        $text = $this->extractText($payload, $rawBody);
        if ($text === "") {
            return [
                "ok"           => false,
                "error"        => "empty_text",
                "duplicate"    => false,
                "id_suggestion" => null,
                "extraction"   => [],
            ];
        }

        $source = !empty($payload["source"]) && is_string($payload["source"])
            ? substr(trim($payload["source"]), 0, 64)
            : "macrodroid";

        $sourceRef = $this->extractSourceRef($payload);
        $hash = $this->model->dedupeHash($sourceRef, $text);

        $ingest = $this->model->ingest($idUser, $source, $sourceRef, $text, $hash);
        if (!$ingest["created"]) {
            return [
                "ok"           => true,
                "error"        => null,
                "duplicate"    => true,
                "id_suggestion" => $ingest["id_suggestion"],
                "extraction"   => [],
            ];
        }

        $idSuggestion = (int) $ingest["id_suggestion"];
        $extraction = $this->extractor->extract($idUser, $text);
        $extraction["ai_model"] = defined("MINIMAX_MODEL") ? MINIMAX_MODEL : null;

        $this->model->applyExtraction($idSuggestion, $extraction);

        return [
            "ok"           => true,
            "error"        => null,
            "duplicate"    => false,
            "id_suggestion" => $idSuggestion,
            "extraction"   => $extraction,
        ];
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
}
