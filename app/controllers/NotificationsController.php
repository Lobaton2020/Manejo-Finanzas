<?php
require_once URL_APP . 'services/NotificationsSyncValidator.php';

/**
 * Controller del proceso de sync de transacciones capturadas desde notificaciones.
 *
 * Es invocado por ApiController (el orquestador), NUNCA directamente
 * via ?url=notifications/... — la ruta publica es /api/notifications/transactions.
 *
 * Responsabilidad: parsear el body, validar fail-fast, ingestar via el modelo,
 * y responder JSON. NO chequea HTTP method ni path — eso es del orquestador.
 *
 * Sin autenticacion: el constructor NO llama a $this->authentication().
 *
 * Contrato completo: odd/tasks/notifications-sync-endpoint.md y
 * api-spec.md del cliente Android (app-reader-notifications).
 */
class NotificationsController extends Controller
{

    private NotificationsTransaction $model;
    private NotificationsSyncValidator $validator;

    /**
     * @param NotificationsTransaction|null $model  Inyectado por el orquestador (camino normal).
     *                                              Si es null, se carga via Controller::model().
     */
    public function __construct(?NotificationsTransaction $model = null)
    {
        parent::__construct();
        $this->model = $model ?? $this->model("notificationsTransaction");
        $this->validator = new NotificationsSyncValidator();
    }

    /**
     * Procesa el batch JSON: valida fail-fast e ingiere idempotentemente.
     */
    public function sync(): void
    {
        $raw = file_get_contents("php://input");
        if ($raw === false || $raw === "") {
            $this->respond(400, ["error" => "empty_body"]);
            return;
        }

        $body = json_decode($raw, true);
        if (!is_array($body)) {
            $this->respond(400, [
                "error"  => "invalid_json",
                "detail" => json_last_error_msg(),
            ]);
            return;
        }

        $errors = [];

        $clientId     = $body["client_id"]     ?? null;
        $batchId      = $body["batch_id"]      ?? null;
        $transactions = $body["transactions"] ?? null;

        if (!is_string($clientId) || trim($clientId) === "") {
            $errors[] = ["field" => "client_id", "reason" => "required non-empty string"];
        }
        if (!is_string($batchId) || trim($batchId) === "") {
            $errors[] = ["field" => "batch_id", "reason" => "required non-empty string"];
        }
        if (!is_array($transactions)) {
            $errors[] = ["field" => "transactions", "reason" => "required array"];
            $this->respond(400, ["error" => "invalid_batch", "errors" => $errors]);
            return;
        }

        $cleanRows = [];
        foreach ($transactions as $i => $tx) {
            $rowErrors = $this->validator->validate($tx);
            if (!empty($rowErrors)) {
                foreach ($rowErrors as $err) {
                    $err["index"] = $i;
                    if (is_array($tx) && isset($tx["id"]) && is_string($tx["id"])) {
                        $err["id"] = $tx["id"];
                    }
                    $errors[] = $err;
                }
                continue;
            }
            $cleanRows[] = $this->validator->normalize($tx);
        }

        if (!empty($errors)) {
            error_log("[notifications/sync] batch rejected, " . count($errors) . " error(s)");
            $this->respond(400, ["error" => "invalid_batch", "errors" => $errors]);
            return;
        }

        try {
            $result = $this->model->ingestBatch((string) $batchId, (string) $clientId, $cleanRows);
        } catch (Throwable $e) {
            error_log("[notifications/sync] batch={$batchId} client={$clientId} insert error: " . $e->getMessage());
            $this->respond(500, ["error" => "ingest_failed"]);
            return;
        }

        error_log(sprintf(
            "[notifications/sync] batch=%s client=%s accepted=%d duplicates=%d",
            $batchId,
            $clientId,
            count($result["accepted"]),
            count($result["duplicates"])
        ));

        $this->respond(200, [
            "accepted"   => $result["accepted"],
            "duplicates" => $result["duplicates"],
            "rejected"   => [],
        ]);
    }

    /**
     * Valida una fila del batch. Devuelve lista de errores (vacio = valida).
     *
     * @param mixed $tx
     * @return array<int, array{field: string|null, reason: string}>
     */
    private function respond(int $status, array $body): void
    {
        http_response_code($status);
        header("Content-Type: application/json; charset=utf-8");

        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            // Fallback defensivo: merchant o raw_text con bytes raros (UTF-8 invalido, etc).
            // Logueamos y devolvemos 500 con detalle.
            error_log("[notifications/sync] json_encode failed: " . json_last_error_msg());
            http_response_code(500);
            $json = json_encode([
                "error"  => "json_encoding_failed",
                "detail" => json_last_error_msg(),
            ], JSON_UNESCAPED_UNICODE);
        }
        echo $json;
    }
}