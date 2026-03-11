<div class="p-6 ml-64">
    <div class="container-fluid px-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h4 class="text-2xl font-bold text-white">Panel de control</h4>
            </div>
            <div>
                <ol class="flex items-center space-x-2 text-dark-400">
                    <li><a href="javascript:void(0);" class="text-dark-300 hover:text-primary-400">Tus Finanzas</a></li>
                    <li class="text-dark-500">/</li>
                    <li class="text-primary-400">Panel de control</li>
                </ol>
            </div>
        </div>
        <?php echo renderMessage("info") ?>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            <div class="bg-dark-800 rounded-xl shadow-lg p-6 border border-dark-700">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-dark-400 text-sm mb-1"><?php echo $allentry["title"] ?></p>
                        <h5 class="text-2xl font-bold text-white show_hide__ammount" amount="<?php echo $allentry["amount"] ?>">$ -'---.--- <span class="text-sm text-dark-400 font-normal">COP</span></h5>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-primary-600 flex items-center justify-center">
                        <i class="mdi mdi-cube-outline text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-dark-800 rounded-xl shadow-lg p-6 border border-dark-700">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-dark-400 text-sm mb-1"><?php echo $allegress["title"] ?></p>
                        <h5 class="text-2xl font-bold text-white show_hide__ammount" amount="<?php echo $allegress["amount"] ?>">$ -'---.--- <span class="text-sm text-dark-400 font-normal">COP</span></h5>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-green-600 flex items-center justify-center">
                        <i class="mdi mdi-briefcase-check text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-dark-800 rounded-xl shadow-lg p-6 border border-dark-700">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-dark-400 text-sm mb-1"><?php echo $allinvestment["title"] ?></p>
                        <h5 class="text-2xl font-bold text-white show_hide__ammount" amount="<?php echo $allinvestment["amount"] ?>">$ -'---.--- <span class="text-sm text-dark-400 font-normal">COP</span></h5>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-yellow-600 flex items-center justify-center">
                        <i class="mdi mdi-tag-text-outline text-white text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-dark-800 rounded-xl shadow-lg p-6 border border-dark-700 cursor-pointer" onclick="handleEditBudget(event)">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-dark-400 text-sm mb-1">Presupuesto <?php echo ucwords(strftime("%B")); ?></p>
                        <div class="w-full bg-dark-700 rounded-full h-2 mb-2" data-toggle="tooltip" data-placement="top" title="<?= $budget["total"] . " - " . $budget["percent"] ?>%">
                            <div class="bg-red-600 h-2 rounded-full" style="width: <?= $budget["percent"] ?>%"></div>
                        </div>
                        <p class="text-dark-400 text-sm"><?php echo $budget["remain"] ?><span class="float-right text-white"><?php echo $budget["budget"] ?></span></p>
                    </div>
                    <div class="w-12 h-12 rounded-lg bg-red-600 flex items-center justify-center">
                        <i class="mdi mdi-buffer text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-dark-800 rounded-xl shadow-lg border border-dark-700">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-xl font-bold text-white">Dinero disponible por deposito</h4>
                        <span class="text-dark-400 text-sm">(Solo los depositos activos)</span>
                    </div>
                    <p class="text-dark-400 mb-4">Representa el dinero que tienes por deposito menos los gastos.</p>
                    <ul>
                        <li class="text-dark-500">Desde siempre</li>
                    </ul>
                    <div id="money-deposit-disponible" class="mt-4"></div>
                </div>
            </div>

            <div class="bg-dark-800 rounded-xl shadow-lg border border-dark-700">
                <div class="p-6">
                    <h4 class="text-xl font-bold text-white mb-4">Patrimonio neto con prestamos</h4>
                    <p class="text-dark-400 mb-4">Representa el dinero que tienes restandole los prestamos que has hecho a otros.</p>
                    <li class="text-dark-500">Desde siempre</li>
                    <div id="money-net-worth-moneyloan" class="mt-4"></div>
                </div>
            </div>

            <div class="bg-dark-800 rounded-xl shadow-lg border border-dark-700">
                <div class="p-6">
                    <h4 class="text-xl font-bold text-white mb-4">Patrimonio neto</h4>
                    <p class="text-dark-400 mb-4">Representa el dinero que tienes y las inversiones que estan activas no cuentas como egreso.</p>
                    <li class="text-dark-500">Desde siempre</li>
                    <div id="money-net-worth" class="mt-4"></div>
                </div>
            </div>

            <div class="bg-dark-800 rounded-xl shadow-lg border border-dark-700">
                <div class="p-6">
                    <h4 class="text-xl font-bold text-white mb-4">Detalle patrimonio</h4>
                    <p class="text-dark-400 mb-4">Representa el dinero que tienes y las inversiones que estan activas no cuentas como egreso.</p>
                    <li class="text-dark-500">Desde siempre</li>
                    <div id="money-net-worth-detail" class="mt-4"></div>
                </div>
            </div>

            <div class="bg-dark-800 rounded-xl shadow-lg border border-dark-700">
                <div class="p-6">
                    <h4 class="text-xl font-bold text-white mb-4">Total dinero gastado por deposito</h4>
                    <p class="text-dark-400 mb-4">Representa todo el dinero que has gastado por cada uno de los depositos existentes.</p>
                    <li class="text-dark-500">Desde siempre</li>
                    <div id="money-deposit-spend" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>

    <footer class="ml-64 py-4 px-6 bg-dark-800 border-t border-dark-700">
        <p class="text-dark-400 text-center">© 2019 - 2020 Stexo <span class="hidden sm:inline"> - Crafted with <i class="mdi mdi-heart text-red-500"></i> by Themesdesign</span>.</p>
    </footer>
