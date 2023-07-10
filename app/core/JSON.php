<?php

class JSON
{
    private $datos = [];
    /*
    * @param $data Array de datos u objeto de datos
    */
    public function __construct($data)
    {
        $this->datos = $data;
    }

    public function array()
    {
        return $this->datos;
    }
    public function object()
    {
        return $this->datos;
    }
    public function firstElement()
    {
        return isset($this->datos[0]) ? $this->datos[0] : null;
    }

    public function json()
    {
        return json_encode($this->datos, true);
    }
}
