<?php

/**
 * Servicio puro de parseo del payload de notificaciones.
 *
 * El celular (MacroDroid) manda la notificacion como un "dump" separado por
 * "|", con este orden:
 *
 *   titulo | subtitulo | texto_grande(+timestamp) | texto | canal | paquete | api | memoria
 *
 * Los dos ultimos campos (y el canal) son METADATOS del dispositivo: no
 * son informacion del egreso, asi que se separan y nunca llegan al prompt
 * ni a la descripcion.
 *
 * El timestamp epoch en milisegundos va PEGADO al final del campo de texto
 * (13 digitos). Si no se quita, parece un monto y rompe el parseo.
 *
 * Todas las funciones son puras (sin base de datos ni HTTP) para poder
 * razonarlas y probarlas aisladas.
 */
class NotificationPayload
{
    /** Numero de campos de contenido antes de los metadatos. */
    private const CONTENT_FIELDS = 4;

    /** Timestamp epoch en milisegundos (13 digitos). */
    private const EPOCH_PATTERN = '/(?<!\d)\d{13}(?!\d)/';

    /** Monto con simbolo de moneda: "COP 62,870.00", "$ 1.000,00". */
    private const MONEY_WITH_CURRENCY = '/(?:COP|USD|EUR|GBP|\$|€)\s*(\d{1,3}(?:[.,\s]\d{3})*(?:[.,]\d{1,2})?|\d+(?:[.,]\d{1,2})?)/i';

    /** Monto sin simbolo, con separadores de miles y decimales: "1.234.567,89". */
    private const MONEY_GROUPED = '/(?<![\d.,])(\d{1,3}(?:[.,]\d{3})+(?:[.,]\d{2})?)(?![\d])/';

    /**
     * Palabras que delatan un pago rechazado / declinado / fallido.
     *
     * "sin saldo" NO esta aca: un aviso de transferencia por saldo
     * insuficiente sigue siendo un egreso. Solo cuenta combinado con
     * una palabra de rechazo (ver `isDeclined`).
     */
    private const DECLINE_PATTERNS = [
        "/rechazad[oa]/u",
        "/declinad[oa]/u",
        "/declined/u",
        "/no se pudo completar/u",
        "/no pudo completarse/u",
        "/no se pudo realizar/u",
        "/pago fallido/u",
        "/transacci[oó]n fallida/u",
        "/payment declined/u",
        "/not authorized/u",
    ];

    /**
     * Separa el dump en contenido util y metadatos.
     *
     * @return array{
     *   text: string, title: string, body: string,
     *   timestamp: ?int, metadata: array
     * }
     */
    public static function parse(string $rawText): array
    {
        $rawText = trim($rawText);
        if ($rawText === "") {
            return ["text" => "", "title" => "", "body" => "", "timestamp" => null, "metadata" => []];
        }

        $fields = array_map("trim", explode("|", $rawText));

        // El dump real empieza con "| ": el primer segmento sale vacio.
        if (count($fields) > 1 && $fields[0] === "") {
            array_shift($fields);
        }

        $content = array_slice($fields, 0, self::CONTENT_FIELDS);

        // Con el template completo (8 campos) la cola es posicional:
        // canal, paquete, api_level, memoria.
        if (count($fields) >= 8) {
            $tail = array_slice($fields, -4);
            $metadata = [
                "canal"     => $tail[0] ?? null,
                "paquete"   => $tail[1] ?? null,
                "api_level" => $tail[2] ?? null,
                "memoria"   => $tail[3] ?? null,
            ];
        } else {
            $metadata = self::classifyMetadata(array_slice($fields, self::CONTENT_FIELDS));
        }

        $timestamp = null;
        foreach ($content as $index => $field) {
            if (preg_match(self::EPOCH_PATTERN, $field, $matches, PREG_OFFSET_CAPTURE)) {
                $value = $matches[0][0];
                if ($timestamp === null) {
                    $timestamp = (int) $value;
                }
                $content[$index] = trim(str_replace($value, " ", $field));
            }
        }

        $content = array_values(array_filter($content, function ($field) {
            return trim($field) !== "";
        }));

        $title = $content[0] ?? "";
        $body = count($content) > 1 ? implode("\n", array_slice($content, 1)) : "";

        return [
            "text"      => trim($title . ($body !== "" ? "\n" . $body : "")),
            "title"     => $title,
            "body"      => $body,
            "timestamp" => $timestamp,
            "metadata"  => $metadata,
        ];
    }