</div>

<div id="first-visit" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark-800">
            <div class="modal-header border-b border-dark-700">
                <h5 class="modal-title text-white" id="myModalLabel">Bienvenid@</h5>
                <button type="button" class="close text-dark-300" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="border border-dark-600 rounded-lg p-4" role="alert">
                    <h4 class="alert-heading text-white">Version Beta!</h4>
                    <p class="text-dark-300">Te presentamos la version Beta...</p>
                    <hr class="border-dark-600">
                    <p class="mb-0 text-dark-300">Aqui encontraras la funcionalidades basicas del sistema para poder hacer un oden de tus finanzas.<br>
                        Para que contabilices el dinero y lo manejes de la manera mas optima posible</p>
                </div>
            </div>
            <div class="modal-footer border-t border-dark-700">
                <button type="button" class="px-4 py-2 bg-dark-600 text-white rounded-lg hover:bg-dark-500" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div id="edit-budget" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content bg-dark-800">
            <div class="modal-header border-b border-dark-700">
                <h5 class="modal-title text-white" id="myModalLabel">Editar presupuesto del mes</h5>
                <button type="button" class="close text-dark-300" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="show-message"></div>
                <form id="form-item" onsubmit="updateBudget(event)" action="">
                    <div class="mb-4">
                        <label class="block text-dark-300 mb-2">Actualizar presupuesto: <span class="text-red-500">*</span></label>
                        <input type="number" oninput="formatCurrency(event)" class="w-full bg-dark-700 border border-dark-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary-500" name="total" value="<?= $budget["budget_int"] ?>" required="" placeholder="Añade un valor">
                        <span class="block w-full bg-dark-700 border border-dark-600 rounded-lg px-4 py-2 text-dark-400 mt-2" id="formattedMoney"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-t border-dark-700">
                <button type="button" class="px-4 py-2 bg-dark-600 text-white rounded-lg hover:bg-dark-500" data-dismiss="modal">Cerrar</button>
                <button type="submit" form="form-item" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-500">Guardar</button>
            </div>
        </div>
    </div>
</div>
