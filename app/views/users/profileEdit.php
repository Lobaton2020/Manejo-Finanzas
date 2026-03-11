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
                            <li class="breadcrumb-item"><a href="javascript:void(0);" class="text-gray-600 dark:text-gray-400">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);" class="text-gray-600 dark:text-gray-400">Perfil</a></li>
                            <li class="breadcrumb-item active text-gray-800 dark:text-white">Editar</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6">
                <div class="w-full">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md mb-6">
                        <div class="p-6">
                            <h4 class="mt-0 text-xl font-semibold text-center text-gray-800 dark:text-white">Editar mi perfil</h4>
                            <?php echo renderMessage("error") ?>
                            <?php echo renderMessage("success") ?>
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <div class="md:col-span-2"></div>
                                <div class="md:col-span-8">
                                    <form action="<?php echo route("user/updateProfile") ?>" method="POST">
                                        <div class="mb-4">
                                            <label for="id_document_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de documento <span class="text-red-500">*</span></label>
                                            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" name="document_type" id="id_document_type" required>
                                                <option value="">--Seleccione--</option>
                                                <?php foreach ($documents  as $document) : ?>
                                                    <option <?php echo $user->id_document_type == $document->id_document_type ? "selected" : "" ?> value="<?php echo $document->id_document_type ?>"><?php echo $document->name ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-4">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label for="total" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Numero de documento <span class="text-red-500">*</span></label>
                                                    <input type="text" name="document" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required="" value="<?php echo $user->number_document ?>">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nacimiento</label>
                                                    <input type="date" name="born_date" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="<?php echo $user->born_date ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre completo <span class="text-red-500">*</span></label>
                                            <input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" value="<?php echo $user->complete_name ?>">
                                        </div>
                                        <div class="mb-4">
                                            <div class="flex justify-end gap-3">
                                                <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow transition duration-200">
                                                    Enviar datos
                                                </button>
                                                <a href="<?php echo route("main") ?>" class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white font-semibold rounded-lg shadow transition duration-200 ml-3">
                                                    Regresar
                                                </a>
                                            </div>
                                    </form>
                                    <div class="mb-4 mt-4">
                                        <form onsubmit="questionSend(event)" action="<?php echo route("user/disableMyAcount") ?>" method="POST">
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm"><i>Eliminar mi cuenta</i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="md:col-span-2"></div>
                        </div>

                        </div>

                    </div>

                </div>
            </div>


    </div>

</div>
<?php include_document("layouts.footerbar") ?>

</div>