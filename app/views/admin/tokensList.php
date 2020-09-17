<?php

$head = ["#", "Rol", "Descripcion", "Token", "Estado", "Creado"];
$fillable = ["id_token_register", "rol", "description", "token", "status", "create_at"];
$data = [
    "redirect" => "tokenRegister",
    "use" => "delete",
    "btn_delete_delete" => "delete"
];
$card_body =  renderMessage("success");
$card_body .=  renderMessage("info");
$card_body .=  renderMessage("error");
$card_body .=  renderJumbotron($tokens, "Parece que no hay tokens de registro almacenados.");
$card_body .=  make_table($head, $fillable, $tokens, $data);

$config = [
    "title" => "Listado de tokens de registro",
    "subtitle" => "Tokens de registro",
    "active_button" => [
        "title" => "Crear token",
        "path" => route("tokenRegister/create")
    ]
];

echo wrapper_html($config, $card_body);
