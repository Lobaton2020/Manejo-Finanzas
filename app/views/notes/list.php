<?php

$head = ["#", "Descripcion", "Total ", "Creado"];
$fillable = ["id_note", "description", "total", "create_at"];

foreach ($notes as $note) {
    $aux_description = $note->description;
    $note->description = '
    <div>
        <a data-target="#myModal' . $note->id_note . '" href="#" data-toggle="modal" class="text-blue-400 hover:text-blue-300 underline">' . $note->preview . '</a>
        <div id="myModal' . $note->id_note . '" class="fixed inset-0 z-50 hidden overflow-auto bg-black bg-opacity-50 flex items-center justify-center" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="relative bg-gray-800 border border-gray-700 rounded-lg shadow-xl w-full max-w-4xl mx-4">
                <div class="flex items-center justify-between p-4 border-b border-gray-700">
                    <h5 class="text-lg font-semibold text-white" id="myModalLabel">Detalle</h5>
                    <button type="button" class="text-gray-400 hover:text-white transition-colors" data-dismiss="modal" aria-label="Close">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="p-4">
                        <div class="mb-0">
                            <textarea style="background-color:white; height:16rem" class="w-full px-3 py-2 border-none text-sm text-gray-800" disabled="">' . $aux_description . '</textarea>
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
