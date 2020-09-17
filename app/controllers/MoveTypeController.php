<?php

class MoveTypeController extends Controller
{
    private $inflow_type;
    private $outflow_type;
    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->inflow_type = $this->model("inflowType");
        $this->outflow_type = $this->model("outflowType");
    }
    public function index()
    {
        $data = [
            "movetypes_inflow" => $this->inflow_type->select("*", ["id_user[=]" => $this->id])->array(),
            "movetypes_outflow" => $this->outflow_type->select("*", ["id_user[=]" => $this->id])->array()
        ];
        return view("movetypes.list", $data);
    }


    public function store()
    {
        return execute_post(function ($request) {
            $type = "";
            if (arrayEmpty(["name", "id_move_type"], $request)) {
                return httpResponse(400, "empty", "The data is empty");
            }
            if ($request->id_move_type == "1") {
                $type = "inflow";
            } else {
                $type = "outflow";
            }
            $data = [
                "id_user" => $this->id,
                "name" => $request->name,
                "status" => 1,
                "create_at" => getCurrentDatetime()
            ];
            if ($this->{"{$type}_type"}->has(["name[=]" => $data["name"], "id_user[=]" => $this->id, "AND"])->array()) {
                return httpResponse(403, "exists", "The register already exists")->json();
            }
            if ($this->{"{$type}_type"}->insert($data)->array()) {
                return httpResponse(200, "ok", "type of move registered")->json();
            } else {
                return httpResponse(500, "error", "Error unknown")->json();
            }
        });
    }

    public function disable($id = null, $type = null)
    {
        $route  = "moveType";
        switch ($type) {
            case "inflow":
                $message = "de ingreso";
                return $this->typeDisable($id, $type, $message, $route, "disable");
                break;
            case "outflow":
                $message = "de egreso";
                return $this->typeDisable($id, $type, $message, $route, "disable");
                break;
            default;
                return redirect($route)->with("error", "Error, revisa la ruta de peticion.");
        }
    }
    public function enable($id = null, $type = null)
    {
        $route  = "moveType";
        switch ($type) {
            case "inflow":
                $message = "de ingreso";
                return $this->typeDisable($id, $type, $message, $route, "enable");
                break;
            case "outflow":
                $message = "de egreso";
                return $this->typeDisable($id, $type, $message, $route, "enable");
                break;
            default;
                return redirect($route)->with("error", "Error, revisa la ruta de peticion.");
        }
    }

    private function typeDisable($id, $type, $message, $route, $action)
    {
        $message = "de ingreso";
        $cond = ["id_user[=]" => $this->id, "id_{$type}_type[=]" => $id, "AND"];
        if ($this->{"{$type}_type"}->has($cond)->array()) {
            if ($this->{"{$type}_type"}->{$action}($cond)->array()) {
                return redirect($route)->with("success", "Tipo de movimiento {$message} inactivado correctamente");
            } else {
                return redirect($route)->with("error", "El tipo de movimiento {$message} no se pudo inactivar");
            }
        } else {
            return redirect($route)->with("error", "El tipo de movimiento {$message} no existe.");
        }
    }

    public function edit($id = null, $type  = null)
    {
        dd($id, $type);
    }
}
