<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$reqId = base64_decode($_GET['reqid'] ?? '');
$rm = new requerimientosmaterial();
$res = $rm->getrequerimientobyid($reqId);
if (!$res || $res == 0) {
    echo '<div class="alert alert-warning">No se encontró el requerimiento.</div>';
    return;
}

$header = $res[0];
$sugerencias = $rm->obtenerSugerenciasAlmacenTraspaso($reqId);
$cumplimiento = $rm->obtenerCumplimientoDetallado($reqId);
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Requerimiento de Material <strong><?= htmlspecialchars($header['REQMATERIAL_FOLIO'] ?? '') ?></strong></h4>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr>
                    <th>Almacén</th>
                    <td><?= htmlspecialchars($header['ALMACEN_NOMBRE'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Sucursal</th>
                    <td><?= htmlspecialchars($header['SUCURSAL_NOMBRE'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td style="color:#<?= htmlspecialchars($header['STATUS_COLOR'] ?? '000') ?>"><?= htmlspecialchars($header['STATUS_NOMBRE'] ?? '') ?></td>
                </tr>
                <tr>
                    <th>Observaciones</th>
                    <td><?= nl2br(htmlspecialchars($header['REQMATERIAL_OBSERVACIONES'] ?? '')) ?></td>
                </tr>
            </table>



            <div class="table-responsive mt-3">
                <table class="table table-bordered table-sm font-weight-normal">
                    <thead>
                        <tr style="background-color: #f8fafc;">
                            <th>Clave</th>
                            <th>Artículo</th>
                            <th class="text-center" width="90px">Requerido</th>
                            <th class="text-center" width="90px">Traspasado</th>
                            <th class="text-center" width="90px">Comprado</th>
                            <th class="text-center" width="110px">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cumplimiento as $item):
                            $cantFaltante = max(0, $item['cantidad_requerida'] - $item['cantidad_transferida'] - $item['cantidad_comprada']);

                            // ── Badge de Traspaso ──────────────────────────────────────────
                            $badgeTraspaso = '';
                            if (!empty($item['movimientos_traspasos'])) {
                                foreach ($item['movimientos_traspasos'] as $t) {
                                    $sid = (int)($t['status_id'] ?? 0);
                                    $color = '#' . ($t['color'] ?? '64748b');
                                    // Texto descriptivo según status del traspaso
                                    if ($sid == 1)      $label = 'Traspaso Guardado';
                                    elseif ($sid == 2)  $label = 'Traspaso En Revisión';
                                    elseif ($sid == 7)  $label = 'Traspaso Enviado';
                                    elseif ($sid == 3)  $label = 'Traspaso Finalizado';
                                    elseif ($sid == 4)  $label = 'Traspaso Cancelado';
                                    else                $label = 'Traspaso: ' . htmlspecialchars($t['status']);
                                    $badgeTraspaso .= "<span class='badge text-white d-inline-block mb-1' style='background-color:{$color}; padding:3px 8px; font-size:10px; border-radius:6px;'>"
                                        . "<i class='mdi mdi-swap-horizontal'></i> {$label} (". (int)$t['cantidad'] .")</span><br>";
                                }
                            }

                            // ── Badge de OC ───────────────────────────────────────────────
                            $badgeOC = '';
                            if (!empty($item['movimientos_ocs'])) {
                                foreach ($item['movimientos_ocs'] as $o) {
                                    $sid = (int)($o['status_id'] ?? 0);
                                    $color = '#' . ($o['color'] ?? '64748b');
                                    if ($sid == 1)      $label = 'OC Guardada';
                                    elseif ($sid == 8)  $label = 'OC En Revisión';
                                    elseif ($sid == 2)  $label = 'OC Solicitada';
                                    elseif ($sid == 3)  $label = 'OC Finalizada';
                                    elseif ($sid == 4)  $label = 'OC Cancelada';
                                    else                $label = 'OC: ' . htmlspecialchars($o['status']);
                                    $badgeOC .= "<span class='badge text-white d-inline-block mb-1' style='background-color:{$color}; padding:3px 8px; font-size:10px; border-radius:6px;'>"
                                        . "<i class='mdi mdi-file-document'></i> {$label} (". (int)$o['cantidad'] .")</span><br>";
                                }
                            }
                            // OC pendiente de crear
                            if ($cantFaltante > 0 && $header['REQMATERIAL_STATUS'] == 2) {
                                $badgeOC .= "<span class='badge text-white d-inline-block mb-1' style='background-color:#f59e0b; padding:3px 8px; font-size:10px; border-radius:6px;'>"
                                    . "<i class='mdi mdi-clock-outline'></i> OC Por Crear ({$cantFaltante})</span><br>";
                            }

                            // ── Estado general de la fila ─────────────────────────────────
                            $totalSatisfecho = $item['cantidad_transferida'] + $item['cantidad_comprada'];
                            if ($totalSatisfecho >= $item['cantidad_requerida'] && $item['cantidad_requerida'] > 0) {
                                $estadoBadge = '<span class="badge badge-success text-white" style="padding:4px 10px; font-size:11px; border-radius:8px;">Completo</span>';
                            } elseif ($totalSatisfecho > 0 && $cantFaltante > 0) {
                                $estadoBadge = '<span class="badge badge-warning text-white" style="padding:4px 10px; font-size:11px; border-radius:8px;">Parcial</span>';
                            } else {
                                $estadoBadge = '<span class="badge badge-danger text-white" style="padding:4px 10px; font-size:11px; border-radius:8px;">Pendiente</span>';
                            }
                        ?>
                            <tr>
                                <td class="align-middle"><?= htmlspecialchars($item['clave_articulo']) ?></td>
                                <td class="align-middle">
                                    <span class="font-weight-bold" style="color: #1e293b;"><?= htmlspecialchars($item['articulo_nombre']) ?></span>

                                    <!-- Detalles de movimientos asociados -->
                                    <?php if (!empty($item['movimientos_traspasos']) || !empty($item['movimientos_ocs']) || $cantFaltante > 0): ?>
                                        <div class="mt-2 pl-3 border-left" style="font-size: 11px; color: #64748b; border-color: #cbd5e1 !important; line-height: 1.6;">
                                            <?php foreach ($item['movimientos_traspasos'] as $t): ?>
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="mr-2"><i class="mdi mdi-swap-horizontal text-info"></i> <strong>Traspaso:</strong> <?= htmlspecialchars($t['folio']) ?></span>
                                                    <span class="mr-2">Cant: <?= (int)$t['cantidad'] ?></span>
                                                    <span class="badge badge-xs text-white" style="background-color:#<?= $t['color'] ?>; font-size: 9px; padding: 2px 5px;"><?= htmlspecialchars($t['status']) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php foreach ($item['movimientos_ocs'] as $o): ?>
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="mr-2"><i class="mdi mdi-file-document text-primary"></i> <strong>OC:</strong> <?= htmlspecialchars($o['folio']) ?></span>
                                                    <span class="mr-2">Cant: <?= (int)$o['cantidad'] ?></span>
                                                    <span class="badge badge-xs text-white" style="background-color:#<?= $o['color'] ?>; font-size: 9px; padding: 2px 5px;"><?= htmlspecialchars($o['status']) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if ($cantFaltante > 0 && $header['REQMATERIAL_STATUS'] == 2): ?>
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="mr-2"><i class="mdi mdi-clock-outline text-warning"></i> <strong>OC Pendiente:</strong></span>
                                                    <span class="mr-2">Cant: <?= $cantFaltante ?></span>
                                                    <span class="badge badge-xs text-white" style="background-color:#f59e0b; font-size: 9px; padding: 2px 5px;">Por Crear</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center align-middle font-weight-bold"><?= (int)$item['cantidad_requerida'] ?></td>
                                <td class="text-center align-middle text-info font-weight-bold"><?= (int)$item['cantidad_transferida'] ?></td>
                                <td class="text-center align-middle text-primary font-weight-bold"><?= (int)$item['cantidad_comprada'] ?></td>
                                <td class="align-middle" style="min-width: 140px;">
                                    <?php if ($badgeTraspaso || $badgeOC): ?>
                                        <div style="line-height: 1.4;">
                                            <?= $badgeTraspaso ?>
                                            <?= $badgeOC ?>
                                        </div>
                                    <?php else: ?>
                                        <?= $estadoBadge ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer y Acciones de Confirmación -->
            <div class="d-flex justify-content-end mt-4 pt-3 border-top">

                <button type="button" class="btn btn-light font-weight-bold" data-dismiss="modal" style="border-radius: 8px; border: 1px solid #cbd5e1;">Cerrar</button>
            </div>
        </div>
    </div>
</div>
