<?php

$head = ["#", "Tipo", "Descripcion", "Total prestado", "Estado", "Fecha Devolucion", "Creado"];
$fillable = ["id_money_loan", "type", "description", "total", "status", "set_date", "create_at"];

$data = [
    "redirect" => "moneyLoan",
    "use" => "delete",
    "btn_delete_delete" => "delete",
    "verify-date-before" => "true"
];

$card_body =  renderMessage("success");
$card_body .=  renderMessage("info");
$card_body .=  renderMessage("error");
$card_body .=  renderJumbotron($loans, "Parece que no hay prestamos actualmente.");
$card_body .=  make_table($head, $fillable, $loans, $data);

$config = [
    "title" => "Listado de prestamos hechos",
    "subtitle" => "Prestamos",
    "active_button" => [
        "title" => "Añadir prestamo",
        "path" => route("moneyLoan/create")
    ]
];

echo wrapper_html($config, $card_body);
