<div class="error-bg"></div>
<div class="account-pages">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-8">
                <div class="card shadow-lg">
                    <div class="card-block">
                        <div class="text-center p-3">
                            <?php
                            echo renderMessage("success");
                            echo renderMessage("info");
                            echo renderMessage("error");
                            ?>
                            <?php if (!isset($only_message)) : ?>
                                <h1 class="error-page mt-4"><span><?php echo  isset($type) ? $type : "404"; ?>!</span></h1>
                                <h4 class="mb-4 mt-5"><?php echo  isset($title) ? $title : "Lo sentimos, pagina no encontrada!"; ?></h4>
                                <p class="mb-4"><?php echo  isset($description) ? $description : "Error intenta regresar para continuar con tu accion"; ?></p>
                            <?php endif; ?>
                            <a class="btn btn-primary mb-4 waves-effect waves-light" href="<?php echo route("main") ?>"><i class="mdi mdi-home"></i> Ir al Inicio</a>
                        </div>

                    </div>
                </div>

            </div>
        </div>
        <!-- end row -->
    </div>
</div>