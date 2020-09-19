<?php

class MoneyLoanController extends Controller
{
    private $model;
    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->model = $this->model("moneyLoan");
    }

    public function index()
    {
        $loans = $this->model->select("*", ["id_user[=]" => $this->id, "status[is not]" => null, "AND"])->array();
        return view("loans.list", ["loans" => $loans]);
    }
    public function create()
    {
        return view("loans.create");
    }

    public function store()
    {
        return execute_post(function ($request) {
            if (arrayEmpty(["description", "total", "set_date"], $request)) {
                return redirect("moneyLoan")->with("error", "Lo sentimos, llena todos los campos");
            }
            $data = [
                "id_user" => $this->id,
                "description" => $request->description,
                "total" => $request->total,
                "set_date" => $request->set_date,
                "status" => 1,
                "create_at" => getCurrentDatetime()
            ];
            if ($this->model->insert($data)->array()) {
                return redirect("moneyLoan")->with("success", "Prestamo creado correctamente");
            } else {
                return redirect("moneyLoan")->with("error", "Lo sentimos, no se pudo crear el prestamo");
            }
        });
    }

    public function enable($id)
    {
        $cond = ["id_user[=]" => $this->id, "id_money_loan[=]" => $id, "AND"];
        if ($this->model->has($cond)->array()) {
            if ($this->model->enable($cond)->array()) {
                return redirect("moneyLoan")->with("success", "Prestamo activado correctamente");
            } else {
                return redirect("moneyLoan")->with("error", "No se pudo activar");
            }
        } else {
            return redirect("moneyLoan")->with("error", "El prestamo no pertenece a los tuyos.");
        }
    }
    public function disable($id)
    {
        $cond = ["id_user[=]" => $this->id, "id_money_loan[=]" => $id, "AND"];
        if ($this->model->has($cond)->array()) {
            if ($this->model->disable($cond)->array()) {
                return redirect("moneyLoan")->with("success", "Prestamo inactivado correctamente");
            } else {
                return redirect("moneyLoan")->with("error", "No se pudo inactivar");
            }
        } else {
            return redirect("moneyLoan")->with("error", "El prestamo no pertenece a los tuyos.");
        }
    }
    public function delete($id)
    {
        $cond = ["id_user[=]" => $this->id, "id_money_loan[=]" => $id, "AND"];
        if ($this->model->has($cond)->array()) {
            if ($this->model->update(["status" => null], $cond)->array()) {
                return redirect("moneyLoan")->with("success", "Prestamo eliminado correctamente");
            } else {
                return redirect("moneyLoan")->with("error", "No se pudo eliminar");
            }
        } else {
            return redirect("moneyLoan")->with("error", "El prestamo no pertenece a los tuyos.");
        }
    }
}
