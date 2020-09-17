<?php

class CategoryController extends Controller
{
    private $model;
    private $outflow_type;
    private $notification;

    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->model = $this->model("category");
        $this->notification = $this->model("notification");
        $this->outflow_type = $this->model("outflowType");
    }
    public function index()
    {
        $categories = $this->model->select("*", ["id_user[=]" => $this->id])->array();
        foreach ($categories as $category) {
            $category->outflow_type = $this->outflow_type->get("*", ["id_outflow_type[=]" => $category->id_outflow_type])->array()->name;
        }

        $outflow_types = $this->outflow_type->select("*", ["id_user[=]" => $this->id, "status[=]" => 1, "AND"])->array();
        return view("categories.list", ["categories" => $categories, "outflow_types" => $outflow_types]);
    }
    public function select($id = null)
    {
        return httpResponse($this->model->select("*", ["id_outflow_type[=]" => $id, "status[=]" => 1, "AND"])->array())->json();
    }

    public function store()
    {
        return execute_post(function ($request) {
            if (arrayEmpty(["name", "id_outflow_type"], $request)) {
                return httpResponse(400, "empty", "The data is empty");
            }
            $data = [
                "id_outflow_type" => $request->id_outflow_type,
                "id_user" => $this->id,
                "name" => $request->name,
                "status" => 1,
                "create_at" => getCurrentDatetime()
            ];
            if ($this->model->has(["name[=]" => $data["name"], "id_user[=]" => $this->id, "AND"])->array()) {
                return httpResponse(403, "exists", "The register already exists")->json();
            }
            if ($this->model->insert($data)->array()) {
                $this->notification->insert([$this->id, "category"]);
                return httpResponse($this->model->get("*", ["id_category[=]" => $this->model->id(), "id_user[=]" => $this->id, "AND"])->array())->json();
            } else {
                return httpResponse(500, "error", "Error unknown")->json();
            }
        });
    }

    public function disable($id = null)
    {
        $cond = ["id_user[=]" => $this->id, "id_category[=]" => $id, "AND"];
        if ($this->model->has($cond)->array()) {
            if ($this->model->disable($cond)->array()) {
                return redirect("category")->with("success", "Categoria inactivada correctamente");
            } else {
                return redirect("category")->with("error", "No se pudo inactivar");
            }
        } else {
            return redirect("category")->with("error", "La categoria no pertenece a las tuyas.");
        }
    }
    public function enable($id = null)
    {
        $cond = ["id_user[=]" => $this->id, "id_category[=]" => $id, "AND"];
        if ($this->model->has($cond)->array()) {
            if ($this->model->enable($cond)->array()) {
                return redirect("category")->with("success", "Categoria activada correctamente");
            } else {
                return redirect("category")->with("error", "No se pudo activar");
            }
        } else {
            return redirect("category")->with("error", "La categoria no pertenece a las tuyas.");
        }
    }
}
