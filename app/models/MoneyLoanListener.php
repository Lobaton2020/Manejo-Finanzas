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
            AND executed_at <> set_date
            and set_date = curdate()
        ) OR (
            is_subscription = 1
            AND executed_at <> set_date
            AND DATE_FORMAT(set_date, '%d') = DATE_FORMAT(CURDATE(), '%d')
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
