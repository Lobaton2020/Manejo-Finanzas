<div class="accountbg"></div>
<div class="wrapper-page  mb-0">
    <div class="card card-pages shadow-none">
        <div class="card-body">
            <div class="text-center m-t-0 m-b-15">
                <a href=" <?php echo route("main") ?>" class="">
                    <img width="40" src="<?php echo URL_PROJECT ?>assets/img/logo.png"" alt=""> <h5 class=" d-inline">Mis Finanzas</h5></a>
            </div>
            <hr class="mt-n1 mb-n1">
            <h5 class="font-18 text-center">Cambiar la contraseña</h5>
            <?php echo renderMessage("error") ?>
            <?php echo renderMessage("success") ?>
            <?php if (!isset($token_empty)) : ?>
                <form class="form-horizontal m-t-30" action="<?php echo route("auth/changePasswordWithToken") ?>" method="POST">
                    <input type="hidden" name="token" value="<?php echo $token ?>">
                    <div class="form-group">
                        <div class="col-12">
                            <label>Nueva contraseña</label>
                            <input name="password" class="form-control" type="password" required="" minlength="8" placeholder="*********">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-12">
                            <label>Repite contraseña</label>
                            <input name="password_repeat" class="form-control" type="password" required="" minlength="8" placeholder="*********">
                        </div>
                    </div>

                    <div class="form-group text-center m-t-20">
                        <div class="col-12">
                            <button class="btn btn-primary btn-block btn-lg waves-effect waves-light" type="submit">Cambiar contraseña</button>
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