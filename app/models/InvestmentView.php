<?php
class InvestmentView extends Orm
{
    public function __construct()
    {
        parent::__construct("investments_view");
    }

    public function getResumeByState($state)
    {
        $sql = "select coalesce(sum(amount),0) invested_amount,
            coalesce( sum(earn_amount),0) as earned_amount,
            coalesce( sum(real_retribution),0) as real_retribution,
            state
            from investments_view
            where state = :state
            group by state
            union
            select 0 AS invested_amount,
                0 AS earned_amount,
                0 AS real_retribution,
                :state AS state
            limit 1;"
        ;
        $this->querye($sql);
        $this->bind(":state", $state);
        $this->execute();
        return new JSON($this->fetch());
    }

}