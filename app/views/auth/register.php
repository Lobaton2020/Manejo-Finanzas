<div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 p-4">
    <div class="w-full max-w-md">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg mt-n3">
            <div class="p-6">
                <div class="text-center m-b-15">
                    <a href="<?php echo route("main") ?>" class="">
                        <img width="40" src="<?php echo URL_ASSETS ?>assets/img/logo.png" alt=""> <h5 class="d-inline text-gray-800 dark:text-white">Mis Finanzas</h5></a>
                </div>
                <hr class="mt-n1 mb-n1 border-gray-300 dark:border-gray-700">
                <h5 class="font-19 text-center text-gray-700 dark:text-gray-200">Registro usuario</h5>
                <?php echo renderMessage("error") ?>
                <?php echo renderMessage("success") ?>
                <?php if (!isset($token_empty)) : ?>
                    <form class="mt-8" action="<?php echo route("auth/storeUser") ?>" method="POST">
                        <input type="hidden" name="token" value="<?php echo $token ?>">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo documento <span class="text-red-500">*</span></label>
                            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" name="type_document" id="type_document" required>
                                <option value="">--Seleccione--</option>
                                <?php foreach ($type_documents  as $type_document) : ?>
                                    <option value="<?php echo $type_document->id_document_type  ?>"><?php echo $type_document->name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Numero de documento <span class="text-red-500">*</span></label>
                            <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" name="document" required="" placeholder="Numero de documento">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre completo <span class="text-red-500">*</span></label>
                            <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" name="name" required="" placeholder="Tu nombre">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email <span class="text-red-500">*</span></label>
                            <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" name="email" type="text" required="" placeholder="Email">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password <span class="text-red-500">*</span></label>
                            <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" minlength="8" name="password" type="password" required="" placeholder="Password">
                        </div>

                        <div class="mb-4">
                            <div class="flex items-center">
                                <input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600" id="customCheck1" required>
                                <label class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300" for="customCheck1">Acepto los
                                    <a class="text-blue-600 hover:underline dark:text-blue-400" href="#" data-toggle="modal" data-target="#myModal">Terminos y condiciones</a>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow transition duration-200" type="submit">Registrarme!</button>
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

<!-- sample modal content -->
<div id="myModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-white dark:bg-gray-800">
            <div class="modal-header border-b border-gray-200 dark:border-gray-700">
                <h5 class="modal-title mt-0 text-gray-800 dark:text-white" id="myModalLabel">Terminos y condiciones</h5>
                <button type="button" class="close text-gray-500 dark:text-gray-400" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-gray-700 dark:text-gray-300">
                <ul class="list-disc list-inside space-y-2">
                    <li>Seguridad de datos:
                        <ul class="list-disc list-inside ml-4">
                            <li>Tus datos no se compartiran</li>
                            <li>No usamos cookies</li>
                            <li>Este sitio esta bajo un subdomio</li>
                        </ul>
                    </li>
                    <li>Tus restricciones:
                        <ul class="list-disc list-inside ml-4">
                            <li>Evita el guardar datos inncesarios</li>
                            <li>No Trates de atentar contra la seguridad del sitio, puedes ser detectado por el sistema</li>
                        </ul>
                    </li>
                </ul>
                <div class="modal-footer py-0 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
