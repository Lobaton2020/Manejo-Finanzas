<?php
require_once "../initializer.php";
require_once "../models/Investment.php";
header('Content-Type: application/json');
try {
    $investment = new Investment();
    $investment->updateExpirationState();
    http_response_code(200);
    echo json_encode(["is_error" => false, "message" => "Proceso finalizado con exito"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["is_error" => true, "message" => $e->getMessage()]);
}