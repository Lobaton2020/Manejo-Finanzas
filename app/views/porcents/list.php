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
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Depositos</a></li>
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
                                <h4 class="mt-0  mb-3 header-title ">Listado de depositos</h4>
                            </div>
                            <div class="float-right">
                                <button type="button" class="btn btn-success float-right" data-toggle="modal" data-target="#myModal"><i class="mdi mdi-plus "></i> Nuevo deposito </button>
                            </div>
                        </div>
                        <div class="card-body">
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
                </div> <!-- end col -->


            </div> <!-- end row -->
            <!-- container-fluid -->
        </div>

    </div>
    <?php include_document("layouts.footerbar") ?>


</div>
<!-- sample modal content -->
<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mt-0" id="myModalLabel">Añadir deposito</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="show-message"></div>
                <form id="form-item" onsubmit="savePorcent(event)" action="">
                    <div class="form-group">
                        <label>Nueva deposito: <span class="text-danger">*</span></label>
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