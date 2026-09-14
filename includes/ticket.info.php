<?php include_once("../includes/includes.php"); ?>
<?php include_once("../includes/head.php"); ?>
<?php
$tickets = new tickets();
$res = $tickets->getticketbyid(base64_decode($_GET['ticketid']));
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Detalle de Incidencia</h4>
        </div>
        <div class="card-body">
            <h4>Generales:
                <hr>
            </h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th width="200px">FOLIO</th>
                        <td><?= $res[0]['TICKET_FOLIO']; ?></td>
                    </tr>
                    <tr>
                        <th width="200px">STATUS</th>
                        <?php
                            $statusNombre = $res[0]['STATUS_NOMBRE'];
                            if (strtolower(trim($statusNombre)) == 'guardado') $statusNombre = 'Abierta';
                            if (strtolower(trim($statusNombre)) == 'en proceso') $statusNombre = 'Abierta';
                            if (strtolower(trim($statusNombre)) == 'enviado') $statusNombre = 'Resuelta';
                        ?>
                        <td style="color:#<?= $res[0]['STATUS_COLOR'] ?>"><b><?= $statusNombre; ?></b></td>
                    </tr>
                    <tr>
                        <th>RESPONSABLE</th>
                        <td><?= $res[0]['USUARIO_NOMBRE'] ?: '-' ?></td>
                    </tr>
                    <tr>
                        <th>ALMACÉN (MALETA)</th>
                        <td><?= $res[0]['ESCANEO_FOLIO'] ?: '-' ?></td>
                    </tr>
                    <tr>
                        <th>CONCEPTO</th>
                        <td><?= $res[0]['TICKET_CONCEPTO']; ?></td>
                    </tr>
                    <?php if (!empty($res[0]['TICKET_COMENTARIOCIERRE'])): ?>
                    <tr>
                        <th>COMENTARIO RESOLUCIÓN</th>
                        <td><?= $res[0]['TICKET_COMENTARIOCIERRE']; ?> <span class="text-muted" style="font-size:12px;">(Fecha: <?= $res[0]['TICKET_FECHACIERRE']; ?>)</span></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            <br><br>
            <h4>Detalle de Artículos Faltantes:
                <hr>
            </h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th>ARTÍCULO</th>
                        <th width="100px" class="text-center">CANTIDAD</th>
                    </tr>
                    <?php
                    $agrupados = [];
                    $folioTicket = $res[0]['TICKET_FOLIO'] ?? '';
                    if (strpos($folioTicket, 'ACT-ML-') === 0 || strpos($folioTicket, 'ACT-AL-') === 0) {
                        // Es incidencia de actividad de escaneo
                        $fechaEv = date('Y-m-d', strtotime($res[0]['TICKET_FECHA']));
                        $isMaleta = strpos($folioTicket, 'ACT-ML-') === 0;
                        $prefix = $isMaleta ? 'ML%' : 'AL%';
                        
                        $dbAux = new FirebirdConnection();
                        
                        $extraCond = $isMaleta ? "" : " AND (ALMACEN_TIPOALMACEN IS NULL OR ALMACEN_TIPOALMACEN <> 4) ";
                        
                        $sqlMissing = "
                            SELECT ALMACEN_FOLIO, ALMACEN_NOMBRE 
                            FROM AMPAR_HIS_ALMACEN 
                            WHERE UPPER(TRIM(ALMACEN_FOLIO)) LIKE '{$prefix}' 
                            AND ALMACEN_STATUS = 17 
                            {$extraCond}
                            AND UPPER(TRIM(ALMACEN_FOLIO)) NOT IN (
                                SELECT UPPER(TRIM(ESCANEO_FOLIO)) 
                                FROM AMPAR_HIS_ESCANEO 
                                WHERE UPPER(TRIM(ESCANEO_FOLIO)) LIKE '{$prefix}' 
                                AND CAST(ESCANEO_FECHA AS DATE) = '{$fechaEv}' 
                                AND EXTRACT(HOUR FROM ESCANEO_FECHA) >= 17
                            )
                        ";
                        $missingRes = $dbAux->query($sqlMissing);
                        if (is_array($missingRes)) {
                            foreach ($missingRes as $m) {
                                $agrupados[$m['ALMACEN_FOLIO'] . ' - ' . $m['ALMACEN_NOMBRE']] = 1;
                            }
                        }
                        $dbAux->close();
                    } else {
                        foreach ($res as $re) {
                            $nombre = $re['NOMBRE'] ?: $re['TICKETDET_ETIQUETABD'];
                            if ($nombre) {
                                if (!isset($agrupados[$nombre])) {
                                    $agrupados[$nombre] = 0;
                                }
                                $agrupados[$nombre]++;
                            }
                        }
                    }

                    foreach ($agrupados as $nombre => $cantidad) { ?>
                        <tr>
                            <td><?= $nombre ?></td>
                            <td class="text-center"><b><?= $cantidad ?></b></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php"); ?>