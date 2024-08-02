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
                            <li class="breadcrumb-item active">Crear</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card m-b-30">
                        <div class="card-body">
                            <h4 class="mt-0 header-title text-center">Crear salida de dinero</h4>
                            <?php echo renderMessage("error") ?>
                            <form onsubmit="saveOutflow(event)" action="<?php echo route("{$controller_uri}") ?>" method="POST">
                                <div class="row">
                                    <div class="col-md-8">
                                        <?php if (empty($outflow_types)) : ?>
                                            <p class="border rounded-pill p-2 pl-4">
                                                No tienes tipos de movimientos de egresos agregados o activos.
                                                <a href="<?php echo route("moveType/create") ?>" style="text-decoration:underline">Añadir </a>
                                            </p>
                                            <div id="not-send-form"></div>
                                        <?php else : ?>
                                            <div id=""></div>
                                            <div class="form-group">
                                                <label for="inflow_type">Tipo de salida del dinero <span class="text-danger">*</span></label>
                                                <select onchange="renderCategories(event)" class="form-control" name="id_outflow_type" id="inflow_type" required>
                                                    <option value="">--Seleccione--</option>
                                                    <?php foreach ($outflow_types  as $outflow_type) : ?>
                                                        <option value="<?php echo $outflow_type->id_outflow_type ?>"><?php echo $outflow_type->name ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (empty($porcents)) : ?>
                                            <p class="border borcer-rounded rounded-pill p-2 pl-4">
                                                No existen depositos o los tienes inativos.
                                                <a href="<?php echo route("moveType/create") ?>" style="text-decoration:underline">Añadir </a>
                                            </p>
                                            <div id="not-send-form"></div>
                                        <?php else : ?>
                                            <div id="input-required-hide"></div>
                                            <div class="form-group">
                                                <label for="id_porcent">Deposito <span class="text-danger">*</span></label>
                                                <select class="form-control" name="id_porcent" id="id_porcent" required>
                                                    <option value="">--Seleccione--</option>
                                                    <?php foreach ($porcents  as $porcent) : ?>
                                                        <option value="<?php echo $porcent->id_porcent ?>"><?php echo $porcent->name ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php endif; ?>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="total">Monto a sacar <span class="text-danger">*</span></label>
                                                    <input onkeyup="formatPrice(event)" type="text" name="amount" id="total" class="form-control" required="" parsley-type="email" placeholder="ej: 2000000">
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
                                                <textarea name="description" placeholder="Añade una descripcion." class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>
                                        <? if (!$is_budget): ?>
                                        <div class="form-group">
                                            <label>Registrar la fecha <span class="text-danger">*</span></label>
                                            <div>
                                                <input type="date" value=<?= date('Y-m-d') ?> class="form-control" name="set_date" required="" placeholder="Pon la fecha">
                                            </div>
                                        </div>
                                        <? endif; ?>
                                        <div class="form-group">
                                            <label>Esta en el presupuesto? <span class="text-danger">*</span></label>
                                            <select class="form-control" name="is_in_budget" id="is_in_budget" required>
                                                <option value="1" selected>Si</option>
                                                <option value="0">No</option>
                                            </select>
                                        </div>

                                    </div>
                                    <div class="col-md-4">
                                        <h6>Categorias de egresos:</h6>
                                        <div class="text-right">
                                            <button type="button" class="btn btn-primary " data-toggle="modal" data-target="#myModal"><i class="mdi mdi-plus "></i> Nueva categoria </button>
                                        </div>
                                        <div id="empty">

                                            <h5 class="text-center my-0">Ohh!</h5>
                                            <p class="text-muted text-center ">
                                                Selecciona un tipo de salida de dinero!
                                            </p>

                                        </div>
                                        <div id="elements" class="mt-4 vertical-scroll"></div>
                                        <template id="template">
                                            <div class="form-group row my-0">
                                                <label for="id_category" class="name col-sm-10 col-form-label"></label>
                                                <div class="col-md-2 mt-center">
                                                    <input type="radio" name="id_category" id="id_category" required class="value">
                                                </div>
                                            </div>
                                            <hr style='margin:-1px 0 -1px 0' />
                                        </template>
                                    </div>
                                </div>
                                <div class="form-group mt-2">
                                    <div>
                                        <button type="submit" class="btn btn-success waves-effect waves-light float-right">
                                            Enviar datos
                                        </button>
                                        <a href="<?php echo route("outflow") ?>" class="btn btn-secondary waves-effect m-l-5">
                                            Regresar
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div> <!-- end col -->
                </div> <!-- end row -->
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
                <h5 class="modal-title mt-0" id="myModalLabel">Añadir categoria</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="show-message"></div>
                <form id="form-item" onsubmit="saveCategory(event)" action="">
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