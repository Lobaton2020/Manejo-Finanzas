<?php

$head = ["#", "Descripcion", "Creado"];
$fillable = ["id_notification", "description", "create_at"];

$card_body =  renderMessage("success");
$card_body .=  renderMessage("info");
$card_body .=  renderMessage("error");
$card_body = '<div class="dark:bg-gray-800 dark:text-gray-100">' . renderJumbotron($notifications, "Parece que no hay notificaciones actualmente.") . '</div>';
$card_body .= make_table($head, $fillable, $notifications, ["use" => "none"]);

$config = [
    "title" => "Listado de notificaciones",
    "subtitle" => "Notificaciones",
    "dark_mode" => true
];

echo wrapper_html($config, $card_body);
