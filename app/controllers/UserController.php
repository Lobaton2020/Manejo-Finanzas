<?php

class UserController extends Controller
{
    private $model;
    private $rol_b;

    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->model = $this->model("user");
        $this->document_type = $this->model("documentType");
    }

    public function index()
    {
        return redirect("main");
    }
    public function profile()
    {
        $user = $this->model->get("*", ["id_user[=]" => $this->id])->array();
        $documents = $this->document_type->select()->array();
        return view("users.profileEdit", ["user" => $user, "documents" => $documents]);
    }
    public function updateProfile()
    {
        execute_post(function ($request) {

            if (arrayEmpty(["document_type", "document", "name"], $request)) {
                return redirect("user/profile")->with("error", "Debes llenar todos los campos que son obligatorios");
            }
            $data = [
                "id_document_type" => $request->document_type,
                "number_document" => $request->document,
                "born_date" => $request->born_date,
                "complete_name" => $request->name,
                "update_at" => getCurrentDatetime()
            ];
            if ($this->model->update($data, ["id_user[=]" => $this->id])->array()) {
                return redirect("user/profile")->with("success", "Tu perfil se ha actualizado correctamente");
            } else {
                return redirect("user/profile")->with("error", "Lo sentimos, no se pudo actualizar tu perfil");
            }
        });
    }


    public function disableMyAcount()
    {
        return execute_post(function () {

            $cond = ["id_user[=]" => $this->id];
            if ($this->model->has($cond)->array()) {
                if ($this->model->disable($cond)->array()) {
                    return redirect("main/logout");
                } else {
                    return redirect("admin/users")->with("error", "No pudimos eliminar tu cuenta");
                }
            } else {
                return redirect("admin/users")->with("error", "Error en el servidor");
            }
        });
    }

    public function disable($id = null)
    {
        $cond = ["id_user[=]" => $id];
        if ($this->model->has($cond)->array()) {
            if ($this->model->disable($cond)->array()) {
                return redirect("admin/users")->with("success", "Usuario inactivado correctamente");
            } else {
                return redirect("admin/users")->with("error", "No se pudo inactivar al usuario");
            }
        } else {
            return redirect("admin/users")->with("error", "El usuario no existe");
        }
    }
    public function enable($id = null)
    {
        $cond = ["id_user[=]" => $id];
        if ($this->model->has($cond)->array()) {
            if ($this->model->enable($cond)->array()) {
                return redirect("admin/users")->with("success", "Usuario activado correctamente");
            } else {
                return redirect("admin/users")->with("error", "No se pudo activar al usuario");
            }
        } else {
            return redirect("admin/users")->with("error", "El usuario no existe");
        }
    }
}
