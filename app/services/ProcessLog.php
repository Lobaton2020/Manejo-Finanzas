<?php

/**
 * Log de proceso para requests que atienden fuentes externas (webhook).
 *
 * Escribe una linea por evento con error_log (=> stderr => `docker logs`
 * y `kubectl logs`). El formato es clave=valor para poder grepear:
 *
 *   [2026-11-22 05:40:12.345] [pid=17] [req=a1b2c3] [suggestions] stage=request method=POST
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

    /**
     * Marca el arranque del request y genera su id.
     */
    public static function start(string $scope): void
    {
        if (self::$startedAt === null) {
            self::$startedAt = microtime(true);
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
        $parts = [
            sprintf("[%s]", date("Y-m-d H:i:s")),
            sprintf("[pid=%d]", getmypid()),
            sprintf("[req=%s]", self::requestId()),
            sprintf("[%s]", $scope),
            sprintf("stage=%s", $stage),
        ];

        foreach ($data as $key => $value) {
            $parts[] = sprintf("%s=%s", $key, self::sanitize($value));
        }

        error_log(implode(" ", $parts));
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
     * Un valor limpio para el log: sin saltos de linea y acotado.
     */
    private static function sanitize($value): string
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

        if (mb_strlen($value) > 200) {
            $value = mb_substr($value, 0, 200, "UTF-8");
        }

        return $value;
    }
}
