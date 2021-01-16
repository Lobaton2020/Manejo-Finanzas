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
                            <li class="breadcrumb-item active">Crear Nota</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card m-b-30">
                        <div class="card-body">
                            <h4 class="mt-0 header-title text-center">Crear Nota</h4>
                            <?php echo renderMessage("error") ?>
                            <form action="<?php echo route("note/store") ?>" method="POST">
                                <div class="row">
                                    <div class="col-md-2"></div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="total">Monto que quieres anotar: <span class="text-danger">*</span></label>
                                                    <input onkeyup="formatPrice(event)" type="text" name="total" id="total" class="form-control" required="" parsley-type="email" placeholder="ej: 2000000">
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Numero formateado</label>
                                                    <input id="number-format" type="text" readonly class="form-control" value="ej: 100,000.00">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Descripcion de tu nota <span class="text-danger">*</span> </label>
                                            <div>
                                                <textarea name="description" placeholder="Breve descripcion menos de 2000 letras." class="form-control" rows="4"></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <small class="text-muted">
                                                Esto solo son anotaciones, no influye en las graficas.
                                            </small>
                                        </div>
                                        <div class="form-group">
                                            <div>
                                                <button type="submit" class="btn btn-success waves-effect waves-light">
                                                    Enviar datos
                                                </button>
                                                <a href="<?php echo route("note") ?>" class="btn btn-info waves-effect m-l-5 float-right">
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