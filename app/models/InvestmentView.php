<?php
class InvestmentView extends Orm
{
    public function __construct()
    {
        parent::__construct("investments_view");
    }

    public function getResumeByState($state)
    {
        $fieldSum = $state === Investment::$InvestmentState["COMPLETED"] ? 'original_amount' : 'amount';
        $sql = "SELECT COALESCE(SUM({$fieldSum}), 0) AS invested_amount,
            COALESCE(SUM(earn_amount), 0) AS earned_amount,
            COALESCE(SUM(real_retribution + retirement_real_retribution), 0) AS real_retribution,
            :state AS state
            FROM investments_view
            WHERE state = :state
            GROUP BY state";

        $this->querye($sql);
        $this->bind(":state", $state);
        $this->execute();

        $result = $this->fetch();
        if (!$result || ($result->invested_amount ?? 0) == 0) {
            return new JSON([
                'invested_amount' => 0,
                'earned_amount' => 0,
                'real_retribution' => 0,
                'state' => $state
            ]);
        }
        return new JSON($result);
    }

}