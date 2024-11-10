<?php
$head = [
    "ID",
    "Id inversion",
    "Descripcion",
    "Fecha Inicio",
    "Fecha Final",
    "Dinero retirado",
    "Ganacia Real",
];
$fillable = [
    "id_retirement_investment",
    "id_retirement_investment",
    "descripcion",
    "init_date",
    "end_date",
    "retirement_amount",
    "real_retribution",
];

$configTable = [
    "use" => "none",
    "simple-date-enddate" => true,
    "redirect" => "investment",
    "properties" => [
        "data-type" => "datatable-state-asc"
    ],
    "row-sums" => [
        "retirement_amount",
        "real_retribution",
    ]
];
$card_body = renderMessage("success");
$card_body .= renderMessage("info");
$card_body .= renderMessage("error");
$card_body .= renderJumbotron($data, "Parece que no hay informacion aun.");

$card_body .= make_table($head, $fillable, $data, $configTable);
$config = [
    "title" => "Listado de retiros parciales",
    "subtitle" => "Retiros",
    "active_button" => [
        "title" => "Regresar",
        "path" => '#',
        "onclick" => "window.history.go(-2);",
        "type" => "secondary"
    ],
];

echo wrapper_html($config, $card_body);
;
?>