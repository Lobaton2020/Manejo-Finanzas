<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Fondo Compartido</h4>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-right">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="<?php echo route("sharedFound") ?>">Fondo Compartido</a></li>
                            <li class="breadcrumb-item active">Editar</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="card m-b-30">
                        <div class="card-body">
                            <h4 class="mt-0 header-title text-center">Editar Aporte</h4>
                            <?php echo renderMessage("error") ?>
                            <form action="<?php echo route("sharedFound/update") ?>" method="POST">
                                <input type="hidden" name="id" value="<?php echo $record->id ?>">
                                <div class="row">
                                    <div class="col-md-2"></div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label>Año <span class="text-danger">*</span></label>
                                                    <select name="year" class="form-control" required>
                                                        <?php for($y = 2020; $y <= 2030; $y++): ?>
                                                        <option value="<?php echo $y ?>" <?php echo ($y == $record->year) ? 'selected' : '' ?>><?php echo $y ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Mes <span class="text-danger">*</span></label>
                                                    <select name="month" class="form-control" required>
                                                        <?php for($m = 1; $m <= 12; $m++): ?>
                                                        <option value="<?php echo $m ?>" <?php echo ($m == $record->month) ? 'selected' : '' ?>><?php echo date('F', mktime(0,0,0,$m)) ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label>Andres ($) <span class="text-danger">*</span></label>
                                                    <input type="number" name="amount_andres" class="form-control" value="<?php echo $record->amount_andres ?>" min="0" step="1000" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label>Ivan ($) <span class="text-danger">*</span></label>
                                                    <input type="number" name="amount_ivan" class="form-control" value="<?php echo $record->amount_ivan ?>" min="0" step="1000" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-success waves-effect waves-light">
                                                Actualizar
                                            </button>
                                            <a href="<?php echo route("sharedFound") ?>" class="btn btn-info waves-effect m-l-5 float-right">
                                                Regresar
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-md-2"></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include_document("layouts.footerbar") ?>
    </div>
</div>
