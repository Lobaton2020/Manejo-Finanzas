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
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Prestamos</a></li>
                            <li class="breadcrumb-item active">Configurar notificacion</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card m-b-30">
                        <div class="card-body">
                            <h4 class="mt-0 header-title text-center">Configurar quien recibe la notificacion en vencimiento de forma automatica</h4>
                            <?php echo renderMessage("error") ?>
                            <form action="<?php echo route("moneyLoan/storeListener/{$idMoneyLoan}/{$listener->id}") ?>" method="POST">
                                <div class="row">
                                    <div class="col-md-2"></div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="is_subscription">Pago recurrente:<span class="text-danger">*</span></label>
                                            <select class="form-control " name="is_subscription" id="is_subscription" required>
                                            <option value="">--Seleccione--</option>
                                                <option <?= $listener->is_subscription ? "selected" : "" ?> value="1">Si</option>
                                                <option <?= !$listener->is_subscription ? "selected" : "" ?> value="0">No</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Nombre del destinatario:<span class="text-danger">*</span></label>
                                            <div>
                                                <input type="text" maxlength="255" class="form-control" value="<?= $listener->username ?>" name="username" required="">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Email del destinatario:<span class="text-danger">*</span></label>
                                            <div>
                                                <input type="email" maxlength="255" class="form-control" value="<?= $listener->email ?>" name="email" required="" >
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label for="is_active">Notificacion Activada:<span class="text-danger">*</span></label>
                                            <select class="form-control " name="is_active" id="is_active" required>
                                            <option value="">--Seleccione--</option>
                                            <option <?= $listener->is_active ? "selected" : "" ?> value="1">Si</option>
                                            <option <?= !$listener->is_active ? "selected" : "" ?> value="0">No</option>
                                            </select>
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