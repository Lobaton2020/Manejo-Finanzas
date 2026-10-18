<?php

/**
 * Orquestador de la API.
 *
 * Convencion de routing (UNICO path publico):
 *   POST /api/notifications/transactions
 *   Mapea a:  ?url=api/notifications/transactions
 *             -> ApiController::notifications('transactions')
 *             -> NotificationsController::sync()
 *
 * Cualquier intento de acceder directo via ?url=notifications/... BYPASEA
 * este orquestador (sin check de POST, sin prefijo consistente). Documentar
 * solo /api/... como punto de entrada publico.
 *
 * Este controller es DELIBERADAMENTE delgado:
 *   - Verifica HTTP method (POST only)
 *   - Despacha al controller del recurso (un Controller real, no un handler)
 *   - NO tiene logica de dominio
 *
 * Sin autenticacion: este controller NO llama a $this->authentication().
 * Pensado para uso personal en red privada. Ver api-spec.md.
 */
class ApiController extends Controller
{
    private NotificationsTransaction $notificationsModel;

    public function __construct()
    {
        parent::__construct();
        $this->notificationsModel = $this->model("notificationsTransaction");
    }

    /**
     * GET|POST /api/notifications/<subrecurso>
     *
     * Despacha al controller real del recurso.
     */
    public function notifications($subroute = null)
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            return $this->respond(405, [
                "error"   => "method_not_allowed",
                "allowed" => "POST",
            ]);
        }

        switch ($subroute) {
            case "transactions":
                require_once URL_APP . "controllers/NotificationsController.php";
                $controller = new NotificationsController($this->notificationsModel);
                $controller->sync();
                return null;

            default:
                return $this->respond(404, [
                    "error"    => "subroute_not_found",
                    "subroute" => $subroute,
                    "hint"     => "Try POST /api/notifications/transactions",
                ]);
        }
    }

    private function respond(int $status, array $body)
    {
        http_response_code($status);
        header("Content-Type: application/json; charset=utf-8");

        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            error_log("[api] json_encode failed: " . json_last_error_msg());
            http_response_code(500);
            $json = json_encode([
                "error"  => "json_encoding_failed",
                "detail" => json_last_error_msg(),
            ], JSON_UNESCAPED_UNICODE);
        }
        echo $json;
        return null;
    }
}