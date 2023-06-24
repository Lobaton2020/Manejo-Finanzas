<?php

$head = ["#", "ID", "Presupuesto", "Tipo Egreso", "Categoria", "Deposito", "Monto", "Descripcion", "Estado", "Esta en presupuesto"];
$fillable = ["id_temporal_budget_outflow", "id_temporal_budget_outflow", "temporal_budget_name", "outfow_type", "category", "porcent", "amount", "description", "status", "is_in_budget"];

$configTable = [
    "html" => '<form method="POST" action="' . route("budget/execOne/:id_temporal_budget_outflow/{$id_temporal_budget}") . '" id="form_save" />
                <button class="dropdown-item" role="button" type="submit" form="form_save"> Prosupuestar </button>
                ',
    "html-replace" => ['id_temporal_budget_outflow'],
    "redirect" => "budget",
    "use" => "delete",
    "btn_delete_delete" => "delete_element",
    "param-extra" => $id_temporal_budget
];


$card_body = renderMessage("success");
$card_body .= renderMessage("info");
$card_body .= renderMessage("error");
$card_body .= renderJumbotron($data, "Parece que no hay ningun elemento del presupuesto actualmente.");
$card_body .= make_table($head, $fillable, $data, $configTable);

$config = [
    "title" => "Listado de elementos del presupuestos",
    "subtitle" => "Presupuestos",
    "active_button" => [
        "title" => "Añadir item",
        "path" => route("outflow/create?is_budget=true&id_temporal_budget={$id_temporal_budget}")
    ]
];

echo wrapper_html($config, $card_body);
?>