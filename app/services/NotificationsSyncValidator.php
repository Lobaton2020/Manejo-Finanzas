<?php

/**
 * Validador para filas del batch de /api/notifications/transactions.
 *
 * Pura, sin I/O. Testable aisladamente sin DB ni HTTP.
 *
 * Reglas segun api-spec.md del cliente Android (app-reader-notifications).
 */
class NotificationsSyncValidator
{
    /** Buckets fijos del cliente Android. El backend NO inventa nuevos. */
    public const ALLOWED_CATEGORIES = [
        "Gasolina/Transporte",
        "Supermercado",
        "Delivery/Transporte",
        "Salud",
        "Suscripciones",
        "Entretenimiento",
        "Transferencia",
    ];

    private const UUID_REGEX = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
    private const AMOUNT_REGEX = '/^\d+(\.\d{1,2})?$/';

    /**
     * Valida una fila del batch.
     * Devuelve lista de errores (vacio = valida).
     *
     * @param mixed $tx
     * @return array<int, array{field: string|null, reason: string}>
     */
    public function validate($tx): array
    {
        $errors = [];
        if (!is_array($tx)) {
            return [["field" => null, "reason" => "must be a JSON object"]];
        }

        if (!isset($tx["id"]) || !is_string($tx["id"]) ||
            !preg_match(self::UUID_REGEX, $tx["id"])) {
            $errors[] = ["field" => "id", "reason" => "must be a UUID string"];
        }

        if (!isset($tx["amount"]) || !is_string($tx["amount"]) ||
            !preg_match(self::AMOUNT_REGEX, $tx["amount"]) ||
            (float) $tx["amount"] <= 0) {
            $errors[] = ["field" => "amount", "reason" => "must be a numeric string with up to 2 decimals and value > 0"];
        }

        if (!isset($tx["currency"]) || !is_string($tx["currency"]) || strlen($tx["currency"]) !== 3) {
            $errors[] = ["field" => "currency", "reason" => "must be a 3-char ISO 4217 code"];
        }

        if (!isset($tx["merchant"]) || !is_string($tx["merchant"]) || trim($tx["merchant"]) === "") {
            $errors[] = ["field" => "merchant", "reason" => "required non-empty string"];
        }

        if (!isset($tx["category"]) || !is_string($tx["category"]) ||
            !in_array($tx["category"], self::ALLOWED_CATEGORIES, true)) {
            $errors[] = [
                "field"  => "category",
                "reason" => "must be one of: " . implode(", ", self::ALLOWED_CATEGORIES),
            ];
        }

        if (!isset($tx["bank"]) || !is_string($tx["bank"]) || trim($tx["bank"]) === "") {
            $errors[] = ["field" => "bank", "reason" => "required non-empty string"];
        }

        if (array_key_exists("card_brand", $tx) && $tx["card_brand"] !== null) {
            if (!is_string($tx["card_brand"]) || trim($tx["card_brand"]) === "") {
                $errors[] = ["field" => "card_brand", "reason" => "when present must be a non-empty string"];
            }
        }

        if (array_key_exists("card_last_four", $tx) && $tx["card_last_four"] !== null) {
            if (!is_string($tx["card_last_four"]) || trim($tx["card_last_four"]) === "") {
                $errors[] = ["field" => "card_last_four", "reason" => "when present must be a non-empty string"];
            }
        }

        if (!isset($tx["payment_method"]) || !is_string($tx["payment_method"]) ||
            trim($tx["payment_method"]) === "") {
            $errors[] = ["field" => "payment_method", "reason" => "required non-empty string"];
        }

        if (!isset($tx["datetime"]) || !is_int($tx["datetime"]) || $tx["datetime"] <= 0) {
            $errors[] = ["field" => "datetime", "reason" => "must be a positive integer (epoch ms)"];
        }

        if (!isset($tx["captured_at"]) || !is_int($tx["captured_at"]) || $tx["captured_at"] <= 0) {
            $errors[] = ["field" => "captured_at", "reason" => "must be a positive integer (epoch ms)"];
        }

        if (!isset($tx["sources"]) || !is_array($tx["sources"]) || empty($tx["sources"])) {
            $errors[] = ["field" => "sources", "reason" => "required non-empty array of strings"];
        } else {
            $bad = false;
            foreach ($tx["sources"] as $s) {
                if (!is_string($s) || trim($s) === "") {
                    $bad = true;
                    break;
                }
            }
            if ($bad) {
                $errors[] = ["field" => "sources", "reason" => "must be array of non-empty strings"];
            }
        }

        if (!isset($tx["raw_text"]) || !is_array($tx["raw_text"])) {
            $errors[] = ["field" => "raw_text", "reason" => "required JSON object (may be empty)"];
        }

        return $errors;
    }

    /**
     * Limpia campos opcionales que el cliente envio vacios/null (el spec dice
     * "omit when unknown", nunca null/empty string).
     */
    public function normalize(array $tx): array
    {
        if (array_key_exists("card_brand", $tx) && ($tx["card_brand"] === null || $tx["card_brand"] === "")) {
            unset($tx["card_brand"]);
        }
        if (array_key_exists("card_last_four", $tx) && ($tx["card_last_four"] === null || $tx["card_last_four"] === "")) {
            unset($tx["card_last_four"]);
        }
        return $tx;
    }
}