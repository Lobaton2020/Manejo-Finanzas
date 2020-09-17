<?php

$head = ["#", "Descripcion", "Creado"];
$fillable = ["id_notification", "description", "create_at"];

$card_body =  renderMessage("success");
$card_body .=  renderMessage("info");
$card_body .=  renderMessage("error");
$card_body .=  renderJumbotron($notifications, "Parece que no hay notificaciones actualmente.");
$card_body .=  make_table($head, $fillable, $notifications, ["use" => "none"]);

$config = [
    "title" => "Listado de notificaciones",
    "subtitle" => "Notificaciones"
];

echo wrapper_html($config, $card_body);
