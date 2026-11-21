<?php
/**
 * Router para el servidor embebido de PHP.
 *
 * El server embebido no lee el .htaccess de Apache, asi que sin este router
 * las URLs de la app no funcionan: solo `index.php?url=...`. Este script
 * emula el rewrite del .htaccess:
 *
 *   RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]
 *
 * Los archivos que existen en disco (css, js, imagenes) se sirven tal cual.
 */

$root = dirname(__DIR__);
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) ?: "/";
$full = $root . $path;

// Archivos estaticos: los sirve el propio server.
if ($path !== "/" && file_exists($full) && !is_dir($full)) {
    return false;
}

// Directorio con index.php propio.
if (is_dir($full) && file_exists($full . "/index.php")) {
    chdir($full);
    require $full . "/index.php";
    return true;
}

// Todo lo demas va al router de la app.
chdir($root);
if (!isset($_GET["url"])) {
    $_GET["url"] = ltrim($path, "/");
}
require $root . "/index.php";
