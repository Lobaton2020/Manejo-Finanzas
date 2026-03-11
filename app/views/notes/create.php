<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Finanzas</h4>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-right flex items-center space-x-2">
                            <li class="breadcrumb-item"><a href="javascript:void(0);" class="text-gray-300 hover:text-blue-400">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);" class="text-gray-300 hover:text-blue-400">Notas</a></li>
                            <li class="breadcrumb-item text-gray-400">Crear Nota</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="bg-gray-800 rounded-lg border border-gray-700">
                        <div class="p-6">
                            <h4 class="mt-0 header-title text-center text-white text-xl font-bold mb-6">Crear Nota</h4>
                            <?php echo renderMessage("error") ?>
                            <form action="<?php echo route("note/store") ?>" method="POST">
                                <div class="row">
                                    <div class="col-md-2"></div>
                                    <div class="col-md-8">
                                        <div class="form-group mb-4">
                                            <div class="row">
                                                <div class="col-md-6 mb-4 md:mb-0">
                                                    <label for="total" class="text-gray-300 block mb-2">Monto que quieres anotar: <span class="text-red-500">*</span></label>
                                                    <input onkeyup="formatPrice(event)" type="text" name="total" id="total" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required="" placeholder="ej: 2000000">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-gray-300 block mb-2">Numero formateado</label>
                                                    <input id="number-format" type="text" readonly class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-gray-400 rounded-lg" value="ej: 100,000.00">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group mb-4">
                                            <label class="text-gray-300 block mb-2">Descripcion de tu nota <span class="text-red-500">*</span> </label>
                                            <div>
                                                <textarea name="description" placeholder="Breve descripcion menos de 2000 letras." class="w-full px-3 py-2 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="4"></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group mb-4">
                                            <small class="text-gray-400">
                                                Esto solo son anotaciones, no influye en las graficas.
                                            </small>
                                        </div>
                                        <div class="form-group mb-0">
                                            <div>
                                                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg float-right">
                                                    Enviar datos
                                                </button>
                                                <a href="<?php echo route("note") ?>" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg ml-3 float-right">
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

    </div>
    <?php include_document("layouts.footerbar") ?>

</div>
