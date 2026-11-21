<?php
$env = @file_get_contents("./.env");
if(!$env){
    $env = file_get_contents("../../.env");
    if (!$env) {
        exit("ENVIROMENT FILE NOT FOUND");
    }
}
foreach (explode("\n", $env) as $dotenven) {
    $dotenven = trim($dotenven);
    // Se ignoran comentarios y lineas vacias.
    if ($dotenven === "" || strpos($dotenven, "#") === 0 || strpos($dotenven, "=") === false) {
        continue;
    }
    // Limite 2: el valor puede contener "=" (ej. URLs con query string).
    $parts = explode("=", $dotenven, 2);
    $_ENV[$parts[0]] = $parts[1];
}
define("DBHOST", trim($_ENV["DB_HOST"]));
define("DBNAME", trim($_ENV["DB_NAME"]));
define("DBUSER", trim($_ENV["DB_USER"]));
define("DBPASWORD", trim($_ENV["DB_PASWORD"]));
define("DBDRIVER",  trim($_ENV["DB_DRIVER"]));
define("DBCHARSET", trim($_ENV["DB_CHARSET"]));

// --- Integracion con IA (MiniMax) para el modulo "Suggested" ---
// Ver odd/tasks/suggested-outflows-ai.md
define("MINIMAX_API_KEY", trim($_ENV["MINIMAX_API_KEY"] ?? ""));
define("MINIMAX_MODEL", trim($_ENV["MINIMAX_MODEL"] ?? "MiniMax-M3"));
define("MINIMAX_BASE_URL", rtrim(trim($_ENV["MINIMAX_BASE_URL"] ?? "https://api.minimax.io"), "/"));
define("MINIMAX_TIMEOUT", intval($_ENV["MINIMAX_TIMEOUT"] ?? 30));
define("MINIMAX_THINKING", trim($_ENV["MINIMAX_THINKING"] ?? "disabled"));
define("MINIMAX_MAX_TOKENS", intval($_ENV["MINIMAX_MAX_TOKENS"] ?? 4096));

// Usuario dueño de las sugerencias que llegan por el webhook (sin auth).
define("SUGGESTION_USER_ID", intval($_ENV["SUGGESTION_USER_ID"] ?? 1));
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
