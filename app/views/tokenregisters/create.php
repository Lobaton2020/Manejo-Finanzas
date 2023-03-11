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
                            <li class="breadcrumb-item active">Crear token de registro</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card m-b-30">
                        <div class="card-body">
                            <h4 class="mt-0 header-title text-center">Crear token de registro</h4>
                            <?php echo renderMessage("error") ?>
                            <form action="<?php echo route("tokenRegister/store") ?>" method="POST">
                                <div class="row">
                                    <div class="col-md-2"></div>

                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label for="id_rol">Selecciona el rol que quieres dar: <span class="text-danger">*</span></label>
                                            <select class="form-control " name="id_rol" id="id_rol" required>
                                                <option value="">--Seleccione--</option>
                                                <?php foreach ($rols  as $rol) : ?>
                                                    <option value="<?php echo $rol->id_rol ?>"><?php echo $rol->name ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Descripcion</label>
                                            <div>
                                                <textarea name="description" placeholder="Añade una descripcion para que tengas en cuenta a quien le creas el token." class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div>
                                                <button type="submit" class="btn btn-success waves-effect waves-light float-right">
                                                    Enviar datos
                                                </button>
                                                <a href="<?php echo route("admin/tokens") ?>" class="btn btn-secondary waves-effect m-l-5">
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