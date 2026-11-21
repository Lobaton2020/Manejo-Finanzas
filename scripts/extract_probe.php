<?php
/**
 * Sonda de linea de comandos para el extractor de Suggested.
 *
 * Uso:
 *   php scripts/extract_probe.php                 # usa la notificacion BBVA de ejemplo
 *   php scripts/extract_probe.php /ruta/texto.txt # usa un archivo con el texto crudo
 *
 * Llama a la API real de MiniMax y muestra el JSON que devuelve el modelo,
 * el parseo, los ids resueltos y el fallback. Sirve para iterar el prompt
 * sin levantar la app.
 */

if (PHP_SAPI !== "cli") {
    exit("Este script es solo de linea de comandos.\n");
}

$root = dirname(__DIR__);
chdir($root);
$_SERVER["HTTP_HOST"] = $_SERVER["HTTP_HOST"] ?? "localhost";

require_once $root . "/app/initializer.php";
require_once $root . "/app/models/OutflowType.php";
require_once $root . "/app/models/Category.php";
require_once $root . "/app/models/Porcent.php";
require_once $root . "/app/services/AiClient.php";
require_once $root . "/app/services/AiExtractor.php";

$sample = <<<'TEXT'
notificacionesBreB - andrespipe021028@gmail.com - Tu envío con llave fue exitoso 1795142792234 - Tu envío con llave fue exitoso
El envío se completó correctamente
Andres felipe Lobaton , tu envío a través de Bre-B fue exitoso. La llave *****8385 recibió el valor que le enviaste.
Detalles de la operación
Fecha y hora
2026/11/19 21:46
Valor enviado
$ 100,00
Persona que recibe
IVAN DARIO LOBATON VIVAS
Entidad que recibe
Nequi
Tipo de llave
Número de celular
Cuenta origen
*****5913
Código de operación
59698741888326238596060642164617832
TEXT;

$text = $sample;
if (isset($argv[1]) && is_file($argv[1])) {
    $text = file_get_contents($argv[1]);
}

$client = new AiClient();
$extractor = new AiExtractor(
    $client,
    new OutflowType(),
    new Category(),
    new Porcent()
);

$idUser = intval(SUGGESTION_USER_ID);

echo "=== Catalogo del usuario {$idUser} ===\n";
echo json_encode($extractor->catalogue($idUser), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n\n";

$start = microtime(true);
$result = $extractor->extract($idUser, $text);
$elapsed = round((microtime(true) - $start) * 1000);

echo "=== Resultado ({$elapsed} ms) ===\n";
echo json_encode([
    "source"       => $result["source"],
    "needs_review" => $result["needs_review"],
    "error"        => $result["error"],
    "data"         => $result["data"],
    "ids"          => $result["ids"],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n\n";

if ($result["raw_response"] !== "") {
    echo "=== Respuesta cruda del modelo ===\n";
    echo $result["raw_response"], "\n";
}
