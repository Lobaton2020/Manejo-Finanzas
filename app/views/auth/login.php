<div class="accountbg"></div>
<div class="wrapper-page mb-0"">
    <div class=" card card-pages shadow-none mt-n3">
    <div class="card-body">
        <div class="text-center m-t-0 m-b-15">
            <a href=" <?php echo route("main") ?>" class="">
                <img width="40" src="<?php echo URL_ASSETS ?>assets/img/logo.png"" alt=""> <h5 class=" d-inline">Mis Finanzas</h5></a>
        </div>
        <hr class="mt-n1 mb-n1">
        <h5 class=" font-19 text-center">Iniciar Sesión</h5>
        <form method="POST" class="form-horizontal m-t-30" action="<?php echo route("auth/login") ?>">
            <div class="form-group">
                <?php echo renderMessage("error") ?>
            </div>
            <div class="form-group">
                <div class="col-12">
                    <label>Correo</label>
                    <input class="form-control" name="email" type="text"="" placeholder="ej: example@gmail.com">
                </div>
            </div>

            <div class="form-group">
                <div class="col-12">
                    <label>Contraseña</label>
                    <input class="form-control" name="password" type="password"="" placeholder="***********">
                </div>
            </div>
            <!-- 
            <div class="form-group">
                <div class="col-12">
                    <div class="checkbox checkbox-primary">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="customCheck1">
                            <label class="custom-control-label" for="customCheck1"> Recordarme!</label>
                        </div>
                    </div>
                </div>
            </div> -->

            <div class="form-group text-center m-t-20">
                <div class="col-12">
                    <button class="btn btn-primary btn-block btn-lg waves-effect waves-light" type="submit">Iniciar Sesión</button>
                </div>
            </div>

            <div class="form-groupm-t-30 m-b-0">
                <div class="text-center">
                    <a href="<?php echo route("auth/recoveryPassword") ?>" class="textmuted"><i class="fa fa-lock m-r-5"></i>¿Olvidaste la contraseña?</a>
                </div>
            </div>
        </form>
    </div>

</div>
</div>