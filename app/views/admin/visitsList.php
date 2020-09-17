<?php

$head = ["#", "User", "Total visitas", "Ultima visita", "Primer visita"];
$fillable = ["id_count_visit", "user", "count", "update_at", "create_at"];

$card_body =  renderMessage("success");
$card_body .=  renderMessage("info");
$card_body .=  renderMessage("error");
$card_body .=  renderJumbotron($visits, "Parece que no hay registros.");
$card_body .=  make_table($head, $fillable, $visits, ["use" => "none"]);

$config = [
    "title" => "Listado de visitas",
    "subtitle" => "Visitas al sitio"
];

echo wrapper_html($config, $card_body);
