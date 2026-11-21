<?php

/**
 * Extrae datos estructurados de una notificacion bancaria en texto crudo
 * usando un LLM (MiniMax-M3).
 *
 * Reglas de diseño (ver odd/tasks/suggested-outflows-ai.md):
 *  - El LLM NUNCA devuelve ids de base de datos: devuelve NOMBRES tomados del
 *    catalogo real del usuario. Los ids se resuelven aqui, de forma
 *    deterministica, para que la IA no pueda inventar referencias.
 *  - Si el LLM falla, hay un fallback por regex: la sugerencia nunca se pierde.
 *  - La IA no decide si el egreso es valido ni si el saldo alcanza: eso es
 *    `OutflowServie::perform_egress()`.
 */
class AiExtractor
{
    /** Campos que se le piden al modelo. */
    private const SCHEMA_FIELDS = [
        "amount", "currency", "description", "event_datetime",
        "outflow_type_name", "category_name", "deposit_name",
        "counterparty", "payment_method", "card_last_four",
        "confidence", "fields_missing",
    ];

    private $client;
    private $outflowType;
    private $category;
    private $porcent;

    /**
     * @param AiClient $client
     * @param Orm      $outflowType  Modelo `outflowtypes`
     * @param Orm      $category     Modelo `categories`
     * @param Orm      $porcent      Modelo `porcents`
     */
    public function __construct(AiClient $client, $outflowType, $category, $porcent)
    {
        $this->client = $client;
        $this->outflowType = $outflowType;
        $this->category = $category;
        $this->porcent = $porcent;
    }

    /**
     * Extrae los datos de una notificacion en texto crudo.
     *
     * @param int    $idUser
     * @param string $rawText
     * @return array{
     *   ok: bool, data: array, ids: array, needs_review: bool,
     *   source: string, error: ?string, raw_response: string
     * }
     */
    public function extract(int $idUser, string $rawText): array
    {
        $rawText = trim($rawText);
        if ($rawText === "") {
            return $this->result([], [], true, "none", "El texto de la notificacion esta vacio.", "");
        }

        $catalogue = $this->catalogue($idUser);
        $messages = [
            ["role" => "system", "content" => $this->buildPrompt($catalogue)],
            ["role" => "user", "content" => $rawText],
        ];

        $response = $this->client->chat($messages);
        if (!$response["ok"]) {
            // El LLM fallo: fallback determinista para no perder la sugerencia.
            $fallback = $this->fallback($rawText, $catalogue);
            return $this->result(
                $fallback["data"],
                $fallback["ids"],
                true,
                "fallback",
                $response["error"],
                ""
            );
        }

        $parsed = $this->parseJson($response["content"]);
        if ($parsed === null) {
            $fallback = $this->fallback($rawText, $catalogue);
            return $this->result(
                $fallback["data"],
                $fallback["ids"],
                true,
                "fallback",
                "MiniMax no devolvio un JSON utilizable.",
                $response["content"]
            );
        }

        $data = $this->normalizeData($parsed);
        $ids = $this->resolveIds($data, $catalogue);

        // Si el modelo no pudo resolver tipo o categoria, la sugerencia queda
        // para revision manual: el usuario los elige en el formulario.
        $needsReview = false;
        if ($ids["id_outflow_type"] === null || $ids["id_category"] === null) {
            $needsReview = true;
        }
        if (!empty($data["fields_missing"])) {
            $needsReview = true;
        }
        if ($data["amount"] === null || $data["amount"] <= 0) {
            $needsReview = true;
        }

        return $this->result($data, $ids, $needsReview, "ai", null, $response["content"]);
    }

