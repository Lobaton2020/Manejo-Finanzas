<?php
class Notification extends Orm
{
    public function __construct()
    {
        parent::__construct("notifications");
    }

    public function insert($data)
    {
        $data = [
            "id_user" => $data[0],
            "key_notification_type" => $data[1],
            "readed" => 0,
            "create_at" => getCurrentDatetime()
        ];
        if (parent::insert($data)) {
            return true;
        } else {
            return false;
        }
    }
}
