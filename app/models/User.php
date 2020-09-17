<?php
class User extends Orm
{
    public function __construct()
    {
        parent::__construct("users");
    }

    public function login($data)
    {
        $user = $this->get("*", ["email[=]" => $data->email, "status[=]" => 1, "AND"])->array();
        if (!empty($user)) {
            if (verify($data->password, $user->password)) {
                unset($user->password);
                return $user;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
