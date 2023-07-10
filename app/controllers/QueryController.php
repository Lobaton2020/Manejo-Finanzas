<?php

class QueryController extends Controller
{
    private $model;
    private $notification;

    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->only_access_admin();
        $this->model = $this->model("query");
    }

    public function index()
    {
        $data = [
            "queries" => []
        ];
        return view("queries.index", $data);
    }
    // using ajax
    public function queries()
    {
        $data = $this->model->select("*", ["id_user[=]" => $this->id])->array();
        return httpResponse($data)->json();
    }
    public function update($id)
    {
        return execute_post(function ($request) use ($id) {
            if (arrayEmpty(["querySql", "descriptionQuerySql"], $request)) {
                return redirect("query")->with("error", "Parametros invalidos");
            }
            $data = [
                "description" => is_correct($request->descriptionQuerySql),
                "query" => $request->querySql
            ];
            $where = ["id_query[=]" => $id, "id_user[=]" => $this->id, "AND"];
            if ($this->model->update($data, $where)->array()) {
                $msg = "Consulta SQL \"" . $request->descriptionQuerySql . "\" actualizada con exito";
                return redirect("query")->with("success", $msg);
            } else {
                return redirect("query")->with("error", "No se pudo actualizar la consulta SQL");
            }
        });
    }

    public function store()
    {
        return execute_post(function ($request) {
            if (arrayEmpty(["query", "description"], $request)) {
                return httpResponse(400, "field_empties", "The field 'query' is required")->json();
            }
            $data = [
                "id_user" => $this->id,
                "description" => is_correct($request->description),
                "query" => $request->query,
                "create_at" => getCurrentDatetime()
            ];
            if ($this->model->insert($data)->array()) {
                return httpResponse(200, "success", "Query saved successfully")->json();
            } else {
                return httpResponse(500, "error", "Error to save query")->json();
            }
        });
    }

    public function sql($query = null)
    {
        $query = strtolower(str_replace("-", " ", $query));
        if (
            !(in_array("update", explode(" ", $query))) &&
            !(in_array("delete", explode(" ", $query))) &&
            !(in_array("alter", explode(" ", $query))) &&
            !(in_array("drop", explode(" ", $query))) &&
            !(in_array("create", explode(" ", $query))) &&
            !(in_array("insert", explode(" ", $query))) &&
            !(in_array(";", str_split($query)))
        ) {
            $data = $this->model->query($query)->array();
            return httpResponse($data)->json();
        } else {
            return httpResponse(400, "error", "Command not Allowed,and without ';'", [])->json();
        }
    }

    public function delete($id = null)
    {
        $cond = ["id_user[=]" => $this->id, "id_query[=]" => $id, "AND"];

        if ($this->model->delete($cond)->array()) {
            return redirect("query")->with("success", "Consulta SQL eliminada Correctamente");
        } else {
            return redirect("query/")->with("error", "No se pudo eliminar la consulta SQL");
        }
    }
}
