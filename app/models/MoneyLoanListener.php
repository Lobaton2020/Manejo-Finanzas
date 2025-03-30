<?php
class MoneyLoanListener extends Orm
{
    public function __construct()
    {
        parent::__construct("moneyloan_notifications");
    }

    public function getNotificationListeners()
    {
        $sql = "SELECT
                mn.id,
                mn.id_money_loan,
                email as email_to,
                username as username_to,
                is_subscription,
                total,
                set_date as expiration_date,
                mn.executed_at
            FROM moneyloan_notifications mn
            JOIN moneyloans m ON m.id_money_loan = mn.id_money_loan
            WHERE is_active = true AND m.status = true  AND (
                is_subscription = 0
                and executed_at is null
                OR executed_at <> set_date
                and set_date = curdate()
            ) AND (
                is_subscription = 1
                and executed_at is null
                OR DATE_FORMAT(executed_at, '%m-%d') <> DATE_FORMAT(set_date, '%m-%d')
                AND DATE_FORMAT(set_date, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
            )";
        $this->querye($sql);
        $this->execute();
        return new JSON($this->fetchAll());
    }

    public function actualizarUltimoLlamado($id)
    {
        $this->update(
            [
                "executed_at" => getCurrentDatetime(),
            ],
            ["id[=]" => $id]
        );
    }
}
