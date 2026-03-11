<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 p-4">
    <div class="w-full max-w-md">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg mt-n3">
            <div class="p-6">
                <div class="text-center m-b-15">
                    <a href="<?php echo route("main") ?>" class="">
                        <img width="40" src="<?php echo URL_ASSETS ?>assets/img/logo.png" alt=""> <h5 class="d-inline text-gray-800 dark:text-white">Mis Finanzas</h5></a>
                </div>
                <hr class="mt-n1 mb-n1 border-gray-300 dark:border-gray-700">
                <h5 class="font-19 text-center text-gray-700 dark:text-gray-200">Cambiar la contraseña</h5>
                <?php echo renderMessage("error") ?>
                <?php echo renderMessage("success") ?>
                <?php if (!isset($token_empty)) : ?>
                    <form class="mt-8" action="<?php echo route("auth/changePasswordWithToken") ?>" method="POST">
                        <input type="hidden" name="token" value="<?php echo $token ?>">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nueva contraseña</label>
                            <input name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" type="password" required="" minlength="8" placeholder="*********">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Repite contraseña</label>
                            <input name="password_repeat" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" type="password" required="" minlength="8" placeholder="*********">
                        </div>

                        <div class="mt-6">
                            <button class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow transition duration-200" type="submit">Cambiar contraseña</button>
                        </div>
                    </form>
                <?php endif; ?>
                <div class="mt-6 text-center">
                    <a href="<?php echo route("auth") ?>" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Quiero iniciar sesion!</a>
                </div>
            </div>
        </div>
    </div>
</div>
