<div class="p-6 ml-64">
    <div class="container-fluid px-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h4 class="text-2xl font-bold text-white">Finanzas</h4>
            </div>
            <div>
                <ol class="flex items-center space-x-2 text-gray-400">
                    <li><a href="javascript:void(0);" class="text-gray-300 hover:text-blue-400">Inicio</a></li>
                    <li class="text-gray-500">/</li>
                    <li><a href="javascript:void(0);" class="text-gray-300 hover:text-blue-400">Depositos</a></li>
                    <li class="text-gray-500">/</li>
                    <li class="text-blue-400">Listado</li>
                </ol>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg border border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h4 class="text-xl font-bold text-white mb-1">Listado de depositos</h4>
                    </div>
                    <div>
                        <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg" data-toggle="modal" data-target="#myModal">
                            <i class="mdi mdi-plus"></i>
                            Nuevo deposito
                        </button>
                    </div>
                </div>

                <?php echo renderMessage("success") ?>
                <?php echo renderMessage("info") ?>
                <?php echo renderMessage("error") ?>
                <?php echo renderJumbotron($porcents, "No se ha encontrado depositos") ?>

                <?php
                $head = ["#", "Nombre", "Estado", "Fecha"];
                $fillable = ["id_porcent", "name", "status", "create_at"];
                echo make_table($head, $fillable, $porcents, ["redirect" => "porcent", "use" => "delete"]);
                ?>
            </div>
        </div>
    </div>
    <?php include_document("layouts.footerbar") ?>
</div>

<div id="myModal" class="fixed inset-0 z-50 hidden overflow-auto bg-black bg-opacity-50 flex items-center justify-center" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="relative bg-gray-800 border border-gray-700 rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="flex items-center justify-between p-4 border-b border-gray-700">
            <h5 class="text-lg font-semibold text-white" id="myModalLabel">Añadir deposito</h5>
            <button type="button" class="text-gray-400 hover:text-white transition-colors" data-dismiss="modal" aria-label="Close">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-4">
            <div id="show-message"></div>
            <form id="form-item" onsubmit="savePorcent(event)" action="">
                <div class="mb-4">
                    <label class="block text-gray-300 mb-2">Nueva deposito: <span class="text-red-500">*</span></label>
                    <input type="text" class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" name="name" required="" placeholder="Agrega el nombre">
                </div>
            </form>
        </div>
        <div class="flex justify-end gap-3 p-4 border-t border-gray-700">
            <button type="button" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors" data-dismiss="modal">Cerrar</button>
            <button type="submit" form="form-item" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors">Guardar</button>
        </div>
    </div>
</div>
