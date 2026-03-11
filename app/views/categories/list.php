<div class="row">
    <div class="content-page w-full">
        <div class="content">
            <div class="container-fluid">
                <div class="page-title-box">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <h4 class="page-title">Finanzas</h4>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-right flex items-center space-x-2">
                                <li class="breadcrumb-item"><a href="javascript:void(0);" class="text-gray-300 hover:text-blue-400">Inicio</a></li>
                                <li class="breadcrumb-item"><a href="javascript:void(0);" class="text-gray-300 hover:text-blue-400">Categorias de egresos</a></li>
                                <li class="breadcrumb-item text-gray-400">Listado</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="bg-gray-800 rounded-lg border border-gray-700 p-4">
                            <div class="m-3">
                                <div class="float-left">
                                    <h4 class="mt-0 text-white mb-3 header-title">Listado de categorias de egresos</h4>
                                </div>
                                <div class="float-right">
                                    <button type="button" class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-500 text-white" data-toggle="modal" data-target="#myModal"><i class="mdi mdi-plus"></i> Añadir categoria de egreso </button>
                                </div>
                            </div>
                            <div class="card-body bg-gray-800 rounded-lg">
                                <?php echo renderMessage("success") ?>
                                <?php echo renderMessage("info") ?>
                                <?php echo renderMessage("error") ?>
                                <?php echo renderJumbotron($categories, "No tienes categorias de egresos almacenados.") ?>
                                <?php
                                $head = ["#", "Tipo de Egreso", "Categoria", "Estado", "Fecha"];
                                $fillable = ["id_category", "outflow_type", "name", "status", "create_at"];

                                echo make_table($head, $fillable, $categories, ["redirect" => "category", "use" => "delete"]);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
        <?php include_document("layouts.footerbar") ?>

    </div>


    <!-- sample modal content -->
    <div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-gray-800 border border-gray-700 rounded-lg">
                <div class="modal-header border-b border-gray-700 flex items-center justify-between p-4">
                    <h5 class="modal-title mt-0 text-white" id="myModalLabel">Añadir categoria</h5>
                    <button type="button" class="close text-gray-300 hover:text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div id="show-message"></div>
                    <form id="form-item" onsubmit="saveCategoryEgress(event)" action="">
                        <?php if (empty($outflow_types)) : ?>
                            <p class="border rounded-full px-4 py-2 text-gray-300">
                                No tienes tipos moviemientos de egresos agregados o activos.
                                <a href="<?php echo route("moveType/create") ?>" class="underline">Añadir </a></p>
                        <?php else : ?>
                            <div id=""></div>
                            <div class="form-group mb-4">
                                <label for="inflow_type" class="text-gray-300 block mb-2">Tipo de salida del dinero <span class="text-red-500">*</span></label>
                                <select class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" name="id_outflow_type" id="inflow_type" required>
                                    <option value="">--Seleccione--</option>
                                    <?php foreach ($outflow_types  as $outflow_type) : ?>
                                        <option value="<?php echo $outflow_type->id_outflow_type ?>"><?php echo $outflow_type->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="form-group mb-4">
                            <label class="text-gray-300 block mb-2">Nueva categoria: <span class="text-red-500">*</span></label>
                            <div>
                                <input type="text" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" name="name" required="" placeholder="Agrega el nombre">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-t border-gray-700 p-4 flex justify-end gap-3">
                    <button type="button" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg" data-dismiss="modal">Cerrar</button>
                    <button type="submit" form="form-item" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</div>
