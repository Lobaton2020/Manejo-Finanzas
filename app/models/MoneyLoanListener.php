<?php
class MoneyLoanListener extends Orm
{
    public function __construct()
    {
        parent::__construct("moneyloan_notifications");
    }
}
