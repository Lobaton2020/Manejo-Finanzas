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
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Admin</a></li>
                            <li class="breadcrumb-item active">Editar prestamo</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card m-b-30">
                        <div class="card-body">
                            <h4 class="mt-0 header-title text-center">Editar prestamo</h4>
                            <?php echo renderMessage("error") ?>
                            <form action="<?php echo route("moneyLoan/update/" . $loan->id_money_loan) ?>" method="POST">
                                <div class="row">
                                    <div class="col-md-2"></div>

                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="state">Tipo de prestamo<span class="text-danger">*</span></label>
                                                    <select class="form-control" name="type" id="type" disabled>
                                                        <option value="FROM_ME" <?php echo $loan->type == 'FROM_ME' ? 'selected' : '' ?>>Yo presto</option>
                                                        <option value="TO_ME" <?php echo $loan->type == 'TO_ME' ? 'selected' : '' ?>>Me prestan</option>
                                                    </select>
                                                    <input type="hidden" name="type" value="<?php echo $loan->type ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Fecha estimada de devolucion del dinero <span class="text-danger">*</span></label>
                                                    <div>
                                                        <input type="date" name="set_date" class="form-control" value="<?php echo $loan->set_date ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="total">Monto prestado: <span class="text-danger">*</span></label>
                                                    <input onkeyup="formatPrice(event)" type="text" name="total" id="total" class="form-control" value="<?php echo $loan->total ?>" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Numero formateado</label>
                                                    <input id="number-format" type="text" readonly class="form-control" value="<?php echo number_format($loan->total, 0, ',', '.') ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <script>
                                            document.addEventListener("DOMContentLoaded", function() {
                                                const totalInput = document.querySelector("#total");
                                                const numberFormat = document.querySelector("#number-format");
                                                if (totalInput && numberFormat) {
                                                    let value = parseInt(totalInput.value);
                                                    if (value) {
                                                        numberFormat.value = value.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
                                                    }
                                                }
                                            });
                                        </script>
                                        <div class="form-group">
                                            <label>Descripcion <span class="text-danger">*</span> </label>
                                            <div>
                                                <textarea name="description" placeholder="Escribe a quien y por que le vas a prestar el dinero." class="form-control" rows="4"><?php echo htmlspecialchars($loan->description) ?></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div>
                                                <button type="submit" class="btn btn-success waves-effect waves-light float-right">
                                                    Actualizar datos
                                                </button>
                                                <a href="<?php echo route("moneyLoan") ?>" class="btn btn-secondary waves-effect m-l-5">
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
        <!-- container-fluid -->

    </div>
    <?php include_document("layouts.footerbar") ?>

</div>
