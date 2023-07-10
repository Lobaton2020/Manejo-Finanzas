<?php
$head = ["#", "ID", "Name", "Total", "Fecha"];
$fillable = ["id_temporal_budget", "id_temporal_budget", "name", "total_amount", "created_at"];

$configTable = [
    "use" => "custom",
    "html" => '<form method="POST" action="' . route("budget/execList/:id_temporal_budget") . '" id="form_save_:id_temporal_budget" />
                <button class="dropdown-item " onsubmit="questionSend(event)" role="button" type="submit" form="form_save_:id_temporal_budget"> Ejecutar presupuesto </button>
                <a class="dropdown-item"  href="' . route("budget/list_outflows/:id_temporal_budget") . '"> Listar presupuesto </a>
                <a id="edit-modal-budget__me" onclick="editTemporalBudget(event)" data-name=":name" data-id=":id_temporal_budget" data-target="#myModal" href="#" type="button" data-toggle="modal" class="dropdown-item" "> Editar </a>
                <a class="dropdown-item question"  href="' . route("budget/delete/:id_temporal_budget") . '"> Eliminar </a>
                ',
    "html-replace" => ["id_temporal_budget", 'name'],
    "redirect" => "budget"
];

$card_body = renderMessage("success");
$card_body .= renderMessage("info");
$card_body .= renderMessage("error");
$card_body .= renderJumbotron($data, "Parece que no hay ningun presupuesto actualmente.");
$card_body .= make_table($head, $fillable, $data, $configTable);

$config = [
    "title" => "Listado de presupuestos hechos",
    "subtitle" => "Presupuestos",
    "active_button" => [
        "title" => "Añadir presupuesto",
        "path" => route("#")
    ]
];

echo wrapper_html($config, $card_body . include_document("budgets/modals/create"), true);
;


?>
