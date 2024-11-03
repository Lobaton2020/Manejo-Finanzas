<?php
class InvestmentRetirement extends Orm
{
    public function __construct()
    {
        parent::__construct("retirement_investments");
    }

    public function consultaSaldosRetirosNoCompletados($id_user)
    {
        $sql = "SELECT
            sum(x.retirement_amount) as retirement_amount,
            sum(x.real_retribution) as real_retribution
            FROM retirement_investments x
        join investments i on i.id_investment = x.id_investment
        where x.id_user = :id_user AND i.state NOT IN (:s_complete)";
        $this->querye($sql);
        $this->bind(":id_user", $id_user);
        $this->bind(":s_complete", Investment::$InvestmentState["COMPLETED"]);
        $this->execute();
        return new JSON($this->fetch());
    }

    public function consultaSaldoRetirosActivos($id_user, $state)
    {
        $sql = "SELECT
            sum(x.retirement_amount) as retirement_amount
            FROM retirement_investments x
        join investments i on i.id_investment = x.id_investment
        where x.id_user = :id_user AND i.state IN (:state)";
        $this->querye($sql);
        $this->bind(":id_user", $id_user);
        $this->bind(":state", $state);
        $this->execute();
        return intval($this->fetch()->retirement_amount);
    }
}