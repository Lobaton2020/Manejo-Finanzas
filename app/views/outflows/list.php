<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Finanzas</h4>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-right">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Egresos</a></li>
                            <li class="breadcrumb-item active">Listado</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card m-b-30">
                        <div class="m-3">
                            <div class="float-left">
                                <h4 class="mt-0  mb-3 header-title ">Listado de salidas de dinero</h4>
                            </div>
                            <div class="float-right d-flex align-items-center">
                                <?php if (!empty($filters)): ?>
                                    <a href="javascript:void(0)" class="btn btn-link text-danger btn-sm mr-2 clear-all-filters" style="text-decoration: none;">Limpiar</a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-secondary mr-2" id="btnFilters" data-toggle="modal" data-target="#filterModal">
                                    <i class="mdi mdi-filter"></i> Filtros
                                    <?php if (!empty($filters)): ?>
                                        <span class="badge badge-light ml-1"><?php echo count($filters); ?></span>
                                    <?php endif; ?>
                                </button>
                                <a href="<?php echo route("outflow/create") ?>" class="btn btn-success"><i class="mdi mdi-plus "></i> Añadir Egresos</a>
                            </div>
                        </div>
                        <?php if (!empty($filters)): ?>
                        <div class="px-3 pb-2">
                            <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
                                <span class="text-muted small">Filtros activos:</span>
                                <?php foreach ($filters as $key => $value): ?>
                                    <?php 
                                    $label = '';
                                    $displayValue = $value;
                                    switch($key) {
                                        case 'id_outflow': $label = 'ID'; break;
                                        case 'id_outflow_type': 
                                            $label = 'Tipo Egreso'; 
                                            $displayValue = implode(', ', array_map(function($id) use ($outflowTypes) {
                                                foreach($outflowTypes as $t) if($t->id_outflow_type == $id) return $t->name;
                                                return $id;
                                            }, (array)$value));
                                            break;
                                        case 'id_category': 
                                            $label = 'Categoría'; 
                                            $displayValue = implode(', ', array_map(function($id) use ($categories) {
                                                foreach($categories as $c) if($c->id_category == $id) return $c->name;
                                                return $id;
                                            }, (array)$value));
                                            break;
                                        case 'id_porcent': 
                                            $label = 'Depósito'; 
                                            $displayValue = implode(', ', array_map(function($id) use ($porcents) {
                                                foreach($porcents as $p) if($p->id_porcent == $id) return $p->name;
                                                return $id;
                                            }, (array)$value));
                                            break;
                                        case 'amount_min': $label = 'Monto Mín'; break;
                                        case 'amount_max': $label = 'Monto Máx'; break;
                                        case 'description': $label = 'Descripción'; break;
                                        case 'is_in_budget': $label = 'En Presupuesto'; $displayValue = $value == 1 ? 'Si' : 'No'; break;
                                        case 'status': $label = 'Estado'; $displayValue = $value == 1 ? 'Activo' : 'Inactivo'; break;
                                        case 'date_from': $label = 'Desde'; break;
                                        case 'date_to': $label = 'Hasta'; break;
                                    }
                                    ?>
                                    <span class="badge filter-chip d-inline-flex align-items-center" style="background-color: #e9ecef; color: #495057; padding: 5px 10px; font-size: 0.8rem; font-weight: 500;">
                                        <strong><?php echo htmlspecialchars($label); ?>:</strong><span style="margin-left: 4px;"><?php echo htmlspecialchars($displayValue); ?></span>
                                        <a href="javascript:void(0)" class="remove-filter" data-key="<?php echo $key; ?>" style="text-decoration: none; color: #6c757d; font-size: 0.9rem; font-weight: bold; margin-left: 4px;" title="Quitar filtro">&times;</a>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <?php echo renderMessage("success") ?>
                            <?php echo renderMessage("info") ?>
                            <?php echo renderMessage("error") ?>
                            <?php if (empty($outflows)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-center">No se encontró información</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="text-center text-muted">No se han encontrado egresos. <a href="<?php echo route('outflow/create'); ?>">Añadir egreso</a></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <?php echo make_length_menu(route("outflow"), $pagination['perPage'] ?? 10, $pagination['current'] ?? 1); ?>
                                </div>
                                <?php if (!empty($totalAmount)): ?>
                                <div class="text-right">
                                    <span class="text-muted small">Total filtros: </span>
                                    <strong style="color: #28a745;"><?php echo number_price_without_html($totalAmount, true); ?></strong>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php
                            $head = ["#","ID", "Tipo Egreso", "Categoria", "Deposito", "Monto", "Descripcion","Esta en presupuesto", "Fecha"];
                            $fillable = ["id_outflow","id_outflow", "outfow_type", "category", "porcent", "amount", "description","is_in_budget", "set_date"];
                            echo make_table($head, $fillable, $outflows, ["redirect" => "outflow", "use" => "btn_delete_delete", "btn_delete_delete" => "delete", "datatable" => false]);
                            ?>
                            <?php if (isset($pagination)): ?>
                                <?php echo make_pagination($pagination['current'], $pagination['total'], route("outflow"), $pagination['perPage'], $pagination['totalRecords']); ?>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div> <!-- end col -->


            </div> <!-- end row -->
        </div>
        <!-- container-fluid -->

    </div>
    <?php include_document("layouts.footerbar") ?>

</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Filtrar Egresos</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="filterForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="filterId">ID Egreso</label>
                            <input type="number" class="form-control" id="filterId" name="id_outflow" value="<?php echo $filters['id_outflow'] ?? ''; ?>" placeholder="ID exacto">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="filterDescription">Descripción</label>
                            <input type="text" class="form-control" id="filterDescription" name="description" value="<?php echo $filters['description'] ?? ''; ?>" placeholder="Buscar en descripción">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="filterOutflowType">Tipo de Egreso</label>
                            <select class="form-control select2" id="filterOutflowType" name="id_outflow_type[]" multiple>
                                <option value=""></option>
                                <?php foreach ($outflowTypes as $type): ?>
                                    <option value="<?php echo $type->id_outflow_type; ?>" <?php echo (isset($filters['id_outflow_type']) && in_array($type->id_outflow_type, $filters['id_outflow_type'])) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="filterCategory">Categoría</label>
                            <select class="form-control select2-search" id="filterCategory" name="id_category[]" multiple>
                                <option value=""></option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat->id_category; ?>" <?php echo (isset($filters['id_category']) && in_array($cat->id_category, $filters['id_category'])) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="filterPorcent">Depósito</label>
                            <select class="form-control select2" id="filterPorcent" name="id_porcent[]" multiple>
                                <option value=""></option>
                                <?php foreach ($porcents as $por): ?>
                                    <option value="<?php echo $por->id_porcent; ?>" <?php echo (isset($filters['id_porcent']) && in_array($por->id_porcent, $filters['id_porcent'])) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($por->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="filterAmountMin">Monto Mínimo</label>
                            <input type="number" step="0.01" class="form-control" id="filterAmountMin" name="amount_min" value="<?php echo $filters['amount_min'] ?? ''; ?>" placeholder="0.00">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="filterAmountMax">Monto Máximo</label>
                            <input type="number" step="0.01" class="form-control" id="filterAmountMax" name="amount_max" value="<?php echo $filters['amount_max'] ?? ''; ?>" placeholder="0.00">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="quickDateRange">Rango de fecha</label>
                            <select class="form-control" id="quickDateRange">
                                <option value="">Personalizado</option>
                                <option value="today">Hoy</option>
                                <option value="yesterday">Ayer</option>
                                <option value="last2days">Últimos 2 días</option>
                                <option value="last7days">Última semana</option>
                                <option value="last30days">Último mes</option>
                                <option value="last90days">Últimos 3 meses</option>
                                <option value="last6months">Últimos 6 meses</option>
                                <option value="current_month">Mes actual</option>
                                <option value="last_year_full">Año pasado (ene-dic)</option>
                                <option value="lastyear">Últimos 12 meses</option>
                                <option value="last2years">Últimos 2 años</option>
                                <option value="last3years">Últimos 3 años</option>
                                <option value="year_to_date">Año actual (YTD)</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="filterDateFrom">Desde</label>
                            <input type="date" class="form-control" id="filterDateFrom" name="date_from" value="<?php echo $filters['date_from'] ?? ''; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="filterDateTo">Hasta</label>
                            <input type="date" class="form-control" id="filterDateTo" name="date_to" value="<?php echo $filters['date_to'] ?? ''; ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>En Presupuesto</label>
                            <div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="is_in_budget" id="isInBudgetAll" value="" <?php echo (!isset($filters['is_in_budget']) || $filters['is_in_budget'] === '') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="isInBudgetAll">Todos</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="is_in_budget" id="isInBudgetYes" value="1" <?php echo (isset($filters['is_in_budget']) && $filters['is_in_budget'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="isInBudgetYes">Si</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="is_in_budget" id="isInBudgetNo" value="0" <?php echo (isset($filters['is_in_budget']) && $filters['is_in_budget'] == 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="isInBudgetNo">No</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="clearFilters">Limpiar Todo</button>
                <button type="button" class="btn btn-primary" id="applyFilters">Aplicar Filtros</button>
            </div>
        </div>
    </div>
</div>