    /**
     * Clasifica los campos de cola (canal, paquete, api_level, memoria)
     * por forma y no por posicion: asi el parseo aguanta dumps incompletos.
     */
    private static function classifyMetadata(array $tail): array
    {
        $metadata = ["canal" => null, "paquete" => null, "api_level" => null, "memoria" => null];

        foreach ($tail as $field) {
            if ($field === "") {
                continue;
            }
            if ($metadata["paquete"] === null && preg_match('/^[a-z0-9]+(?:\.[a-z0-9_]+)+$/i', $field)) {
                $metadata["paquete"] = $field;
                continue;
            }
            if ($metadata["memoria"] === null && preg_match('/^\d+(?:[.,]\d+)?\s*(?:gb|mb|kb|%)$/i', $field)) {
                $metadata["memoria"] = $field;
                continue;
            }
            if ($metadata["api_level"] === null && preg_match('/^\d{1,3}$/', $field)) {
                $metadata["api_level"] = $field;
                continue;
            }
            if ($metadata["canal"] === null) {
                $metadata["canal"] = $field;
            }
        }

        return $metadata;
    }

    /**
     * El texto tiene un numero que parece plata.
     *
     * NO cuenta: un numero largo suelto (telefonos, ids, el epoch), ni una
     * fecha ("19/11/2026"), ni la memoria del dispositivo ("3.48GB").
     */
    public static function hasAmount(string $text): bool
    {
        return self::findAmount($text) !== null;
    }

    /**
     * Primer valor con forma de dinero del texto, o null.
     */
    public static function parseAmount(string $text): ?float
    {
        $match = self::findAmount($text);
        if ($match === null) {
            return null;
        }
        return self::parseNumber($match);
    }

