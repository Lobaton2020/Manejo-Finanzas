<?php

class Redirect
{
    public function with($data, $value)
    {
        $_SESSION["messages"][$data] = $value;
        return $this;
    }
}


function redirect($path)
{
    echo "<script>window.location.href = '" . URL_PROJECT . $path . "'</script>";
    return new Redirect();
}

function addMessage($data, $value)
{
    $_SESSION["messages"][$data] = $value;
}

function sessionExist($key)
{
    return isset($_SESSION["messages"][$key]) ? true : false;
}

function session($key)
{
    $message = $_SESSION["messages"][$key];
    unset($_SESSION["messages"][$key]);
    return $message;
}
