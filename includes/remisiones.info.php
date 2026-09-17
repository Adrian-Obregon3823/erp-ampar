<?php include_once("../includes/includes.php");?>
<?php 
$remisionid = base64_decode($_GET['remisionid']);
$remisiones = new remisiones();
$res = $remisiones->getremisionbyid(null,$remisionid);
$resp = $remisiones->getremisionprovinfobyid($remisionid);
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Nota de Remisión</h4>
        </div>
        <div class="card-body">
            <h4>Generales:<hr></h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th width="200px">FOLIO</th>
                        <td><?=$res[0]['REMISION_FOLIO'];?></td>
                    </tr>
                    <tr>
                        <th width="200px">FECHA</th>
                        <td><?=$res[0]['REMISION_FECHA'];?></td>
                    </tr>
                    <tr>
                        <th>ALMACÉN</th>
                        <td><?=$res[0]['REMISION_ALMACEN_NOMBRE'] ?? 'Almacén de ' . $res[0]['SUCURSAL_NOMBRE'];?></td>
                    </tr>
                    <?php if ($res[0]['CONCEPTO_REMISION'] === 'PROCEDIMIENTO' && !empty($res[0]['MALETA_NOMBRE'])): ?>
                    <tr>
                        <th>MALETA</th>
                        <td><?=$res[0]['MALETA_FOLIO'] . ' - ' . $res[0]['MALETA_NOMBRE'];?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>CONCEPTO</th>
                        <td><?= !empty($res[0]['EVENTO_CONCEPTO']) ? $res[0]['EVENTO_CONCEPTO'] : str_replace('_', ' ', $res[0]['CONCEPTO_REMISION'] ?? '');?></td>
                    </tr>
                    <tr>
                        <th>CLIENTE</th>
                        <td><?=$res[0]['CLIENTE_NOMBRE'];?></td>
                    </tr>
                    <tr>
                        <th>COND. PAGO</th>
                        <td><?=mb_convert_encoding($res[0]['CLIENTE_CONDICION_PAGO'] ?? '', 'UTF-8', 'ISO-8859-1');?></td>
                    </tr>
                </table>
            </div>
            <br><br>
            <h4>Detalle de Artículos:<hr></h4>
            <?php if ($res[0]['ESDET_ID']=='' and $resp==0){?>
                No se encontraron artículos
            <?php }else{ ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th width="40px" class="text-center">#</th>
                            <th>CLAVE</th>
                            <th>ARTICULO</th>
                            <th>PRECIO</th>
                        </tr>
                        <?php 
                            $grantotal = 0;
                            $graniva = 0;
                            $gransubtotal = 0;
                            $rowIndex = 1;
                            $esUap = (isset($res[0]['HOSPITAL_ID']) && $res[0]['HOSPITAL_ID'] == 25);
                        ?>
                        <?php foreach ($res as $re){ ?>
                            <?php if (!is_null($re['REMISIONARTICULO_TOTAL'])) { ?>
                                <?php 
                                    $mostrarPrecio = !$esUap; // En stock normal de ampar no puede ser UAP
                                    if ($mostrarPrecio) {
                                        $gransubtotal += $re['REMISIONARTICULO_SUBTOTAL']; 
                                        $graniva += $re['REMISIONARTICULO_IVA']; 
                                        $grantotal += $re['REMISIONARTICULO_TOTAL']; 
                                    }
                                ?>
                                <tr>
                                    <td class="text-center"><?=$rowIndex++?></td>
                                    <td><?=$re['STOCK_FOLIO']?></td>
                                    <td>
                                        <b><?=$re['CLAVE_ARTICULO']?></b> - <?=$re['ARTICULO_NOMBRE']?>
                                        <?php if ($res[0]['CONCEPTO_REMISION'] === 'PROCEDIMIENTO' && !empty($re['MALETA_NOMBRE'])): ?>
                                            <br><small class="text-muted">Maleta: <?=$re['MALETA_FOLIO']?> - <?=$re['MALETA_NOMBRE']?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?=$mostrarPrecio ? '$' . number_format($re['REMISIONARTICULO_SUBTOTAL'],2,'.',',') : '$0.00'?></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                        <?php
                        if ($resp <> 0){
                            foreach ($resp as $re){?>
                                <?php 
                                    $mostrarPrecioProv = true;
                                    if ($esUap) {
                                        $refProv = strtoupper($re['CLAVE_ARTICULO'] ?? '');
                                        // Solo mostrar si es el paquete UAP
                                        $mostrarPrecioProv = (substr($refProv, -4) === '-UAP');
                                        if ($mostrarPrecioProv) {
                                            $gransubtotal += $re['REMISIONPROVARTICULO_SUBTOTAL']; 
                                            $graniva += $re['REMISIONPROVARTICULO_IVA']; 
                                            $grantotal += $re['REMISIONPROVARTICULO_TOTAL']; 
                                        }
                                    } else {
                                        $gransubtotal += $re['REMISIONPROVARTICULO_SUBTOTAL']; 
                                        $graniva += $re['REMISIONPROVARTICULO_IVA']; 
                                        $grantotal += $re['REMISIONPROVARTICULO_TOTAL']; 
                                    }
                                ?>
                                <tr>
                                    <td class="text-center"><?=$rowIndex++?></td>
                                    <td></td>
                                    <td><b><?=$re['CLAVE_ARTICULO']?></b> - <?=$re['NOMBRE_ARTICULO']?> (<?=$re['NOMBREPROVEEDOR'] ?? 'PAQUETE UAP'?>)</td>
                                    <td><?=$mostrarPrecioProv ? '$' . number_format($re['REMISIONPROVARTICULO_SUBTOTAL'],2,'.',',') : '$0.00'?></td>
                                </tr>
                            <?php }
                        }?>
                        <tr>
                            <td colspan="3" class="text-right"><b>SUBTOTAL</b></td>
                            <td>$<?=number_format($gransubtotal,2,'.',',')?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-right"><b>IVA</b></td>
                            <td>$<?=number_format($graniva,2,'.',',')?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-right"><b>TOTAL</b></td>
                            <td>$<?=number_format($grantotal,2,'.',',')?></td>
                        </tr>
                    </table>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php");?>