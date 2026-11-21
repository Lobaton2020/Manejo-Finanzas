<?php
$probeExample = json_encode([
    "source"   => "macrodroid",
    "raw_text" => "Compra Exitosa\nTarjeta Mastercard Black ****2849\nEDS ENGATIVA FR\n\$ 35.127,00\n19/11/2026 18:30",
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
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
                                <button type="button" class="btn btn-outline-primary mr-2" data-toggle="modal"
                                    data-target="#probeModal" title="Probar POST /api/suggestions/inbox">
                                    <i class="mdi mdi-flask-outline"></i> Probar endpoint
                                </button>
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
        <div class="modal fade" id="probeModal" tabindex="-1" role="dialog"
            aria-labelledby="probeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="probeModalLabel">Probar endpoint de sugerencias</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-2">
                            Envia un payload a <code>POST <?php echo route("api/suggestions/inbox") ?></code>.
                            Si el texto ya existe, el endpoint responde <code>duplicate</code> y no crea otra sugerencia.
                        </p>
                        <label for="probePayload" class="font-weight-bold">Payload JSON</label>
                        <textarea id="probePayload" class="form-control font-monospace" rows="8"
                            style="font-size: 0.8rem;"><?php echo htmlspecialchars($probeExample, ENT_QUOTES) ?></textarea>
                        <div id="probeResult" class="mt-3"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="button" id="probeSend" class="btn btn-primary">
                            <i class="mdi mdi-send"></i> Enviar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function () {
            // JS puro a proposito: esta vista se renderiza ANTES que footer.php,
            // donde se cargan jQuery y Bootstrap. No depender de ninguno para
            // que el handler exista; el modal se abre por data-* de Bootstrap.
            var resultEl = document.getElementById('probeResult');
            var sendBtn = document.getElementById('probeSend');
            var payloadEl = document.getElementById('probePayload');
            var endpoint = '<?php echo route("api/suggestions/inbox") ?>';

            sendBtn.addEventListener('click', function () {
                var raw = payloadEl.value.trim();

                if (raw.charAt(0) === '{') {
                    try {
                        JSON.parse(raw);
                    } catch (e) {
                        resultEl.innerHTML = '<div class="alert alert-danger mb-0">JSON invalido: ' + esc(e.message) + '</div>';
                        return;
                    }
                }

                sendBtn.disabled = true;
                sendBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Enviando...';

                fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: raw
                })
                    .then(function (res) {
                        return res.json()
                            .catch(function () { return null; })
                            .then(function (data) { return { status: res.status, data: data }; });
                    })
                    .then(function (r) {
                        renderProbeResult(r.status, r.data);
                    })
                    .catch(function (e) {
                        resultEl.innerHTML = '<div class="alert alert-danger mb-0">Error de red: ' + esc(e.message) + '</div>';
                    })
                    .finally(function () {
                        sendBtn.disabled = false;
                        sendBtn.innerHTML = '<i class="mdi mdi-send"></i> Enviar';
                    });
            });

            function renderProbeResult(status, data) {
                if (!data) {
                    resultEl.innerHTML = '<div class="alert alert-warning mb-0">HTTP ' + status + ' sin JSON legible.</div>';
                    return;
                }

                var alertClass = data.error
                    ? 'alert-danger'
                    : (data.status === 'created' ? 'alert-success' : 'alert-info');

                var html = '<div class="alert ' + alertClass + ' mb-2"><strong>HTTP ' + status +
                    '</strong> &middot; status: <code>' + esc(data.status || '-') + '</code></div>';

                html += '<table class="table table-sm table-bordered mb-2">';
                [
                    ['id_suggestion', data.id_suggestion],
                    ['amount', data.amount != null ? ((data.currency ? data.currency + ' ' : '') + data.amount) : null],
                    ['description', data.description],
                    ['event_date', data.event_date],
                    ['outflow_type_name', data.outflow_type_name],
                    ['category_name', data.category_name],
                    ['deposit_name', data.deposit_name],
                    ['confidence', data.confidence],
                    ['needs_review', data.needs_review],
                    ['source', data.source],
                    ['error', data.error]
                ].forEach(function (row) {
                    if (row[1] === null || row[1] === undefined || row[1] === '') {
                        return;
                    }
                    html += '<tr><th style="width: 180px;">' + row[0] + '</th><td>' +
                        esc(String(row[1])) + '</td></tr>';
                });
                html += '</table>';

                html += '<details><summary class="text-muted" style="cursor:pointer;">Ver JSON completo</summary>' +
                    '<pre class="mt-2 mb-0 p-2 bg-light" style="font-size:0.75rem; white-space:pre-wrap;">' +
                    esc(JSON.stringify(data, null, 2)) + '</pre></details>';

                resultEl.innerHTML = html;
            }

            function esc(value) {
                var div = document.createElement('div');
                div.textContent = String(value);
                return div.innerHTML;
            }
        })();
        </script>

        <?php include_document("layouts.footerbar") ?>
    </div>
</div>
