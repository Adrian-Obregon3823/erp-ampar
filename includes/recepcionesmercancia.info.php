<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/recepcionesmercancia.php");

$recepciones = new recepcionesmercancia();
$id = base64_decode($_GET['id'] ?? '');

if (!$id) {
    echo "ID de recepción inválido.";
    exit;
}

$detalles = $recepciones->getRecepcionDetalleById($id);
$recepInfo = $recepciones->getRecepcionById($id);
$docsUnidades = $recepciones->getDocumentosUnidadesPorRecepcion($id);
if ($detalles === 0) {
    echo "No se encontraron detalles para esta recepción.";
    exit;
}
?>

<style>
.tabla-detalle-resp th, .tabla-detalle-resp td {
    vertical-align: middle !important;
}
.tabla-detalle-resp th:nth-child(2), .tabla-detalle-resp td:nth-child(2) {
    min-width: 220px;
}
.tabla-detalle-resp th:nth-child(1), .tabla-detalle-resp td:nth-child(1) {
    min-width: 130px;
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

<div class="row">
    <div class="col-12">
        <h5 class="mb-3">Detalle de Recepción #REC-<?=$id?></h5>
        <?php if (!empty($recepInfo['RECEPCION_FACTURA']) || !empty($recepInfo['RECEPCION_DOCUMENTO2']) || !empty($recepInfo['RECEPCION_EVIDENCIA']) || !empty($recepInfo['RECEPCION_LISTA_EMBARQUE'])) { ?>
        <div class="mb-3 p-3 bg-light rounded border">
            <h6 class="font-weight-bold mb-2">Documentos Generales de la Recepción:</h6>
            <?php if (!empty($recepInfo['RECEPCION_FACTURA'])) { ?>
                <a href="../<?=$recepInfo['RECEPCION_FACTURA']?>" target="_blank" class="btn btn-sm btn-outline-danger mr-2 mb-1"><i class="mdi mdi-file-document mr-1"></i> Ver Documento 1</a>
            <?php } ?>
            <?php 
            if (!empty($recepInfo['RECEPCION_DOCUMENTO2'])) { 
                $docs2 = json_decode($recepInfo['RECEPCION_DOCUMENTO2'], true);
                if (is_array($docs2)) {
                    foreach ($docs2 as $idx => $docPath) {
                        $numHoja = $idx + 1;
                        echo '<a href="../' . $docPath . '" target="_blank" class="btn btn-sm btn-outline-primary mr-2 mb-1"><i class="mdi mdi-format-list-bulleted mr-1"></i> Ver Lista de Empaque (Hoja ' . $numHoja . ')</a>';
                    }
                } else {
                    echo '<a href="../' . $recepInfo['RECEPCION_DOCUMENTO2'] . '" target="_blank" class="btn btn-sm btn-outline-primary mr-2 mb-1"><i class="mdi mdi-format-list-bulleted mr-1"></i> Ver Lista de Empaque</a>';
                }
            } 
            ?>
            <?php if (!empty($recepInfo['RECEPCION_EVIDENCIA'])) { ?>
                <a href="../<?=$recepInfo['RECEPCION_EVIDENCIA']?>" target="_blank" class="btn btn-sm btn-outline-info mr-2 mb-1"><i class="mdi mdi-image mr-1"></i> Ver Evidencia</a>
            <?php } ?>
            <?php if (!empty($recepInfo['RECEPCION_LISTA_EMBARQUE'])) { ?>
                <a href="../<?=$recepInfo['RECEPCION_LISTA_EMBARQUE']?>" target="_blank" class="btn btn-sm btn-outline-success mr-2 mb-1"><i class="mdi mdi-truck-delivery mr-1"></i> Ver Lista de Embarque</a>
            <?php } ?>
            <?php if (!empty($recepInfo['RECEPCION_CARTA_CANJE'])) { ?>
                <a href="../<?=$recepInfo['RECEPCION_CARTA_CANJE']?>" target="_blank" class="btn btn-sm btn-outline-primary mr-2 mb-1"><i class="mdi mdi-file-document mr-1"></i> Ver Carta Canje</a>
                <a href="../<?=$recepInfo['RECEPCION_CARTA_CANJE']?>" target="_blank" class="btn btn-sm btn-outline-dark mr-2 mb-1"><i class="mdi mdi-file-document mr-1"></i> Ver Delivery</a>
            <?php } ?>
            <?php if (!empty($recepInfo['RECEPCION_DELIVERY_NUM'])) { ?>
                <span class="badge badge-dark text-white p-2 mr-2 mb-1" style="font-size: 0.9rem;"><i class="mdi mdi-truck-delivery mr-1"></i> Num. Delivery: <?=$recepInfo['RECEPCION_DELIVERY_NUM']?></span>
            <?php } ?>
        </div>
        <?php } ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped tabla-detalle-resp">
                <thead>
                    <tr>
                        <?php if (!empty($recepInfo['RECEPCION_TRASPASOID'])) { ?>
                            <th>Folio Stock</th>
                            <th>Clave</th>
                            <th>Artículo</th>
                            <th>Lote</th>
                            <th>Serie</th>
                            <th>Caducidad</th>
                            <th>Cant. Recibida</th>
                        <?php } else { ?>
                            <th>Clave</th>
                            <th>Artículo</th>
                            <th>Cant. Esperada</th>
                            <th>Cant. Recibida</th>
                            <th>Motivo (Rechazo/Parcial)</th>
                            <th>Carta Canje</th>
                            <th>Delivery</th>
                            <th>Num. Delivery</th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($detalles as $det) { ?>
                        <tr>
                            <?php if (!empty($recepInfo['RECEPCION_TRASPASOID'])) { ?>
                                <td data-label="Folio Stock"><?=$det['STOCK_FOLIO']?></td>
                                <td data-label="Clave"><?=$det['CLAVE_ARTICULO']?></td>
                                <td data-label="Artículo"><?=$det['ARTICULO_NOMBRE']?></td>
                                <td data-label="Lote"><?=$det['STOCK_LOTE']?></td>
                                <td data-label="Serie"><?=$det['STOCK_SERIE']?></td>
                                <td data-label="Caducidad"><?=$det['STOCK_CADUCIDAD']?></td>
                                <td data-label="Cant. Recibida" class="font-weight-bold text-success"><?=floatval($det['RECDET_CANTIDAD_RECIBIDA'])?></td>
                            <?php } else { ?>
                                <td data-label="Clave"><?=$det['CLAVE_ARTICULO']?></td>
                                <td data-label="Artículo"><?=$det['ARTICULO_NOMBRE']?></td>
                                <td data-label="Cant. Esperada"><?=floatval($det['RECDET_CANTIDAD_ESPERADA'])?></td>
                                <td data-label="Cant. Recibida" class="font-weight-bold text-success"><?=floatval($det['RECDET_CANTIDAD_RECIBIDA'])?></td>
                                <td data-label="Motivo (Rechazo/Parcial)"><?=$det['RECDET_MOTIVO_RECHAZO']?></td>
                                <td data-label="Carta Canje">
                                    <?php 
                                    $artId = $det['RECDET_ARTICULOID'];
                                    $cartas = $docsUnidades[$artId]['cartas'] ?? [];
                                    if (!empty($cartas)) {
                                        foreach ($cartas as $idx => $doc) {
                                            $label = "Ver Carta Canje";
                                            if (count($cartas) > 1) {
                                                $sub = !empty($doc['lote']) ? "Lote: " . $doc['lote'] : (!empty($doc['serie']) ? "Serie: " . $doc['serie'] : "#" . ($idx + 1));
                                                $label .= " (" . $sub . ")";
                                            }
                                    ?>
                                            <a href="../<?=$doc['ruta']?>" target="_blank" class="btn btn-sm btn-outline-primary mb-1 d-block text-left" title="Ver Carta Canje">
                                                <i class="mdi mdi-file-document"></i> <?=$label?>
                                            </a>
                                    <?php 
                                        }
                                    } elseif (!empty($det['RUTA_IMG'])) { ?>
                                        <a href="../<?=$det['RUTA_IMG']?>" target="_blank" class="btn btn-sm btn-outline-primary mb-1" title="Ver Carta Canje">
                                            <i class="mdi mdi-file-document"></i> Ver Carta Canje
                                        </a>
                                    <?php } else { ?>
                                        <span class="text-muted small">N/A</span>
                                    <?php } ?>
                                </td>
                                <td data-label="Delivery">
                                    <?php 
                                    if (!empty($cartas)) {
                                        foreach ($cartas as $idx => $doc) {
                                            $label = "Ver Delivery";
                                            if (count($cartas) > 1) {
                                                $sub = !empty($doc['lote']) ? "Lote: " . $doc['lote'] : (!empty($doc['serie']) ? "Serie: " . $doc['serie'] : "#" . ($idx + 1));
                                                $label .= " (" . $sub . ")";
                                            }
                                    ?>
                                            <a href="../<?=$doc['ruta']?>" target="_blank" class="btn btn-sm btn-outline-dark mb-1 d-block text-left" title="Ver Documento Delivery">
                                                <i class="mdi mdi-file-document"></i> <?=$label?>
                                            </a>
                                    <?php 
                                        }
                                    } elseif (!empty($det['RUTA_IMG'])) { ?>
                                        <a href="../<?=$det['RUTA_IMG']?>" target="_blank" class="btn btn-sm btn-outline-dark mb-1" title="Ver Documento Delivery">
                                            <i class="mdi mdi-file-document"></i> Ver Delivery
                                        </a>
                                    <?php } else { ?>
                                        <span class="text-muted small">N/A</span>
                                    <?php } ?>
                                </td>
                                <td data-label="Num. Delivery">
                                    <?php 
                                    $deliveries = $docsUnidades[$artId]['deliveries'] ?? [];
                                    if (!empty($deliveries)) {
                                        foreach ($deliveries as $idx => $doc) {
                                            $valDel = $doc['val'];
                                            if (strpos((string)$valDel, 'uploads/') === false) {
                                    ?>
                                                <span class="badge badge-dark text-white p-2 mb-1 d-block text-left">Delivery: <?=$valDel?></span>
                                    <?php   }
                                        }
                                    } elseif (!empty($det['NUM_DELIVERY'])) { ?>
                                        <?php if (strpos((string)$det['NUM_DELIVERY'], 'uploads/') === false) { ?>
                                            <span class="badge badge-dark text-white p-2">Delivery: <?=$det['NUM_DELIVERY']?></span>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <span class="text-muted small">N/A</span>
                                    <?php } ?>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal-footer mt-4">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
</div>
