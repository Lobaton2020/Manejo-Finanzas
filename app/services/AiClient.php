<?php

/**
 * Cliente HTTP de IA con dos proveedores:
 *
 *   1. MiniMax (primario)  -> POST {MINIMAX_BASE_URL}/v1/chat/completions
 *   2. Gemini   (fallback) -> POST {GEMINI_BASE_URL}/v1beta/models/{model}:generateContent
 *
 * `chat()` intenta MiniMax y, si falla (sin saldo, caido, sin key...), intenta
 * Gemini cuando hay `GEMINI_API_KEY`. Asi una cuenta de MiniMax sin saldo no
 * deja el modulo Suggested sin IA.
 *
 * Gotchas que este cliente resuelve (ver odd/tasks/suggested-outflows-ai.md):
 *   - Los errores de MiniMax NO llegan en el status HTTP, llegan en
 *     `base_resp.status_code` (1004 auth, 1002 rate limit, 1001 timeout...).
 *   - `max_completion_tokens` de MiniMax cuenta los tokens del razonamiento: si
 *     queda corto la respuesta llega con `finish_reason: "length"` y content VACIO.
 *   - `temperature` y `top_p` no tienen efecto en los modelos M3 de MiniMax.
 *   - Gemini 3.x razona por defecto y ese razonamiento consume `maxOutputTokens`
 *     y ensucia la salida; se apaga con `thinkingConfig.thinkingBudget = 0`.
 *   - La API key viaja en el header `x-goog-api-key`, NUNCA en la URL, para no
 *     dejarla en logs. Tampoco se escribe en los logs propios.
 */
class AiClient
{
    private $apiKey;
    private $model;
    private $baseUrl;
    private $timeout;
    private $thinking;
    private $maxTokens;

    private $geminiKey;
    private $geminiModel;
    private $geminiBaseUrl;
    private $geminiTimeout;
    private $geminiMaxTokens;
    private $geminiThinkingBudget;

    /** Proveedor que respondio en la ultima llamada ("minimax" | "gemini" | "none"). */
    private $lastProvider = "none";
    /** Modelo que respondio en la ultima llamada. */
    private $lastModel = null;

    public function __construct(array $config = [])
    {
        $this->apiKey   = $config["api_key"]   ?? (defined("MINIMAX_API_KEY") ? MINIMAX_API_KEY : "");
        $this->model    = $config["model"]      ?? (defined("MINIMAX_MODEL") ? MINIMAX_MODEL : "MiniMax-M3");
        $this->baseUrl  = $config["base_url"]   ?? (defined("MINIMAX_BASE_URL") ? MINIMAX_BASE_URL : "https://api.minimax.io");
        $this->timeout  = intval($config["timeout"]    ?? (defined("MINIMAX_TIMEOUT") ? MINIMAX_TIMEOUT : 30));
        $this->thinking = $config["thinking"]   ?? (defined("MINIMAX_THINKING") ? MINIMAX_THINKING : "disabled");
        $this->maxTokens = intval($config["max_tokens"] ?? (defined("MINIMAX_MAX_TOKENS") ? MINIMAX_MAX_TOKENS : 4096));

        $this->geminiKey      = $config["gemini_api_key"] ?? (defined("GEMINI_API_KEY") ? GEMINI_API_KEY : "");
        $this->geminiModel    = $config["gemini_model"]   ?? (defined("GEMINI_MODEL") ? GEMINI_MODEL : "gemini-3.5-flash");
        $this->geminiBaseUrl  = $config["gemini_base_url"] ?? (defined("GEMINI_BASE_URL") ? GEMINI_BASE_URL : "https://generativelanguage.googleapis.com");
        $this->geminiTimeout  = intval($config["gemini_timeout"] ?? (defined("GEMINI_TIMEOUT") ? GEMINI_TIMEOUT : 40));
        $this->geminiMaxTokens = intval($config["gemini_max_tokens"] ?? (defined("GEMINI_MAX_TOKENS") ? GEMINI_MAX_TOKENS : 4096));
        $this->geminiThinkingBudget = intval($config["gemini_thinking_budget"] ?? (defined("GEMINI_THINKING_BUDGET") ? GEMINI_THINKING_BUDGET : 0));
    }

    /**
     * Modelo que respondio la ultima llamada (o el primario si aun no hubo).
     */
    public function model(): string
    {
        return $this->lastModel ?? $this->model;
    }

    /**
     * Proveedor que respondio la ultima llamada.
     */
    public function provider(): string
    {
        return $this->lastProvider;
    }

    /**
     * Envia los mensajes al proveedor primario y, si falla, al fallback.
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @return array{ok: bool, content: string, error: ?string, code: ?string, raw: array, provider: string, model: ?string}
     */
    public function chat(array $messages): array
    {
        $primary = $this->chatMinimax($messages);
        if ($primary["ok"]) {
            $this->lastProvider = "minimax";
            $this->lastModel = $this->model;
            return $primary;
        }

        // Fallback: Gemini solo si hay key configurada.
        if (trim((string) $this->geminiKey) === "") {
            $this->lastProvider = "none";
            $this->lastModel = null;
            return $primary;
        }

        $fallback = $this->chatGemini($messages);
        if ($fallback["ok"]) {
            $fallback["fallback_from"] = $primary["code"];
            $this->lastProvider = "gemini";
            $this->lastModel = $this->geminiModel;
            return $fallback;
        }

        $this->lastProvider = "none";
        $this->lastModel = null;
        return $this->fail(
            "all_providers_failed",
            "MiniMax: {$primary["error"]} | Gemini: {$fallback["error"]}",
            ["minimax" => $primary["raw"], "gemini" => $fallback["raw"]],
            "ai"
        );
    }

