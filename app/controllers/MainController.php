<?php

class MainController extends Controller
{
    private $inflow;
    private $outflow;
    private $budgetView;
    private $budget;
    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->inflow = $this->model("inflow");
        $this->outflow = $this->model("outflow");
        $this->budgetView = $this->model("budgetView");
        $this->budget = $this->model("budget");
    }


    public function index()
    {
        if (!isset($_COOKIE["firstvisit"])) {
            setcookie("firstvisit", "ok", time() + (60 * 60 * 24 * 365 * 2));
            setcookie("show-cookie", "ok", time() + 4);
        } else {
            setcookie("show-cookie", "ok", time() - 4);
        }

        $number_ingres = $this->inflow->count(["id_user[=]" => $this->id])->array();
        $number_egres = $this->outflow->count(["id_user[=]" => $this->id])->array();
        $sum_egress = intval($this->outflow->sum("amount", ["id_user[=]" =>  $this->id])->array());
        $sum_entrys = intval($this->inflow->sum("total", ["id_user[=]" => $this->id])->array());
        $budgetView = $this->budgetView->get("*", ["date[=]" => date('Y-m-01'), "id_user[=]" => $this->id, "AND"])->array();
        $number_disponible = $sum_entrys - $sum_egress;
        $data = [
            "allentry" => [
                "title" => "Total Ingresos",
                "amount" => number_price($sum_entrys, true)
            ],
            "allegress" => [
                "title" => "Total Egresos",
                "amount" => number_price($sum_egress, true)
            ],
            "allinvestment" => [
                "title" => "Total disponible",
                "amount" => number_price($number_disponible, true)
            ],
            "allspends" => [
                "title" => "Numero: <br> Ingresos / Egresos",
                "amount" => $number_ingres . " / " .$number_egres
            ],
            "budget" => $this->get_budget_formatted($budgetView)
        ];
        return view("main.index", $data);
    }

    public function set_budget()
    {
        return execute_post(function ($request) {
            if (arrayEmpty(["budget"], $request)) {
                return httpResponse(401, "bad_request", "the budget doesn't come")->json();
            }
            $budgetViewCurrentMonth = $this->budgetView->get("*", ["date[=]" => date('Y-m-01'), "id_user" => $this->id, "AND"])->array();
            $data = ["total" => $request->budget];
            if ($budgetViewCurrentMonth) {
                if ($this->budget->update($data, ["id_budget[=]" => $budgetViewCurrentMonth->id_budget, "id_user" => $this->id, "AND"])->array()) {
                    return httpResponse(201, "created", "successfuly created")->json();
                }
                return  httpResponse(500, "error", "Error to save record")->json();
            } else {
                if ($this->budget->insert(["total" => $request->budget,  "id_user" => $this->id])->array()) {
                    return httpResponse(201, "created", "successfuly created")->json();
                }
                return  httpResponse(500, "error", "Error to save record")->json();
            }
        });
    }
    private function get_budget_formatted($budgetView)
    {
        $budget = [
            "remain" => number_price(0, true),
            "total" => number_price_without_html(0),
            "budget" => number_price(0, true),
            "percent" => "0",
            "date" => ""
        ];
        if ($budgetView) {
            $budget = [
                "remain" => number_price($budgetView->remain, true),
                "total" => number_price_without_html($budgetView->total),
                "budget" => number_price($budgetView->budget, true),
                "budget_int" => $budgetView->budget,
                "percent" => $budgetView->percent,
                "date" => $budgetView->date
            ];
        }
        return $budget;
    }
    public function logout()
    {
        $this->destroySession();
        $this->redirect("auth");
    }
}
