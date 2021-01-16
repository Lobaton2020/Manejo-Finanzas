<?php

$head = ["#", "Descripcion", "Total ", "Creado"];
$fillable = ["id_note", "description", "total", "create_at"];

$card_body =  renderMessage("success");
$card_body .=  renderMessage("info");
$card_body .=  renderMessage("error");
$card_body .=  renderJumbotron($notes, "No tienes notas.");
$card_body .=  make_table($head, $fillable, $notes, ["redirect" => "note", "btn_delete_delete" => "disable", "use" => "edit"]);

$config = [
    "title" => "Listado de notas",
    "subtitle" => "Notas",
    "active_button" => [
        "title" => "Añadir nota",
        "path" => route("note/create")
    ]
];

echo wrapper_html($config, $card_body);
