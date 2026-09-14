<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$proyectoid = isset($_GET['proyectoid']) ? intval(base64_decode($_GET['proyectoid'])) : 0;
$proyectos = new proyectos();
$res = $proyectos->getproyectobyid($proyectoid);
if (!$res || count($res) === 0) {
    echo "<div class='alert alert-danger'>Proyecto no encontrado</div>";
    exit;
}
$p = $res[0];

// Calcular cumplimiento
$cump = $proyectos->getcumplimiento($p['PROYECTO_ID']);
$porcentaje = $cump['meta'] > 0 ? min(100, round(($cump['remisionados'] / $cump['meta']) * 100)) : 100;

// Obtener remisiones del proyecto
$db = new FirebirdConnection(true);
$sqlRemisiones = "
    SELECT 
        R.REMISION_ID, R.REMISION_FECHA, R.REMISION_FOLIO, S.STATUS_NOMBRE, S.STATUS_COLOR,
        (
            COALESCE((SELECT SUM(REMISIONARTICULO_TOTAL) FROM AMPAR_HIS_REMISIONESARTICULOS WHERE REMISIONARTICULO_REMISIONID = R.REMISION_ID), 0)
            +
            COALESCE((SELECT SUM(REMISIONPROVARTICULO_TOTAL) FROM AMPAR_HIS_REMISIONESPARTICULOS WHERE REMISIONPROVARTICULO_REMISIONID = R.REMISION_ID), 0)
        ) AS TOTAL
    FROM AMPAR_HIS_REMISIONES R
    LEFT JOIN AMPAR_CONF_STATUS S ON S.STATUS_ID = R.REMISION_STATUS
    WHERE R.REMISION_PROYECTOID = ?
    ORDER BY R.REMISION_ID DESC
";
$remisiones = $db->query($sqlRemisiones, [$p['PROYECTO_ID']]) ?: [];

