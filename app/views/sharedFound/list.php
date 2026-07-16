<?php

$monthsNames = [1=>"Enero",2=>"Febrero",3=>"Marzo",4=>"Abril",5=>"Mayo",6=>"Junio",7=>"Julio",8=>"Agosto",9=>"Septiembre",10=>"Octubre",11=>"Noviembre",12=>"Diciembre"];

foreach ($records as $r) {
    $r->period = $monthsNames[$r->month] . " " . $r->year;
}

$head = ["#", "Periodo", "Andres", "Ivan", "Total"];
$fillable = ["id", "period", "amount_andres", "amount_ivan", "total"];

$card_body = renderMessage("success");
$card_body .= renderMessage("error");
$card_body .= renderJumbotron($records, "No hay registros.");
$card_body .= make_table($head, $fillable, $records, ["redirect" => "sharedFound", "btn_delete_delete" => "delete", "use" => "edit"]);

$config = [
    "title" => "Fondo Compartido",
    "subtitle" => "Administración",
    "active_button" => [
        "title" => "Agregar registro",
        "path" => route("sharedFound/create")
    ]
];

echo wrapper_html($config, $card_body);
