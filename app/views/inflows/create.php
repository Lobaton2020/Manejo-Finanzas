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
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Entradas</a></li>
                            <li class="breadcrumb-item active">Crear</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card m-b-30">
                        <div class="card-body">
                            <h4 class="mt-0 header-title text-center">Crear entrada de dinero</h4>
                            <?php echo renderMessage("error") ?>
                            <form onsubmit="saveInflow(event)" action="<?php echo route("inflow/store") ?>" method="POST">
                                <div class="row">
                                    <div class="col-md-8">
                                    <?php if (empty($inflow_types)) : ?>
                                       <p class="border borcer-rounded rounded-pill p-2 pl-4">No tienes tipos de moviemiento de ingreso agregados o activos. <a href="<?php echo route("moveType/create")?>" style="text-decoration:underline">Añadir </a></p>
                                       <div id="not-send-form"></div>
                                       <?php else : ?>
                                        <div class="form-group">
                                            <label for="inflow_type">Tipo de entrada del dinero <span class="text-danger">*</span></label>
                                            <select class="form-control " name="id_inflow_type" id="inflow_type" required>
                                                <option value="">--Seleccione--</option>
                                                <?php foreach ($inflow_types  as $inflow_type) : ?>
                                                    <option value="<?php echo $inflow_type->id_inflow_type ?>"><?php echo $inflow_type->name ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <?php endif; ?>

                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="total">Monto a registrar <span class="text-danger">*</span></label>
                                                    <input onkeyup="formatPrice(event)" type="text" name="total" id="total" class="form-control" required="" parsley-type="email" placeholder="ej: 2000000">
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Numero formateado</label>
                                                    <input id="number-format" type="text" readonly class="form-control" value="ej: 2,000,000.00">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Descripcion</label>
                                            <div>
                                                <textarea name="description" placeholder="Añade una descripcion." class="form-control" rows="1"></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Registrar la fecha <span class="text-danger">*</span></label>
                                            <div>
                                                <input type="date" class="form-control" value=<?= date('Y-m-d') ?> name="set_date" required="" placeholder="Pon la fecha">
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-4">
                                       <h6>Depositos:</h6>
                                        <div class="text-right">
                                            <button type="button" class="btn btn-primary " data-toggle="modal" data-target="#myModal"><i class="mdi mdi-plus "></i> Nuevo deposito </button>
                                        </div>
                                        <div id="empty">
                                        <?php if(empty($porcents)):?>
                                            <h5 class="text-center mt-3">Opps!</h5>
                                            <p class="text-muted text-center ">
                                                No tienes nada <br> Añade un deposito
                                                </p>
                                        <?endif;?>
                                        </div>
                                        <div id="elements">
                                            <?php foreach ($porcents as $porcent) : ?>
                                                <div class="row">
                                                    <div class="form-group col-6">
                                                        <label for="total"><?php echo $porcent->name ?> <span class="text-danger">*</span></label>
                                                        <input type="number" onkeyup="handleKeyUp(event)" name="porcents[<?php echo $porcent->id_porcent ?>]" class="porcents form-control" required="" parsley-type="email" placeholder="ej: <?php echo rand(1, 60) ?>">
                                                    </div>
                                                    <div class="form-group col-6">
                                                    <label for="total">&nbsp;</label>
                                                        <div id="money_deposit" name="" class="porcents form-control" disabled ></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <h6>A tener en cuenta:</h6>

                                            <ul>
                                                <li>Escribe el porcentaje de dinero para organizarte!</li>
                                                <li>La suma de los porcentajes debe ser 100.</li>
                                            </ul>
                                        <template id="template-porcent">
                                            <div class="form-group">
                                                <label for="total" class="name"><span class="text-danger">*</span></label>
                                                <input type="number" class="porcents form-control value" required="" placeholder="ej: <?php echo rand(1, 60) ?>">
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div>
                                        <button type="submit" class="btn btn-success waves-effect waves-light">
                                            Enviar datos
                                        </button>
                                        <a href="<?php echo route("inflow") ?>" class="btn btn-secondary waves-effect m-l-5 float-right">
                                            Regresar
                                        </a>
                                    </div>
                                </div>
                            </form>


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
                                                    <label>Nuevo deposito: <span class="text-danger">*</span></label>
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


        </div> <!-- end col -->
    </div> <!-- end row -->
</div>
</div>


</div>
<!-- container-fluid -->

</div>
<?php include_document("layouts.footerbar") ?>

</div>
