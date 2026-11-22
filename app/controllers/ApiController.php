<?php

require_once URL_APP . "services/ProcessLog.php";

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
        require_once URL_APP . "services/ProcessLog.php";

        ProcessLog::start("suggestions");

        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            $this->logRespond(405, "error", ["reason" => "method_not_allowed"]);
            ProcessLog::finish("suggestions", "error", ["reason" => "method_not_allowed"]);
            return $this->respond(405, [
                "error"   => "method_not_allowed",
                "allowed" => "POST",
            ]);
        }

        if ($subroute !== "inbox") {
            $this->logRespond(404, "error", ["reason" => "subroute_not_found", "subroute" => $subroute]);
            ProcessLog::finish("suggestions", "error", ["reason" => "subroute_not_found"]);
            return $this->respond(404, [
                "error"    => "subroute_not_found",
                "subroute" => $subroute,
                "hint"     => "Try POST /api/suggestions/inbox",
            ]);
        }

        $rawBody = file_get_contents("php://input");
        $rawBody = $rawBody === false ? "" : $rawBody;

        // Decodificacion del body: se registra de donde salio el payload
        // (json / form / unparsed / empty) y el error de `json_decode`.
        // El texto crudo de la notificacion NUNCA se registra.
        $payload = [];
        $payloadSource = "empty";
        $jsonError = null;
        $hasBody = trim($rawBody) !== "";

        if ($hasBody) {
            $decoded = json_decode($rawBody, true);
            $decodeError = json_last_error();
            $jsonError = $decodeError === JSON_ERROR_NONE ? null : json_last_error_msg();
            if ($decodeError === JSON_ERROR_NONE && is_array($decoded)) {
                $payload = $decoded;
                $payloadSource = "json";
            }
        }

        if ($payloadSource !== "json" && !empty($_POST)) {
            $payload = $_POST;
            $payloadSource = "form";
        } elseif ($payloadSource !== "json") {
            $payloadSource = $hasBody ? "unparsed" : "empty";
        }

        ProcessLog::event("suggestions", "payload", [
            "source"       => $payloadSource,
            "json_error"   => $jsonError,
            "bytes"        => strlen($rawBody),
            "content_type" => $_SERVER["CONTENT_TYPE"] ?? "",
            "keys"         => implode(",", array_keys($payload)),
        ]);

        $idUser = $this->resolveUserId($payload);

        ProcessLog::event("suggestions", "request", [
            "method"     => $_SERVER["REQUEST_METHOD"] ?? "",
            "path"       => $_SERVER["REQUEST_URI"] ?? "",
            "bytes"      => strlen($rawBody),
            "ip"         => $_SERVER["REMOTE_ADDR"] ?? "",
            "user_agent" => $_SERVER["HTTP_USER_AGENT"] ?? "",
            "user_id"    => $idUser,
        ]);

        try {
            require_once URL_APP . "models/NotificationSuggestion.php";
            require_once URL_APP . "models/OutflowType.php";
            require_once URL_APP . "models/Category.php";
            require_once URL_APP . "models/Porcent.php";
            require_once URL_APP . "services/AiClient.php";
            require_once URL_APP . "services/AiExtractor.php";
            require_once URL_APP . "services/NotificationPayload.php";
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

            ProcessLog::event("suggestions", "ingest_call", ["user_id" => $idUser]);
            $result = $ingest->ingest($payload, $rawBody, $idUser);
        } catch (Throwable $e) {
            ProcessLog::exception("suggestions", "exception", $e, ["stage_hint" => "ingest"]);
            error_log("[api/suggestions] error: " . $e->getMessage());
            $this->logRespond(500, "error", ["reason" => "ingest_failed"]);
            ProcessLog::finish("suggestions", "error", ["reason" => "ingest_failed"]);
            return $this->respond(500, ["error" => "ingest_failed"]);
        }

        $extraction = $result["extraction"] ?? [];
        $data = $extraction["data"] ?? [];

        if (!empty($result["skipped"])) {
            $outcome = "skipped";
        } elseif (!$result["ok"]) {
            $outcome = "error";
        } elseif ($result["duplicate"]) {
            $outcome = "duplicate";
        } else {
            $outcome = "created";
        }

        ProcessLog::event("suggestions", "ingest_result", [
            "outcome"       => $outcome,
            "reason"        => $result["reason"] ?? null,
            "id_suggestion" => $result["id_suggestion"] ?? null,
            "source"        => $extraction["source"] ?? null,
            "confidence"    => $data["confidence"] ?? null,
        ]);

        // Pago rechazado o payload vacio: no es un egreso. Se responde 200
        // para que el celular NO reintente.
        if (!empty($result["skipped"])) {
            $this->logRespond(200, "skipped", ["reason" => $result["reason"] ?? null]);
            ProcessLog::finish("suggestions", "skipped", [
                "reason"        => $result["reason"] ?? null,
                "id_suggestion" => $result["id_suggestion"] ?? null,
            ]);
            return $this->respond(200, [
                "status" => "skipped",
                "reason" => $result["reason"] ?? null,
            ]);
        }

        if (!$result["ok"]) {
            $this->logRespond(400, "error", ["reason" => $result["error"] ?? null]);
            ProcessLog::finish("suggestions", "error", ["reason" => $result["error"] ?? null]);
            return $this->respond(400, ["error" => $result["error"]]);
        }

        if ($result["duplicate"]) {
            $this->logRespond(200, "duplicate", ["id_suggestion" => $result["id_suggestion"]]);
            ProcessLog::finish("suggestions", "duplicate", [
                "reason"        => $result["reason"] ?? null,
                "id_suggestion" => $result["id_suggestion"],
            ]);
            return $this->respond(200, [
                "status"        => "duplicate",
                "id_suggestion" => $result["id_suggestion"],
            ]);
        }

        $this->logRespond(200, "created", [
            "id_suggestion" => $result["id_suggestion"],
            "source"        => $extraction["source"] ?? null,
        ]);
        ProcessLog::finish("suggestions", "created", [
            "id_suggestion" => $result["id_suggestion"],
            "source"        => $extraction["source"] ?? null,
            "confidence"    => $data["confidence"] ?? null,
        ]);

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
     * Logs the response that is about to be sent.
     *
     * @param int    $status  HTTP status code.
     * @param string $outcome Final outcome of the request.
     * @param array  $data    Extra key/value pairs.
     */
    private function logRespond(int $status, string $outcome, array $data = []): void
    {
        $data["http"] = $status;
        $data["outcome"] = $outcome;

        ProcessLog::event("suggestions", "respond", $data);
    }

    /**
     * Usuario destino de la sugerencia. Sin auth (red privada): se puede
     * forzar por payload y si no, usa SUGGESTION_USER_ID.
     */
    private function resolveUserId(array $payload): int
    {
        if (!empty($payload["user_id"]) && is_numeric($payload["user_id"])) {
            $idUser = intval($payload["user_id"]);
            ProcessLog::event("suggestions", "user", [
                "origin"  => "payload_user_id",
                "user_id" => $idUser,
            ]);
            return $idUser;
        }

        if (!empty($payload["user_email"]) && is_string($payload["user_email"])) {
            require_once URL_APP . "models/User.php";
            $user = $this->model("user")->get("*", ["email[=]" => trim($payload["user_email"]), "AND"])->array();
            if (!empty($user)) {
                $idUser = (int) $user->id_user;
                // El email NUNCA se registra: solo el origen y si se encontro.
                ProcessLog::event("suggestions", "user", [
                    "origin"  => "payload_email",
                    "found"   => true,
                    "user_id" => $idUser,
                ]);
                return $idUser;
            }

            ProcessLog::event("suggestions", "user", [
                "origin" => "payload_email",
                "found"  => false,
            ]);
        }

        $idUser = defined("SUGGESTION_USER_ID") ? SUGGESTION_USER_ID : 1;
        ProcessLog::event("suggestions", "user", [
            "origin"  => "default",
            "user_id" => intval($idUser),
        ]);

        return $idUser;
    }

    private function respond(int $status, array $body)
    {
        http_response_code($status);
        header("Content-Type: application/json; charset=utf-8");

        $json = json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            ProcessLog::event("api", "json_encode_failed", ["detail" => json_last_error_msg()]);
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