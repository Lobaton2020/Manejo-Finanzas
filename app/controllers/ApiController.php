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

    /**
     * GET|POST /api/suggestions/<subrecurso>
     *
     * Punto de entrada del webhook de MacroDroid: recibe el texto crudo de la
     * notificacion, lo estructura con IA y deja la sugerencia lista para
     * revision en la UI de Egresos.
     */
    public function suggestions($subroute = null)
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            return $this->respond(405, [
                "error"   => "method_not_allowed",
                "allowed" => "POST",
            ]);
        }

        if ($subroute !== "inbox") {
            return $this->respond(404, [
                "error"    => "subroute_not_found",
                "subroute" => $subroute,
                "hint"     => "Try POST /api/suggestions/inbox",
            ]);
        }

        $rawBody = file_get_contents("php://input");
        $rawBody = $rawBody === false ? "" : $rawBody;

        $payload = [];
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            $payload = $decoded;
        } elseif (!empty($_POST)) {
            $payload = $_POST;
        }

        $idUser = $this->resolveUserId($payload);

        try {
            require_once URL_APP . "models/NotificationSuggestion.php";
            require_once URL_APP . "models/OutflowType.php";
            require_once URL_APP . "models/Category.php";
            require_once URL_APP . "models/Porcent.php";
            require_once URL_APP . "services/AiClient.php";
            require_once URL_APP . "services/AiExtractor.php";
            require_once URL_APP . "services/SuggestionIngest.php";

            $ingest = new SuggestionIngest(
                $this->model("notificationSuggestion"),
                new AiExtractor(
                    new AiClient(),
                    $this->model("outflowType"),
                    $this->model("category"),
                    $this->model("porcent")
                )
            );

            $result = $ingest->ingest($payload, $rawBody, $idUser);
        } catch (Throwable $e) {
            error_log("[api/suggestions] error: " . $e->getMessage());
            return $this->respond(500, ["error" => "ingest_failed"]);
        }

        if (!$result["ok"]) {
            return $this->respond(400, ["error" => $result["error"]]);
        }

        if ($result["duplicate"]) {
            return $this->respond(200, [
                "status"        => "duplicate",
                "id_suggestion" => $result["id_suggestion"],
            ]);
        }

        $extraction = $result["extraction"];
        $data = $extraction["data"] ?? [];

        return $this->respond(200, [
            "status"        => "created",
            "id_suggestion" => $result["id_suggestion"],
            "source"        => $extraction["source"] ?? "unknown",
            "needs_review"  => $extraction["needs_review"] ?? true,
            "error"         => $extraction["error"] ?? null,
            "amount"        => $data["amount"] ?? null,
            "currency"      => $data["currency"] ?? null,
            "description"   => $data["description"] ?? null,
            "event_date"    => $data["event_date"] ?? null,
            "outflow_type_name" => $data["outflow_type_name"] ?? null,
            "category_name" => $data["category_name"] ?? null,
            "deposit_name"  => $data["deposit_name"] ?? null,
            "confidence"    => $data["confidence"] ?? null,
            "ids"           => $extraction["ids"] ?? [],
        ]);
    }

    /**
     * Usuario destino de la sugerencia. Sin auth (red privada): se puede
     * forzar por payload y si no, usa SUGGESTION_USER_ID.
     */
    private function resolveUserId(array $payload): int
    {
        if (!empty($payload["user_id"]) && is_numeric($payload["user_id"])) {
            return intval($payload["user_id"]);
        }

        if (!empty($payload["user_email"]) && is_string($payload["user_email"])) {
            require_once URL_APP . "models/User.php";
            $user = $this->model("user")->get("*", ["email[=]" => trim($payload["user_email"]), "AND"])->array();
            if (!empty($user)) {
                return (int) $user->id_user;
            }
        }

        return defined("SUGGESTION_USER_ID") ? SUGGESTION_USER_ID : 1;
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