<div class="ml-64">
    <div class="container-fluid px-6">
        <div class="flex items-center justify-between mb-6 pt-6">
            <div>
                <h4 class="text-2xl font-bold text-white">Finanzas</h4>
            </div>
            <div>
                <ol class="flex items-center space-x-2 text-gray-400">
                    <li><a href="javascript:void(0);" class="text-gray-300 hover:text-blue-400">Inicio</a></li>
                    <li class="text-gray-500">/</li>
                    <li><a href="javascript:void(0);" class="text-gray-300 hover:text-blue-400">Admin</a></li>
                    <li class="text-gray-500">/</li>
                    <li class="text-blue-400">Crear prestamo</li>
                </ol>
            </div>
        </div>

        <div class="grid grid-cols-1">
            <div class="bg-gray-800 rounded-lg border border-gray-700">
                <div class="p-6">
                    <h4 class="text-xl font-bold text-white text-center mb-6">Crear prestamo</h4>
                    <?php echo renderMessage("error") ?>
                    <form action="<?php echo route("moneyLoan/store") ?>" method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-8 gap-4">
                            <div class="md:col-span-2"></div>

                            <div class="md:col-span-4">
                                <div class="mb-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="state" class="block text-gray-300 mb-2">Tipo de prestamo<span class="text-red-500">*</span></label>
                                            <select class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" name="type" id="type" required>
                                                <option value="">--Seleccione--</option>
                                                <option selected value="FROM_ME">Yo presto</option>
                                                <option value="TO_ME">Me prestan</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-gray-300 mb-2">Fecha estimada de devolucion del dinero <span class="text-red-500">*</span></label>
                                            <input type="date" name="set_date" class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="total" class="block text-gray-300 mb-2">Monto que va a prestar: <span class="text-red-500">*</span></label>
                                            <input onkeyup="formatPrice(event)" type="number" name="total" id="total" class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required="" placeholder="ej: 2000000">
                                        </div>
                                        <div>
                                            <label class="block text-gray-300 mb-2">Numero formateado</label>
                                            <input id="number-format" type="text" readonly class="w-full px-3 py-2 bg-gray-600 text-gray-300 border border-gray-600 rounded-lg" value="ej: 100,000.00">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-gray-300 mb-2">Descripcion <span class="text-red-500">*</span> </label>
                                    <textarea name="description" placeholder="Escribe a quien y por que le vas a prestar el dinero." class="w-full px-3 py-2 bg-gray-700 text-white border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" rows="4"></textarea>
                                </div>

                                <div class="mb-4">
                                    <small class="text-gray-400">
                                        Ten en cuenta que el dinero registrado aqui no influira en las graficas pero si en mi resumen de liquidez.
                                    </small>
                                </div>
                                <div class="mb-0">
                                    <div class="flex justify-end gap-3">
                                        <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg transition-colors">
                                            Enviar datos
                                        </button>
                                        <a href="<?php echo route("moneyLoan") ?>" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors">
                                            Regresar
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="md:col-span-2">

                            </div>
                        </div>
                    </form>


                </div>
            </div>
        </div>



    </div>
    <?php include_document("layouts.footerbar") ?>

</div>
