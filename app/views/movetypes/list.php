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
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Tipos movimiento</a></li>
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
                                    <h4 class="mt-0  mb-3 header-title ">Listado de tipos de moviemiento</h4>
                                </div>
                                <div class="float-right">
                                    <button type="button" class="btn btn-success float-right" data-toggle="modal" data-target="#myModal"><i class="mdi mdi-plus "></i> Añadir tipo movimiento</button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php echo renderMessage("success") ?>
                                <?php echo renderMessage("info") ?>
                                <?php echo renderMessage("error") ?>
                                <?php
                                $head = ["#", "Nombre", "Estado", "Fecha"];
                                $fillable = ["id_inflow_type", "name", "status", "create_at"];
                                ?>
                                <div class="w-100">
                                    <!-- Nav tabs -->
                                    <ul class="nav nav-tabs" role="">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-toggle="tab" href="#home" role="tab">
                                                <span class="d-none d-md-block">Tipos movimiento de Ingresos</span><span class="d-block d-md-none"><i class="mdi mdi-home-variant h5"></i></span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#profile" role="tab">
                                                <span class="d-none d-md-block">Tipos movimiento de Egresos</span><span class="d-block d-md-none"><i class="mdi mdi-account h5"></i></span>
                                            </a>
                                        </li>

                                    </ul>

                                    <!-- Tab panes -->
                                    <div class="tab-content">
                                        <div class="tab-pane active p-3" id="home" role="tabpanel">
                                            <p class="mb-0">
                                                <?php echo renderMessage("success") ?>
                                                <?php echo renderMessage("info") ?>
                                                <?php echo renderMessage("error") ?>
                                                <?php echo renderJumbotron($movetypes_inflow, "No hay categorias de Ingresos") ?>
                                                <?php

                                                echo make_table($head, $fillable, $movetypes_inflow, ["redirect" => "moveType", "param-extra" => "inflow", "use" => "delete"]);
                                                ?>
                                            </p>
                                        </div>
                                        <div class="tab-pane p-3" id="profile" role="tabpanel">
                                            <p class="mb-0">
                                                <?php echo renderMessage("success") ?>
                                                <?php echo renderMessage("info") ?>
                                                <?php echo renderMessage("error") ?>
                                                <?php echo renderJumbotron($movetypes_outflow, "No hay categorias de Egresos") ?>
                                                <?php
                                                $head[1] = "Tipo de Egreso";
                                                $fillable[0] = "id_outflow_type";
                                                echo make_table($head, $fillable, $movetypes_outflow, ["redirect" => "moveType", "param-extra" => "outflow", "use" => "delete"]);
                                                ?>
                                            </p>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div> <!-- end col -->


                </div> <!-- end row -->
            </div>
        </div>


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
                <h5 class="modal-title mt-0" id="myModalLabel">Añadir tipo de movimiento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="show-message"></div>
                <form id="form-item" onsubmit="saveTypeMove(event)" action="">
                    <div class="form-group">
                        <label for="id_type_move">Tipo de movimiento<span class="text-danger">*</span></label>
                        <select class="form-control" name="id_type_move" id="id_type_move" required>
                            <option value="">--Seleccione--</option>
                            <option value="1">Ingresos</option>
                            <option value="2">Egresos</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nombre del tipo de movimiento: <span class="text-danger">*</span></label>
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