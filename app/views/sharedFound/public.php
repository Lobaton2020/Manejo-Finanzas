<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fondo Compartido</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; min-height: 100vh; padding: 20px 0; }
        .card { border: none; border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center mb-4">Fondo Compartido - Andres & Ivan</h2>
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card" style="background:#a8d0e1;">
                    <div class="card-body text-center py-4">
                        <h4 class="text-dark">Andres</h4>
                        <h2 class="text-dark">$<?php echo number_format($totalAndres) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card" style="background:#e1a8a8;">
                    <div class="card-body text-center py-4">
                        <h4 class="text-dark">Ivan</h4>
                        <h2 class="text-dark">$<?php echo number_format($totalIvan) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card" style="background:#a8e1c3;">
                    <div class="card-body text-center py-4">
                        <h4 class="text-dark">Total</h4>
                        <h2 class="text-dark">$<?php echo number_format($total) ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-body">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formModal" onclick="limpiarForm()">+ Agregar</button>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Periodo</th>
                            <th>Andres</th>
                            <th>Ivan</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $monthsNames = [1=>"Enero",2=>"Febrero",3=>"Marzo",4=>"Abril",5=>"Mayo",6=>"Junio",7=>"Julio",8=>"Agosto",9=>"Septiembre",10=>"Octubre",11=>"Noviembre",12=>"Diciembre"];
                        foreach ($records as $r): ?>
                        <tr>
                            <td><?php echo $monthsNames[$r->month] . " " . $r->year ?></td>
                            <td>$<?php echo number_format($r->amount_andres) ?></td>
                            <td>$<?php echo number_format($r->amount_ivan) ?></td>
                            <td>$<?php echo number_format($r->total) ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#formModal" 
                                    onclick="editar(<?php echo $r->id ?>, <?php echo $r->year ?>, <?php echo $r->month ?>, <?php echo $r->amount_andres ?>, <?php echo $r->amount_ivan ?>)">Editar</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="formModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="<?php echo URL_PROJECT ?>sharedFound/store">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="id">
                        <div class="mb-3">
                            <label>Año</label>
                            <select name="year" id="year" class="form-control" required>
                                <?php for($y = 2020; $y <= 2030; $y++): ?>
                                <option value="<?php echo $y ?>"><?php echo $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Mes</label>
                            <select name="month" id="month" class="form-control" required>
                                <?php for($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo $m ?>"><?php echo $monthsNames[$m] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Andres ($)</label>
                            <input type="text" name="amount_andres" id="amount_andres" class="form-control" value="" placeholder="150,000" oninput="formatear(this)" required>
                        </div>
                        <div class="mb-3">
                            <label>Ivan ($)</label>
                            <input type="text" name="amount_ivan" id="amount_ivan" class="form-control" value="" placeholder="150,000" oninput="formatear(this)" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function formatear(input) {
            let valor = input.value.replace(/[^0-9]/g, '');
            if (valor) {
                input.value = parseInt(valor).toLocaleString('es-CO');
            }
        }
        
        function limpiarForm() {
            document.getElementById('id').value = '';
            document.getElementById('year').value = new Date().getFullYear();
            document.getElementById('month').value = new Date().getMonth() + 1;
            document.getElementById('amount_andres').value = '';
            document.getElementById('amount_ivan').value = '';
        }
        
        function editar(id, year, month, andres, ivan) {
            document.getElementById('id').value = id;
            document.getElementById('year').value = year;
            document.getElementById('month').value = month;
            document.getElementById('amount_andres').value = andres.toLocaleString('es-CO');
            document.getElementById('amount_ivan').value = ivan.toLocaleString('es-CO');
        }
        
        document.querySelector('form').addEventListener('submit', function(e) {
            document.getElementById('amount_andres').value = document.getElementById('amount_andres').value.replace(/[^0-9]/g, '') || 0;
            document.getElementById('amount_ivan').value = document.getElementById('amount_ivan').value.replace(/[^0-9]/g, '') || 0;
        });
    </script>
</body>
</html>