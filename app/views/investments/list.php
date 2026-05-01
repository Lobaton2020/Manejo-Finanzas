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
    "Ganacia Real",
    "Dinero retirado"
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
    "real_retribution",
    "retirements_amount"
];

$hasGroupedData = isset($groupedTotals) && isset($groupedTotals["amount"]) && $groupedTotals["amount"] > 0;

function formatCurrency($amount) {
    return "$" . number_format($amount, 0, ',', '.');
}

$configTable = [
    "use" => "custom",
    "html" => ' <a class="dropdown-item "  href="' . route("investment/retirement/:id_investment") . '" "> Retiro parcial </a>
                <a class="dropdown-item "  href="' . route("investment/retiremnetList/:id_investment") . '" "> Ver retiros </a>
                <a class="dropdown-item "  href="' . route("investment/edit/:id_investment") . '" "> Editar </a>
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
    ],
    "show-percentage-real-retribution" => true
];
$card_body = renderMessage("success");
$card_body .= renderMessage("info");
$card_body .= renderMessage("error");
$card_body .= renderJumbotron($data, "Parece que no hay ninguna inversion actualmente.");

if ($hasGroupedData) {
    $groupedItems = [];
    $otherItems = [];
    $activeCategory = $groupedCategory ?? "Compra activo/casa";
    
    foreach ($data as $item) {
        if ($item->state === "Activo" && $item->name === $activeCategory) {
            $groupedItems[] = $item;
        } else {
            $otherItems[] = $item;
        }
    }
    
    $tableData = array_merge($groupedItems, $otherItems);
    
    $card_body .= '<div class="table-responsive">';
    $card_body .= '<table class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">';
    $card_body .= '<thead><tr>';
    foreach ($head as $k => $h) {
        if ($k == 0) continue;
        $card_body .= "<th>{$h}</th>";
    }
    $card_body .= '<th>Actions</th></tr></thead><tbody>';
    
    $totalAmount = 0;
    $totalEarn = 0;
    $groupedCount = count($groupedItems);
    $rowIndex = 0;
    
    foreach ($tableData as $item) {
        $item = (object) $item;
        
        if ($item->state === "Activo" && $item->name === $activeCategory) {
            $totalAmount += floatval($item->amount);
            $totalEarn += floatval($item->earn_amount);
            $rowIndex++;
            
            $card_body .= '<tr style="background-color: #e3f2fd;">';
            $card_body .= '<td>' . htmlspecialchars($item->description) . '</td>';
            $card_body .= '<td>' . htmlspecialchars($item->name) . '</td>';
            $card_body .= '<td>' . formatCurrency($item->amount) . '</td>';
            $card_body .= '<td>' . format_date($item->init_date) . '</td>';
            $card_body .= '<td>' . format_date($item->end_date) . '</td>';
            $card_body .= '<td class="alert alert-primary">' . htmlspecialchars($item->state) . '</td>';
            $card_body .= '<td><p class="badge badge-primary">' . htmlspecialchars($item->risk_level) . '</p></td>';
            $card_body .= '<td>' . $item->percent_annual_effective . '%</td>';
            $card_body .= '<td>' . formatCurrency($item->earn_amount) . '</td>';
            $card_body .= '<td>' . formatCurrency($item->real_retribution) . '</td>';
            $card_body .= '<td>' . formatCurrency($item->retirements_amount) . '</td>';
            $card_body .= '<td>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="mdi mdi-dots-horizontal"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="' . route("investment/retirement/{$item->id_investment}") . '">Retiro parcial</a>
                        <a class="dropdown-item" href="' . route("investment/retiremnetList/{$item->id_investment}") . '">Ver retiros</a>
                        <a class="dropdown-item" href="' . route("investment/edit/{$item->id_investment}") . '">Editar</a>
                        <a class="dropdown-item question" href="' . route("investment/hide/{$item->id_investment}") . '">Quitar</a>
                    </div>
                </div>
            </td>';
            $card_body .= '</tr>';
            
            if ($rowIndex === $groupedCount && $groupedCount > 0) {
                $card_body .= '<tr class="table-success font-weight-bold" style="background-color: #bbdefb;">';
                $card_body .= '<td colspan="2"><strong>*** TOTAL ' . strtoupper($activeCategory) . ' ***</strong></td>';
                $card_body .= '<td>' . formatCurrency($totalAmount) . '</td>';
                $card_body .= '<td colspan="3"></td>';
                $card_body .= '<td>' . formatCurrency($totalEarn) . '</td>';
                $card_body .= '<td colspan="4"></td>';
                $card_body .= '</tr>';
            }
        } else {
            $card_body .= '<tr>';
            $card_body .= '<td>' . htmlspecialchars($item->description) . '</td>';
            $card_body .= '<td>' . htmlspecialchars($item->name) . '</td>';
            $card_body .= '<td>' . formatCurrency($item->amount) . '</td>';
            $card_body .= '<td>' . format_date($item->init_date) . '</td>';
            $card_body .= '<td>' . format_date($item->end_date) . '</td>';
            $card_body .= '<td class="alert alert-' . ($configTable["state_colors"][$item->state] ?? 'secondary') . '">' . htmlspecialchars($item->state) . '</td>';
            $card_body .= '<td><p class="badge badge-' . ($configTable["risk_color"][$item->risk_level] ?? 'secondary') . '">' . htmlspecialchars($item->risk_level) . '</p></td>';
            $card_body .= '<td>' . $item->percent_annual_effective . '%</td>';
            $card_body .= '<td>' . formatCurrency($item->earn_amount) . '</td>';
            $card_body .= '<td>' . formatCurrency($item->real_retribution) . '</td>';
            $card_body .= '<td>' . formatCurrency($item->retirements_amount) . '</td>';
            $card_body .= '<td>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="mdi mdi-dots-horizontal"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="' . route("investment/retirement/{$item->id_investment}") . '">Retiro parcial</a>
                        <a class="dropdown-item" href="' . route("investment/retiremnetList/{$item->id_investment}") . '">Ver retiros</a>
                        <a class="dropdown-item" href="' . route("investment/edit/{$item->id_investment}") . '">Editar</a>
                        <a class="dropdown-item question" href="' . route("investment/hide/{$item->id_investment}") . '">Quitar</a>
                    </div>
                </div>
            </td>';
            $card_body .= '</tr>';
        }
    }
    
    $card_body .= '</tbody></table></div>';
} else {
    $card_body .= make_table($head, $fillable, $data, $configTable);
}

function pie_charts(){
    return ' <div class="col-xl-6">
        <div class="card m-b-30">
            <div class="card-body">
                <h4 class="mt-0 header-title">Porfafolio de inversion</h4>
                <div id="simple-pie-porfolio" class="ct-chart ct-golden-section simple-pie-chart-chartist"></div>
            </div>
        </div>
    </div>
     <div class="col-xl-6">
        <div class="card m-b-30">
            <div class="card-body">
                <h4 class="mt-0 header-title">Nivel de riesgo</h4>
                <div id="simple-pie-risk" class="ct-chart ct-golden-section simple-pie-chart-chartist"></div>
            </div>
        </div>
    </div>
    ';
}

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
            ["Monto de ganancia real", $completed->real_retribution],
            "Este saldo suma montos retirados de estados diferentes de activo y completado",
            [
                "hide-1" => true //Se esconde por que el valor se cacula y al haber retiros va a parecer erroneo, Los retiros no tienen estimacion
            ]
        ),
        pie_charts(),
    )
];

echo wrapper_html($config, $card_body);
;


?>