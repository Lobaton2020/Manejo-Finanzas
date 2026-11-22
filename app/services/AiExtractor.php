<?php

require_once URL_APP . "services/NotificationPayload.php";
require_once URL_APP . "services/ProcessLog.php";

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

    /**
     * Palabras que delatan un tipo de egreso (ademas de las del propio nombre).
     */
    private const TYPE_KEYWORDS = [
        "inversion" => [
            "inversion", "invertir", "inverti", "bitcoin", "crypto", "acciones",
            "cedula", "finca", "ursi", "etf", "renta4", "alpaca", "binance",
            "mercado de valores", "bolsa",
        ],
    ];

    /**
     * Palabras que delatan una categoria (ademas de las del propio nombre).
     * La clave es un fragmento en minusculas del nombre de la categoria.
     */
    private const CATEGORY_KEYWORDS = [
        "mercado"   => ["supermercado", "tienda", "comercio", "plaza", "exito", "almacen"],
        "comida"    => ["almuerzo", "cena", "restaurante", "comida", "domicilio", "rapida", "hamburgues", "pizza", "hamburguesa"],
        "gasolina"  => ["gasolina", "bencina", "combustible", "cargador", "eds", "terpel", "petrobras", "primus", "copia"],
        "pasaje"    => ["transmilenio", "pasaje", "bus", "taxi", "metro", "sitp", "buseta"],
        "salud"     => ["doctor", "medico", "medicina", "salud", "farmacia", "dental", "odontologo", "hospital", "psicolog"],
        "educacion" => ["universidad", "colegio", "matricula", "educacion", "curso", "semestre", "universitario"],
        "viajes"    => ["viaje", "vuelo", "hotel", "airbnb", "pasajes", "aviacion", "aerolinea", "tiquete"],
        "transporte" => ["uber", "taxi", "mensajeria", "envio a domicilio", "domicilio"],
        "deudas"    => ["deuda", "prestamo", "cuota", "credito", "financiacion"],
        "arriendo"  => ["arriendo", "renta", "alquiler", "vivienda", "apartamento", "canon"],
        "servicios" => ["servicio", "luz", "agua", "internet", "energia", "telefono", "factura", "gas domiciliario"],
        "ropa"      => ["ropa", "zapato", "camisa", "pantalon", "vestido", "calzado", "zapatilla"],
        "fitness"   => ["gimnasio", "gym", "fitness", "entrenamiento", "crossfit"],
        "electrodomesticos" => ["nevera", "lavadora", "televisor", "electrodomestico", "aspiradora", "horno"],
        "suscripciones" => ["netflix", "spotify", "amazon", "disney", "youtube", "suscripcion", "membership", "hbo", "prime video"],
        "tecnologia" => ["computador", "laptop", "celular", "teclado", "monitor", "tecnolog", "audifonos", "mouse", "tablet"],
        "regalos"   => ["regalo", "gift", "obsequio"],
        "terapia"   => ["terapia", "psicologo", "consulta medica"],
        "trabajo"   => ["trabajo", "servicio prestado", "horas extra"],
        "electro"   => ["electrodomestico", "electrodomesticos"],
    ];

    /**
     * Palabras de la notificacion que apuntan a una categoria "generica".
     * Se usa cuando el mensaje no menciona ninguna categoria concreta pero
     * si es claramente un pago o una transferencia.
     */
    private const CATEGORY_HINTS = [
        "envio"        => ["otros", "otro", "transferencia", "transferencias", "envio", "envios"],
        "transferencia" => ["transferencia", "transferencias", "otros", "otro", "envio", "envios"],
        "bre-b"        => ["transferencia", "otros", "otro"],
        "breb"         => ["transferencia", "otros", "otro"],
        "nequi"        => ["transferencia", "otros", "otro", "recarga"],
        "llave"        => ["transferencia", "otros", "otro"],
        "consignacion" => ["transferencia", "otros", "otro"],
        "pago"         => ["otros", "otro", "pago", "pagos"],
        "compra"       => ["mercado", "compras", "otros"],
        "retiro"       => ["retiro", "retiros", "caja", "otros"],
    ];

    /**
     * Palabras que delatan un deposito (ademas de las del propio nombre).
     */
    private const DEPOSIT_KEYWORDS = [
        "ahorro"    => ["ahorro", "ahorros", "ahorrar", "cedula", "ahorro en dolares"],
        "viajes"    => ["viaje", "vuelo", "hotel", "pasajes", "turismo"],
        "educacion" => ["universidad", "colegio", "matricula", "universidad"],
        "inversion" => ["inversion", "invertir", "bitcoin", "crypto"],
        "donativos" => ["donacion", "donativo", "ayuda", "donar"],
        "comida"    => ["mercado", "supermercado", "comida", "groceries"],
        "moto"      => ["moto", "motocicleta"],
        "finca"     => ["finca", "granja", "siembra", "cosecha"],
        "diversion" => ["diversion", "fiesta", "cine", "paseo", "vacaciones"],
    ];

    /**
     * Palabras del nombre de una categoria que describen el MEDIO de pago y
     * no el motivo del gasto: no sirven como evidencia de categoria.
     */
    private const CATEGORY_NAME_STOPWORDS = [
        "tarjeta", "tarjetas", "mastercard", "visa", "debito", "credito",
        "efectivo", "consignacion", "transferencia", "transferencias",
        "compra", "compras", "pago", "pagos", "envio", "envios",
    ];

    /**
     * Palabras que no sirven como evidencia: aparecen en cualquier lado.
     */
    private const GENERIC_WORDS = [
        "compra", "compras", "compra y", "otros", "otro", "pago", "pagos",
        "gasto", "gastos", "salida", "egreso", "movimiento", "transaccion",
        "me", "no", "aplica", "varios",
        "inversion", "inversiones", "fondo", "cuenta", "llave", "operacion",
        "persona", "entidad", "codigo", "detalle", "detalles", "atencion",
        "linea", "oficinas", "establecimiento", "banco", "valor",
    ];

    /**
     * Contextos negativos: "numero de celular" no es evidencia de una compra
     * de celular, es el tipo de llave de una transferencia.
     */
    private const NEGATIVE_CONTEXT = [
        "numero de", "tipo de", "cuenta de", "clave de", "llave de", "codigo de",
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
     * @param array  $previous  Sugerencias pendientes previas del usuario
     *                          ("valores previos" que se le pasan al modelo).
     * @return array{
     *   ok: bool, data: array, ids: array, needs_review: bool,
     *   source: string, error: ?string, raw_response: string
     * }
     */
    public function extract(int $idUser, string $rawText, array $previous = []): array
    {
        $rawText = trim($rawText);
        if ($rawText === "") {
            ProcessLog::event("ai_extract", "review_reasons", ["reasons" => "force_review", "count" => 1]);
            ProcessLog::event("ai_extract", "finish", [
                "source"         => "none",
                "needs_review"   => true,
                "amount"         => null,
                "confidence"     => null,
                "id_outflow_type" => null,
                "id_category"     => null,
                "id_porcent"      => null,
            ]);
            return $this->result([], [], true, "none", "El texto de la notificacion esta vacio.", "");
        }

        // El texto puede venir como dump de MacroDroid ("|"): se deja solo el
        // contenido real, sin metadatos del dispositivo ni epoch pegado.
        $payload = NotificationPayload::parse($rawText);
        if ($payload["text"] !== "") {
            $rawText = $payload["text"];
        }

        $catalogue = $this->catalogue($idUser);
        $categoryCount = 0;
        foreach ($catalogue["categories"] as $categoriesOfType) {
            $categoryCount += count($categoriesOfType);
        }
        ProcessLog::event("ai_extract", "catalogue", [
            "outflow_types" => count($catalogue["outflow_types"]),
            "categories"    => $categoryCount,
            "deposits"      => count($catalogue["deposits"]),
        ]);

        $systemPrompt = $this->buildPrompt($catalogue, $previous);
        $messages = [
            ["role" => "system", "content" => $systemPrompt],
            ["role" => "user", "content" => $rawText],
        ];
        ProcessLog::event("ai_extract", "prompt", [
            "chars"      => mb_strlen($systemPrompt),
            "text_chars" => mb_strlen($rawText),
            "previous"   => count($previous),
        ]);

        $response = $this->client->chat($messages);
        if (!$response["ok"]) {
            // El LLM fallo: fallback determinista para no perder la sugerencia.
            ProcessLog::error("ai_extract", "ai_failed", [
                "error" => $response["error"] ?? null,
                "code"  => $response["code"] ?? null,
            ]);
            $fallback = $this->fallback($rawText, $catalogue);
            ProcessLog::event("ai_extract", "heuristics", [
                "type"     => $fallback["ids"]["id_outflow_type"] !== null,
                "category" => $fallback["ids"]["id_category"] !== null,
                "deposit"  => $fallback["ids"]["id_porcent"] !== null,
            ]);
            return $this->finish(
                $fallback["data"],
                $fallback["ids"],
                true,
                "fallback",
                $response["error"],
                "",
                $catalogue,
                $rawText
            );
        }

        $parsed = $this->parseJson($response["content"]);
        $parseData = ["ok" => $parsed !== null];
        if ($parsed === null) {
            $parseData["response"] = ProcessLog::preview($response["content"], 300);
        }
        ProcessLog::event("ai_extract", "parse_json", $parseData);
        if ($parsed === null) {
            ProcessLog::error("ai_extract", "parse_json_failed", [
                "response" => ProcessLog::preview($response["content"], 300),
            ]);
            $fallback = $this->fallback($rawText, $catalogue);
            ProcessLog::event("ai_extract", "heuristics", [
                "type"     => $fallback["ids"]["id_outflow_type"] !== null,
                "category" => $fallback["ids"]["id_category"] !== null,
                "deposit"  => $fallback["ids"]["id_porcent"] !== null,
            ]);
            return $this->finish(
                $fallback["data"],
                $fallback["ids"],
                true,
                "fallback",
                "MiniMax no devolvio un JSON utilizable.",
                $response["content"],
                $catalogue,
                $rawText
            );
        }

        $data = $this->normalizeData($parsed);
        $ids = $this->resolveIds($data, $catalogue);

        ProcessLog::event("ai_extract", "resolve_ids", [
            "outflow_type_name" => $data["outflow_type_name"] ?? null,
            "category_name"     => $data["category_name"] ?? null,
            "deposit_name"      => $data["deposit_name"] ?? null,
            "id_outflow_type"   => $ids["id_outflow_type"] ?? null,
            "id_category"       => $ids["id_category"] ?? null,
            "id_porcent"        => $ids["id_porcent"] ?? null,
        ]);

        // Si el modelo no resolvio tipo o categoria, se completan con
        // heuristicas por palabras clave del mensaje: la sugerencia llega con
        // una opcion por defecto en lugar de vacia.
        $heuristic = $this->inferByKeywords($rawText, $catalogue);
        $heuristicType = false;
        $heuristicCategory = false;
        $heuristicDeposit = false;
        if ($ids["id_outflow_type"] === null && $heuristic["id_outflow_type"] !== null) {
            $ids["id_outflow_type"] = $heuristic["id_outflow_type"];
            $data["outflow_type_name"] = $heuristic["outflow_type_name"];
            $heuristicType = true;
        }
        if ($ids["id_category"] === null && $heuristic["id_category"] !== null) {
            $ids["id_category"] = $heuristic["id_category"];
            $data["category_name"] = $heuristic["category_name"];
            $heuristicCategory = true;
        }
        if ($ids["id_porcent"] === null && $heuristic["id_porcent"] !== null) {
            $ids["id_porcent"] = $heuristic["id_porcent"];
            $data["deposit_name"] = $heuristic["deposit_name"];
            $heuristicDeposit = true;
        }
        ProcessLog::event("ai_extract", "heuristics", [
            "type"     => $heuristicType,
            "category" => $heuristicCategory,
            "deposit"  => $heuristicDeposit,
        ]);

        return $this->finish($data, $ids, false, "ai", null, $response["content"], $catalogue, $rawText);
    }

    /**
     * Deja la extraccion lista para guardarse: descripcion limpia, fecha por
     * defecto y deposito por defecto.
     *
     * @param bool $forceReview Fuerza la revision manual (fallback sin IA).
     */
    private function finish(
        array $data,
        array $ids,
        bool $forceReview,
        string $source,
        ?string $error,
        string $rawResponse,
        array $catalogue,
        string $rawText = ""
    ): array {
        // Descripcion limpia: sin "|", sin epoch y sin metadatos.
        if (!empty($data["description"])) {
            $data["description"] = NotificationPayload::cleanDescription((string) $data["description"]);
        } elseif ($rawText !== "") {
            // El modelo no devolvio descripcion: se usa la del texto limpio.
            $data["description"] = NotificationPayload::cleanDescription($rawText);
        }

        // Fecha por defecto: si el mensaje no trae fecha, se usa la del evento
        // generado (hoy, America/Bogota).
        if (empty($data["event_date"])) {
            $now = getCurrentDatetime();
            $data["event_datetime"] = $now;
            $data["event_date"] = substr($now, 0, 10);
        }

        // Deposito por defecto: "Inversion" para el tipo de egreso
        // inversion, "Gastos en general" para el resto.
        if ($ids["id_porcent"] === null) {
            $default = $this->defaultDeposit($data, $catalogue);
            if ($default !== null) {
                $ids["id_porcent"] = $default["id"];
                $data["deposit_name"] = $default["name"];
                // El deposito ya quedo preseleccionado: no falta.
                $data["fields_missing"] = array_values(array_filter(
                    (array) $data["fields_missing"],
                    fn($field) => $field !== "deposit_name"
                ));
            }
        }

        // Si el modelo no pudo resolver tipo o categoria, la sugerencia queda
        // para revision manual: el usuario los elige en el formulario.
        // El booleano final es el mismo de antes; lo que se agrega es el
        // detalle de POR QUE hay que revisarla.
        $reviewReasons = [];
        if ($forceReview) {
            $reviewReasons[] = "force_review";
        } else {
            if ($ids["id_outflow_type"] === null) {
                $reviewReasons[] = "missing_outflow_type";
            }
            if ($ids["id_category"] === null) {
                $reviewReasons[] = "missing_category";
            }
            if (!empty($data["fields_missing"])) {
                $reviewReasons[] = "missing_fields";
            }
            if ($data["amount"] === null || $data["amount"] <= 0) {
                $reviewReasons[] = "missing_amount";
            }
            // El deposito decide de que plata sale: sin evidencia se presume
            // el deposito por defecto, asi que esto no marca revision.
            if ($ids["id_porcent"] === null) {
                $reviewReasons[] = "missing_deposit";
            }
        }
        $needsReview = !empty($reviewReasons);

        ProcessLog::event("ai_extract", "review_reasons", [
            "reasons" => implode(",", $reviewReasons),
            "count"   => count($reviewReasons),
        ]);

        ProcessLog::event("ai_extract", "finish", [
            "source"          => $source,
            "needs_review"    => $needsReview,
            "amount"          => $data["amount"] ?? null,
            "confidence"      => $data["confidence"] ?? null,
            "id_outflow_type" => $ids["id_outflow_type"] ?? null,
            "id_category"     => $ids["id_category"] ?? null,
            "id_porcent"      => $ids["id_porcent"] ?? null,
        ]);

        return $this->result($data, $ids, $needsReview, $source, $error, $rawResponse);
    }

    /**
     * Deposito por defecto del usuario (resuelto por NOMBRE en el catalogo).
     */
    private function defaultDeposit(array $data, array $catalogue): ?array
    {
        if (empty($catalogue["deposits"])) {
            return null;
        }

        $typeName = mb_strtolower((string) ($data["outflow_type_name"] ?? ""));
        $wanted = (strpos($typeName, "inversion") !== false) ? "Inversion" : "Gastos en general";

        foreach ($catalogue["deposits"] as $deposit) {
            if (mb_strtolower(trim($deposit["name"])) === mb_strtolower($wanted)) {
                return $deposit;
            }
        }

        // Si el usuario no tiene ese deposito: el primero activo del catalogo.
        return $catalogue["deposits"][0];
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
     *
     * @param array $catalogue Catalogo del usuario.
     * @param array $previous  Sugerencias pendientes previas ("valores previos").
     */
    private function buildPrompt(array $catalogue, array $previous = []): string
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
        $lines[] = "- Si el mensaje da pistas para decidir un tipo, una categoria o un deposito, elige SIEMPRE la opcion mas probable del catalogo: esa es la que el usuario va a ver preseleccionada. Solo deja null cuando el texto no de ninguna pista.";
        $lines[] = "- description: maximo 120 caracteres, en español, empezando por el comercio o el proposito del gasto (sin \"Compra en\", \"Pago en\" ni preposiciones de relleno), para poder compararla con otras notificaciones.";
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

        // Valores previos: los pendientes recientes del usuario. La misma
        // compra puede llegar por otro canal (correo del banco, Wallet).
        if (!empty($previous)) {
            $lines[] = "VALORES PREVIOS (sugerencias pendientes del usuario, pueden ser la MISMA compra por otro canal: no dupliques):";
            foreach (array_slice($previous, 0, 5) as $item) {
                $date = !empty($item["event_date"]) ? (string) $item["event_date"] : "sin fecha";
                $amount = ($item["amount"] ?? null) !== null
                    ? number_format((float) $item["amount"], 2, ".", "")
                    : "sin monto";
                $description = trim((string) ($item["description"] ?? ""));
                $lines[] = "{$date} | {$amount} | {$description}";
            }
            $lines[] = "";
        }

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
     *
     * OJO: las tildes y la enye.spanolas son letras PRECOMPUESTAS (U+00F1,
     * U+00F3...), no marcas combinantes, asi que `\p{Mn}` NO las quita:
     * "Acompanar" terminaba en "acompa ar" y "acompanamos" en "acompa amos",
     * lo que hacia coincidir dos palabras que no tienen nada que ver. Por eso
     * se transliteran explicitamente antes de limpiar.
     */
    private function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), "UTF-8");
        $text = strtr($text, [
            "á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u",
            "ü" => "u", "ñ" => "n", "â" => "a", "ê" => "e", "î" => "i",
            "ô" => "o", "û" => "u", "ç" => "c",
        ]);
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
     * Elige tipo, categoria y deposito por palabras clave del mensaje.
     *
     * Es la version determinista de lo que hace el LLM: sirve cuando la IA no
     * esta disponible y como red de seguridad cuando el modelo no resuelve
     * algo. El deposito es el mas estricto (exige palabras de 5+ letras)
     * porque decide de que plata sale el dinero.
     *
     * @return array{id_outflow_type: ?int, id_category: ?int, id_porcent: ?int,
     *               outflow_type_name: ?string, category_name: ?string, deposit_name: ?string}
     */
    private function inferByKeywords(string $rawText, array $catalogue): array
    {
        $text = $this->normalize($rawText);
        $result = [
            "id_outflow_type"   => null,
            "id_category"       => null,
            "id_porcent"        => null,
            "outflow_type_name" => null,
            "category_name"     => null,
            "deposit_name"      => null,
        ];

        // 1) Tipo de egreso: se decide PRIMERO y no lo puede cambiar la
        //    categoria. Sin pistas de inversion, el tipo normal.
        $type = $this->pickType($text, $catalogue["outflow_types"]);
        if ($type !== null) {
            $result["id_outflow_type"] = $type["id"];
            $result["outflow_type_name"] = $type["name"];
        }

        // 2) Categoria: solo dentro del tipo elegido.
        $pool = $type !== null ? ($type["categories"] ?? []) : [];
        $match = $this->bestCategory($text, $pool);
        if ($match === null) {
            $match = $this->hintedCategory($text, $pool);
        }
        if ($match !== null) {
            $result["id_category"] = $match["id"];
            $result["category_name"] = $match["name"];
        }

        // 4) Deposito: evidencia mas estricta (palabras de 5+ letras).
        $bestDeposit = null;
        $bestDepositScore = 0;
        foreach ($catalogue["deposits"] as $deposit) {
            $score = $this->scoreKeywords($text, $this->keywordsFor($deposit["name"], self::DEPOSIT_KEYWORDS), 5);
            if ($score > $bestDepositScore) {
                $bestDepositScore = $score;
                $bestDeposit = $deposit;
            }
        }
        if ($bestDeposit !== null) {
            $result["id_porcent"] = $bestDeposit["id"];
            $result["deposit_name"] = $bestDeposit["name"];
        }

        return $result;
    }

    /**
     * Tipo de egreso mas probable. Sin pistas de inversion se elige el tipo
     * normal (el que no es "Inversion"), no el primero de la lista.
     */
    private function pickType(string $text, array $types): ?array
    {
        $best = null;
        $bestScore = 0;
        foreach ($types as $type) {
            $score = $this->scoreKeywords($text, $this->keywordsFor($type["name"], self::TYPE_KEYWORDS));
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $type;
            }
        }
        if ($best !== null) {
            return $best;
        }

        foreach ($types as $type) {
            if (strpos($this->normalize($type["name"]), "inversion") === false) {
                return $type;
            }
        }
        return $types[0] ?? null;
    }

    /**
     * Palabras que identifican un elemento del catalogo: las de su propio
     * nombre (4+ letras) mas las de la tabla de sinonimos.
     */
    private function keywordsFor(string $name, array $table): array
    {
        $keywords = [];
        $normalized = $this->normalize($name);
        foreach (explode(" ", $normalized) as $word) {
            if (strlen($word) >= 4 && !in_array($word, self::CATEGORY_NAME_STOPWORDS, true)) {
                $keywords[] = $word;
            }
        }
        foreach ($table as $fragment => $words) {
            if (strpos($normalized, $fragment) !== false) {
                foreach ($words as $word) {
                    $keywords[] = $word;
                }
            }
        }
        return array_values(array_unique($keywords));
    }

    /**
     * Palabras clave que aparecen en el texto.
     */
    private function matchKeywords(string $text, array $keywords, int $minLength = 4): array
    {
        $haystack = " " . $text . " ";
        $matched = [];
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if (strlen($keyword) < $minLength) {
                continue;
            }
            $pos = strpos($haystack, " " . $keyword . " ");
            if ($pos === false) {
                continue;
            }
            // "numero de celular" no es evidencia de una compra de celular:
            // es el tipo de llave de una transferencia.
            if ($this->inNegativeContext($haystack, $pos)) {
                continue;
            }
            $matched[] = $keyword;
        }
        return $matched;
    }

    /**
     * Indica si la aparicion de una palabra clave (en $pos) esta precedida
     * por un contexto negativo como "numero de", "tipo de" o "codigo de".
     */
    private function inNegativeContext(string $haystack, int $pos): bool
    {
        $before = rtrim(substr($haystack, 0, $pos));
        foreach (self::NEGATIVE_CONTEXT as $negative) {
            if (substr($before, -strlen($negative)) === $negative) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cuenta cuantas palabras clave aparecen en el texto.
     */
    private function scoreKeywords(string $text, array $keywords, int $minLength = 4): int
    {
        return count($this->matchKeywords($text, $keywords, $minLength));
    }

    /**
     * Categoria con mejor coincidencia dentro de un conjunto.
     *
     * Las palabras genericas ("compra", "gastos", "pago") no cuentan como
     * evidencia: si solo hay coincidencia generica, la categoria no se elige.
     */
    private function bestCategory(string $text, array $pool): ?array
    {
        $best = null;
        $bestScore = 0;
        foreach ($pool as $category) {
            $matched = $this->matchKeywords($text, $this->keywordsFor($category["name"], self::CATEGORY_KEYWORDS), 3);
            $matched = array_values(array_filter($matched, fn($word) => !in_array($word, self::GENERIC_WORDS, true)));
            if (count($matched) > $bestScore) {
                $bestScore = count($matched);
                $best = $category;
            }
        }
        return $best;
    }

    /**
     * Categoria sugerida por el tipo de operacion (pago, envio, transferencia).
     */
    private function hintedCategory(string $text, array $pool): ?array
    {
        foreach (self::CATEGORY_HINTS as $hint => $nameFragments) {
            if (strpos(" " . $text . " ", " " . $hint . " ") === false) {
                continue;
            }
            foreach ($pool as $category) {
                $normalized = $this->normalize($category["name"]);
                foreach ($nameFragments as $fragment) {
                    if (strpos($normalized, $fragment) !== false) {
                        return $category;
                    }
                }
            }
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

        $amount = NotificationPayload::parseAmount($rawText);
        if ($amount !== null) {
            $data["amount"] = $amount;
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

        $data["description"] = NotificationPayload::cleanDescription($rawText);

        // Tipo, categoria y deposito por palabras clave del mensaje.
        $inferred = $this->inferByKeywords($rawText, $catalogue);
        $data["outflow_type_name"] = $inferred["outflow_type_name"];
        $data["category_name"] = $inferred["category_name"];
        $data["deposit_name"] = $inferred["deposit_name"];
        $data["fields_missing"] = [];
        foreach (["outflow_type_name", "category_name", "deposit_name"] as $field) {
            if (empty($data[$field])) {
                $data["fields_missing"][] = $field;
            }
        }
        $data["confidence"] = empty($data["fields_missing"]) ? 0.4 : 0.2;

        return [
            "data" => $data,
            "ids" => [
                "id_outflow_type" => $inferred["id_outflow_type"],
                "id_category"     => $inferred["id_category"],
                "id_porcent"      => $inferred["id_porcent"],
            ],
        ];
    }

    /**
     * @return array{ok: bool, data: array, ids: array, needs_review: bool, source: string, error: ?string, raw_response: string}
     */
    private function result(array $data, array $ids, bool $needsReview, string $source, ?string $error, string $rawResponse): array
    {
        $provider = $this->client->provider();

        return [
            "ok"           => true,
            "data"         => $data,
            "ids"          => $ids,
            "needs_review" => $needsReview,
            "source"       => $source,
            "error"        => $error,
            "raw_response" => $rawResponse,
            // Que proveedor respondio realmente (MiniMax o el fallback Gemini).
            "provider"     => $provider,
            "ai_model"     => $provider === "none" ? null : $this->client->model(),
        ];
    }
}
