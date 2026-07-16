<?php

class SharedFoundController extends Controller
{
    private $model;
    public function __construct()
    {
        parent::__construct();
        $this->authentication();
        $this->only_access_admin();
        $this->model = $this->model("sharedFound");
    }

    public function index()
    {
        $records = $this->model->select("*", [], "ORDER BY `year` DESC, `month` DESC")->array();
        return view("sharedFound.list", ["records" => $records]);
    }

    public function create()
    {
        return view("sharedFound.create");
    }

    public function edit($id = null)
    {
        $record = $this->model->get("*", ["id[=]" => $id])->array();
        return view("sharedFound.edit", ["record" => $record]);
    }

    public function store()
    {
        return execute_post(function ($request) {
            if (arrayEmpty(["year", "month", "amount_andres", "amount_ivan"], $request)) {
                return redirect("sharedFound/create")->with("error", "Llena todos los campos");
            }

            $year = (int)$request->year;
            $month = (int)$request->month;
            $amountAndres = (float)$request->amount_andres;
            $amountIvan = (float)$request->amount_ivan;

            $exists = $this->model->get("id, amount_andres, amount_ivan", ["year[=]" => $year, "month[=]" => $month])->array();
            if ($exists && !empty($exists->id)) {
                $newAndres = $exists->amount_andres + $amountAndres;
                $newIvan = $exists->amount_ivan + $amountIvan;
                $data = [
                    "amount_andres" => $newAndres,
                    "amount_ivan" => $newIvan,
                    "total" => $newAndres + $newIvan
                ];
                $this->model->update($data, ["id[=]" => $exists->id]);
            } else {
                $data = [
                    "year" => $year,
                    "month" => $month,
                    "amount_andres" => $amountAndres,
                    "amount_ivan" => $amountIvan,
                    "total" => $amountAndres + $amountIvan
                ];
                $this->model->insert($data);
            }

            return redirect("sharedFound")->with("success", "Registro guardado correctamente");
        });
    }

    public function update()
    {
        return execute_post(function ($request) {
            if (arrayEmpty(["year", "month", "amount_andres", "amount_ivan", "id"], $request)) {
                return redirect("sharedFound")->with("error", "Llena todos los campos");
            }

            $amountAndres = (float)$request->amount_andres;
            $amountIvan = (float)$request->amount_ivan;

            $data = [
                "year" => (int)$request->year,
                "month" => (int)$request->month,
                "amount_andres" => $amountAndres,
                "amount_ivan" => $amountIvan,
                "total" => $amountAndres + $amountIvan
            ];

            $this->model->update($data, ["id[=]" => $request->id]);
            return redirect("sharedFound")->with("success", "Registro actualizado correctamente");
        });
    }

    public function delete($id)
    {
        if ($this->model->delete(["id[=]" => $id])->array()) {
            return redirect("sharedFound")->with("success", "Registro eliminado");
        }
        return redirect("sharedFound")->with("error", "Error al eliminar");
    }
}
