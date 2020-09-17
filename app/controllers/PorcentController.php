<?php

class PorcentController extends Controller
{
    private $model;
    private $notification;

    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->model = $this->model("porcent");
        $this->notification = $this->model("notification");
    }

    public function index()
    {
        $porcents = $this->model->select("*", ["id_user[=]" => $this->id])->array();
        return view("porcents.list", ["porcents" => $porcents]);
    }
    // USE AJAX
    public function store()
    {
        return execute_post(function ($request) {
            if (arrayEmpty(["name"], $request)) {
                return httpResponse(400, "empty", "The data is empty");
            }
            $data = [
                "id_user" => $this->id,
                "name" => $request->name,
                "status" => 1,
                "create_at" => getCurrentDatetime()
            ];
            if ($this->model->has(["name[=]" => $data["name"], "id_user[=]" => $this->id, "AND"])->array()) {
                return httpResponse(403, "exists", "The register already exists")->json();
            }
            if ($this->model->insert($data)->array()) {
                $this->notification->insert([$this->id, "deposit"]);
                return httpResponse($this->model->get("*", ["id_porcent[=]" => $this->model->id(), "id_user[=]" => $this->id, "AND"])->array())->json();
            } else {
                return httpResponse(500, "error", "Error unknown")->json();
            }
        });
    }
    // end
    public function edit($id = null)
    {
        dd($id);
    }

    public function disable($id = null)
    {
        $cond = ["id_user[=]" => $this->id, "id_porcent[=]" => $id, "AND"];
        if ($this->model->has($cond)->array()) {
            if ($this->model->disable($cond)->array()) {
                return redirect("porcent")->with("success", "Porcentaje inactivado correctamente");
            } else {
                return redirect("porcent")->with("error", "No se pudo inactivar");
            }
        } else {
            return redirect("porcent")->with("error", "El porcentaje no pertenece a los tuyos.");
        }
    }
    public function enable($id = null)
    {
        $cond = ["id_user[=]" => $this->id, "id_porcent[=]" => $id, "AND"];
        if ($this->model->has($cond)->array()) {
            if ($this->model->enable($cond)->array()) {
                return redirect("porcent")->with("success", "Porcentaje activado correctamente");
            } else {
                return redirect("porcent")->with("error", "No se pudo activar");
            }
        } else {
            return redirect("porcent")->with("error", "El porcentaje no pertenece a los tuyos.");
        }
    }
}
