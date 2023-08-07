<?php

$head = ["#", "Descripcion", "Total ", "Creado"];
$fillable = ["id_note", "description", "total", "create_at"];

foreach ($notes as $note) {
    $aux_description = $note->description;
    $note->description = '
    <div>
        <a data-target="#myModal' . $note->id_note . '" href="#" data-toggle="modal" class="float-left link-underline-primary" style="text-decoration: underline">' . $note->preview . '</a>
        <div id="myModal' . $note->id_note . '" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title mt-0" id="myModalLabel">Detalle</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                            <div class="form-group">
                                <textarea style="background-color:white; height:16rem"  class="form-control  text-peque border_none " disabled="">' . $aux_description . '</textarea>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    <div>';
}
$card_body =  renderMessage("success");
$card_body .=  renderMessage("info");
$card_body .=  renderMessage("error");
$card_body .=  renderJumbotron($notes, "No tienes notas.");
$card_body .=  make_table($head, $fillable, $notes, ["redirect" => "note", "btn_delete_delete" => "disable", "use" => "edit"]);

$config = [
    "title" => "Listado de notas",
    "subtitle" => "Notas",
    "active_button" => [
        "title" => "Añadir nota",
        "path" => route("note/create")
    ]
];

echo wrapper_html($config, $card_body);
