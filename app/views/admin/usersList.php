<?php

$head = ["#", "Rol", "Nombre", "Correo",  "Documento", "Numero documento", "Estado", "Creado"];
$fillable = ["id_user", "rol", "complete_name", "email", "document", "number_document", "status", "create_at"];

$card_body =  renderMessage("success");
$card_body .=  renderMessage("info");
$card_body .=  renderMessage("error");
$card_body .=  renderJumbotron($users, "Parece que no hay mas usuarios regisrados.");
$card_body .=  make_table($head, $fillable, $users, ["redirect" => "user", "use" => "delete"]);

$config = [
    "title" => "Listado de usuarios",
    "subtitle" => "Usuarios"
];

echo wrapper_html($config, $card_body);
