<?php

$head = ["#", "ID", "Presupuesto", "Tipo Egreso", "Categoria", "Deposito", "Monto", "Descripcion", "Estado", "Esta en presupuesto"];
$fillable = ["id_temporal_budget_outflow", "id_temporal_budget_outflow", "temporal_budget_name", "outfow_type", "category", "porcent", "amount", "description", "status", "is_in_budget"];

$configTable = [
    "html" => '<form method="POST" onsubmit="questionSend(event)" action="' . route("budget/execOne/:id_temporal_budget_outflow/{$id_temporal_budget}") . '" id="form_save_:id_temporal_budget_outflow" ></form>
                <button class="dropdown-item " role="button" type="submit" form="form_save_:id_temporal_budget_outflow"> Presupuestar </button>
                <a class="dropdown-item" href="#" onclick="openEditBudgetItemModal(event)" 
                   data-id=":id_temporal_budget_outflow" 
                   data-amount=":amount" 
                   data-description=":description"
                   data-budget="' . $id_temporal_budget . '"> Editar </a>
                ',
    "html-replace" => ['id_temporal_budget_outflow', 'amount', 'description'],
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

<!-- Edit Budget Item Modal -->
<div id="editBudgetItemModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="editBudgetItemModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mt-0" id="editBudgetItemModalLabel">Editar elemento del presupuesto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-edit-budget-item" method="post" action="<?= route("budget/update_element") ?>">
                    <input type="hidden" id="edit_id_temporal_budget_outflow" name="id_temporal_budget_outflow" value="">
                    <input type="hidden" id="edit_id_temporal_budget" name="id_temporal_budget" value="<?= $id_temporal_budget ?>">
                    <div class="form-group">
                        <label>Monto: <span class="text-danger">*</span></label>
                        <input type="number" id="edit_amount" class="form-control" name="amount" required step="0.01" min="0" placeholder="Ingresa el monto">
                    </div>
                    <div class="form-group">
                        <label>Descripcion:</label>
                        <textarea id="edit_description" class="form-control" name="description" rows="3" placeholder="Descripcion opcional"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary waves-effect" data-dismiss="modal">Cancelar</button>
                <button type="submit" form="form-edit-budget-item" class="btn btn-primary waves-effect waves-light">Guardar</button>
            </div>
        </div>
    </div>
</div>
