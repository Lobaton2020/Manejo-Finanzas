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
                    <li><a href="javascript:void(0);" class="text-dark-300 hover:text-primary-400">Préstamos</a></li>
                    <li class="text-dark-500">/</li>
                    <li class="text-primary-400">Listado</li>
                </ol>
            </div>
        </div>

        <div class="bg-dark-800 rounded-lg border border-dark-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h4 class="text-xl font-bold text-white mb-1">Listado de préstamos hechos</h4>
                    </div>
                    <div>
                        <a href="<?php echo route("moneyLoan/create") ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg">
                            <i class="mdi mdi-plus"></i>
                            Añadir préstamo
                        </a>
                    </div>
                </div>

                <?php echo renderMessage("success") ?>
                <?php echo renderMessage("info") ?>
                <?php echo renderMessage("error") ?>
                <?php echo renderJumbotron($loans, "Parece que no hay prestamos actualmente.", "moneyLoan/create") ?>

                <?php
                $head = ["#", "Tipo", "Descripcion", "Total prestado", "Estado", "Fecha Devolucion", "Creado"];
                $fillable = ["id_money_loan", "type", "description", "total", "status", "set_date", "create_at"];
                $data = [
                    "use" => "custom",
                    "html" => require URL_APP . "/views/loans/custom/options.php",
                    "html-replace" => ["id_money_loan"],
                    "redirect" => "moneyLoan",
                    "verify-date-before" => "true"
                ];
                echo make_table($head, $fillable, $loans, $data);
                ?>
            </div>
        </div>
    </div>
    <?php include_document("layouts.footerbar") ?>
</div>
