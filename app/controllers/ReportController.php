<?php

class ReportController extends Controller
{

    private $outflow;
    private $porcent; // simil to deposit

    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->outflow = $this->model("outflow");
        $this->porcent = $this->model("porcent");
    }

    public function moneyTotalbyDeposit()
    {
        $porcents = $this->porcent->query_complete("select p.*,sum(i.total * (ip.porcent / 100)) as total  from porcents as p
        left join inflow_porcent as ip on ip.id_porcent = p.id_porcent
        left join inflows as i on ip.id_inflow = i.id_inflow  where p.id_user = 1
        group by p.id_porcent ORDER BY ip.id_porcent ASC")->array();
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
