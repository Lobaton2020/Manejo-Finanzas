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
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Egresos</a></li>
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
                                <h4 class="mt-0  mb-3 header-title ">Listado de salidas de dinero</h4>
                            </div>
                            <div class="float-right">
                                <a href="<?php echo route("outflow/create") ?>" class="btn btn-success float-right"><i class="mdi mdi-plus "></i> Añadir Egresos</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php echo renderMessage("success") ?>
                            <?php echo renderMessage("info") ?>
                            <?php echo renderMessage("error") ?>
                            <?php echo renderJumbotron($outflows, "No se ha encontrado egresos", "outflow/create") ?>
                            <?php
                            $head = ["#","ID", "Tipo Egreso", "Categoria", "Deposito", "Monto", "Descripcion", "Fecha"];
                            $fillable = ["id_outflow","id_outflow", "outfow_type", "category", "porcent", "amount", "description", "set_date"];
                            echo make_table($head, $fillable, $outflows, ["redirect" => "outflow", "use" => "none"]);
                            ?>
                        </div>
                    </div>
                </div> <!-- end col -->


            </div> <!-- end row -->
        </div>
        <!-- container-fluid -->

    </div>
    <?php include_document("layouts.footerbar") ?>

</div>