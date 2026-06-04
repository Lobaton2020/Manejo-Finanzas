<?php
$env = @file_get_contents("./.env");
if(!$env){
    $env = file_get_contents("../../.env");
    if (!$env) {
        exit("ENVIROMENT FILE NOT FOUND");
    }
}
foreach (explode("\n", $env) as $dotenven) {
    if (strlen($dotenven) > 3) {
        $key = explode("=", $dotenven)[0];
        $value = explode("=", $dotenven)[1];
        $_ENV[$key] = $value;
    }
}
define("DBHOST", trim($_ENV["DB_HOST"]));
define("DBNAME", trim($_ENV["DB_NAME"]));
define("DBUSER", trim($_ENV["DB_USER"]));
define("DBPASWORD", trim($_ENV["DB_PASWORD"]));
define("DBDRIVER",  trim($_ENV["DB_DRIVER"]));
define("DBCHARSET", trim($_ENV["DB_CHARSET"]));
// datos del servidor
$scheme = "http";
if (
    (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ||
    (!empty($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https") ||
    (!empty($_SERVER["HTTP_X_FORWARDED_SSL"]) && $_SERVER["HTTP_X_FORWARDED_SSL"] === "on")
) {
    $scheme = "https";
}
define("SEPARATOR", "/");
define("DOMAIN", $_SERVER["HTTP_HOST"]);
define("URL_APP",  dirname(dirname(__FILE__)) . SEPARATOR);
define("URL_PROJECT",  $scheme . '://' . $_SERVER["HTTP_HOST"]. SEPARATOR);
define("URL_ASSETS",  $scheme . '://' . $_SERVER["HTTP_HOST"] . SEPARATOR . "public" . SEPARATOR);
