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
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Perfil</a></li>
                            <li class="breadcrumb-item active">Editar</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card m-b-30">
                        <div class="card-body">
                            <h4 class="mt-0 header-title text-center">Editar mi perfil</h4>
                            <?php echo renderMessage("error") ?>
                            <?php echo renderMessage("success") ?>
                            <div class="row">
                                <div class="col-md-2"></div>
                                <div class="col-md-8">
                                    <form action="<?php echo route("user/updateProfile") ?>" method="POST">
                                        <div class="form-group">
                                            <label for="id_document_type">Tipo de documento <span class="text-danger">*</span></label>
                                            <select class="form-control" name="document_type" id="id_document_type" required>
                                                <option value="">--Seleccione--</option>
                                                <?php foreach ($documents  as $document) : ?>
                                                    <option <?php echo $user->id_document_type == $document->id_document_type ? "selected" : "" ?> value="<?php echo $document->id_document_type ?>"><?php echo $document->name ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="total">Numero de documento <span class="text-danger">*</span></label>
                                                    <input type="text" name="document" class="form-control" required="" value="<?php echo $user->number_document ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Nacimiento</label>
                                                    <input type="date" name="born_date" class="form-control" value="<?php echo $user->born_date ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div>
                                                <label>Nombre completo <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" value="<?php echo $user->complete_name ?>">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div>
                                                <button type="submit" class="btn btn-success waves-effect waves-light float-right">
                                                    Enviar datos
                                                </button>
                                                <a href="<?php echo route("main") ?>" class="btn btn-secondary waves-effect m-l-5">
                                                    Regresar
                                                </a>
                                            </div>
                                    </form>
                                    <div class="form-group mt-4">
                                        <form onsubmit="questionSend(event)" action="<?php echo route("user/disableMyAcount") ?>" method="POST">
                                            <button type="submit" class="btn btn-search"><i>Eiminar mi cuenta</i></button>
                                        </form>
                                    </div>
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