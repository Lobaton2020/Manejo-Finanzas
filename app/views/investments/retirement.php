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
                            <li class="breadcrumb-item active">Retirar dinero</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card m-b-30">
                        <div class="card-body">
                            <h4 class="mt-0 header-title text-center">Retirar dinero parcialmente</h4>
                            <?php echo renderMessage("error") ?>
                            <?php echo renderMessage("success") ?>
                            <div class="row">
                                <div class="col-md-2"></div>
                                <div class="col-md-8">
                                    <form
                                        action="<?php echo route("investment/retirementCreate/{$data->id_investment}") ?>"
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
                                                    <label for="retirement_amount">Monto a retirar<span
                                                            class="text-danger">*</span></label>
                                                    <input onkeyup="formatPrice(event)" type="number"
                                                        value="<?= $data->amount ?>" name="retirement_amount"
                                                        id="retirement_amount" class="form-control"
                                                        placeholder="ej: 5000">
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
                                                    <label for="real_retribution">Monto ganancia real <span
                                                            class="text-danger">*</span></label>
                                                    <input onkeyup="formatPrice(event, '#number-format-2')"
                                                        type="number" value="<?= $data->real_retribution ?>"
                                                        name="real_retribution" id="real_retribution"
                                                        class="form-control" required="" parsley-type="email"
                                                        placeholder="ej: 5000">
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Numero formateado</label>
                                                    <input id="number-format-2" type="text" readonly=""
                                                        class="form-control" value="ej: 5.000">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <label for="real_retribution">Descripcion <span
                                                            class="text-danger">*</span></label>
                                                    <textarea name="descripcion" placeholder="Añade una descripcion."
                                                        class="form-control" rows="2"></textarea>
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