<?php

class OutflowController extends Controller
{
    private $model;
    private $outflow_type;
    private $porcent;
    private $notification;
    private $category;
    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->model = $this->model("outflow");
        $this->outflow_type = $this->model("outflowType");
        $this->porcent = $this->model("porcent");
        $this->notification = $this->model("notification");
        $this->category = $this->model("category");
    }
    private function get_amounts_disponible()
    {
        require_once URL_APP . "controllers/ReportController.php";
        $report = new ReportController();
        return toArray($report->moneyDisponiblebyDeposit())->data;
    }
    private function is_amount_disponible($id_porcent, $amount)
    {
        $disponibles = $this->get_amounts_disponible();
        $is_amount_disponible = false;
        foreach ($disponibles as $disponible) {
            if ($id_porcent == $disponible->id_porcent) {
                if ($amount <= $disponible->total) {
                    $is_amount_disponible = true;
                }
            }
        };
        return $is_amount_disponible;
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
        $max_porcents = $this->get_amounts_disponible();
        $porcents = $this->porcent->select("*", ["id_user[=]" => $this->id, "status[=]" => 1, "AND"])->array();
        for ($i = 0; $i < count($porcents); $i++) {
            $porcents[$i]->name = "{$porcents[$i]->name}    &nbsp;&nbsp;   Disponible: " . number_price($max_porcents[$i]->total);
        }
        $data = [
            "porcents" => $porcents,
            "outflow_types" => $this->outflow_type->select("*", ["id_user[=]" => $this->id, "status[=]" => 1, "AND"])->array()
        ];
        return view("outflows.create", $data);
    }
    public function store()
    {
        execute_post(function ($request) {
            if (arrayEmpty(["id_outflow_type", "id_category", "id_porcent", "amount", "set_date", "is_in_budget"], $request)) {
                return redirect("outflow/create")->with("error", "Debes llenar todos los campos requeridos.");
            }
            if (!$this->is_amount_disponible($request->id_porcent, $request->amount)) {
                return redirect("outflow/create")->with("error", "  El saldo del deposito seleccionado, NO es suficiente para el monto que quieres retirar.");
            }
            $data = [
                "id_user" => $this->id,
                "id_outflow_type" => $request->id_outflow_type,
                "id_category" => $request->id_category,
                "id_porcent" => $request->id_porcent,
                "description" => is_correct($request->description),
                "amount" => $request->amount,
                "set_date" => $request->set_date,
                "status" => 1,
                "is_in_budget" => intval($request->is_in_budget),
                "update_at" => getCurrentDatetime(),
                "create_at" => getCurrentDatetime()
            ];
            if ($this->model->insert($data)->array()) {
                $this->notification->insert([$this->id, "egress"]);
                return redirect("outflow")->with("success", "Egreso agregado correctamente.");
            } else {
                return redirect("outflow/create")->with("error", "No se pudo añadir la salida de dinero");
            }
        });
    }

    public function edit($id)
    {
        dd($id);
    }

    public function disable($id)
    {
        dd($id);
    }
}
