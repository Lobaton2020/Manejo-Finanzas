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
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Notas</a></li>
                            <li class="breadcrumb-item active">Editar nota</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card m-b-30">
                        <div class="d-block mx-4 mt-2">
                            <h4 class=" header-title float-left">Consultas SQL</h4>
                            <button class="mt-2 ml-4 btn btn-primary float-right" data-toggle="modal" data-target="#add-querie-sql"><i class="mdi  mdi-content-save "></i> Añadir</button>
                            <button class="mt-2 btn btn-info float-right" id="button-show-queries">Consultas Almacenadas</button>
                        </div>
                        <div class="card-body">
                            <?php echo renderMessage("success") ?>
                            <?php echo renderMessage("error") ?>
                            <div class="">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            >> <label id="description-query"></label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <textarea autofocus="on" id="field-sql" cols="50" rows="8" class="form-control" placeholder="Instrucciones SQL, example: select * from users"></textarea>
                                    </div>
                                    <div class="col-md-12">
                                        <button class="mt-2 btn btn-success float-right" id="button-play"><i class="mdi mdi-play "></i></button>

                                    </div>
                                </div>
                            </div>
                            <div id="loader" style="display:none">
                                <img src="<?php echo URL_ASSETS ?>assets/img/loader.gif" style="width:150px" class="rounded mx-auto d-block">
                            </div>
                            <div id="result-error"></div>
                            <div id="result-data" style="display:none">
                                <h4 class=" header-title text-center">Resultado de Consulta SQL y Grafico</h4>
                            </div>
                            <div id="div-show-graphics"></div>
                        </div>
                    </div>
                </div>
            </div>




        </div>
        <!-- container-fluid -->

    </div>
    <?php include_document("layouts.footerbar") ?>

</div>


<!-- View List Of Querys saveds -->
<div id="add-querie-sql" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mt-0" id="myModalLabel">Añadir Consulta SQL</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="show-message"></div>
                <form id="form-item" onsubmit="handlerSaveQuerySql(event)" action="">
                    <div class="form-group">
                        <label>Descripcion:</label>
                        <div>
                            <textarea autofocus="on" name="descriptionQuerySql" cols="50" rows="3" class="form-control" placeholder="Breve descripcion"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Instrucciones SQL: <span class="text-danger">*</span></label>
                        <textarea autofocus="on" name="querySql" cols="50" rows="6" class="form-control" placeholder="Consulta SQL, example: select * from users"></textarea>
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


<!-- Add new Query to saved -->
<div id="show-queries" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mt-0" id="myModalLabel">Listado de Consultas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="show-message"></div>
                <template id="card-show-queries">
                    <div class="card m-b-30 card-body">
                        <h4 class="card-title font-16 mt-0 description">Card title</h4>
                        <p class="card-text query"></p>
                        <p class="card-text">
                            <small class="text-muted date"></small>
                            <a href="#" class="float-right btn btn-success add-query-form">Usarla</a>
                            <a href="#" class="float-right btn btn-danger delete-query-form mr-2" onclick="return confirm('¿Estas Seguro?')"><i class="mdi mdi-delete"></i></a>
                        </p>
                    </div>
                </template>
                <div id="show-data-result"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary waves-effect" data-dismiss="modal">Cerrar</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->