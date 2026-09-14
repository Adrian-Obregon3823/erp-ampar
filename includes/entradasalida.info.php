<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php 
$entradasalida = new entradasalida();
$res = $entradasalida->getinfoentradasalidabyid(base64_decode($_GET['esid']));
?>
<style>
.tabla-detalle-resp th, .tabla-detalle-resp td {
    vertical-align: middle !important;
}
.tabla-detalle-resp th:nth-child(3), .tabla-detalle-resp td:nth-child(3) {
    min-width: 200px;
}
@media (max-width: 991px) {
    .tabla-detalle-resp {
        width: 100% !important;
        border: none !important;
    }
    .tabla-detalle-resp thead {
        display: none !important;
    }
    .tabla-detalle-resp tbody,
    .tabla-detalle-resp tr,
    .tabla-detalle-resp td {
        display: block !important;
        width: 100% !important;
    }
    .tabla-detalle-resp tr {
        margin-bottom: 1.25rem !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 8px !important;
        padding: 0.75rem !important;
        background-color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08) !important;
    }
    .tabla-detalle-resp td {
        border: none !important;
        border-bottom: 1px solid #f1f3f5 !important;
        padding: 0.6rem 0.2rem !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        width: 100% !important;
        text-align: left !important;
    }
    .tabla-detalle-resp td:last-child {
        border-bottom: none !important;
    }
    .tabla-detalle-resp td::before {
        content: attr(data-label);
        font-weight: bold;
        color: #495057;
        font-size: 0.8rem;
        text-transform: uppercase;
        margin-bottom: 0.25rem;
        display: block;
    }
}
</style>
<?php
$ocFolioStrInfo = '';
if (!empty($res[0]['OC_FOLIO_REAL'])) {
    $ocFolioStrInfo = "OC-" . $res[0]['OC_FOLIO_REAL'];
} elseif (strpos((string)$res[0]['ES_MOTIVO'], 'Recepcion de OC Folio:') !== false) {
    $numOc = trim(str_replace('Recepcion de OC Folio:', '', (string)$res[0]['ES_MOTIVO']));
    $ocFolioStrInfo = "OC-" . $numOc;
}
?>
<div class="col-12">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Solicitud de Inventario <b><?=$res[0]['ES_FOLIO'];?></b> <?php if (!empty($ocFolioStrInfo)) { ?><span class="badge badge-info ml-2" style="font-size: 1.1rem; background-color: #17a2b8; color: #fff;"><?=$ocFolioStrInfo?></span><?php } ?></h4>
        </div>
        <div class="card-body">
            <h4>Generales:<hr></h4>
            <div style="overflow-x:auto; width: 100%;">
                <table class="table table-bordered">
                    <tr>
                        <th width="200px">Folio</th>
                        <td><?=$res[0]['ES_FOLIO'];?> <span style="font-size:12px; font-weight:bold; color:<?= ($res[0]['ES_STATUS'] == 5 || $res[0]['STATUS_ID'] == 5) ? '#dc3545' : '#' . $res[0]['STATUS_COLOR'] ?>">(<?= ($res[0]['ES_STATUS'] == 5 || $res[0]['STATUS_ID'] == 5) ? 'Rechazado' : $res[0]['STATUS_NOMBRE'] ?>)</span> <?php if (!empty($ocFolioStrInfo)) { ?><span class="badge badge-info ml-3 px-2 py-1" style="background-color: #17a2b8; color: #fff; font-size: 0.85rem;"><i class="mdi mdi-cart mr-1"></i> Folio OC: <?=$ocFolioStrInfo?></span><?php } ?></td>
                    </tr>
                    <tr>
                        <th>Fecha Creación</th>
                        <td><?=$res[0]['ES_FECHA'];?></td>
                    </tr>
                    <tr>
                        <th>Almacén</th>
                        <td><?=$res[0]['ALMACEN_NOMBRE'];?> <b>(Categoría:<?=$res[0]['SUCURSAL_NOMBRE'];?>)</b></td>
                    </tr>
                    <tr>
                        <th>Concepto</th>
                        <td><?=$res[0]['CONCEPTO_NOMBRE'];?></td>
                    </tr>
                    <tr>
                        <th>Descripción</th>
                        <td><strong>Solicitud de <?=(($res[0]['ES_TIPO'] == "E") ? "Entrada" : (($res[0]['ES_TIPO'] == "S") ? "Salida" : (($res[0]['ES_TIPO'] == "R") ? "Reposición" : "")))?></strong> <?=$res[0]['ES_MOTIVO'];?></td>
                    </tr>
                    <tr>
                        <th>Usuario</th>
                        <td><?=$res[0]['USUARIO_NOMBRE'];?></td>
                    </tr>
                    <?php if (($res[0]['ES_STATUS'] == 5 || $res[0]['STATUS_ID'] == 5) || !empty($res[0]['ES_MOTIVO_RECHAZO'])) { ?>
                    <tr>
                        <th style="color: #dc3545; font-weight: bold;"><i class="mdi mdi-alert-circle mr-1"></i> Motivo de Rechazo</th>
                        <td style="color: #dc3545; font-weight: bold; background-color: #fff8f8; border-left: 4px solid #dc3545;">
                            <?= !empty($res[0]['ES_MOTIVO_RECHAZO']) ? htmlspecialchars($res[0]['ES_MOTIVO_RECHAZO'], ENT_QUOTES, 'UTF-8') : 'Rechazado en inspección de recepción (Sin motivo especificado).' ?>
                        </td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
            <?php 
            $general_carta_canje = $res[0]['RECEPCION_CARTA_CANJE'] ?? null;
            $general_delivery = $res[0]['RECEPCION_DELIVERY_NUM'] ?? null;
            if (empty($general_carta_canje)) {
                foreach ($res as $re) {
                    if (!empty($re['CARTACANJE_RUTA_IMG'])) { $general_carta_canje = $re['CARTACANJE_RUTA_IMG']; break; }
                }
            }
            if (empty($general_delivery)) {
                foreach ($res as $re) {
                    if (!empty($re['CARTACANJE_NUM_DELIVERY'])) { $general_delivery = $re['CARTACANJE_NUM_DELIVERY']; break; }
                }
            }
            ?>
            <?php if (!empty($res[0]['RECEPCION_FACTURA']) || !empty($res[0]['RECEPCION_EVIDENCIA']) || !empty($res[0]['RECEPCION_LISTA_EMBARQUE']) || !empty($general_carta_canje) || !empty($general_delivery)) { ?>
            <div class="mt-3 p-3 bg-light rounded border">
                <h6 class="font-weight-bold mb-2">Documentos Generales:</h6>
                <?php if (!empty($res[0]['RECEPCION_FACTURA'])) { ?>
                    <a href="../<?=$res[0]['RECEPCION_FACTURA']?>" target="_blank" class="btn btn-sm btn-outline-danger mr-2 mb-1"><i class="mdi mdi-file-pdf mr-1"></i> Ver Factura</a>
                <?php } ?>
                <?php if (!empty($res[0]['RECEPCION_EVIDENCIA'])) { ?>
                    <a href="../<?=$res[0]['RECEPCION_EVIDENCIA']?>" target="_blank" class="btn btn-sm btn-outline-info mr-2 mb-1"><i class="mdi mdi-image mr-1"></i> Ver Evidencia</a>
                <?php } ?>
                <?php if (!empty($res[0]['RECEPCION_LISTA_EMBARQUE'])) { ?>
                    <a href="../<?=$res[0]['RECEPCION_LISTA_EMBARQUE']?>" target="_blank" class="btn btn-sm btn-outline-success mr-2 mb-1"><i class="mdi mdi-file-document mr-1"></i> Ver Lista de Embarque</a>
                <?php } ?>
                <?php if (!empty($general_carta_canje)) { ?>
                    <a href="../<?=$general_carta_canje?>" target="_blank" class="btn btn-sm btn-outline-primary mr-2 mb-1"><i class="mdi mdi-file-document mr-1"></i> Ver Carta Canje</a>
                <?php } ?>
                <?php if (!empty($general_delivery)) { ?>
                    <span class="badge badge-dark text-white p-2" style="font-size: 14px; vertical-align: top; margin-top: 2px;">Delivery: <?=$general_delivery?></span>
                <?php } ?>
            </div>
            <?php } ?>
            <br><br>
            <h4>Detalle:<hr></h4>
            <div style="overflow-x:auto; width: 100%;">
                <table class="table table-bordered tabla-detalle-resp">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>CVE</th>
                            <th>ARTICULO</th>
                            <th>LOTE</th>
                            <th>CADUCIDAD</th>
                            <th>SERIE</th>
                            <th>CARTA CANJE</th>
                            <th>DELIVERY</th>
                            <th>NUM. DELIVERY</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res[0]['CLAVE_ARTICULO'] <> "") { ?>   
                            <?php foreach ($res as $index => $re) { ?>
                                <?php 
                                    $contador = $index + 1;
                                    $esActivo = $re['ESDET_ACTIVO'] ?? 0;
                                    $style = ($esActivo == 0) ? 'background-color: #fffacc;' : '';
                                    $nota = ($esActivo == 0) ? ' <span style="font-size:10px;color:#caa200">(No activo)</span>' : '';
                                ?>
                                <tr style="<?=$style?>">
                                    <td data-label="#"><?=$contador?></td>
                                    <td data-label="CVE"><?=$re['CLAVE_ARTICULO']?><?=($re['STOCK_FOLIO'] <> "" ? "<br>(".$re['STOCK_FOLIO'].") <b>&#10132;</b>" : "")?></td>
                                    <td data-label="ARTICULO"><?=$re['ARTICULO_NOMBRE']?><?=$nota?></td>
                                    <td data-label="LOTE"><?=$re['ESDET_LOTE']?></td>
                                    <td data-label="CADUCIDAD"><?=$re['ESDET_CADUCIDAD']?></td>
                                    <td data-label="SERIE"><?=$re['ESDET_SERIE']?></td>
                                    <td data-label="CARTA CANJE">
                                        <?php if (!empty($re['CARTACANJE_RUTA_IMG'])) { ?>
                                            <a href="../<?=$re['CARTACANJE_RUTA_IMG']?>" target="_blank" class="btn btn-sm btn-outline-primary mb-1" title="Ver Carta Canje">
                                                <i class="mdi mdi-file-document"></i> Ver Carta Canje
                                            </a>
                                        <?php } else { ?>
                                            <span class="text-muted small">N/A</span>
                                        <?php } ?>
                                    </td>
                                    <td data-label="DELIVERY">
                                        <?php if (!empty($re['CARTACANJE_RUTA_IMG'])) { ?>
                                            <a href="../<?=$re['CARTACANJE_RUTA_IMG']?>" target="_blank" class="btn btn-sm btn-outline-dark mb-1" title="Ver Documento Delivery">
                                                <i class="mdi mdi-file-document"></i> Ver Delivery
                                            </a>
                                        <?php } else { ?>
                                            <span class="text-muted small">N/A</span>
                                        <?php } ?>
                                    </td>
                                    <td data-label="NUM. DELIVERY">
                                        <?php if (!empty($re['CARTACANJE_NUM_DELIVERY'])) { ?>
                                            <?php if (strpos((string)$re['CARTACANJE_NUM_DELIVERY'], 'uploads/') === false) { ?>
                                                <span class="badge badge-dark text-white p-2">Delivery: <?=$re['CARTACANJE_NUM_DELIVERY']?></span>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <span class="text-muted small">N/A</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php }else{ ?>
                            <tr><td colspan="9"><div class="alert alert-warning text-center">No se encontró detalle</div></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>