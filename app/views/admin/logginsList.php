<?php

$head = ["#", "Usuario", "Servidor /IP ", "Navegador", "Creado"];
$fillable = ["id_login", "user", "server", "browser", "create_at"];

$card_body =  renderMessage("success");
$card_body .=  renderMessage("info");
$card_body .=  renderMessage("error");
$card_body .=  renderJumbotron($loggins, "Hasta el momento ningun usuario ha iniciado sesion.");
$card_body .=  make_table($head, $fillable, $loggins, ["use" => "none"]);

$config = [
    "title" => "Listado de ingresos al sistema",
    "subtitle" => "Inicios de sesion"
];

echo wrapper_html($config, $card_body);