    /**
     * Busca el primer monto y devuelve solo la parte numerica.
     */
    private static function findAmount(string $text): ?string
    {
        if (preg_match(self::MONEY_WITH_CURRENCY, $text, $matches)) {
            return $matches[1];
        }
        if (preg_match(self::MONEY_GROUPED, $text, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Interpreta un numero monetario en formato Colombia ("1.234.567,89")
     * o USA ("62,870.00").
     */
    private static function parseNumber(string $value): ?float
    {
        $value = trim(str_replace([" ", "\u{00A0}"], "", $value));
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
            // Solo coma: decimal (100,00) o miles (11,000).
            $decimals = strlen($value) - $lastComma - 1;
            $value = ($decimals === 3 && substr_count($value, ",") === 1)
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
     * El texto dice que el pago fue rechazado / declinado / fallido.
     */
    public static function isDeclined(string $text): bool
    {
        foreach (self::DECLINE_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        // "sin saldo" solo declina si viene con una palabra de rechazo.
        if (preg_match('/\bsin saldo\b/u', $text)) {
            foreach (self::DECLINE_PATTERNS as $pattern) {
                if (preg_match($pattern, $text)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Descripcion lista para guardar: sin "|", sin epoch, sin metadatos,
     * sin espacios de mas y de 120 caracteres como maximo.
     *
     * Se queda con la primera linea con contenido (el titulo / comercio).
     */
    public static function cleanDescription(string $text): string
    {
        $text = str_replace("|", " ", $text);

        $lines = preg_split('/\R/u', $text);
        foreach ($lines as $line) {
            $line = trim((string) preg_replace('/\s+/u', " ", $line));
            if ($line === "") {
                continue;
            }

            // El template viejo de MacroDroid unia las partes con " - ".
            $parts = array_map("trim", explode(" - ", $line));
            $clean = [];
            foreach ($parts as $part) {
                $part = self::cleanPart($part);
                if ($part === "") {
                    continue;
                }
                $key = mb_strtolower($part);
                $repeated = false;
                foreach ($clean as $kept) {
                    if (mb_strtolower($kept) === $key) {
                        $repeated = true;
                        break;
                    }
                }
                if (!$repeated) {
                    $clean[] = $part;
                }
            }

            if (!empty($clean)) {
                return mb_substr(implode(" - ", $clean), 0, 120, "UTF-8");
            }
        }

        return "Notificacion sin descripcion";
    }

    /**
     * Limpia una parte suelta de la descripcion: fuera epoch, correos, ids
     * largos, paquetes de android y metadatos del dispositivo.
     */
    private static function cleanPart(string $part): string
    {
        $part = (string) preg_replace(self::EPOCH_PATTERN, " ", $part);
        // Correos electronicos.
        $part = (string) preg_replace('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/u', " ", $part);
        // Paquetes de android y canales con guion bajo.
        $part = (string) preg_replace('/\b[a-z0-9]+(?:\.[a-z0-9_]+){2,}\b/iu', " ", $part);
        $part = (string) preg_replace('/\b[a-z]+(?:_[a-z0-9]+)+\b/iu', " ", $part);
        // Memoria del dispositivo ("3.48GB").
        $part = (string) preg_replace('/\b\d+(?:[.,]\d+)?\s*(?:gb|mb|kb|%)$/iu', " ", $part);
        // Ids largos (codigos de operacion) que no son informacion del gasto.
        $part = (string) preg_replace('/\b\d{7,}\b/u', " ", $part);

        return trim((string) preg_replace('/\s+/u', " ", $part));
    }

    /**
     * Palabras genericas que NO identifican a un comercio.
     *
     * Sin esta lista, "Compra Exitosa - ALPHA" y "Compra Exitosa - BETA"
     * se parecerian por "compra"/"exitosa" y dos compras distintas del
     * mismo monto quedarían marcadas como duplicado.
     */
    private const GENERIC_TOKENS = [
        "compra", "compras", "compraexitosa", "exitosa", "exitoso", "exitosas",
        "pago", "pagos", "transaccion", "transacciones", "operacion", "movimiento",
        "tarjeta", "tarjetas", "retiro", "retiros", "transferencia", "transferencias",
        "cuenta", "cuentas", "saldo", "valor", "total", "compraexitosa",
        "mastercard", "visa", "amex", "débito", "debito", "credito", "crédito",
        "wallet", "google", "apple", "pay", "nequi", "daviplata", "movil",
        "realizada", "realizado", "aprobada", "aprobado", "confirmado",
        "notification", "notificacion", "aviso", "compraenlinea",
    ];

    /**
     * Dos descripciones son equivalentes.
     *
     * 1. Normalizadas iguales.
     * 2. Prefijo de 8 caracteres en ambos sentidos (misma tecnica que
     *    `TransactionMerger::isMerchantSimilar` del cliente Android).
     * 3. Una contiene a la otra ("TIENDA D1" dentro de "TIENDA D1 ENGATIVA").
     * 4. Comparten una palabra significativa de 6+ letras que no sea
     *    generica. Este caso es el que cubre el cruce entre canales:
     *    "Compra Exitosa - EDS ENGATIVA FR" (correo del banco) contra
     *    "TIENDA D1 ENGATIVA EL" (Google Wallet) comparten "engativa".
     */
    public static function isSimilar(string $a, string $b): bool
    {
        $na = self::normalize($a);
        $nb = self::normalize($b);
        if ($na === "" || $nb === "") {
            return false;
        }
        if ($na === $nb) {
            return true;
        }

        // La comparacion se hace sobre las palabras SIGNIFICATIVAS (las que
        // identifican un comercio). Comparar el prefijo crudo seria un
        // error: TODAS las compras del banco empiezan con "Compra
        // Exitosa", asi que dos compras distintas de igual monto
        // parecerian la misma.
        $tokensA = self::significantTokens($na);
        $tokensB = self::significantTokens($nb);
        if (empty($tokensA) || empty($tokensB)) {
            // Sin palabras que identifiquen al comercio no se puede
            // confirmar que sean el mismo: es preferible duplicar que
            // tragarse un egreso real.
            return false;
        }

        if ($tokensA === $tokensB) {
            return true;
        }

        $joinedA = implode(" ", $tokensA);
        $joinedB = implode(" ", $tokensB);

        // Una contiene a la otra ("TIENDA D1" dentro de "TIENDA D1 ENGATIVA").
        if (strpos($joinedA, $joinedB) !== false || strpos($joinedB, $joinedA) !== false) {
            return true;
        }

        // Prefijo de 8 caracteres sobre la firma significativa.
        $length = min(8, min(strlen($joinedA), strlen($joinedB)));
        if ($length >= 3 && substr($joinedA, 0, $length) === substr($joinedB, 0, $length)) {
            return true;
        }

        // Comparten al menos una palabra significativa igual. Este es el
        // caso que cubre el cruce entre canales: "Compra Exitosa - EDS
        // ENGATIVA FR" (correo del banco) contra "TIENDA D1 ENGATIVA EL"
        // (Google Wallet) comparten "engativa".
        foreach ($tokensB as $tokenB) {
            if (in_array($tokenB, $tokensA, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Palabras que identifican un comercio: 6+ letras y no genericas.
     *
     * @return array<int, string>
     */
    private static function significantTokens(string $normalized): array
    {
        $tokens = [];
        foreach (explode(" ", $normalized) as $token) {
            if (strlen($token) < 6) {
                continue;
            }
            if (in_array($token, self::GENERIC_TOKENS, true)) {
                continue;
            }
            $tokens[] = $token;
        }

        return array_values(array_unique($tokens));
    }

    /**
     * Normaliza para comparar: minusculas, sin tildes, sin signos.
     *
     * OJO: las tildes y la enye.spanolas son letras PRECOMPUESTAS, asi que
     * `\p{Mn}` NO las quita: se transliteran explicitamente.
     */
    private static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), "UTF-8");
        $text = strtr($text, [
            "á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u",
            "ü" => "u", "ñ" => "n", "â" => "a", "ê" => "e", "î" => "i",
            "ô" => "o", "û" => "u", "ç" => "c",
        ]);
        $text = (string) preg_replace('/\p{Mn}/u', '', $text);
        $text = (string) preg_replace('/[^a-z0-9]+/u', ' ', $text);
        return trim($text);
    }
}
