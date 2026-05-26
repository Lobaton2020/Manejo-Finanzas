<?php
$head = ["#", "Nombre", "Descripcion", "Acciones"];
$fillable = ["id_group_investment", "name", "description"];

$configTable = [
    "use" => "custom",
    "html" => '<a class="dropdown-item" href="#" data-toggle="modal" data-target="#editGroupModal" data-id=":id_group_investment" data-name=":name" data-description=":description">Editar</a>
               <a class="dropdown-item text-danger" href="' . route("investment/groupsDelete/:id_group_investment") . '">Eliminar</a>',
    "html-replace" => ["id_group_investment", "name", "description"],
    "redirect" => "investment/groups",
    "confirm-delete" => true
];

$card_body = renderMessage("success");
$card_body .= renderMessage("error");
$card_body .= make_table($head, $fillable, $groups, $configTable);

$config = [
    "title" => "Gestion de Grupos de Inversiones",
    "subtitle" => "Grupos",
    "active_button" => [
        "title" => "Nuevo Grupo",
        "path" => "#",
        "data-toggle" => "modal",
        "data-target" => "#createGroupModal"
    ]
];

echo wrapper_html($config, $card_body);
?>

<div class="modal fade" id="createGroupModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Grupo</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="<?php echo route("investment/groupsStore") ?>" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Descripcion</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editGroupModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Grupo</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form action="" method="POST" id="editGroupForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="name" id="groupName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Descripcion</label>
                        <textarea name="description" id="groupDescription" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$('#editGroupModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var id = button.data('id');
    var name = button.data('name');
    var description = button.data('description');
    var modal = $(this);
    modal.find('#groupName').val(name);
    modal.find('#groupDescription').val(description);
    modal.find('#editGroupForm').attr('action', '<?php echo route("investment/groupsUpdate") ?>/' + id);
});
</script>