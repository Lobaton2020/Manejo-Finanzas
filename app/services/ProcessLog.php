<?php

/**
 * Log de proceso para requests que atienden fuentes externas (webhook).
 *
 * Escribe una linea por evento con error_log (=> stderr => `docker logs`
 * y `kubectl logs`). El formato es clave=valor para poder grepear:
 *
 *   [2026-11-22 05:40:12.345] [pid=17] [req=a1b2c3] [seq=4] [suggestions] level=info stage=request method=POST
 *
 * El formato completo de una linea es:
 *
 *   [timestamp] [pid=N] [req=xxxxxx] [seq=N] [scope] level=<info|error> stage=<nombre> k=v k=v ...
 *
 * - `level=error` marca SOLO fallos: `grep level=error` devuelve las lineas
 *   que salieron mal, nunca las de INFO.
 * - `seq` es el numero de evento dentro del request: permite reconstruir el
 *   orden de la traza.
 * - `req` es el id corto del request: `grep req=<id>` devuelve la traza
 *   completa de principio a fin.
 *
 * Nunca se registran secretos (keys de las IA, clave de la BD) ni el texto
 * crudo completo de la notificacion: como mucho una descripcion recortada
 * y el monto.
 */
class ProcessLog
{
    /** Instante de arranque del request (microtime). */
    private static $startedAt = null;

    /** Id corto del request en curso. */
    private static $requestId = null;

    /** Numero de evento dentro del request en curso. */
    private static $seq = 0;

    /**
     * Marca el arranque del request y genera su id.
     */
    public static function start(string $scope): void
    {
        if (self::$startedAt === null) {
            self::$startedAt = microtime(true);
            self::$seq = 0;
        }

        self::event($scope, "start");
    }

    /**
     * Id corto del request actual.
     */
    public static function requestId(): string
    {
        if (self::$requestId === null) {
            self::$requestId = substr(bin2hex(random_bytes(3)), 0, 6);
        }

        return self::$requestId;
    }

    /**
     * Milisegundos desde el arranque del request.
     */
    public static function elapsedMs(): float
    {
        if (self::$startedAt === null) {
            return 0.0;
        }

        return round((microtime(true) - self::$startedAt) * 1000, 1);
    }

    /**
     * Una linea de log con datos extra en formato clave=valor.
     */
    public static function event(string $scope, string $stage, array $data = []): void
    {
        self::write($scope, "info", $stage, $data);
    }

    /**
     * Una linea de log de ERROR (level=error).
     */
    public static function error(string $scope, string $stage, array $data = []): void
    {
        self::write($scope, "error", $stage, $data);
    }

    /**
     * Una linea de log de ERROR con el detalle de una excepcion.
     *
     * Agrega `exception` (clase), `message`, `where` (archivo:linea) y
     * `trace` (marcos apilados). El limite de sanitizacion se sube para que
     * la traza no se corte.
     */
    public static function exception(string $scope, string $stage, \Throwable $e, array $data = []): void
    {
        $data = array_merge($data, [
            "exception" => get_class($e),
            "message"   => $e->getMessage(),
            "where"     => basename($e->getFile()) . ":" . $e->getLine(),
            "trace"     => self::formatTrace($e),
        ]);

        self::write($scope, "error", $stage, $data, 1200);
    }

    /**
     * Linea de cierre: agrega el tiempo total y la memoria usada.
     */
    public static function finish(string $scope, string $outcome, array $data = []): void
    {
        $data["outcome"] = $outcome;
        $data["elapsed_ms"] = self::elapsedMs();
        $data["mem_mb"] = round(memory_get_usage(true) / 1048576, 2);

        self::event($scope, "done", $data);
    }

    /**
     * Vista previa corta y segura de un texto.
     *
     * El texto crudo de la notificacion nunca se registra completo: es
     * informacion financiera. Con esto solo queda un fragmento para poder
     * reconocer de que se trata.
     */
    public static function preview(?string $text, int $length = 60): string
    {
        if ($text === null) {
            return "";
        }

        $text = (string) preg_replace('/\s+/u', " ", trim($text));
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length, "UTF-8") . "...";
    }

    /**
     * Escribe la linea de log con el formato del docblock de la clase.
     *
     * @param string $scope Ambito del evento ("suggestions", "ingest"...).
     * @param string $level Nivel del evento ("info" | "error").
     * @param string $stage Nombre del evento.
     * @param array  $data  Datos extra en clave=valor.
     * @param int    $limit Longitud maxima de cada valor.
     */
    private static function write(string $scope, string $level, string $stage, array $data = [], int $limit = 200): void
    {
        self::$seq++;

        $parts = [
            sprintf("[%s]", date("Y-m-d H:i:s")),
            sprintf("[pid=%d]", getmypid()),
            sprintf("[req=%s]", self::requestId()),
            sprintf("[seq=%d]", self::$seq),
            sprintf("[%s]", $scope),
            sprintf("level=%s", $level),
            sprintf("stage=%s", $stage),
        ];

        foreach ($data as $key => $value) {
            $parts[] = sprintf("%s=%s", $key, self::sanitize($value, $limit));
        }

        error_log(implode(" ", $parts));
    }

    /**
     * Traza compacta de una excepcion: los primeros marcos con archivo,
     * linea y funcion ("A.php:12 fn() <- B.php:34 fn()").
     */
    private static function formatTrace(\Throwable $e, int $frames = 6): string
    {
        $parts = [];
        foreach (array_slice($e->getTrace(), 0, $frames) as $frame) {
            $file = isset($frame["file"]) ? basename((string) $frame["file"]) : "?";
            $line = isset($frame["line"]) ? (int) $frame["line"] : 0;
            $function = isset($frame["function"]) ? (string) $frame["function"] : "?";
            $parts[] = sprintf("%s:%d %s()", $file, $line, $function);
        }

        return implode(" <- ", $parts);
    }

    /**
     * Un valor limpio para el log: sin saltos de linea y acotado.
     */
    private static function sanitize($value, int $limit = 200): string
    {
        if ($value === null) {
            return "null";
        }
        if (is_bool($value)) {
            return $value ? "true" : "false";
        }
        if (is_float($value)) {
            $value = round($value, 2);
        }
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $value = (string) $value;
        $value = (string) preg_replace('/\s+/u', " ", $value);
        $value = trim($value);

        if (mb_strlen($value) > $limit) {
            $value = mb_substr($value, 0, $limit, "UTF-8");
        }

        return $value;
    }
}
