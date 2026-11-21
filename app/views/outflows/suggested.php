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
                            <li class="breadcrumb-item"><a href="<?php echo route("outflow") ?>">Egresos</a></li>
                            <li class="breadcrumb-item active">Suggested</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card m-b-30">
                        <div class="m-3">
                            <div class="float-left">
                                <h4 class="mt-0 mb-3 header-title">Sugerencias de egresos</h4>
                                <p class="text-muted mb-0">
                                    Notificaciones procesadas automaticamente. Revisa y aprueba para crear el egreso real.
                                </p>
                            </div>
                            <div class="float-right">
                                <a href="<?php echo route("outflow") ?>" class="btn btn-secondary">
                                    <i class="mdi mdi-arrow-left"></i> Volver a Egresos
                                </a>
                            </div>
                            <div class="clearfix"></div>
                        </div>

                        <?php echo renderMessage("error") ?>
                        <?php echo renderMessage("success") ?>

                        <div class="card-body">
                            <?php if (empty($suggestions)) : ?>
                                <div class="text-center py-4">
                                    <h5 class="mt-0">No hay sugerencias pendientes</h5>
                                    <p class="text-muted mb-0">
                                        Cuando llegue una notificacion de pago, aparecera aqui para su revision.
                                    </p>
                                </div>
                            <?php else : ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Descripción</th>
                                                <th>Monto</th>
                                                <th>Tipo</th>
                                                <th>Categoría</th>
                                                <th>Depósito</th>
                                                <th>Confianza</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($suggestions as $suggestion) : ?>
                                                <tr>
                                                    <td>
                                                        <?php echo !empty($suggestion->event_date) ? htmlspecialchars($suggestion->event_date) : "—" ?>
                                                        <?php if (!empty($suggestion->needs_review)) : ?>
                                                            <span class="badge badge-warning ml-1" title="Revisar: la IA no extrajo todos los datos">Revisar</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="max-width: 320px; white-space: normal;">
                                                        <?php echo htmlspecialchars($suggestion->description ?: "Sin descripción") ?>
                                                        <button type="button" class="btn btn-link btn-sm p-0 ml-1" data-toggle="collapse"
                                                            data-target="#raw<?php echo (int) $suggestion->id_suggestion ?>" title="Ver notificación original">
                                                            <i class="mdi mdi-text-box-outline"></i>
                                                        </button>
                                                        <div class="collapse" id="raw<?php echo (int) $suggestion->id_suggestion ?>">
                                                            <pre class="mt-2 mb-0 p-2 bg-light" style="white-space: pre-wrap; font-size: 0.75rem;"><?php echo htmlspecialchars($suggestion->raw_text) ?></pre>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $suggestion->amount_label ?></td>
                                                    <td><?php echo htmlspecialchars($suggestion->outflow_type_name) ?></td>
                                                    <td><?php echo htmlspecialchars($suggestion->category_name) ?></td>
                                                    <td><?php echo htmlspecialchars($suggestion->deposit_name) ?></td>
                                                    <td><?php echo htmlspecialchars($suggestion->confidence_label) ?></td>
                                                    <td>
                                                        <?php if ($suggestion->can_approve) : ?>
                                                            <form method="POST" action="<?php echo route("suggestion/approve/{$suggestion->id_suggestion}") ?>" class="d-inline">
                                                                <button type="submit" class="btn btn-success btn-sm" title="Crear el egreso real">
                                                                    <i class="mdi mdi-check"></i> Aprobar
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <a href="<?php echo route("outflow/create?suggestion={$suggestion->id_suggestion}") ?>"
                                                            class="btn btn-info btn-sm" title="Revisar y editar los datos">
                                                            <i class="mdi mdi-pencil"></i> Editar
                                                        </a>
                                                        <form method="POST" action="<?php echo route("suggestion/reprocess/{$suggestion->id_suggestion}") ?>" class="d-inline">
                                                            <button type="submit" class="btn btn-warning btn-sm" title="Volver a procesarlo con la IA">
                                                                <i class="mdi mdi-refresh"></i>
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="<?php echo route("suggestion/discard/{$suggestion->id_suggestion}") ?>" class="d-inline">
                                                            <button type="submit" class="btn btn-secondary btn-sm" title="Descartar">
                                                                <i class="mdi mdi-close"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include_document("layouts.footerbar") ?>
    </div>
</div>
