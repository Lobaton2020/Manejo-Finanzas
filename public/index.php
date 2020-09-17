<?php

include_once "../app/initializer.php";

if (isset($_COOKIE["num"])) {
    $_COOKIE["num"] = intval($_COOKIE["num"]) + 1;
} else {
    setcookie("num", 1, time() + 600);
}
// $controller = new Controller;
// $modelVisit = $controller->model("countVisit");
// countVisits($modelVisit);
new Router();
