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
                            <li class="breadcrumb-item"><a href="<?= route("main") ?>">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="<?= route("investment") ?>">Inversiones</a></li>
                            <li class="breadcrumb-item active">Editar</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card m-b-30">
                        <div class="card-body">
                            <h4 class="mt-0 header-title text-center">Actualizar inversion</h4>
                            <?php echo renderMessage("error") ?>
                            <?php echo renderMessage("success") ?>
                            <div class="row">
                                <div class="col-md-2"></div>
                                <div class="col-md-8">
                                    <form action="<?php echo route("investment/update/{$data->id_investment}") ?>"
                                        method="POST">
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label>Fecha Inicio</label>
                                                    <input type="date" name="init_date" class="form-control"
                                                        value="<?php echo $data->init_date ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Fecha Final</label>
                                                    <input type="date" name="end_date" class="form-control"
                                                        value="<?php echo $data->end_date ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="state">Estado<span class="text-danger">*</span></label>
                                                    <select class="form-control" name="state" id="state" required>
                                                        <option value="">--Seleccione--</option>
                                                        <?php foreach ($stateList as $item): ?>
                                                            <option <?= $data->state == $item ? "selected" : "" ?>
                                                                value="<?= $item ?>"><?= $item ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="risk_level">Nivel de riesgo<span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-control" name="risk_level" id="risk_level"
                                                        required>
                                                        <option value="">--Seleccione--</option>
                                                        <?php foreach ($riskList as $item): ?>
                                                            <option <?= $data->risk_level == $item ? "selected" : "" ?>
                                                                value="<?= $item ?>"><?= $item ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="real_retribution">Monto ganancia real <span
                                                            class="text-danger">*</span></label>
                                                    <input onkeyup="formatPrice(event)" type="text"
                                                        value="<?= $data->real_retribution ?>" name="real_retribution"
                                                        id="real_retribution" class="form-control" required=""
                                                        parsley-type="email" placeholder="ej: 5000">
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Numero formateado</label>
                                                    <input id="number-format" type="text" readonly=""
                                                        class="form-control" value="ej: 5.000">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label>Porcentaje efectivo anual<span
                                                            class="text-danger">*</span></label>
                                                    <input type="text" name="percent_annual_effective"
                                                        class="form-control"
                                                        value="<?php echo $data->percent_annual_effective ?>">
                                                </div>
                                                <div class="col-md-6">

                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div>
                                                <button type="submit"
                                                    class="btn btn-success waves-effect waves-light float-right">
                                                    Enviar datos
                                                </button>
                                                <a href="<?php echo route("investment") ?>"
                                                    class="btn btn-secondary waves-effect m-l-5">
                                                    Regresar
                                                </a>
                                            </div>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-2">
                            </div>

                        </div>

                    </div>

                </div> <!-- end col -->
            </div> <!-- end row -->
        </div>


    </div>
    <!-- container-fluid -->

</div>
<?php include_document("layouts.footerbar") ?>

</div>