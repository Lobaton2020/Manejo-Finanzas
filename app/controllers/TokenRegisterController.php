<?php

class TokenRegisterController extends Controller
{
    private $model;
    private $rol_b;
    private $notification;

    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->only_access_admin();
        $this->model = $this->model("tokenRegister");
        $this->notification = $this->model("notification");
        $this->rol_b = $this->model("rol");
    }

    public function create()
    {
        $rols = $this->rol_b->select()->array();
        return view("tokenregisters.create", ["rols" => $rols]);
    }

    public function store()
    {
        return execute_post(function ($request) {
            if (arrayEmpty(["id_rol"], $request)) {
                return redirect("tokenRegister/create")->with("error", "Debes llenar todos los campos");
            }
            $data = [
                "id_rol" => $request->id_rol,
                "id_user" => $this->id,
                "description" =>  $request->description,
                "token" => token(),
                "status" => 1,
                "create_at" => getCurrentDatetime()
            ];
            if ($this->model->insert($data)->array()) {
                $this->notification->insert([$this->id, "token"]);
                return redirect("admin/tokens")->with("success", "Token creado correctamente. Ya puedes compartir el link!");
            } else {
                return redirect("tokenRegister/create")->with("error", "Sucedio algo, no se creó el token.");
            }
        });
    }
    public function disable($id = null)
    {
        $cond = ["id_user[=]" => $this->id, "id_token_register[=]" => $id, "AND"];

        if ($this->model->disable($cond)->array()) {
            return redirect("admin/tokens")->with("success", "Token inactivado correctamente");
        } else {
            return redirect("admin/tokens")->with("error", "No se pudo inactivar el token");
        }
    }
    public function enable($id = null)
    {
        $cond = ["id_user[=]" => $this->id, "id_token_register[=]" => $id, "AND"];

        if ($this->model->enable($cond)->array()) {
            return redirect("admin/tokens")->with("success", "Token activado correctamente");
        } else {
            return redirect("admin/tokens")->with("error", "No se pudo activar el token");
        }
    }

    public function delete($id = null)
    {
        $cond = ["id_user[=]" => $this->id, "id_token_register[=]" => $id, "AND"];

        if ($this->model->delete($cond)->array()) {
            return redirect("admin/tokens")->with("success", "Token eliminado correctamente");
        } else {
            return redirect("admin/tokens")->with("error", "No se pudo eliminar el token");
        }
    }
}
