<?php

class InflowController extends Controller
{
    private $model;
    private $user;
    private $inflow_type;
    private $inflow_porcent;
    private $notification;
    private $porcent;
    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->model = $this->model("inflow");
        $this->user = $this->model("user");
        $this->notification = $this->model("notification");
        $this->inflow_type = $this->model("inflowType");
        $this->inflow_porcent = $this->model("inflowPorcent");
        $this->porcent = $this->model("porcent");
    }


    public function index()
    {
        $inflows = $this->model->select("*", ["id_user[=]" => $this->id], "id_inflow DESC")->array();
        foreach ($inflows as $inflow) {
            $inflow->inflow_type = $this->inflow_type->get("*", ["id_user[=]" => $this->id, "id_inflow_type[=]" => $inflow->id_inflow_type, "AND"])->array();
            $inflow->detail = $this->inflow_porcent->select("*", ["id_inflow[=]" => $inflow->id_inflow])->array();
            foreach ($inflow->detail as $detail) {
                $detail->porcents = $this->porcent->get("*", ["id_user[=]" => $this->id, "id_porcent[=]" => $detail->id_porcent, "AND"])->array();
                $detail->value = ($inflow->total * $detail->porcent) / 100;
            }
        }
        return view("inflows.list", ["inflows" => $inflows], true);
    }

    public function create()
    {
        $data = [
            "porcents" => $this->porcent->select("*", ["status[=]" => 1, "id_user[=]" => $this->id, "AND"])->array(),
            "inflow_types" => $this->inflow_type->select("*", ["status[=]" => 1, "id_user[=]" => $this->id, "AND"])->array(),
        ];
        return view("inflows.create", $data);
    }

    public function store()
    {
        return execute_post(function ($request) {
            $sum = 0;
            $insert_porcents = true;
            $id_inflow = null;
            if (arrayEmpty(["id_inflow_type", "total", "set_date"], $request)) {
                return redirect("inflow/create")->with("error", "Debes llenar todos los campos.");
            }
            foreach ($request->porcents as $num) {
                $sum += intval($num);
            }
            if ($sum != 100) {
                return redirect("inflow/create")->with("error", "La suma de porcentajes no es igual a 100");
            }
            $data = [
                "id_user" => $this->id,
                "id_inflow_type" => $request->id_inflow_type,
                "total" => $request->total,
                "description" => is_correct($request->description),
                "set_date" => $request->set_date,
                "status" => 1,
                "update_at" => getCurrentDatetime(),
                "create_at" => getCurrentDatetime()
            ];

            if ($this->model->insert($data)->array()) {
                $id_inflow = $this->model->id();
                foreach ($request->porcents as $idporcent => $porcent) {
                    $data = [
                        "id_inflow" => $id_inflow,
                        "id_porcent" => $idporcent,
                        "porcent" => $porcent,
                        "status" => 1,
                        "create_at" => getCurrentDatetime()
                    ];
                    if (!$this->inflow_porcent->insert($data)->array()) {
                        $insert_porcents = false;
                    }
                }
                if ($insert_porcents) {
                    $this->notification->insert([$this->id, "ingres"]);
                    return redirect("inflow")->with("success", "Entrada de dinero agregada correctamente.");
                } else {
                    $this->model->delete(["id_inflow[=]" => $data["id_inflow"]]);
                    return redirect("inflow/create")->with("error", "Algo pasó con los porcentajes");
                }
            } else {
                return redirect("inflow/create")->with("error", "No se pudo añadir la entrada de dinero");
            }
        });
    }
}
