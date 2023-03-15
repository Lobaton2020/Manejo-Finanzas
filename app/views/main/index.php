<div class="content-page">
    <!-- Start content -->
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Panel de control</h4>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-right">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Tus Finanzas</a></li>
                            <li class="breadcrumb-item active">Panel de control</li>
                        </ol>
                    </div>
                </div>
                <!-- end row -->
            </div>
            <!-- end page-title -->
            <?php echo renderMessage("info") ?>

            <div class="row">
                <div class="col-sm-6 col-xl-3">
                    <div class="card">
                        <div class="card-heading p-4">
                            <div class="mini-stat-icon float-right">
                                <i class="mdi mdi-cube-outline bg-primary  text-white"></i>
                            </div>
                            <div>
                                <h5 class="font-16"><?php echo $allentry["title"] ?></h5>
                            </div>
                            <h5 class="mt-4"><?php echo $allentry["amount"] ?></h5>
                            <!-- <div class="progress mt-4" style="height: 4px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                            </div> -->
                            <!-- <p class="text-muted mt-2 mb-0">Previous period<span class="float-right">75%</span></p> -->
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card">
                        <div class="card-heading p-4">
                            <div class="mini-stat-icon float-right">
                                <i class="mdi mdi-briefcase-check bg-success text-white"></i>
                            </div>
                            <div>
                                <h5 class="font-16"><?php echo $allegress["title"] ?></h5>
                            </div>
                            <h5 class="mt-4"><?php echo $allegress["amount"] ?></h5>
                            <!-- <div class="progress mt-4" style="height: 4px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 88%" aria-valuenow="88" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <p class="text-muted mt-2 mb-0">Previous period<span class="float-right">88%</span></p> -->
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card">
                        <div class="card-heading p-4">
                            <div class="mini-stat-icon float-right">
                                <i class="mdi mdi-tag-text-outline bg-warning text-white"></i>
                            </div>
                            <div>
                                <h5 class="font-16"><?php echo $allinvestment["title"] ?></h5>
                            </div>
                            <h5 class="mt-4"><?php echo $allinvestment["amount"] ?></h5>
                            <!-- <div class="progress mt-4" style="height: 4px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 68%" aria-valuenow="68" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <p class="text-muted mt-2 mb-0">Previous period<span class="float-right">68%</span></p> -->
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="card">
                        <div class="card-heading p-4">
                            <div onclick="handleEditBudget(event)" class="mini-stat-icon float-right">
                                <i class="mdi mdi-buffer bg-danger text-white"></i>
                            </div>
                            <div>
                                <h5 class="font-16">Presupuesto <?php echo ucwords(strftime("%B")); ?></h5>
                            </div>
                            <div class="progress mt-4" style="height: 4px;" data-toggle="tooltip" data-placement="top" title="<?= $budget["total"] . " - " . $budget["percent"] ?>%">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $budget["percent"] ?>%" aria-valuenow="<?= $budget["percent"] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <p class="text-muted mt-2 mb-0"><?php echo $budget["remain"] ?><span class="float-right"><?php echo $budget["budget"] ?></span></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- END ROW -->
            <div class="row">
                <div class="col-xl-12">
                    <div class="card m-b-30">
                        <div class="card-body">
                            <h4 class="mt-0 header-title d-inline">Dinero disponible por deposito</h4> <span>(Solo los depositos activos)</span>
                            <p class="sub-title d-inline-block text-truncate w-100">
                                Representa el dinero que tienes por deposito menos los gastos.
                            </p>

                            <li class="text-muted text-decoration-none">
                                Desde siempre
                            </li>
                            <div id="money-deposit-disponible"></div>

                        </div>
                    </div>
                </div> <!-- end col -->
                <div class="col-xl-12">
                    <div class="card m-b-30">
                        <div class="card-body">
                            <h4 class="mt-0 header-title">Total dinero gastado por deposito</h4>
                            <p class="sub-title d-inline-block text-truncate w-100">
                                Representa todo el dinero que has gastado por cada uno de los depositos existentes.
                            </p>
                            <li class="text-muted text-decoration-none">
                                Desde siempre
                            </li>
                            <div id="money-deposit-spend"></div>
                        </div>
                    </div>
                </div> <!-- end col -->

            </div>
        </div>
        <!-- container-fluid -->

    </div>
    <!-- content -->

    <footer class="footer">
        © 2019 - 2020 Stexo <span class="d-none d-sm-inline-block"> - Crafted with <i class="mdi mdi-heart text-danger"></i> by Themesdesign</span>.
    </footer>

</div>
<!-- Modal welcom first visit -->

<div id="first-visit" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mt-0" id="myModalLabel">Bienvenid@</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="border border-rounded p-3" role="alert">
                    <h4 class="alert-heading">Version Beta!</h4>
                    <p>Te presentamos la version Beta...</p>
                    <hr>
                    <p class="mb-0">Aqui encontraras la funcionalidades basicas del sistema para poder hacer un oden de tus finanzas.<br>
                        Para que contabilices el dinero y lo manejes de la manera mas optima posible</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary waves-effect" data-dismiss="modal">Cerrar</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
<!-- Modal edit budget -->
<div id="edit-budget" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mt-0" id="myModalLabel">Editar presupuesto del mes</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="show-message"></div>
                <form id="form-item" onsubmit="updateBudget(event)" action="">
                    <div class="form-group">
                        <label>Actualizar presupuesto: <span class="text-danger">*</span></label>
                        <div>
                            <input type="number" oninput="formatCurrency(event)" class="form-control" name="total" value="<?= $budget["budget_int"] ?>" required="" placeholder="Añade un valor"><br />
                            <span class="form-control" id="formattedMoney"></span>
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