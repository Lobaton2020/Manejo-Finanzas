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
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $length = isset($_GET['length']) ? (int)$_GET['length'] : 50;

        $length = in_array($length, [10, 25, 50, 100]) ? $length : 50;
        $offset = ($page - 1) * $length;

        $filters = $this->buildFilters($_GET);

        $conditions = ["id_user[=]" => $this->id];
        
        if (!empty($filters['conditions'])) {
            $conditions = array_merge($conditions, $filters['conditions']);
        }

        $total = intval($this->model->count($conditions)->array());

        $outflows = $this->model->select("*", $conditions, "id_outflow DESC", $length, $offset)->array();

        $totalPages = $total > 0 ? ceil($total / $length) : 1;

        foreach ($outflows as $outflow) {
            $outflow->outfow_type = $this->outflow_type->get("*", ["id_outflow_type[=]" => $outflow->id_outflow_type, "id_user[=]" => $this->id, "AND"])->array()->name;
            $outflow->porcent = $this->porcent->get("*", ["id_porcent[=]" => $outflow->id_porcent, "id_user[=]" => $this->id, "AND"])->array()->name;
            $outflow->category = $this->category->get("*", ["id_category[=]" => $outflow->id_category, "id_user[=]" => $this->id, "AND"])->array()->name;
            $outflow->description = empty($outflow->description) ? "No aplica" : $outflow->description;
            $outflow->is_in_budget = boolval($outflow->is_in_budget) ? "Si" : "No";
        }

        $totalAmount = null;
        if (!empty($filters['activeFilters'])) {
            $totalAmount = $this->model->sum("amount", $conditions)->array();
            $totalAmount = $totalAmount ?? 0;
        }

        $outflowTypes = $this->outflow_type->select("id_outflow_type, name", ["id_user[=]" => $this->id, "status[=]" => 1, "AND"])->array();
        $categories = $this->category->select("id_category, name", ["id_user[=]" => $this->id, "status[=]" => 1, "AND"])->array();
        $porcents = $this->porcent->select("id_porcent, name", ["id_user[=]" => $this->id, "status[=]" => 1, "AND"])->array();

        return view("outflows.list", [
            "outflows" => $outflows,
            "pagination" => [
                "current" => $page,
                "total" => $totalPages,
                "perPage" => $length,
                "totalRecords" => $total
            ],
            "filters" => $filters['activeFilters'],
            "totalAmount" => $totalAmount,
            "outflowTypes" => $outflowTypes,
            "categories" => $categories,
            "porcents" => $porcents
        ]);
    }

    private function buildFilters(array $get): array
    {
        $conditions = [];
        $activeFilters = [];

        if (isset($get['id_outflow']) && $get['id_outflow'] !== '') {
            $conditions['id_outflow[=]'] = (int)$get['id_outflow'];
            $activeFilters['id_outflow'] = $get['id_outflow'];
        }

        if (isset($get['id_outflow_type']) && is_array($get['id_outflow_type']) && !empty($get['id_outflow_type'])) {
            $conditions['id_outflow_type[[]]'] = array_map('intval', $get['id_outflow_type']);
            $activeFilters['id_outflow_type'] = $get['id_outflow_type'];
        }

        if (isset($get['id_category']) && is_array($get['id_category']) && !empty($get['id_category'])) {
            $conditions['id_category[[]]'] = array_map('intval', $get['id_category']);
            $activeFilters['id_category'] = $get['id_category'];
        }

        if (isset($get['id_porcent']) && is_array($get['id_porcent']) && !empty($get['id_porcent'])) {
            $conditions['id_porcent[[]]'] = array_map('intval', $get['id_porcent']);
            $activeFilters['id_porcent'] = $get['id_porcent'];
        }

        if (isset($get['amount_min']) && $get['amount_min'] !== '') {
            $conditions['amount[>=]'] = ["value" => (float)$get['amount_min'], "alias" => "amount_min"];
            $activeFilters['amount_min'] = $get['amount_min'];
        }

        if (isset($get['amount_max']) && $get['amount_max'] !== '') {
            $conditions['amount[<=]'] = ["value" => (float)$get['amount_max'], "alias" => "amount_max"];
            $activeFilters['amount_max'] = $get['amount_max'];
        }

        if (isset($get['description']) && $get['description'] !== '') {
            $conditions['description[LIKE]'] = '%' . $get['description'] . '%';
            $activeFilters['description'] = $get['description'];
        }

        if (isset($get['is_in_budget']) && $get['is_in_budget'] !== '') {
            $conditions['is_in_budget[=]'] = (int)$get['is_in_budget'];
            $activeFilters['is_in_budget'] = $get['is_in_budget'];
        }

        if (isset($get['date_from']) && $get['date_from'] !== '') {
            $conditions['set_date[>=]'] = ["value" => $get['date_from'], "alias" => "date_from"];
            $activeFilters['date_from'] = $get['date_from'];
        }

        if (isset($get['date_to']) && $get['date_to'] !== '') {
            $conditions['set_date[<=]'] = ["value" => $get['date_to'], "alias" => "date_to"];
            $activeFilters['date_to'] = $get['date_to'];
        }

        return ['conditions' => $conditions, 'activeFilters' => $activeFilters];
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
