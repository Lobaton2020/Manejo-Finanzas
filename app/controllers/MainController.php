<?php

class MainController extends Controller
{
    private $inflow;
    private $outflow;
    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->inflow = $this->model("inflow");
        $this->outflow = $this->model("outflow");
    }

    public function index()
    {
        if (!isset($_COOKIE["firstvisit"])) {
            setcookie("firstvisit", "ok", time() + (60 * 60 * 24 * 365 * 2));
            setcookie("show-cookie", "ok", time() + 4);
        } else {
            setcookie("show-cookie", "ok", time() - 4);
        }

        $sum_egress = 0;
        $inflows = $this->inflow->select(["id_inflow"], ["id_user[=]" => $this->id])->array();
        $number_ingres = $this->inflow->count(["id_user[=]" => $this->id])->array();
        $number_egres = $this->outflow->count(["id_user[=]" => $this->id])->array();
        foreach ($inflows as $inflow) {
            $sum_egress += intval($this->outflow->sum("amount", ["id_user[=]" => $inflow->id_inflow])->array());
        }
        $sum_entrys = intval($this->inflow->sum("total", ["id_user[=]" => $this->id])->array());
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
                "title" => "Numero Ingresos",
                "amount" => $number_ingres
            ],
            "allspends" => [
                "title" => "Numero Egresos",
                "amount" => $number_egres
            ],
        ];
        return view("main.index", $data);
    }

    public function logout()
    {
        $this->destroySession();
        $this->redirect("auth");
    }
}
