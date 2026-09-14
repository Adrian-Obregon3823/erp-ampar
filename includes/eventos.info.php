<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php 
    $eventoid = base64_decode($_GET['eventoid']);
    $eventos = new eventos();
    $res = $eventos->geteventobyid($eventoid);
    $result2 = $eventos->getproveedoresbyeventoid($eventoid);

    // Obtener equipos capital asignados al evento
    $dbEC = new FirebirdConnection();
    $resEC = $dbEC->query("
        SELECT EC.EQUIPOCAPITAL_ID, EC.FOLIO, AR.NOMBRE AS ARTICULO_NOMBRE, EC.MARCA, EC.REFERENCIA
        FROM AMPAR_HIS_EVENTOSMALETAS EM
        INNER JOIN AMPAR_EQUIPOCAPITAL EC ON EC.EQUIPOCAPITAL_ID = ABS(EM.EVENTOMALETA_MALETAID)
        INNER JOIN ARTICULOS AR ON AR.ARTICULO_ID = EC.ARTICULO_ID
        WHERE EM.EVENTOMALETA_EVENTOID = " . (int)$eventoid . "
          AND EM.EVENTOMALETA_MALETAID < 0
          AND EC.ESTATUS = 'A'
        ORDER BY AR.NOMBRE
    ");
    $dbEC->close();
    $resEC = is_array($resEC) ? $resEC : [];
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Solicitud de Evento <b><?=$res[0]['EVENTO_FOLIO'];?></b></h4>
        </div>
        <div class="card-body">
            <?php
                $fechaI = new DateTime($res[0]['EVENTO_FECHAI']);
                $fechaF = new DateTime($res[0]['EVENTO_FECHAF']);

                // Calcular diferencia en horas y minutos
                $diff = $fechaI->diff($fechaF);
                $horas = $diff->h + ($diff->days * 24);
                $minutos = $diff->i;
                $duracion = $horas . 'h' . ($minutos > 0 ? ' ' . $minutos . 'm' : '');

                // Si es el mismo día
                if ($fechaI->format('Y-m-d') === $fechaF->format('Y-m-d')) {
                    $textofecha = "<b>".$fechaI->format('d/m/Y')."</b> " . $fechaI->format('H:i') . ' a ' . $fechaF->format('H:i') . " ({$duracion})";
                } else {
                    // Días diferentes
                    $textofecha = "<b>".$fechaI->format('d/m/Y')."</b> " . $fechaI->format('H:i') . ' a <b>' . $fechaF->format('d/m/Y')."</b> " . $fechaF->format('H:i') . " ({$duracion})";
                }
            ?>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <td style="background-color: #f2f2f2; width: 150px;"><b>Status</b></td>
                        <td colspan="3"><span style="color:#<?=$res[0]['STATUS_COLORGRAL'];?>"><?=$res[0]['STATUS_NOMBREGRAL'];?></span></td>
                    </tr>
                    <tr>
                        <td style="background-color: #f2f2f2; width: 150px;"><b>Descripción</b></td>
                        <td colspan="3"><?=nl2br($res[0]['EVENTO_CONCEPTO']);?></td>
                    </tr>
                    <tr>
                        <td style="background-color: #f2f2f2; width: 150px;"><b>Nombre del paciente</b></td>
                        <td colspan="3"><?=!empty($res[0]['NOMBRE']) ? $res[0]['NOMBRE'] : (!empty($res[0]['EVENTO_NOMBREPARTICULAR']) ? $res[0]['EVENTO_NOMBREPARTICULAR'] : ($res[0]['EVENTO_CLIENTEID'] ?? ''))?></td>
                    </tr>
                    <tr>
                        <td style="background-color: #f2f2f2; width: 150px;"><b>Lugar</b></td>
                        <td colspan="3"><?=$res[0]['HOSPITAL_NOMBRE'];?></td>
                    </tr>
                    <tr>
                        <td style="background-color: #f2f2f2; width: 150px;"><b>Almacén</b></td>
                        <td><?=$res[0]['EVENTO_ALMACEN_NOMBRE'] ?? 'Almacén de ' . $res[0]['SUCURSAL_NOMBRE'];?></td>
                        <td style="background-color: #f2f2f2; width: 150px;"><b>Fecha / Hora</b></td>
                        <td><?=$textofecha?></td>
                    </tr>
                    <tr>
                        <td style="background-color: #f2f2f2; width: 150px;"><b>Grupo</b></td>
                        <td><?=$res[0]['TIPOEVENTOGRUPO_NOMBRE'];?></td>
                        <td style="background-color: #f2f2f2; width: 150px;"><b>Tipo</b></td>
                        <td><?=$res[0]['TIPOEVENTO_NOMBRE'];?></td>
                    </tr>
                    <tr>
                        <td style="background-color: #f2f2f2; width: 150px;"><b>Subgrupo</b></td>
                        <td colspan="3"><?=$res[0]['TIPOEVENTOSUBGRUPO_NOMBRE'];?></td>
                    </tr>
                    <tr>
                        <td style="background-color: #f2f2f2; width: 150px;"><b>Médico Referidor</b></td>
                        <td><?=$res[0]['DOCTORREFERIDOR_NOMBRE'];?></td>
                        <td style="background-color: #f2f2f2; width: 150px;"><b>Médico Intervencionista</b></td>
                        <td><?=$res[0]['DOCTORINTERVENCIONISTA_NOMBRE'];?></td>
                    </tr>
                    <tr>
                        <td style="background-color: #f2f2f2; width: 150px;"><b>Especialista</b></td>
                        <td><?=$res[0]['ESPECIALISTA_NOMBRE'];?> <span style="color:#<?=$res[0]['STATUSCOLORESPECIALISTA'];?>"><?=$res[0]['STATUSESPECIALISTA'];?></span></td>
                        <td style="background-color: #f2f2f2; width: 150px;"><b>Chofer</b></td>
                        <td><?=$res[0]['CHOFER_NOMBRE'];?> <span style="color:#<?=$res[0]['STATUSCOLORCHOFER'];?>"><?=$res[0]['STATUSCHOFER'];?></span></td>
                    </tr>
                    <tr>
                        <td style="background-color: #f2f2f2;"><b>Maletas</b></td>
                        <td colspan="3">
                            <?php if (!empty($res) && is_array($res)) {
                                $maletasSet = [];
                                foreach($res as $re){
                                    if (!empty($re['EVENTOMALETA_ID']) && (int)($re['ALMACEN_ID'] ?? 0) > 0) {
                                        // Corregir doble codificación UTF-8 que causa "CATÃTERES" en vez de "CATÉTERES"
                                        $nombreMaleta = mb_convert_encoding($re['ALMACEN_NOMBRE'], 'ISO-8859-1', 'UTF-8');
                                        if (mb_detect_encoding($nombreMaleta, 'UTF-8', true) === false) {
                                            $nombreMaleta = $re['ALMACEN_NOMBRE']; // Fallback if conversion corrupted it
                                        }
                                        $maletasSet[$re['EVENTOMALETA_ID']] = $re['ALMACEN_FOLIO']." - ".$nombreMaleta;
                                    }
                                }
                                if(count($maletasSet) > 0){
                                    echo "<ul style='margin-bottom:0;'>";
                                    foreach($maletasSet as $maletaTexto){
                                        echo "<li>".$maletaTexto."</li>";
                                    }
                                    echo "</ul>";
                                } else {
                                    echo "Sin maletas asignadas";
                                }
                            } else {
                                echo "Sin maletas asignadas";
                            }
                            ?>
                        </td>
                    </tr>
                    <?php if (!empty($resEC)): ?>
                    <tr>
                        <td style="background-color: #f2f2f2;"><b>Equipo Capital</b></td>
                        <td colspan="3">
                            <ul style="margin-bottom:0;">
                                <?php foreach($resEC as $ec): ?>
                                    <li>
                                        <b><?= htmlspecialchars($ec['FOLIO']) ?></b>
                                        — <?= htmlspecialchars($ec['ARTICULO_NOMBRE']) ?>
                                        <?php if (!empty($ec['REFERENCIA'])): ?>
                                            <small class="text-muted">(<?= htmlspecialchars($ec['REFERENCIA']) ?>)</small>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <br>
    <div class="card">
        <div class="card-header">
            <h4>Indicador de Maletas</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table" width="100%">
                    <?php 
                        // Para evitar imprimir maletas repetidas o equipos capital
                        $impresas = [];
                        foreach($res as $re){
                            if(empty($re['EVENTOMALETA_ID']) || in_array($re['EVENTOMALETA_ID'], $impresas)) continue;
                            if((int)($re['ALMACEN_ID'] ?? 0) < 0) continue;
                            $impresas[] = $re['EVENTOMALETA_ID'];
                            
                            $nombreMaleta = mb_convert_encoding($re['ALMACEN_NOMBRE'], 'ISO-8859-1', 'UTF-8');
                            if (mb_detect_encoding($nombreMaleta, 'UTF-8', true) === false) {
                                $nombreMaleta = $re['ALMACEN_NOMBRE'];
                            }
                    ?>
                        <tr>
                            <th width="300px"><?=$re['ALMACEN_FOLIO']?> <?=$nombreMaleta?></th>
                            <td>
                                <div class="progress progress-md">
                                    <div class="progress-bar" style="width: <?=$re['PORCENTAJE']?>%;" id="progress"></div>
                                </div>
                            </td>
                            <td width="80px"><?=$re['PORCENTAJE']?>%</td>
                        </tr>
                    <?php } ?>
                    <?php if (count($impresas) === 0): ?>
                        <tr><td colspan="3" class="text-muted">Sin maletas asignadas</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <?php if ($result2 <> 0) {?>
        <br>
        <div class="card">
            <div class="card-header">
                <h4>Proveedores Invitados</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table cellpadding="4" cellspacing="0" width="100%" style="border-collapse: collapse; font-family: helvetica, sans-serif; font-size: 10pt;">
                        <?php foreach ($result2 as $r2) {?>
                            <tr>
                                <td width="30" valign="top" style="font-weight: bold;">•</td>
                                <td valign="top">
                                    <strong><?=htmlspecialchars($r2['NOMBREPROVEEDOR']);?></strong>
                                    <?php if (!empty(trim($r2['EVENTOPROVEEDOR_OBSERVACIONES']))) { ?>
                                        <div style="font-size:9pt; color:#555; margin-top:4px;"><?=nl2br(htmlspecialchars($r2['EVENTOPROVEEDOR_OBSERVACIONES']))?></div>
                                    <?php }?>
                                </td>
                            </tr>
                        <?php }?>
                    </table>
                </div>
            </div>
        </div>
    <?php } ?>
</div>