<?php
$head = [
    "ID",
    "#",
    "Descripcion",
    "Categoria",
    "Inversion",
    "Fecha Inicio",
    "Fecha Final",
    "Estado",
    "Riesgo",
    "PAE %",
    "Estimacion COP",
    "Ganacia Real"
];
$fillable = [
    "id_investment",
    "id_investment",
    "description",
    "name",
    "amount",
    "init_date",
    "end_date",
    "state",
    "risk_level",
    "percent_annual_effective",
    "earn_amount",
    "real_retribution"
];

$configTable = [
    "use" => "custom",
    "html" => ' <a class="dropdown-item "  href="' . route("investment/edit/:id_investment") . '" "> Editar </a>
                <a class="dropdown-item question"  href="' . route("investment/hide/:id_investment") . '"> Quitar </a>
                ',
    "html-replace" => ["id_investment", 'name'],
    "redirect" => "investment",
    "verify-state-color-before" => true,
    "verify-risk-color-before" => true,
    "state_colors" => [
        "Creado" => "info",
        "Expirado" => "danger",
        "Cancelado" => "secondary",
        "Activo" => "primary",
        "Completado" => "success",
        "Perdido" => "danger"
    ],
    "risk_color" => [
        "Conservador" => "primary",
        'Moderado' => 'warning',
        'Agresivo' => 'danger'
    ],
    "properties" => [
        "data-type" => "datatable-state-asc"
    ]
];
$card_body = renderMessage("success");
$card_body .= renderMessage("info");
$card_body .= renderMessage("error");
$card_body .= renderJumbotron($data, "Parece que no hay ninguna inversion actualmente.");

$card_body .= make_table($head, $fillable, $data, $configTable);
$config = [
    "title" => "Listado de inversiones hechas",
    "subtitle" => "Inversiones",
    "active_button" => [
        "title" => "Calcular Efectivo Anual %",
        "path" => '#',
        "onclick" => "calculatePercentageAnualEfective()"
    ],
    "statistic_panel" => card_container_statistic_component(
        card_statistic_component(
            $lost->state,
            ["Monto invertido", $lost->invested_amount],
            ["Monto de ganancia", $lost->earned_amount]
        ),
        card_statistic_component(
            $actived->state,
            ["Monto invertido", $actived->invested_amount],
            ["Monto de ganancia", $actived->earned_amount]
        ),
        card_statistic_component(
            $completed->state,
            ["Monto invertido", $completed->invested_amount],
            ["Monto de ganancia", $completed->earned_amount],
            ["Monto de ganancia real", $completed->real_retribution]
        )
    )
];

echo wrapper_html($config, $card_body);
;


?>