    /**
     * Catalogo real del usuario: lo unico que el modelo puede citar.
     *
     * @return array{outflow_types: array, categories: array, deposits: array}
     */
    public function catalogue(int $idUser): array
    {
        $types = $this->outflowType
            ->select("id_outflow_type, name", ["id_user[=]" => $idUser, "status[=]" => 1, "AND"])
            ->array();

        $categories = $this->category
            ->select("id_category, id_outflow_type, name", ["id_user[=]" => $idUser, "status[=]" => 1, "AND"])
            ->array();

        $deposits = $this->porcent
            ->select("id_porcent, name", ["id_user[=]" => $idUser, "status[=]" => 1, "AND"])
            ->array();

        $byType = [];
        foreach ($categories as $category) {
            $byType[(int) $category->id_outflow_type][] = [
                "id"   => (int) $category->id_category,
                "name" => (string) $category->name,
            ];
        }

        $outflowTypes = [];
        foreach ($types as $type) {
            $outflowTypes[] = [
                "id"         => (int) $type->id_outflow_type,
                "name"       => (string) $type->name,
                "categories" => $byType[(int) $type->id_outflow_type] ?? [],
            ];
        }

        $depositList = [];
        foreach ($deposits as $deposit) {
            $depositList[] = [
                "id"   => (int) $deposit->id_porcent,
                "name" => (string) $deposit->name,
            ];
        }

        return [
            "outflow_types" => $outflowTypes,
            "categories"    => $byType,
            "deposits"      => $depositList,
        ];
    }

