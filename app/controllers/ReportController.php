<?php

class ReportController extends Controller
{
    private $user;
    private $inflow_porcent;
    private $outflow;
    private $inflow;
    private $porcent; // simil to deposit

    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->user = $this->model("user");
        $this->rol_b = $this->model("rol");
        $this->token = $this->model("tokenRegister");
        $this->docuemnt_type = $this->model("documentType");
        $this->notification = $this->model("notification");
        $this->loggin = $this->model("loggin");
        $this->count_visit = $this->model("countVisit");
        $this->notification_type = $this->model("notificationType");
        $this->inflow_porcent = $this->model("inflowPorcent");
        $this->outflow = $this->model("outflow");
        $this->inflow = $this->model("inflow");
        $this->porcent = $this->model("porcent");
    }
    public function moneyTotalbyDeposit()
    {
        $porcents = $this->porcent->select(["id_porcent", "name", "status", "create_at"], ["id_user[=]" => $this->id, "status[=]" => 1, "AND"])->array();
        $ids_porcent = $this->getIdsPorcent($porcents, "id_porcent");
        if (count($ids_porcent) == 0) {
            return [];
        }
        $inflow_porcents = $this->inflow_porcent->in(["id_porcent[in]" => $ids_porcent])->array();
        foreach ($inflow_porcents as $inflow_porcent) {
            $inflow = $this->inflow->get("*", ["id_inflow[=]" => $inflow_porcent->id_inflow])->array();
            $inflow_porcent->subtotal = ((intval($inflow->total) * intval($inflow_porcent->porcent))  / 100);

            if (in_array($inflow_porcent->id_porcent, $ids_porcent)) {
                foreach ($porcents as $porcent) {
                    if ($inflow_porcent->id_porcent == $porcent->id_porcent) {
                        $porcent->total += intval($inflow_porcent->subtotal);
                    }
                }
            }
        }
        return $porcents;
    }

    public function moneyEgressbyDeposit()
    {
        $porcents = $this->porcent->select(["id_porcent", "name", "status", "create_at"], ["id_user[=]" => $this->id, "status[=]" => 1, "AND"])->array();
        $ids_porcent = $this->getIdsPorcent($porcents, "id_porcent");
        if (count($ids_porcent) == 0) {
            return [];
        }
        $outflow_porcents = $this->outflow->in(["id_porcent[in]" => $ids_porcent])->array();
        foreach ($outflow_porcents as $outflow_porcent) {
            if (in_array($outflow_porcent->id_porcent, $ids_porcent)) {
                foreach ($porcents as $porcent) {
                    if ($outflow_porcent->id_porcent == $porcent->id_porcent) {
                        $porcent->total += intval($outflow_porcent->amount);
                    }
                }
            }
        }
        return $porcents;
    }

    public function moneySpendbyDeposit()
    {
        return httpResponse($this->moneyEgressbyDeposit())->json();
    }

    public function moneyDisponiblebyDeposit()

    {
        $money_disponible = $this->moneyTotalbyDeposit();
        $money_egress = $this->moneyEgressbyDeposit();
        for ($i = 0; $i < count($money_egress); $i++) {
            $money_disponible[$i]->total =  intval($money_disponible[$i]->total) - intval($money_egress[$i]->total);
        }

        return httpResponse($money_disponible)->json();
    }
    private function getIdsPorcent($porcents, $name_id)
    {
        $ids_porcent = [];
        foreach ($porcents as $porcent) {
            $porcent->total = 0;
            array_push($ids_porcent, $porcent->{"{$name_id}"});
        }
        return $ids_porcent;
    }
}
