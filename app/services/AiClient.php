<?php

/**
 * Cliente HTTP de MiniMax (endpoint compatible con OpenAI).
 *
 * Contrato verificado en la documentacion de MiniMax:
 *   POST {MINIMAX_BASE_URL}/v1/chat/completions
 *   Authorization: Bearer <API key>
 *
 * Gotchas que este cliente resuelve (ver odd/tasks/suggested-outflows-ai.md):
 *   - Los errores de MiniMax NO llegan en el status HTTP, llegan en
 *     `base_resp.status_code` (1004 auth, 1002 rate limit, 1001 timeout...).
 *   - `max_completion_tokens` cuenta los tokens del razonamiento: si queda
 *     corto la respuesta llega con `finish_reason: "length"` y `content` VACIO.
 *   - `temperature` y `top_p` no tienen efecto en los modelos M3.
 *
 * La API key NUNCA se escribe en los logs ni se devuelve en las respuestas.
 */
class AiClient
{
    private $apiKey;
    private $model;
    private $baseUrl;
    private $timeout;
    private $thinking;
    private $maxTokens;

    public function __construct(array $config = [])
    {
        $this->apiKey   = $config["api_key"]   ?? (defined("MINIMAX_API_KEY") ? MINIMAX_API_KEY : "");
        $this->model    = $config["model"]      ?? (defined("MINIMAX_MODEL") ? MINIMAX_MODEL : "MiniMax-M3");
        $this->baseUrl  = $config["base_url"]   ?? (defined("MINIMAX_BASE_URL") ? MINIMAX_BASE_URL : "https://api.minimax.io");
        $this->timeout  = intval($config["timeout"]    ?? (defined("MINIMAX_TIMEOUT") ? MINIMAX_TIMEOUT : 30));
        $this->thinking = $config["thinking"]   ?? (defined("MINIMAX_THINKING") ? MINIMAX_THINKING : "disabled");
        $this->maxTokens = intval($config["max_tokens"] ?? (defined("MINIMAX_MAX_TOKENS") ? MINIMAX_MAX_TOKENS : 4096));
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * Envia los mensajes y devuelve el contenido de la respuesta.
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @return array{ok: bool, content: string, error: ?string, code: ?string, raw: array}
     */
    public function chat(array $messages): array
    {
        if (trim($this->apiKey) === "") {
            return $this->fail("minimax_api_key_missing", "No hay API key de MiniMax configurada (MINIMAX_API_KEY).");
        }

        $payload = [
            "model" => $this->model,
            "messages" => $messages,
            "max_completion_tokens" => $this->maxTokens,
            // Separa el razonamiento del content: el content queda limpio.
            "reasoning_split" => true,
        ];
        if ($this->thinking !== "" && $this->thinking !== null) {
            $payload["thinking"] = ["type" => $this->thinking];
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            return $this->fail("payload_encode_failed", json_last_error_msg());
        }

        $ch = curl_init($this->baseUrl . "/v1/chat/completions");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS    => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer " . $this->apiKey,
                "Content-Type: application/json",
            ],
        ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);

        if ($response === false || $errno !== 0) {
            return $this->fail("http_error", "cURL error {$errno}: {$error}");
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return $this->fail("invalid_json", "Respuesta no es JSON (HTTP {$httpCode}): " . substr((string) $response, 0, 200));
        }

        // Errores de la API con otro formato: {"type":"error","error":{...}}
        // (ej. 402 insufficient_balance_error, 401 unauthorized).
        if (isset($decoded["type"]) && $decoded["type"] === "error") {
            $err = $decoded["error"] ?? [];
            $code = $err["type"] ?? "api_error";
            $msg = $err["message"] ?? "Error desconocido de MiniMax";
            $httpErr = $err["http_code"] ?? $httpCode;
            return $this->fail("minimax_{$code}", "MiniMax HTTP {$httpErr} - {$code}: {$msg}", $decoded);
        }

        // Otros errores HTTP con cuerpo JSON que no es el formato de error.
        if ($httpCode >= 400) {
            return $this->fail("http_{$httpCode}", "MiniMax respondio HTTP {$httpCode}.", $decoded);
        }

        // Los errores de negocio de MiniMax vienen aqui, no en el status HTTP.
        $baseResp = $decoded["base_resp"] ?? [];
        $baseCode = intval($baseResp["status_code"] ?? 0);
        if ($baseCode !== 0) {
            $msg = $baseResp["status_msg"] ?? "sin detalle";
            return $this->fail("minimax_{$baseCode}", "MiniMax error {$baseCode}: {$msg}", $decoded);
        }

        $choice = $decoded["choices"][0] ?? null;
        if (!is_array($choice)) {
            return $this->fail("empty_choices", "MiniMax no devolvio choices.", $decoded);
        }

        $finish = (string) ($choice["finish_reason"] ?? "");
        $content = trim((string) ($choice["message"]["content"] ?? ""));

        if ($finish === "length") {
            return $this->fail("truncated", "La respuesta se trunco (finish_reason=length). Sube MINIMAX_MAX_TOKENS.", $decoded);
        }
        if ($content === "") {
            return $this->fail("empty_content", "MiniMax devolvio content vacio.", $decoded);
        }

        return [
            "ok"      => true,
            "content" => $content,
            "error"   => null,
            "code"    => null,
            "raw"     => $decoded,
        ];
    }

    /**
     * @return array{ok: bool, content: string, error: ?string, code: ?string, raw: array}
     */
    private function fail(string $code, string $message, array $raw = []): array
    {
        error_log("[ai/minimax] {$code}: {$message}");
        return [
            "ok"      => false,
            "content" => "",
            "error"   => $message,
            "code"    => $code,
            "raw"     => $raw,
        ];
    }
}
