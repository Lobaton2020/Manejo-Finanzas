<?php

class MoneyLoanController extends Controller
{
    private $model;
    private $modelListener;
    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->model = $this->model("moneyLoan");
        $this->modelListener = $this->model("moneyLoanListener");

    }

    public function index()
    {
        $loans = $this->model->select("*", ["id_user[=]" => $this->id, "status[is not]" => null, "AND"])->array();
        foreach ($loans as $item) {
            $item->type = $item->type == 'TO_ME' ? badge('Me prestan', 'warning') : badge("Yo presto", 'info');

        }
        return view("loans.list", ["loans" => $loans]);
    }
    public function create()
    {
        return view("loans.create");

    }
    public function createListenerNotification($id)
    {
        $listener = $this->modelListener->select("*", ["id_money_loan[=]" => $id])->firstElement();
        $default = new stdClass();
        $default->id = 0;
        $default->id_money_loan = 0;
        $default->email = '';
        $default->username = '';
        $default->is_active = false;
        $default->is_subscription = false;
        return view("loans.createListenerNotification", ["listener" => isset($listener) ? $listener : $default, "idMoneyLoan" => $id]);

    }

    public function storeListener($idMoneyLoan, $id = 0)
    {
        return execute_post(function ($request) use ($idMoneyLoan, $id) {
            try {
                if (arrayEmpty(["is_subscription", "username", "email", "is_active", "type"], $request)) {
                    return redirect("moneyLoan/createListenerNotification/" . $idMoneyLoan)->with("error", "Lo sentimos, llena todos los campos");
                }
                $listener = $this->modelListener->select("*", ["id[=]" => $id])->firstElement();
                if ($listener) {
                    $this->modelListener->update((array) $request, ["id[=]" => $id]);
                    return redirect("moneyLoan")->with("success", "Notificacion actualizada correctamente");
                }
                $data = (array) $request;
                $data["id_money_loan"] = $idMoneyLoan;
                $this->modelListener->insert($data);
                return redirect("moneyLoan")->with("success", "Notificacion creada correctamente");
            } catch (Exception $e) {
                return redirect("moneyLoan")->with("error", "Lo sentimos, no se pudo crear el Notificacion: " . $e->getMessage());
            }
        });
    }


    public function store()
    {
        return execute_post(function ($request) {
            if (arrayEmpty(["description", "total", "set_date", "type"], $request)) {
                return redirect("moneyLoan")->with("error", "Lo sentimos, llena todos los campos");
            }
            $data = [
                "id_user" => $this->id,
                "description" => $request->description,
                "total" => $request->total,
                "set_date" => $request->set_date,
                "type" => $request->type,
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
