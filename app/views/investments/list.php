<?php
$head = [
    "ID",
    "#",
    "Descripcion",
    "Categoria",
    "Grupo",
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
    "group_name",
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

usort($data, function($a, $b) {
    return ($b->group_name === '-' || $b->group_name === '-') ? 1 : strcmp($a->group_name ?? '', $b->group_name ?? '');
});

$groupedData = [];
$hasGroup = false;
foreach ($data as $item) {
    $key = ($item->group_name === '-' || empty($item->group_name)) ? 'sin_grupo' : $item->group_name;
    if (!isset($groupedData[$key])) {
        $groupedData[$key] = ['inversiones' => [], 'total' => 0, 'ganancia' => 0];
    }
    $groupedData[$key]['inversiones'][] = $item;
    $groupedData[$key]['total'] += intval($item->amount);
    $groupedData[$key]['ganancia'] += intval($item->earn_amount);
    if ($key !== 'sin_grupo') $hasGroup = true;
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

if (!empty($groupedData)) {
    $card_body .= '<div id="investmentsTable" class="accordion">';
    $first = true;
    foreach ($groupedData as $groupName => $groupInfo) {
        $isSinGrupo = ($groupName === 'sin_grupo');
        $displayName = $isSinGrupo ? 'Sin Grupo' : $groupName;
        $collapseId = 'collapse_' . ($isSinGrupo ? 'none' : md5($groupName));
        $expanded = $first ? 'show' : '';
        
        $card_body .= '<div class="card mb-2">';
        $card_body .= '<div class="card-header" id="heading_' . $collapseId . '">';
        $card_body .= '<h5 class="mb-0">';
        if (!$isSinGrupo) {
            $card_body .= '<button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#' . $collapseId . '">';
            $card_body .= '<i class="mdi mdi-folder-outline mr-1"></i> ' . htmlspecialchars($displayName);
            $card_body .= ' <span class="badge badge-primary">' . count($groupInfo['inversiones']) . '</span>';
            $card_body .= ' <span class="badge badge-success ml-2">$' . number_format($groupInfo['total'], 0, ',', '.') . '</span>';
            $card_body .= '</button>';
        } else {
            $card_body .= '<button class="btn btn-link collapsed" type="button" data-toggle="collapse" data-target="#' . $collapseId . '">';
            $card_body .= '<i class="mdi mdi-folder-off-outline mr-1"></i> Sin Asignar';
            $card_body .= ' <span class="badge badge-secondary">' . count($groupInfo['inversiones']) . '</span>';
            $card_body .= ' <span class="badge badge-warning ml-2">$' . number_format($groupInfo['total'], 0, ',', '.') . '</span>';
            $card_body .= '</button>';
        }
        $card_body .= '</h5>';
        $card_body .= '</div>';
        
        $card_body .= '<div id="' . $collapseId . '" class="collapse ' . $expanded . '" data-parent="#investmentsTable">';
        $card_body .= '<div class="card-body p-0">';
        $card_body .= make_table($head, $fillable, $groupInfo['inversiones'], $configTable);
        $card_body .= '</div>';
        $card_body .= '</div>';
        
        $card_body .= '</div>';
        $first = false;
    }
    $card_body .= '</div>';
} else {
    $card_body .= make_table($head, $fillable, $data, $configTable);
}

$config = [
    "title" => "Listado de inversiones hechos",
    "subtitle" => "Inversiones",
    "buttons_group" => [
        [
            "title" => "Grupos",
            "path" => "#",
            "icon" => "mdi mdi-folder-outline",
            "data-toggle" => "modal",
            "data-target" => "#groupsModal"
        ],
        [
            "title" => "Calcular Efectivo Anual %",
            "path" => '#',
            "onclick" => "calculatePercentageAnualEfective()"
        ]
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
?>

<div class="modal fade" id="groupsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Grupos de Inversiones</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (empty($groups)): ?>
                    <p class="text-muted">No hay grupos creados.</p>
                <?php else: ?>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nombre</th>
                                <th>Descripcion</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groups as $g): ?>
                            <tr>
                                <td><?= $g->id_group_investment ?></td>
                                <td><?= htmlspecialchars($g->name) ?></td>
                                <td><?= htmlspecialchars($g->description ?? '-') ?></td>
                                <td>
                                    <a href="<?= route("investment/groupsDelete/{$g->id_group_investment}") ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar grupo?')">Eliminar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                
                <hr>
                <h6>Crear Nuevo Grupo</h6>
                <form action="<?php echo route("investment/groupsStore") ?>" method="POST" class="form-inline">
                    <div class="form-group mr-2">
                        <input type="text" name="name" class="form-control" placeholder="Nombre del grupo" required>
                    </div>
                    <div class="form-group mr-2">
                        <input type="text" name="description" class="form-control" placeholder="Descripcion (opcional)">
                    </div>
                    <button type="submit" class="btn btn-success">Crear</button>
                </form>
            </div>
        </div>
    </div>
</div>