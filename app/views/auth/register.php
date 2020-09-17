<div class="accountbg"></div>
<div class="wrapper-page mt-5 mb-0">
    <div class="card card-pages shadow-none">

        <div class="card-body">
            <div class="text-center m-t-0 m-b-15">
                <a href=" <?php echo route("main") ?>" class="">
                    <img width="40" src="<?php echo URL_PROJECT ?>assets/img/logo.png"" alt=""> <h5 class=" d-inline">Mis Finanzas</h5></a>
            </div>
            <hr class="mt-n1 mb-n1">
            <h5 class="font-18 text-center">Registro usuario</h5>
            <?php echo renderMessage("error") ?>
            <?php echo renderMessage("success") ?>
            <?php if (!isset($token_empty)) : ?>
                <form class="form-horizontal m-t-30" action="<?php echo route("auth/storeUser") ?>" method="POST">
                    <input type="hidden" name="token" value="<?php echo $token ?>">
                    <div class="form-group">
                        <div class="col-12">

                            <label for="type_document">Tipo documento <span class="text-danger">*</span></label>
                            <select class="form-control " name="type_document" id="type_document" required>
                                <option value="">--Seleccione--</option>
                                <?php foreach ($type_documents  as $type_document) : ?>
                                    <option value="<?php echo $type_document->id_document_type  ?>"><?php echo $type_document->name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-12">
                            <label>Numero de documento <span class="text-danger">*</span></label>
                            <input class="form-control" name="document" required="" placeholder="Numero de documento">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-12">
                            <label>Nombre completo <span class="text-danger">*</span></label>
                            <input class="form-control" name="name" required="" placeholder="Tu nombre">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-12">
                            <label>Email <span class="text-danger">*</span></label>
                            <input class="form-control" name="email" type="text" required="" placeholder="Email">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-12">
                            <label>Password <span class="text-danger">*</span></label>
                            <input class="form-control" minlength="8" name="password" type="password" required="" placeholder="Password">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-12">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="customCheck1" required>
                                <label class="custom-control-label font-weight-normal" for="customCheck1">Acepto los
                                    <a class="text-decoration-underline" href="#" data-toggle="modal" data-target="#myModal">Terminos y condiciones</a>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group text-center m-t-20">
                        <div class="col-12">
                            <button class="btn btn-primary btn-block btn-lg waves-effect waves-light" type="submit">Registrarme!</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
            <div class="form-groupm-t-30 m-b-0">
                <div class="text-center">
                    <a href="<?php echo route("auth") ?>" class="text-">Quiero iniciar sesion!</a>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- sample modal content -->
<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mt-0" id="myModalLabel">Terminos y condiciones</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul>
                    <li>Seguridad de datos:
                        <ul>
                            <li>Tus datos no se compartiran</li>
                            <li>No usamos cookies</li>
                            <li>Este sitio esta bajo un subdomio</li>
                        </ul>
                    </li>
                    <li>Tus restricciones:
                        <ul>
                            <li>Evita el guardar datos inncesarios</li>
                            <li>No Trates de atentar contra la seguridad del sitio, puedes ser detectado por el sistema</li>
                        </ul>
                    </li>
                </ul>
                <div class="modal-footer py-0">
                    <button type="button" class="btn btn-secondary waves-effect" data-dismiss="modal">Cerrar</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->