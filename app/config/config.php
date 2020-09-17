<?php
// constates de la base de datos
define("DBHOST", "localhost");
define("DBNAME", "finances");
define("DBUSER", "root");
define("DBPASWORD", "12345");
define("DBDRIVER", "mysql");
define("DBCHARSET", "utf8");

// define("DBHOST", "sql113.epizy.com");
// define("DBNAME", "epiz_26764125_finanzas");
// define("DBUSER", "epiz_26764125");
// define("DBPASWORD", "QPrAPUzxyPK");
// datos del servidor
define("SEPARATOR", "\\");
define("DOMAIN", $_SERVER["HTTP_HOST"]);
define("URL_APP",  dirname(dirname(__FILE__)) . SEPARATOR);
define("URL_PROJECT",  $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["HTTP_HOST"] . str_replace(basename($_SERVER["PHP_SELF"]), "", $_SERVER["PHP_SELF"]));
define("URL_ASSETS",  $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["HTTP_HOST"] . str_replace(basename($_SERVER["PHP_SELF"]), "", $_SERVER["PHP_SELF"]) . SEPARATOR . "public" . SEPARATOR);