// Obtener inventario actual en el almacén de consigna del proyecto
$consignaId = (int)$p['PROYECTO_ALMACEN_CONSIGNA_ID'];
$sqlStock = "
    SELECT X.CLAVE_ARTICULO, A.NOMBRE, S.STOCK_FOLIO
    FROM AMPAR_HIS_STOCK S
    JOIN ARTICULOS A ON A.ARTICULO_ID = S.STOCK_ARTICULOID
    LEFT JOIN (SELECT CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = A.ARTICULO_ID
    WHERE S.STOCK_ALMACENIDACTUAL = {$consignaId} AND S.STOCK_STOCKSTATUSID = 1
    ORDER BY A.NOMBRE ASC, S.STOCK_FOLIO ASC
";
$inventarioRaw = $db->query($sqlStock) ?: [];

$inventarioGrouped = [];
foreach ($inventarioRaw as $inv) {
    $key = $inv['CLAVE_ARTICULO'] . '|' . $inv['NOMBRE'];
    if (!isset($inventarioGrouped[$key])) {
        $inventarioGrouped[$key] = [
            'CLAVE_ARTICULO' => $inv['CLAVE_ARTICULO'],
            'NOMBRE' => $inv['NOMBRE'],
            'CANTIDAD' => 0,
            'FOLIOS' => []
        ];
    }
    $inventarioGrouped[$key]['CANTIDAD']++;
    if (!empty($inv['STOCK_FOLIO'])) {
        $inventarioGrouped[$key]['FOLIOS'][] = $inv['STOCK_FOLIO'];
    }
}
$inventarioGrouped = array_values($inventarioGrouped);

$db->close();
?>

<div class="modal-header">
    <h5 class="modal-title">Detalles del Proyecto: <?= htmlentities($p['PROYECTO_FOLIO']) ?></h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="background:none; border:none; font-size:1.5rem;">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body">
    
    <!-- Alerta de Cumplimiento -->
    <?php if (!$cump['cumple']): ?>
        <div class="alert alert-danger alert-banner d-flex align-items-center" role="alert">
            <i class="mdi mdi-alert-circle mr-2" style="font-size:1.25rem;"></i>
            <span>
                <strong>Atención:</strong> El cumplimiento no se ha alcanzado. Se han remisionado <strong><?= $cump['remisionados'] ?></strong> de <strong><?= $cump['meta'] ?></strong> artículos requeridos.
            </span>
        </div>
    <?php else: ?>
        <div class="alert alert-success alert-banner d-flex align-items-center" role="alert">
            <i class="mdi mdi-checkbox-marked-circle mr-2" style="font-size:1.25rem;"></i>
            <span>
                <strong>Cumplimiento correcto:</strong> Se ha alcanzado la meta del proyecto (<?= $cump['remisionados'] ?> / <?= $cump['meta'] ?> artículos).
            </span>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Información General -->
        <div class="col-md-6 mb-3">
            <h6>Información General</h6>
            <hr class="mt-1 mb-2">
            <p class="mb-1"><strong>Folio:</strong> <?= htmlentities($p['PROYECTO_FOLIO']) ?></p>
            <p class="mb-1"><strong>Status:</strong> <span style="color:#<?= $p['STATUS_COLORGRAL'] ?>; font-weight:bold;"><?= $p['STATUS_NOMBREGRAL'] ?></span></p>
            <?php
            $tipoText = 'S/A';
            if (isset($p['PROYECTO_TIPO'])) {
                if ($p['PROYECTO_TIPO'] == 1) $tipoText = 'Consignación (Artículos)';
                elseif ($p['PROYECTO_TIPO'] == 2) $tipoText = 'Renta (Solo Equipo Capital)';
                elseif ($p['PROYECTO_TIPO'] == 3) $tipoText = 'Comodato (Artículos y Equipo Capital)';
            }
            ?>
            <p class="mb-1"><strong>Tipo de Proyecto:</strong> <span class="badge badge-info text-white"><?= $tipoText ?></span></p>
            <p class="mb-1"><strong>Sucursal:</strong> <?= htmlentities($p['SUCURSAL_NOMBRE'] ?? 'S/A') ?></p>
            <p class="mb-1"><strong>Cliente / Razón Social:</strong> <?= htmlentities($p['CLIENTE_NOMBRE'] ?? 'S/A') ?></p>
            <p class="mb-1"><strong>Responsable:</strong> <?= htmlentities($p['RESPONSABLE_NOMBRE'] ?? 'S/A') ?></p>
            <p class="mb-1"><strong>Vigencia:</strong> <?= date('Y-m-d H:i', strtotime($p['PROYECTO_FECHAI'])) ?> al <?= date('Y-m-d H:i', strtotime($p['PROYECTO_FECHAF'])) ?></p>
            <p class="mb-1"><strong>Fecha de Creación:</strong> <?= date('Y-m-d H:i', strtotime($p['PROYECTO_FECHACREACION'])) ?></p>
            <?php
            $plazosMap = [
                'SEMANA' => 'Semanal',
                'MES' => 'Mensual',
                'BIMENSUAL' => 'Bimensual',
                'TRIMESTRAL' => 'Trimestral',
                'SEMESTRAL' => 'Semestral',
                'TODO' => 'Total acumulado',
            ];
            $plazoText = isset($plazosMap[$cump['plazo']]) ? $plazosMap[$cump['plazo']] : 'Total acumulado';
            ?>
            <p class="mb-1"><strong>Plazo de Cumplimiento:</strong> <?= $plazoText ?></p>
        </div>

        <!-- Meta de Cumplimiento -->
        <div class="col-md-6 mb-3">
            <h6>Progreso Global</h6>
            <hr class="mt-1 mb-2">
            <div class="d-flex justify-content-between font-weight-bold mb-1">
                <span>Fulfillment (<?= $plazoText ?>)</span>
                <span class="<?= $cump['cumple'] ? 'text-success' : 'text-danger' ?>"><?= $cump['remisionados'] ?> / <?= $cump['meta'] ?></span>
            </div>
            <div class="progress mb-3" style="height: 18px; border-radius: 9px;">
                <div class="progress-bar <?= $cump['cumple'] ? 'bg-success' : 'bg-danger' ?>" role="progressbar" style="width: <?= $porcentaje ?>%;" aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100"><?= $porcentaje ?>%</div>
            </div>

            <h6>Fulfillment por Artículo</h6>
            <hr class="mt-1 mb-2">
            <div style="max-height: 200px; overflow-y: auto; padding-right: 5px;">
            <?php foreach (($cump['detalles'] ?: []) as $det): 
                $detPorc = $det['META'] > 0 ? min(100, round(($det['REMISIONADOS'] / $det['META']) * 100)) : 100;
                $detColor = $det['CUMPLE'] ? 'text-success' : 'text-danger';
            ?>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="font-weight-bold text-truncate" style="max-width:70%;" title="<?= htmlentities($det['ARTICULO_NOMBRE']) ?>"><?= htmlentities(($det['STOCK_FOLIO'] ? '[' . $det['STOCK_FOLIO'] . '] ' : '') . $det['ARTICULO_NOMBRE']) ?></span>
                        <span class="<?= $detColor ?> font-weight-bold text-nowrap"><?= $det['REMISIONADOS'] ?> / <?= $det['META'] ?></span>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 4px;">
                        <div class="progress-bar <?= $det['CUMPLE'] ? 'bg-success' : 'bg-danger' ?>" role="progressbar" style="width: <?= $detPorc ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Descripción -->
    <div class="row">
        <div class="col-12 mb-3">
            <h6>Descripción / Concepto</h6>
            <hr class="mt-1 mb-2">
            <p class="bg-light p-2 border rounded" style="white-space: pre-wrap;"><?= htmlentities($p['PROYECTO_CONCEPTO'] ?? '') ?></p>
        </div>
    </div>

    <!-- Artículos / Equipos Asignados -->
    <div class="row">
        <div class="col-12 mb-3">
            <h6>Artículos y Equipos Capital Asignados</h6>
            <hr class="mt-1 mb-2">
            <?php if (count($p['_MALETAS']) > 0): ?>
                <ul class="list-group">
                    <?php foreach ($p['_MALETAS'] as $m): 
                        $isEq = (trim($m['TIPO']) === 'EQUIPO');
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                            <span>
                                <i class="mdi <?= $isEq ? 'mdi-briefcase-outline text-info' : 'mdi-cube-outline text-primary' ?> mr-2"></i>
                                <?= htmlentities(($m['ALMACEN_FOLIO'] ? '[' . $m['ALMACEN_FOLIO'] . '] ' : '') . $m['ALMACEN_NOMBRE']) ?>
                            </span>
                            <span class="badge <?= $isEq ? 'badge-info text-white' : 'badge-primary text-white' ?>">
                                <?= $isEq ? 'Equipo Capital' : 'Artículo' ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="alert alert-warning p-2">No hay artículos ni equipos capital asignados a este proyecto.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Inventario Actual en Consigna -->
    <div class="row">
        <div class="col-12 mb-3">
            <h6>Inventario Físico Actual en Consigna</h6>
            <hr class="mt-1 mb-2">
            <?php if (count($inventarioGrouped) > 0): ?>
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-bordered table-hover table-sm m-0">
                        <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th style="width: 15%;">Clave</th>
                                <th style="width: 35%;">Artículo</th>
                                <th class="text-center" style="width: 10%;">Cant. Disp.</th>
                                <th style="width: 40%;">Folios (Series/Lotes)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventarioGrouped as $inv): ?>
                                <tr>
                                    <td class="align-middle"><span class="font-weight-bold"><?= htmlentities($inv['CLAVE_ARTICULO'] ?? 'N/A') ?></span></td>
                                    <td class="align-middle"><?= htmlentities($inv['NOMBRE']) ?></td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-success text-white badge-pill px-3 py-1" style="font-size:0.9rem; font-weight:bold;"><?= $inv['CANTIDAD'] ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <?php foreach ($inv['FOLIOS'] as $folio): ?>
                                            <span class="badge badge-outline-primary mb-1 mr-1 px-2 py-1" style="font-size:0.8rem; font-weight:600; border-width: 1px;"><?= htmlentities($folio) ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-light border p-2 text-center text-muted">
                    No hay inventario disponible físicamente en el almacén de este proyecto en este momento.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Historial de Remisiones -->
    <div class="row">
        <div class="col-12">
            <h6>Remisiones Creadas para este Proyecto</h6>
            <hr class="mt-1 mb-2">
            <?php if (count($remisiones) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>Folio</th>
                                <th>Fecha</th>
                                <th>Estatus</th>
                                <th class="text-right">Total</th>
                                <th class="text-center">Formato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($remisiones as $rem): ?>
                                <tr>
                                    <td><?= htmlentities($rem['REMISION_FOLIO']) ?></td>
                                    <td><?= date('Y-m-d H:i', strtotime($rem['REMISION_FECHA'])) ?></td>
                                    <td style="color:#<?= $rem['STATUS_COLOR'] ?>; font-weight:bold;"><?= htmlentities($rem['STATUS_NOMBRE']) ?></td>
                                    <td class="text-right">$<?= number_format($rem['TOTAL'], 2) ?></td>
                                    <td class="text-center">
                                        <a href="../gdocs/remisionES.formato.php?remisionid=<?= base64_encode($rem['REMISION_ID']) ?>" target="_blank" title="Ver PDF">
                                            <img src="../img/pdf.png" style="width:20px; height:auto;">
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-light border p-2 text-center text-muted">No se han generado remisiones para este proyecto aún.</div>
            <?php endif; ?>
        </div>
    </div>

</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
</div>
