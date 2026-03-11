<?php

$head = ["#", "Usuario", "Servidor /IP ", "Navegador", "Creado"];
$fillable = ["id_login", "user", "server", "browser", "create_at"];

$card_body =  renderMessage("success");
$card_body .=  renderMessage("info");
$card_body .=  renderMessage("error");
$card_body = '<div class="dark:bg-gray-800 dark:text-gray-100">' . renderJumbotron($loggins, "Hasta el momento ningun usuario ha iniciado sesion.") . '</div>';
$card_body .= make_table($head, $fillable, $loggins, ["use" => "none"]);

$config = [
    "title" => "Listado de ingresos al sistema",
    "subtitle" => "Inicios de sesion",
    "dark_mode" => true
];

echo wrapper_html($config, $card_body);
