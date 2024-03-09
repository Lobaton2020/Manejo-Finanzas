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
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Admin</a></li>
                            <li class="breadcrumb-item active">Crear prestamo</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card m-b-30">
                        <div class="card-body">
                            <h4 class="mt-0 header-title text-center">Crear prestamo</h4>
                            <?php echo renderMessage("error") ?>
                            <form action="<?php echo route("moneyLoan/store") ?>" method="POST">
                                <div class="row">
                                    <div class="col-md-2"></div>

                                    <div class="col-md-8">
                                    <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="state">Tipo de prestamo<span class="text-danger">*</span></label>
                                                    <select class="form-control" name="type" id="type" required>
                                                        <option value="">--Seleccione--</option>
                                                        <option selected value="FROM_ME">Yo presto</option>
                                                        <option value="TO_ME">Me prestan</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Fecha estimada de devolucion del dinero <span class="text-danger">*</span></label>
                                                    <div>
                                                        <input type="date" name="set_date" class="form-control" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="total">Monto que va a prestar: <span class="text-danger">*</span></label>
                                                    <input onkeyup="formatPrice(event)" type="number" name="total" id="total" class="form-control" required="" parsley-type="email" placeholder="ej: 2000000">
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Numero formateado</label>
                                                    <input id="number-format" type="text" readonly class="form-control" value="ej: 100,000.00">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Descripcion <span class="text-danger">*</span> </label>
                                            <div>
                                                <textarea name="description" placeholder="Escribe a quien y por que le vas a prestar el dinero." class="form-control" rows="4"></textarea>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <small class="text-muted">
                                                Ten en cuenta que el dinero registrado aqui no influira en las graficas pero si en mi resumen de liquides.
                                            </small>
                                        </div>
                                        <div class="form-group">
                                            <div>
                                                <button type="submit" class="btn btn-success waves-effect waves-light float-right">
                                                    Enviar datos
                                                </button>
                                                <a href="<?php echo route("moneyLoan") ?>" class="btn btn-secondary waves-effect m-l-5">
                                                    Regresar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">

                                    </div>
                                </div>
                            </form>


                        </div>
                    </div>
                </div>
            </div>




        </div>
        <!-- container-fluid -->

    </div>
    <?php include_document("layouts.footerbar") ?>

</div>