<?php

$monthsNames = [1=>"Enero",2=>"Febrero",3=>"Marzo",4=>"Abril",5=>"Mayo",6=>"Junio",7=>"Julio",8=>"Agosto",9=>"Septiembre",10=>"Octubre",11=>"Noviembre",12=>"Diciembre"];

$card_body = '
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card" style="background:#a8d0e1;">
            <div class="card-body text-center">
                <h5 class="card-title text-dark">Total Andres</h5>
                <h3 class="text-dark">$' . number_format($totalAndres) . '</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card" style="background:#e1a8a8;">
            <div class="card-body text-center">
                <h5 class="card-title text-dark">Total Ivan</h5>
                <h3 class="text-dark">$' . number_format($totalIvan) . '</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card" style="background:#a8e1c3;">
            <div class="card-body text-center">
                <h5 class="card-title text-dark">Total General</h5>
                <h3 class="text-dark">$' . number_format($total) . '</h3>
            </div>
        </div>
    </div>
</div>
';

foreach ($records as $r) {
    $r->period = $monthsNames[$r->month] . " " . $r->year;
}

$head = ["#", "Periodo", "Andres", "Ivan", "Total"];
$fillable = ["id", "period", "amount_andres", "amount_ivan", "total"];

$card_body .= renderMessage("success");
$card_body .= renderMessage("error");
$card_body .= renderJumbotron($records, "No hay registros.");
$card_body .= make_table($head, $fillable, $records, ["redirect" => "sharedFound", "btn_delete_delete" => "delete", "use" => "edit", "show_id" => false]);

$config = [
    "title" => "Fondo Compartido",
    "subtitle" => "Andres & Ivan",
    "active_button" => [
        "title" => "Agregar registro",
        "path" => route("sharedFound/create")
    ]
];

echo wrapper_html($config, $card_body);
