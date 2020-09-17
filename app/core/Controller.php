<?php

class Controller extends Authentication
{
    public function __construct()
    {
        // prevent de error repeat session_start() for use in 'views/layouts/..'
        @parent::__construct();
    }
    protected function authentication($type = "PRIMARY")
    {

        switch ($type) {
            case "PRIMARY":
                if (!$this->checkSession()) {
                    $this->redirect("auth");
                    // echo json_encode($this->httpResponseError());
                    exit();
                }
                break;
            case "SECONDARY":
                if ($this->checkSession()) {
                    $this->redirect("main");
                    exit();
                }
                break;
            default;
                exit("Error param. Verify Authentication");
        }
    }
    protected function only_access_admin()
    {
        if ($this->rol["name"] != "Administrador") {
            $this->redirect("main");
            exit();
        }
    }
    public function model($model)
    {
        $model = ucwords($model);
        $modelPath =  URL_APP . "models" . SEPARATOR . $model . ".php";
        if (file_exists($modelPath)) {
            require_once $modelPath;
            if (class_exists($model)) {
                return new $model();
            } else {
                exit("Class of model  '{$model}'  not found, inpath  '{$modelPath}'");
            }
        } else {
            exit("Model '{$model}' not found in the route '{$modelPath}'");
        }
    }



    protected function view($view, $params = [])
    {
        if (file_exists("../app/views/" . $view . ".php")) {
            require_once "../app/views/" . $view . ".php";
        } else {
            exit("Vista no encontrada");
        }
    }

    protected function redirect($path)
    {
        echo "<script>window.location.href = '" . URL_PROJECT . $path . "'</script>";
    }

    public function getId()
    {
        return $this->id;
    }
    public function getRol()
    {
        return $this->rol;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getEmail()
    {
        return $this->email;
    }
    public function getImage()
    {
        return $this->image;
    }
}
