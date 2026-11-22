<?php
/**
 * Diagnostico del servidor web (solo lectura).
 *
 * Vive en la raiz del docroot para que se pueda pedir SIN rewrite
 * (/api-debug.php): si el ruteo esta roto, es la unica forma de entrar.
 *
 * Muestra si el .htaccess esta, si mod_rewrite esta cargado, cual es el
 * docroot y que hay adentro. Borrar este archivo cuando el problema de
 * ruteo este resuelto.
 */
header("Content-Type: application/json; charset=utf-8");

$docroot = (string) ($_SERVER["DOCUMENT_ROOT"] ?? "");
$modules = function_exists("apache_get_modules") ? apache_get_modules() : [];
$htaccess = $docroot . "/.htaccess";

$entries = [];
if ($docroot !== "" && is_dir($docroot)) {
    $entries = array_values(array_diff(scandir($docroot) ?: [], [".", ".."]));
}

$controllers = [];
$controllersDir = $docroot . "/app/controllers";
if (is_dir($controllersDir)) {
    $controllers = array_values(array_diff(scandir($controllersDir) ?: [], [".", ".."]));
}

echo json_encode([
    "server_software"  => $_SERVER["SERVER_SOFTWARE"] ?? null,
    "document_root"    => $docroot,
    "request_uri"      => $_SERVER["REQUEST_URI"] ?? null,
    "script_filename"  => $_SERVER["SCRIPT_FILENAME"] ?? null,
    "remote_addr"      => $_SERVER["REMOTE_ADDR"] ?? null,
    "mod_rewrite"      => in_array("mod_rewrite", $modules, true),
    "modules"          => $modules,
    "htaccess_path"    => $htaccess,
    "htaccess_exists"  => is_file($htaccess),
    "htaccess_readable" => is_readable($htaccess),
    "htaccess_size"    => is_file($htaccess) ? filesize($htaccess) : null,
    "htaccess_head"    => is_readable($htaccess)
        ? substr((string) file_get_contents($htaccess), 0, 300)
        : null,
    "docroot_entries"  => $entries,
    "app_controllers"  => $controllers,
    "rewrite_param"    => $_GET["url"] ?? null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