    /**
     * Llamada a MiniMax (endpoint compatible con OpenAI).
     */
    private function chatMinimax(array $messages): array
    {
        if (trim((string) $this->apiKey) === "") {
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
            "ok"       => true,
            "content"  => $content,
            "error"    => null,
            "code"     => null,
            "raw"      => $decoded,
            "provider" => "minimax",
            "model"    => $this->model,
        ];
    }

    /**
     * Llamada a Gemini (Google Generative Language API).
     */
    private function chatGemini(array $messages): array
    {
        // Gemini separa el system prompt de los turnos.
        $systemText = "";
        $contents = [];
        foreach ($messages as $message) {
            $role = (string) ($message["role"] ?? "user");
            $text = (string) ($message["content"] ?? "");
            if ($role === "system") {
                $systemText = $systemText === "" ? $text : $systemText . "\n\n" . $text;
                continue;
            }
            $contents[] = [
                "role"  => $role === "assistant" ? "model" : "user",
                "parts" => [["text" => $text]],
            ];
        }

        $payload = [
            "contents" => $contents,
            "generationConfig" => [
                "maxOutputTokens" => $this->geminiMaxTokens,
                // Fuerza JSON puro: evita fences ```json.
                "responseMimeType" => "application/json",
                // Apaga el razonamiento: no consume tokens ni ensucia la salida.
                "thinkingConfig" => ["thinkingBudget" => $this->geminiThinkingBudget],
            ],
        ];
        if ($systemText !== "") {
            $payload["systemInstruction"] = ["parts" => [["text" => $systemText]]];
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            return $this->fail("payload_encode_failed", json_last_error_msg(), [], "gemini");
        }

        $url = $this->geminiBaseUrl . "/v1beta/models/" . rawurlencode($this->geminiModel) . ":generateContent";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS    => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => $this->geminiTimeout,
            CURLOPT_HTTPHEADER     => [
                // La key va en el header, nunca en la URL.
                "x-goog-api-key: " . $this->geminiKey,
                "Content-Type: application/json",
            ],
        ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);

        if ($response === false || $errno !== 0) {
            return $this->fail("http_error", "cURL error {$errno}: {$error}", [], "gemini");
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return $this->fail("invalid_json", "Respuesta no es JSON (HTTP {$httpCode}): " . substr((string) $response, 0, 200), [], "gemini");
        }

        if (isset($decoded["error"])) {
            $err = $decoded["error"];
            $code = $err["status"] ?? ("http_" . ($err["code"] ?? $httpCode));
            $msg = $err["message"] ?? "Error desconocido de Gemini";
            return $this->fail("gemini_{$code}", "Gemini HTTP {$httpCode} - {$code}: {$msg}", $decoded, "gemini");
        }

        if ($httpCode >= 400) {
            return $this->fail("http_{$httpCode}", "Gemini respondio HTTP {$httpCode}.", $decoded, "gemini");
        }

        $candidate = $decoded["candidates"][0] ?? null;
        if (!is_array($candidate)) {
            $block = $decoded["promptFeedback"]["blockReason"] ?? null;
            $detail = $block !== null ? " (bloqueado: {$block})" : "";
            return $this->fail("empty_candidates", "Gemini no devolvio candidatos{$detail}.", $decoded, "gemini");
        }

        $finish = (string) ($candidate["finishReason"] ?? "");
        $content = "";
        foreach (($candidate["content"]["parts"] ?? []) as $part) {
            if (isset($part["text"]) && is_string($part["text"])) {
                $content .= $part["text"];
            }
        }
        $content = trim($content);

        if ($finish === "MAX_TOKENS") {
            return $this->fail("truncated", "La respuesta se trunco (finishReason=MAX_TOKENS). Sube GEMINI_MAX_TOKENS.", $decoded, "gemini");
        }
        if ($finish !== "" && $finish !== "STOP") {
            return $this->fail("gemini_{$finish}", "Gemini no completo la respuesta (finishReason={$finish}).", $decoded, "gemini");
        }
        if ($content === "") {
            return $this->fail("empty_content", "Gemini devolvio content vacio.", $decoded, "gemini");
        }

        return [
            "ok"       => true,
            "content"  => $content,
            "error"    => null,
            "code"     => null,
            "raw"      => $decoded,
            "provider" => "gemini",
            "model"    => $this->geminiModel,
        ];
    }

    /**
     * @return array{ok: bool, content: string, error: ?string, code: ?string, raw: array}
     */
    private function fail(string $code, string $message, array $raw = [], string $provider = "minimax"): array
    {
        error_log("[ai/{$provider}] {$code}: {$message}");
        return [
            "ok"       => false,
            "content"  => "",
            "error"    => $message,
            "code"     => $code,
            "raw"      => $raw,
            "provider" => $provider,
            "model"    => $provider === "gemini" ? $this->geminiModel : $this->model,
        ];
    }
}
