<?php

class ReportController extends Controller
{

    private $outflow;
    private $porcent; // simil to deposit
    private $query;
    private $moneyLoan;

    public function __construct()
    {
        parent::__construct();
        countVisits($this->model("countVisit"), $this->id);
        $this->authentication();
        $this->outflow = $this->model("outflow");
        $this->porcent = $this->model("porcent");
        $this->query = $this->model("query");
        $this->moneyLoan = $this->model("moneyLoan");
    }

    public function moneyTotalbyDeposit()
    {
        $this->id = intval($this->id);
        $porcents = $this->porcent->query_complete("select p.*,sum(i.total * (ip.porcent / 100)) as total  from porcents as p
                                                    left join inflow_porcent as ip on ip.id_porcent = p.id_porcent
                                                    left join inflows as i on ip.id_inflow = i.id_inflow  where p.id_user = {$this->id} and p.status = 1
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
    public function getNetWorth()
    {
        $data = $this->query->getResumeNetWorthByUserId($this->id)->array();
        return httpResponse($data)->json();
    }
    public function getNetWorthWithRestMoneyLoans()
    {
        $data = $this->query->getResumeNetWorthByUserId($this->id)->array();
        $length = count($data);
        if ($length > 0) {
            $index = count($data) - 1;
            $sumLoansFromMe = $this->moneyLoan->sum("total", ["id_user[=]" => $this->id, "status[is not]" => null, "type[=]" => "FROM_ME", "AND"])->array();
            $data[$index]->net_worth = intval($data[$index]->net_worth) - $sumLoansFromMe;
        }
        return httpResponse($data)->json();
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
