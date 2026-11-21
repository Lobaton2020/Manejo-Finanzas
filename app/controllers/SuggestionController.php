<?php
require_once URL_APP . 'services/OutflowService.php';

/**
 * UI del modulo "Suggested": lista de sugerencias pendientes y su resolucion.
 *
 * La sugerencia NUNCA crea el egreso por su cuenta: el usuario aprueba
 * (un clic) o edita (abre el formulario de egreso prellenable). Al aprobar se
 * usa `OutflowServie::perform_egress()`, la misma logica que usa el modulo
 * de Presupuesto, para no duplicar las reglas (saldo, notificacion, inversion).
 *
 * Ver odd/tasks/suggested-outflows-ai.md
 */
class SuggestionController extends Controller
{
    private $model;
    private $outflowModel;
    private $outflowType;
    private $category;
    private $porcent;
    private $notification;
    private $investment;
    private $outflowService;

    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->model = $this->model("notificationSuggestion");
        $this->outflowModel = $this->model("outflow");
        $this->outflowType = $this->model("outflowType");
        $this->category = $this->model("category");
        $this->porcent = $this->model("porcent");
        $this->notification = $this->model("notification");
        $this->investment = $this->model("investment");
        $this->outflowService = new OutflowServie(
            $this->outflowModel,
            $this->notification,
            $this->outflowType,
            $this->investment
        );
    }

    /**
     * Lista de sugerencias pendientes del usuario.
     */
    public function index()
    {
        $suggestions = $this->model->pending($this->id);

        foreach ($suggestions as $suggestion) {
            $suggestion->outflow_type_name = $this->nameOf($this->outflowType, "id_outflow_type", $suggestion->id_outflow_type);
            $suggestion->category_name = $this->nameOf($this->category, "id_category", $suggestion->id_category);
            $suggestion->deposit_name = $this->nameOf($this->porcent, "id_porcent", $suggestion->id_porcent);
            $suggestion->confidence_label = $suggestion->confidence === null
                ? "—"
                : number_format((float) $suggestion->confidence * 100, 0) . "%";
            $suggestion->amount_label = $suggestion->amount === null
                ? "—"
                : number_price((float) $suggestion->amount);
            $suggestion->can_approve = !empty($suggestion->id_outflow_type)
                && !empty($suggestion->id_category)
                && !empty($suggestion->id_porcent)
                && !empty($suggestion->amount);
        }

        return view("outflows.suggested", ["suggestions" => $suggestions]);
    }

    /**
     * Aprueba la sugerencia en un clic: crea el egreso real.
     */
    public function approve($id)
    {
        execute_post(function ($request) use ($id) {
            $suggestion = $this->model->findOwned($this->id, (int) $id);
            if (empty($suggestion)) {
                return redirect("suggestion")->with("error", "La sugerencia no existe.");
            }
            if ((int) $suggestion->status !== NotificationSuggestion::STATUS_PENDING) {
                return redirect("suggestion")->with("error", "Esa sugerencia ya fue revisada.");
            }
            if (empty($suggestion->id_outflow_type) || empty($suggestion->id_category) || empty($suggestion->id_porcent)) {
                return redirect("outflow/create?suggestion={$id}")
                    ->with("error", "Falta completar tipo, categoria o deposito. Edita la sugerencia antes de aprobarla.");
            }

            $payload = (object) [
                "id_outflow_type" => (int) $suggestion->id_outflow_type,
                "id_category"     => (int) $suggestion->id_category,
                "id_porcent"      => (int) $suggestion->id_porcent,
                "amount"          => (float) $suggestion->amount,
                "description"     => $suggestion->description,
                "set_date"        => $suggestion->event_date ?: date("Y-m-d"),
                "is_in_budget"    => 1,
            ];

            try {
                $this->outflowService->perform_egress($this->id, $payload);
                $this->model->markApproved((int) $id, (int) $this->outflowModel->id());
                return redirect("suggestion")->with("success", "Sugerencia aprobada: egreso creado correctamente.");
            } catch (Exception $e) {
                return redirect("suggestion")->with("error", $e->getMessage());
            }
        });
    }

    /**
     * Descarta la sugerencia.
     */
    public function discard($id)
    {
        execute_post(function ($request) use ($id) {
            $suggestion = $this->model->findOwned($this->id, (int) $id);
            if (empty($suggestion)) {
                return redirect("suggestion")->with("error", "La sugerencia no existe.");
            }
            $this->model->markDiscarded((int) $id);
            return redirect("suggestion")->with("success", "Sugerencia descartada.");
        });
    }

    /**
     * Vuelve a pasar el texto por la IA (util cuando el LLM estuvo caido).
     */
    public function reprocess($id)
    {
        execute_post(function ($request) use ($id) {
            $suggestion = $this->model->findOwned($this->id, (int) $id);
            if (empty($suggestion)) {
                return redirect("suggestion")->with("error", "La sugerencia no existe.");
            }

            try {
                require_once URL_APP . "services/AiClient.php";
                require_once URL_APP . "services/AiExtractor.php";

                $extractor = new AiExtractor(
                    new AiClient(),
                    $this->outflowType,
                    $this->category,
                    $this->porcent
                );
                $extraction = $extractor->extract($this->id, (string) $suggestion->raw_text);
                $extraction["ai_model"] = defined("MINIMAX_MODEL") ? MINIMAX_MODEL : null;
                $this->model->applyExtraction((int) $id, $extraction);

                if (!empty($extraction["error"])) {
                    return redirect("suggestion")->with("error", "La IA no respondio: " . $extraction["error"]);
                }
                return redirect("suggestion")->with("success", "Sugerencia reprocesada por la IA.");
            } catch (Exception $e) {
                return redirect("suggestion")->with("error", $e->getMessage());
            }
        });
    }

    /**
     * Nombre de un catalogo (tipo de egreso, categoria o deposito).
     */
    private function nameOf($model, string $idField, $id): string
    {
        if (empty($id)) {
            return "—";
        }
        $row = $model->get("*", [$idField . "[=]" => (int) $id, "id_user[=]" => $this->id, "AND"])->array();
        return empty($row) ? "—" : (string) $row->name;
    }
}
