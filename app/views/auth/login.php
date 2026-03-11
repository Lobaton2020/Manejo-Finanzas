<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900">
    <div class="w-full max-w-md">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg mt-n3">
            <div class="p-6">
                <div class="text-center m-b-15">
                    <a href="<?php echo route("main") ?>" class="">
                        <img width="40" src="<?php echo URL_ASSETS ?>assets/img/logo.png" alt=""> <h5 class="d-inline text-gray-800 dark:text-white">Mis Finanzas</h5></a>
                </div>
                <hr class="mt-n1 mb-n1 border-gray-300 dark:border-gray-700">
                <h5 class="font-19 text-center text-gray-700 dark:text-gray-200">Iniciar Sesión</h5>
                <form method="POST" class="mt-8" action="<?php echo route("auth/login") ?>">
                    <div class="mb-4">
                        <?php echo renderMessage("error") ?>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Correo</label>
                        <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" name="email" type="text" placeholder="ej: example@gmail.com">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Contraseña</label>
                        <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" name="password" type="password" placeholder="***********">
                    </div>

                    <div class="mt-6">
                        <button class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow transition duration-200" type="submit">Iniciar Sesión</button>
                    </div>

                    <div class="mt-6 text-center">
                        <a href="<?php echo route("auth/recoveryPassword") ?>" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"><i class="fa fa-lock mr-1"></i>¿Olvidaste la contraseña?</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>