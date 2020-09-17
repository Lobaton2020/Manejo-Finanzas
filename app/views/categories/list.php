<div class="row ">
    <div class="content-page w-100">
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
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Categorias de egresos</a></li>
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
                                    <h4 class="mt-0  mb-3 header-title ">Listado de categorias de egresos</h4>
                                </div>
                                <div class="float-right">
                                    <button type="button" class="btn btn-success float-right" data-toggle="modal" data-target="#myModal"><i class="mdi mdi-plus "></i> Añadir categoria de egreso </button>
                                </div>
                            </div>
                            <div class="card-body">
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
                </div> <!-- end col -->


            </div> <!-- end row -->



        </div>
        <!-- container-fluid -->

    </div>
    <?php include_document("layouts.footerbar") ?>

</div>


<!-- sample modal content -->
<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mt-0" id="myModalLabel">Añadir categoria</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="show-message"></div>
                <form id="form-item" onsubmit="saveCategoryEgress(event)" action="">
                    <?php if (empty($outflow_types)) : ?>
                        <p class="border rounded-pill p-2 pl-4">
                            No tienes tipos moviemientos de egresos agregados o activos.
                            <a href="<?php echo route("moveType/create") ?>" style="text-decoration:underline">Añadir </a></p>
                    <?php else : ?>
                        <div id=""></div>
                        <div class="form-group">
                            <label for="inflow_type">Tipo de salida del dinero <span class="text-danger">*</span></label>
                            <select class="form-control" name="id_outflow_type" id="inflow_type" required>
                                <option value="">--Seleccione--</option>
                                <?php foreach ($outflow_types  as $outflow_type) : ?>
                                    <option value="<?php echo $outflow_type->id_outflow_type ?>"><?php echo $outflow_type->name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Nueva categoria: <span class="text-danger">*</span></label>
                        <div>
                            <input type="text" class="form-control" name="name" required="" placeholder="Agrega el nombre">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary waves-effect" data-dismiss="modal">Cerrar</button>
                <button type="submit" form="form-item" class="btn btn-primary waves-effect waves-light">Guardar</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->