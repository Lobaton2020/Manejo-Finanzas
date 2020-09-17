<?php

class Authentication
{
    private $checkSession = false;
    protected $id;
    protected $rol;
    protected $name;
    protected $email;
    protected $image;
    protected $document;

    protected function __construct()
    {
        $this->sessionStart();
        $this->initCredentials();
    }
    protected function initCredentials()
    {
        if (isset($_SESSION["activeSession"]) && isset($_SESSION["credentials"])) {
            if ($_SESSION["activeSession"] == true) {
                $this->checkSession = true;
                $this->id = $_SESSION["credentials"]["id"];
                $this->rol = $_SESSION["credentials"]["rol"];
                $this->name = $_SESSION["credentials"]["name"];
                $this->email = $_SESSION["credentials"]["email"];
                $this->image = $_SESSION["credentials"]["image"];
                $this->document = $_SESSION["credentials"]["document"];
            }
        }
    }
    protected function sessionStart()
    {
        session_start();
    }
    protected function checkSession()
    {
        return $this->checkSession;
    }

    protected function setSession($datauser)
    {
        extract($datauser);
        try {
            if (count($datauser) > 3) {
                $_SESSION["credentials"]["id"] = $id;
                $_SESSION["credentials"]["rol"] = $rol;
                $_SESSION["credentials"]["name"] = $name;
                $_SESSION["credentials"]["email"] = $email;
                $_SESSION["credentials"]["document"] = $document;
                $_SESSION["credentials"]["image"] = isset($image) ? $image : null;
                $_SESSION["activeSession"] = true;
            }
            return true;
        } catch (Exception $e) {
            exit("Error " . $e->getMessage());
        }
    }

    protected function updateSession($datauser)
    {
        extract($datauser);
        try {
            if ($this->checkSession) {
                $_SESSION["credentials"]["name"] = $name;
                $_SESSION["credentials"]["email"] = $email;
                $_SESSION["credentials"]["image"] = isset($image) ? $image : null;
            }
            return true;
        } catch (Exception $e) {
            exit("Error " . $e->getMessage());
        }
    }

    protected function destroySession()
    {
        try {
            unset($_SESSION["credentials"]);
            unset($_SESSION["activeSession"]);
            setcookie(session_name(), session_id(), time() - ((60 * 60 * 24 * 180) + 2000), "/");
            session_destroy();
            return true;
        } catch (Exception $e) {
            exit("Error " . $e->getMessage());
        }
    }
}