    /**
     * Arma el system prompt con el catalogo y el schema de salida.
     */
    private function buildPrompt(array $catalogue): string
    {
        $lines = [];
        $lines[] = "Eres un extractor de datos de notificaciones bancarias para una app de finanzas personales en Colombia.";
        $lines[] = "Recibes el texto crudo de una notificacion (correo o push) y devuelves UN objeto JSON con los campos indicados.";
        $lines[] = "Reglas obligatorias:";
        $lines[] = "- No inventes datos. Si un dato no aparece en el texto, usa null.";
        $lines[] = "- Solo puedes usar los nombres de los catalogos de abajo, copiados EXACTAMENTE (acentos y mayusculas incluidos).";
        $lines[] = "- Los montos son pesos colombianos: \"\$ 100,00\" = 100.00 ; \"1.234.567,89\" = 1234567.89 (punto separa miles, coma separa decimales).";
        $lines[] = "- La fecha puede venir como \"2026/11/19 21:46\" (yyyy/MM/dd HH:mm). Devuelve event_datetime en formato \"YYYY-MM-DDTHH:MM:SS\".";
        $lines[] = "- Si el texto describe una compra con tarjeta, un pago o una transferencia (Bre-B, Nequi, llave, consignacion), NO es una inversion: usa el tipo de egreso normal.";
        $lines[] = "- deposit_name: solo si el texto da evidencia (por ejemplo \"Cuenta origen *****5913\"). Si no hay evidencia clara, null y el usuario lo elige.";
        $lines[] = "- description: maximo 120 caracteres, en español, describiendo el pago (comercio o proposito).";
        $lines[] = "- confidence: numero entre 0 y 1 con tu certeza en la extraccion.";
        $lines[] = "- fields_missing: arreglo con los nombres de los campos que no pudiste extraer.";
        $lines[] = "";

        $lines[] = "CATALOGO DISPONIBLE (respetalo, no inventes nada fuera de aqui):";
        $lines[] = "Tipos de egreso (outflow_type_name):";
        foreach ($catalogue["outflow_types"] as $type) {
            $lines[] = "  - \"{$type["name"]}\"";
        }
        $lines[] = "Categorias (category_name), agrupadas por tipo de egreso:";
        foreach ($catalogue["outflow_types"] as $type) {
            $names = array_map(fn($c) => "\"{$c["name"]}\"", $type["categories"]);
            $lines[] = "  - {$type["name"]}: " . (empty($names) ? "(sin categorias)" : implode(", ", $names));
        }
        $lines[] = "Depositos de origen (deposit_name):";
        $depositNames = array_map(fn($d) => "\"{$d["name"]}\"", $catalogue["deposits"]);
        $lines[] = "  " . (empty($depositNames) ? "(sin depositos)" : implode(", ", $depositNames));
        $lines[] = "";

        $lines[] = "Responde EXCLUSIVAMENTE con el objeto JSON, sin texto antes ni despues, sin ```json y sin comentarios.";
        $lines[] = "Este es el formato exacto de la respuesta:";
        $lines[] = json_encode([
            "amount"           => 100.00,
            "currency"         => "COP",
            "description"      => "Pago de ejemplo",
            "event_datetime"   => "2026-11-19T21:46:00",
            "outflow_type_name" => "Nombre exacto del tipo",
            "category_name"    => "Nombre exacto de la categoria",
            "deposit_name"     => "Nombre exacto del deposito o null",
            "counterparty"     => "Quien recibe o paga, o null",
            "payment_method"   => "Tarjeta, Bre-B, Nequi, efectivo... o null",
            "card_last_four"   => null,
            "confidence"       => 0.9,
            "fields_missing"   => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return implode("\n", $lines);
    }

    /**
     * Parseo robusto del JSON devuelto por el modelo.
     *
     * MiniMax M3 no soporta `response_format`, asi que el JSON llega dentro
     * de texto: puede venir con fences ```json y/o bloques <thinking>.
     */
    private function parseJson(string $content): ?array
    {
        $clean = $content;
        $clean = preg_replace('/<thinking>.*?<\/thinking>/s', '', $clean);
        $clean = preg_replace('/^\s*```(?:json)?/mi', '', $clean);
        $clean = preg_replace('/```\s*$/m', '', $clean);
        $clean = trim((string) $clean);

        $start = strpos($clean, "{");
        $end = strrpos($clean, "}");
        if ($start === false || $end === false || $end < $start) {
            return null;
        }

        $decoded = json_decode(substr($clean, $start, $end - $start + 1), true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Normaliza y valida el JSON del modelo a los campos que usa la app.
     */
    private function normalizeData(array $parsed): array
    {
        $data = [
            "amount"            => null,
            "currency"          => "COP",
            "description"       => null,
            "event_datetime"    => null,
            "event_date"        => null,
            "outflow_type_name" => null,
            "category_name"     => null,
            "deposit_name"      => null,
            "counterparty"      => null,
            "payment_method"    => null,
            "card_last_four"    => null,
            "confidence"        => 0.0,
            "fields_missing"    => [],
        ];

        if (isset($parsed["amount"]) && is_numeric($parsed["amount"])) {
            $data["amount"] = round((float) $parsed["amount"], 2);
        }
        if (!empty($parsed["currency"]) && is_string($parsed["currency"])) {
            $data["currency"] = strtoupper(substr(trim($parsed["currency"]), 0, 3));
        }
        foreach (["description", "outflow_type_name", "category_name", "deposit_name", "counterparty", "payment_method"] as $field) {
            if (!empty($parsed[$field]) && is_string($parsed[$field])) {
                $data[$field] = trim($parsed[$field]);
            }
        }
        if (!empty($parsed["card_last_four"]) && is_string($parsed["card_last_four"])) {
            $digits = preg_replace('/\D/', '', $parsed["card_last_four"]);
            if (strlen($digits) === 4) {
                $data["card_last_four"] = $digits;
            }
        }
        if (isset($parsed["event_datetime"]) && is_string($parsed["event_datetime"])) {
            $datetime = $this->parseEventDateTime($parsed["event_datetime"]);
            if ($datetime !== null) {
                $data["event_datetime"] = $datetime;
                $data["event_date"] = substr($datetime, 0, 10);
            }
        }
        if (isset($parsed["confidence"]) && is_numeric($parsed["confidence"])) {
            $data["confidence"] = max(0.0, min(1.0, round((float) $parsed["confidence"], 3)));
        }
        if (isset($parsed["fields_missing"]) && is_array($parsed["fields_missing"])) {
            $data["fields_missing"] = array_values(array_filter(array_map(
                fn($f) => is_string($f) ? $f : "",
                $parsed["fields_missing"]
            )));
        }

        return $data;
    }

    /**
     * Resuelve los nombres del modelo a ids reales de la base de datos.
     */
    private function resolveIds(array $data, array $catalogue): array
    {
        $ids = ["id_outflow_type" => null, "id_category" => null, "id_porcent" => null];

        $typeId = null;
        if (!empty($data["outflow_type_name"])) {
            foreach ($catalogue["outflow_types"] as $type) {
                if ($this->matches($data["outflow_type_name"], $type["name"])) {
                    $typeId = $type["id"];
                    break;
                }
            }
        }
        $ids["id_outflow_type"] = $typeId;

        if (!empty($data["category_name"])) {
            // Primero dentro del tipo de egreso resuelto; si no, en todo el catalogo.
            $pool = $typeId !== null ? ($catalogue["categories"][$typeId] ?? []) : [];
            $match = $this->matchInPool($data["category_name"], $pool);
            if ($match === null) {
                foreach ($catalogue["outflow_types"] as $type) {
                    $match = $this->matchInPool($data["category_name"], $type["categories"]);
                    if ($match !== null) {
                        break;
                    }
                }
            }
            if ($match !== null) {
                $ids["id_category"] = $match["id"];
                // Si la categoria encontrada es de otro tipo, se ajusta el tipo.
                foreach ($catalogue["outflow_types"] as $type) {
                    foreach ($type["categories"] as $cat) {
                        if ($cat["id"] === $match["id"]) {
                            $ids["id_outflow_type"] = $type["id"];
                            break 2;
                        }
                    }
                }
            }
        }

        if (!empty($data["deposit_name"])) {
            $match = $this->matchInPool($data["deposit_name"], $catalogue["deposits"]);
            if ($match !== null) {
                $ids["id_porcent"] = $match["id"];
            }
        }

        return $ids;
    }

    /**
     * Busca el primer elemento del pool cuyo nombre coincide.
     */
    private function matchInPool(string $needle, array $pool): ?array
    {
        foreach ($pool as $item) {
            if ($this->matches($needle, $item["name"])) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Coincidencia tolerante: exacta -> prefijo de 8 chars -> contenido.
     * Misma tecnica que `TransactionMerger::isMerchantSimilar` del cliente Android.
     */
    private function matches(string $needle, string $haystack): bool
    {
        $a = $this->normalize($needle);
        $b = $this->normalize($haystack);
        if ($a === "" || $b === "") {
            return false;
        }
        if ($a === $b) {
            return true;
        }
        if (strlen($a) >= 8 && strlen($b) >= 8 && substr($a, 0, 8) === substr($b, 0, 8)) {
            return true;
        }
        return strpos($a, $b) !== false || strpos($b, $a) !== false;
    }

    /**
     * Normaliza texto para comparar: minusculas, sin tildes, sin signos.
     */
    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), "UTF-8");
        $text = preg_replace('/\p{Mn}/u', '', $text);
        if ($text === null) {
            $text = mb_strtolower(trim($text), "UTF-8");
        }
        $text = preg_replace('/[^a-z0-9]+/u', ' ', $text);
        return trim((string) $text);
    }

    /**
     * Interpreta una fecha en varios formatos y la normaliza a "Y-m-d H:i:s".
     */
    private function parseEventDateTime(string $value): ?string
    {
        $value = trim($value);
        $value = str_replace("T", " ", $value);
        $value = rtrim($value, "Z ");

        // "2026/11/19 21:46" (formato de las notificaciones del banco)
        if (preg_match('/(\d{4})[\/\-](\d{2})[\/\-](\d{2})(?:[ T](\d{2}):(\d{2}))?/', $value, $m)) {
            return sprintf(
                "%s-%s-%s %s:%s:00",
                $m[1],
                $m[2],
                $m[3],
                $m[4] ?? "00",
                $m[5] ?? "00"
            );
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date("Y-m-d H:i:s", $timestamp);
        }
        return null;
    }

    /**
     * Fallback determinista por regex cuando el LLM no esta disponible.
     */
    private function fallback(string $rawText, array $catalogue): array
    {
        $data = [
            "amount"            => null,
            "currency"          => "COP",
            "description"       => null,
            "event_datetime"    => null,
            "event_date"        => null,
            "outflow_type_name" => null,
            "category_name"     => null,
            "deposit_name"      => null,
            "counterparty"      => null,
            "payment_method"    => null,
            "card_last_four"    => null,
            "confidence"        => 0.0,
            "fields_missing"    => ["outflow_type_name", "category_name", "deposit_name"],
        ];

        if (preg_match('/\$\s*([\d.,]+)/u', $rawText, $m)) {
            $data["amount"] = $this->parseColombianAmount($m[1]);
        }
        if (preg_match('/(\d{4})\/(\d{2})\/(\d{2})[ T]?(\d{2}):(\d{2})/u', $rawText, $m)) {
            $data["event_datetime"] = sprintf("%s-%s-%s %s:%s:00", $m[1], $m[2], $m[3], $m[4], $m[5]);
            $data["event_date"] = substr($data["event_datetime"], 0, 10);
        } elseif (preg_match('/(\d{2})\/(\d{2})\/(\d{4})[ T]?(\d{2}):(\d{2})/u', $rawText, $m)) {
            // Formato dd/mm/yyyy (algunos correos del banco lo usan).
            $data["event_datetime"] = sprintf("%s-%s-%s %s:%s:00", $m[3], $m[2], $m[1], $m[4], $m[5]);
            $data["event_date"] = substr($data["event_datetime"], 0, 10);
        } elseif (preg_match('/(\d{4})\/(\d{2})\/(\d{2})/u', $rawText, $m) && $data["event_date"] === null) {
            $data["event_datetime"] = "{$m[1]}-{$m[2]}-{$m[3]} 00:00:00";
            $data["event_date"] = "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $rawText))));
        $data["description"] = mb_substr($lines[0] ?? "Notificacion sin descripcion", 0, 120, "UTF-8");

        return ["data" => $data, "ids" => ["id_outflow_type" => null, "id_category" => null, "id_porcent" => null]];
    }

    /**
     * Interpreta un monto colombiano: "1.234.567,89" -> 1234567.89
     */
    private function parseColombianAmount(string $value): ?float
    {
        $value = trim(str_replace(" ", "", $value));
        if ($value === "") {
            return null;
        }

        $lastComma = strrpos($value, ",");
        $lastDot = strrpos($value, ".");

        if ($lastComma !== false && $lastDot !== false) {
            // El ultimo separador es el decimal.
            if ($lastComma > $lastDot) {
                $value = str_replace(".", "", $value);
                $value = str_replace(",", ".", $value);
            } else {
                $value = str_replace(",", "", $value);
            }
        } elseif ($lastComma !== false) {
            // Solo coma: decimal (100,00) o miles (1,000)? En Colombia la coma es decimal.
            $decimals = strlen($value) - $lastComma - 1;
            $value = $decimals === 3 && strlen(substr($value, 0, $lastComma)) > 0 && substr_count($value, ",") === 1
                ? str_replace(",", "", $value)
                : str_replace(",", ".", $value);
        } elseif ($lastDot !== false) {
            // Solo punto: decimal (100.50) o miles (1.000.000).
            if (substr_count($value, ".") > 1) {
                $value = str_replace(".", "", $value);
            } elseif (strlen($value) - $lastDot - 1 === 3) {
                $value = str_replace(".", "", $value);
            }
        }

        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    /**
     * @return array{ok: bool, data: array, ids: array, needs_review: bool, source: string, error: ?string, raw_response: string}
     */
    private function result(array $data, array $ids, bool $needsReview, string $source, ?string $error, string $rawResponse): array
    {
        return [
            "ok"           => true,
            "data"         => $data,
            "ids"          => $ids,
            "needs_review" => $needsReview,
            "source"       => $source,
            "error"        => $error,
            "raw_response" => $rawResponse,
        ];
    }
}
