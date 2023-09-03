<?php
class Query extends Orm
{
    public function __construct()
    {
        parent::__construct("queries");
    }

    public function getResumeNetWorthByUserId($id_user)
    {
        $sql = "CALL report_inflows_and_outflows(:id_user)";
        $this->querye($sql);
        $this->bind(":id_user", $id_user);
        $this->execute();
        return new JSON($this->fetchAll());
    }
}
