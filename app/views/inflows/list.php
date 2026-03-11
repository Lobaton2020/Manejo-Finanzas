<div class="p-6 ml-64">
    <div class="container-fluid px-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h4 class="text-2xl font-bold text-white">Finanzas</h4>
            </div>
            <div>
                <ol class="flex items-center space-x-2 text-dark-400">
                    <li><a href="javascript:void(0);" class="text-dark-300 hover:text-primary-400">Inicio</a></li>
                    <li class="text-dark-500">/</li>
                    <li><a href="javascript:void(0);" class="text-dark-300 hover:text-primary-400">Finanzas</a></li>
                    <li class="text-dark-500">/</li>
                    <li class="text-primary-400">Listado</li>
                </ol>
            </div>
        </div>

                        <div class="bg-dark-800 rounded-lg border border-dark-700">
                            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h4 class="text-xl font-bold text-white mb-1">Listado de entradas de dinero</h4>
                    </div>
                    <div>
                        <a href="<?php echo route("inflow/create") ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg">
                            <i class="mdi mdi-plus"></i>
                            Añadir Ingresos
                        </a>
                    </div>
                </div>

                <?php echo renderMessage("success") ?>
                <?php echo renderJumbotron($inflows, "No se ha encontrado ingresos", "inflow/create") ?>
                
                <div>
                    <?php foreach ($inflows as $inflow) : ?>
                        <div id="accordion-<?php echo $inflow->id_inflow ?>" class="mb-4">
                            <div class="bg-dark-700 rounded-lg overflow-hidden border border-dark-600">
                                <div class="card-header bg-dark-700 p-4 cursor-pointer" data-toggle="collapse" data-parent="#accordion-<?php echo $inflow->id_inflow ?>" href="#collapse-<?php echo $inflow->id_inflow ?>" aria-expanded="false" aria-controls="collapseThree" id="headingThree">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-4">
                                            <h5 class="text-white font-medium">
                                                <?php echo $inflow->inflow_type->name ?>
                                            </h5>
                                            <span class="px-3 py-1 bg-primary-600 rounded-full text-white text-sm">
                                                <?php echo number_price($inflow->total) ?>
                                            </span>
                                            <span class="text-dark-400 text-sm"><?php echo format_date($inflow->set_date, false) ?></span>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <span class="text-dark-500 text-sm"><?php echo time_ago($inflow->create_at) ?></span>
                                            <i class="mdi mdi-menu-down-outline mdi-24px text-primary-400"></i>
                                        </div>
                                    </div>
                                </div>
                                <div id="collapse-<?php echo $inflow->id_inflow ?>" class="collapse bg-dark-800 p-4" aria-labelledby="headingThree" data-parent="#accordion-<?php echo $inflow->id_inflow ?>">
                                    <h4 class="text-white font-bold mb-4">Destinacion del dinero</h4>
                                    <ul class="space-y-3">
                                        <?php foreach ($inflow->detail as $detail) : ?>
                                            <li class="pb-2 border-b border-dark-700">
                                                <p class="text-dark-300 mb-1"><?php echo $detail->porcents->name ?></p>
                                                <p class="text-white"><?php echo number_price($detail->value) ?> <strong class="text-primary-400"><?php echo $detail->porcent ?>%</strong></p>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php if (empty($inflow->detail)) : ?>
                                            <p class="text-dark-400">Parece que no te propusiste metas de organizacion</p>
                                        <?php endif; ?>
                                    </ul>
                                    <?php if (!empty($inflow->description)) : ?>
                                        <hr class="border-dark-600 my-4">
                                        <p class="text-dark-300"><?php echo $inflow->description ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php include_document("layouts.footerbar") ?>
</div>
