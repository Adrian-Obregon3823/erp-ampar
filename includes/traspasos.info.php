<?php include_once("../includes/includes.php");?>
<?php include_once("../includes/head.php");?>
<?php 
$traspasos = new traspasos();
$res = $traspasos->getinfotraspasobyid(base64_decode($_GET['traspasoid']));
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Solicitud de Traspaso</h4>
        </div>
        <div class="card-body">
            <h4>Generales:<hr></h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th width="200px">Folio</th>
                        <td><?=$res[0]['TRASPASO_FOLIO'];?></td>
                    </tr>
                    <tr>
                        <th width="200px">Fecha Creación</th>
                        <td><?=$res[0]['TRASPASO_FECHACREACION'];?></td>
                    </tr>
                    <tr>
                        <th>De Almacén</th>
                        <td><?=$res[0]['DEALMACEN']?> - <b><?=$res[0]['DESUCURSAL']?></b></td>
                    </tr>
                    <tr>
                        <th>A Almacén</th>
                        <td><?=$res[0]['AALMACEN']?> - <b><?=$res[0]['ASUCURSAL']?></b></td>
                    </tr>
                    <tr>
                        <th>Motivo</th>
                        <td><?=$res[0]['TRASPASO_MOTIVO'];?></td>
                    </tr>
                    <tr>
                        <th>Usuario</th>
                        <td><?=$res[0]['USUARIO_NOMBRE'];?></td>
                    </tr>
                    <?php if (!empty($res[0]['TRASPASO_LINK']) && !empty($res[0]['TRASPASO_PAQUETERIA'])) { 
                        $rastreoLink = $res[0]['TRASPASO_LINK'];
                        if (strpos($rastreoLink, 'http') !== 0) $rastreoLink = 'https://' . $rastreoLink;
                    ?>
                    <tr>
                        <th>Paquetería / Rastreo</th>
                        <td><a href="<?=$rastreoLink?>" target="_blank"><?=$res[0]['TRASPASO_PAQUETERIA'];?></a></td>
                    </tr>
                    <?php } else if (!empty($res[0]['TRASPASO_PAQUETERIA'])) { ?>
                    <tr>
                        <th>Paquetería</th>
                        <td><?=$res[0]['TRASPASO_PAQUETERIA'];?></td>
                    </tr>
                    <?php } ?>
                    <?php if (!empty($res[0]['TRASPASO_NUMGUIA'])) { ?>
                    <tr>
                        <th>Número de Guía</th>
                        <td><?=$res[0]['TRASPASO_NUMGUIA'];?></td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
            <?php if (!empty($res[0]['TRASPASO_FACTURA']) || !empty($res[0]['TRASPASO_DOCUMENTO2']) || !empty($res[0]['TRASPASO_EVIDENCIA']) || !empty($res[0]['TRASPASO_GUIA']) || !empty($res[0]['TRASPASO_EVIDENCIA1']) || !empty($res[0]['TRASPASO_EVIDENCIA2']) || !empty($res[0]['TRASPASO_EVIDENCIA3']) || !empty($res[0]['TRASPASO_EVIDENCIA4']) || !empty($res[0]['TRASPASO_LISTAEMPAQUE'])) { ?>
            <div class="mt-3 mb-3 p-3 bg-light rounded border">
                <h6 class="font-weight-bold mb-2">Documentos y Evidencias del Traspaso:</h6>
                <?php if (!empty($res[0]['TRASPASO_FACTURA'])) { ?>
                    <a href="../<?=$res[0]['TRASPASO_FACTURA']?>" target="_blank" class="btn btn-sm btn-outline-danger mr-2 mb-1"><i class="mdi mdi-file-document mr-1"></i> Ver Documento 1</a>
                <?php } ?>
                <?php if (!empty($res[0]['TRASPASO_DOCUMENTO2'])) { ?>
                    <a href="../<?=$res[0]['TRASPASO_DOCUMENTO2']?>" target="_blank" class="btn btn-sm btn-outline-secondary mr-2 mb-1"><i class="mdi mdi-file-document mr-1"></i> Ver Documento 2</a>
                <?php } ?>
                <?php if (!empty($res[0]['TRASPASO_EVIDENCIA'])) { ?>
                    <a href="../<?=$res[0]['TRASPASO_EVIDENCIA']?>" target="_blank" class="btn btn-sm btn-outline-info mr-2 mb-1"><i class="mdi mdi-image mr-1"></i> Ver Evidencia</a>
                <?php } ?>
                <?php if (!empty($res[0]['TRASPASO_GUIA'])) { ?>
                    <a href="../<?=$res[0]['TRASPASO_GUIA']?>" target="_blank" class="btn btn-sm btn-outline-success mr-2 mb-1"><i class="mdi mdi-truck-delivery mr-1"></i> Ver Guía de Embarque</a>
                <?php } ?>
                
                <?php if (!empty($res[0]['TRASPASO_EVIDENCIA1'])) { ?>
                    <a href="../<?=$res[0]['TRASPASO_EVIDENCIA1']?>" target="_blank" class="btn btn-sm btn-outline-info mr-2 mb-1"><i class="mdi mdi-image mr-1"></i> Caja por fuera</a>
                <?php } ?>
                <?php if (!empty($res[0]['TRASPASO_EVIDENCIA2'])) { ?>
                    <a href="../<?=$res[0]['TRASPASO_EVIDENCIA2']?>" target="_blank" class="btn btn-sm btn-outline-info mr-2 mb-1"><i class="mdi mdi-image mr-1"></i> Caja abierta</a>
                <?php } ?>
                <?php if (!empty($res[0]['TRASPASO_EVIDENCIA3'])) { ?>
                    <a href="../<?=$res[0]['TRASPASO_EVIDENCIA3']?>" target="_blank" class="btn btn-sm btn-outline-info mr-2 mb-1"><i class="mdi mdi-image mr-1"></i> Artículos</a>
                <?php } ?>
                <?php if (!empty($res[0]['TRASPASO_EVIDENCIA4'])) { ?>
                    <a href="../<?=$res[0]['TRASPASO_EVIDENCIA4']?>" target="_blank" class="btn btn-sm btn-outline-success mr-2 mb-1"><i class="mdi mdi-truck-delivery mr-1"></i> Guía pegada</a>
                <?php } ?>
                <?php 
                if (!empty($res[0]['TRASPASO_LISTAEMPAQUE'])) { 
                    $docsLista = json_decode($res[0]['TRASPASO_LISTAEMPAQUE'], true);
                    if (is_array($docsLista)) {
                        foreach ($docsLista as $idx => $docPath) {
                            $numHoja = $idx + 1;
                            echo '<a href="../' . $docPath . '" target="_blank" class="btn btn-sm btn-outline-primary mr-2 mb-1"><i class="mdi mdi-format-list-bulleted mr-1"></i> Lista de Empaque (Hoja ' . $numHoja . ')</a>';
                        }
                    } else {
                        echo '<a href="../' . $res[0]['TRASPASO_LISTAEMPAQUE'] . '" target="_blank" class="btn btn-sm btn-outline-primary mr-2 mb-1"><i class="mdi mdi-format-list-bulleted mr-1"></i> Lista de Empaque</a>';
                    }
                } 
                ?>
            </div>
            <?php } ?>
            <br><br>
            <h4>Detalle:<hr></h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <tr>
                        <th>ID</th>
                        <th>CVE</th>
                        <th>ARTICULO</th>
                        <th>LOTE</th>
                        <th>CADUCIDAD</th>
                        <th>SERIE</th>
                    </tr>
                    <?php if ($res[0]['STOCK_FOLIO'] <> "") { ?>
                        <?php foreach ($res as $re){ ?>
                            <?php
                                $esActivo = $re['TRASPASODET_ACTIVO'] ?? 0;
                                $style = ($esActivo == 0) ? 'background-color: #fffacc;' : '';
                                $nota = ($esActivo == 0) ? ' <span style="font-size:10px;color:#caa200">(No activo)</span>' : '';
                            ?>
                            <tr style="<?=$style?>">
                                <td><?=$re['STOCK_FOLIO']?></td>
                                <td><?=$re['CLAVE_ARTICULO']?></td>
                                <td><?=$re['ARTICULO_NOMBRE']?><?=$nota?></td>
                                <td><?=$re['ESDET_LOTE']?></td>
                                <td><?=$re['ESDET_CADUCIDAD']?></td>
                                <td><?=$re['ESDET_SERIE']?></td>
                            </tr>
                        <?php } ?>
                    <?php }else{ ?>
                        <tr><td colspan="6"><div class="alert alert-warning text-center">No se encontró detalle</div></td></tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php");?>