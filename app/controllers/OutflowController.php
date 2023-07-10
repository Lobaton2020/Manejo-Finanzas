<?php
require_once URL_APP . 'services/OutflowService.php';
class OutflowController extends Controller
{
    private $path = "outflow";
    private $model;
    private $outflow_type;
    private $porcent;
    private $notification;
    private $category;
    private $outflowService;
    private $investment;
    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->model = $this->model("outflow");
        $this->notification = $this->model("notification");
        $this->porcent = $this->model("porcent");
        $this->outflow_type = $this->model("outflowType");
        $this->category = $this->model("category");
        $this->investment = $this->model("investment");
        $this->outflowService = new OutflowServie(
            $this->model,
            $this->notification,
            $this->outflow_type,
            $this->investment
        );
    }


    public function index()
    {
        $outflows = $this->model->select("*", ["id_user[=]" => $this->id], "id_outflow DESC")->array();
        foreach ($outflows as $outflow) {
            $outflow->outfow_type = $this->outflow_type->get("*", ["id_outflow_type[=]" => $outflow->id_outflow_type, "id_user[=]" => $this->id, "AND"])->array()->name;
            $outflow->porcent = $this->porcent->get("*", ["id_porcent[=]" => $outflow->id_porcent, "id_user[=]" => $this->id, "AND"])->array()->name;
            $outflow->category = $this->category->get("*", ["id_category[=]" => $outflow->id_category, "id_user[=]" => $this->id, "AND"])->array()->name;
            $outflow->description = empty($outflow->description) ? "No aplica" : $outflow->description;
            $outflow->is_in_budget = boolval($outflow->is_in_budget) ? "Si" : "No";
        }
        return view("outflows.list", ["outflows" => $outflows]);
    }
    public function create()
    {
        $max_porcents = $this->outflowService->get_amounts_disponible();
        $porcents = $this->porcent->select("*", ["id_user[=]" => $this->id, "status[=]" => 1, "AND"])->array();
        for ($i = 0; $i < count($porcents); $i++) {
            $porcents[$i]->name = "{$porcents[$i]->name}    &nbsp;&nbsp;   Disponible: " . number_price($max_porcents[$i]->total);
        }
        $is_budget = isset($_GET["is_budget"]) && $_GET["is_budget"] == "true";
        $id = isset($_GET["id_temporal_budget"]) ? $_GET["id_temporal_budget"] : "";
        $data = [
            "porcents" => $porcents,
            "outflow_types" => $this->outflow_type->select("*", ["id_user[=]" => $this->id, "status[=]" => 1, "AND"])->array(),
            "controller_uri" => $is_budget ? "budget/store_element/" . $id : "outflow/store",
            "is_budget" => $is_budget
        ];
        return view("outflows.create", $data);
    }
    public function store()
    {
        execute_post(function ($request) {
            if (arrayEmpty(["id_outflow_type", "id_category", "id_porcent", "amount", "set_date", "is_in_budget"], $request)) {
                return redirect("outflow/create")->with("error", "Debes llenar todos los campos requeridos.");
            }
            try {
                $this->outflowService->perform_egress($this->id, $request);
                return redirect("outflow")->with("success", "Egreso agregado correctamente.");
            } catch (Exception $e) {
                return redirect("outflow/create")->with("error", $e->getMessage());
            }
        });
    }

    public function delete($id)
    {
        $cond = ["id_user[=]" => $this->id, "id_outflow[=]" => $id, "AND"];
        if ($this->model->has($cond)->array()) {
            if ($this->model->delete($cond)->array()) {
                return redirect($this->path)->with("success", "El egreso se ha eliminado con exito");
            } else {
                return redirect($this->path)->with("error", "No se pudo eliminar");
            }
        } else {
            return redirect($this->path)->with("error", "El estado no pertenece a los tuyos.");
        }
    }
}
