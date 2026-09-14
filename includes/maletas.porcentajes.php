<?php include_once ("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Interfaz para ver porcentajes de articulos de Maletas
*********************************************************************************
*/
?>
<?php 
$maletas = new maletas();
$resarticulos = $maletas->getporcentajebymaleta(base64_decode($_GET['maletaid']));
?>
<!-- Botón de regresar -->
<button id="btnRegresarMaleta" class="btn btn-warning">
    <i class="bi bi-arrow-left me-1"></i> Regresar
</button>
<br><br>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Porcentajes de Maleta <strong><?=$resarticulos[0]['ALMACEN_FOLIO'] ?></strong></h4>
        </div>
        <div class="card-body">
            <!-- Información General de Almacen -->
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <table class="table" width="100%">
                    <tr>
                        <th width="120px">Tipo de Maleta</th>
                        <td><?= $resarticulos[0]['TIPOMALETA_NOMBRE'] ?></td>
                    </tr>
                    <tr>
                        <th  width="100px">Sucursal</th>
                        <td><strong><?=$resarticulos[0]['SUCURSAL_NOMBRE'] ?></strong></td>
                    </tr>
                    <tr>
                        <th>Nombre</th>
                        <td><?= $resarticulos[0]['ALMACEN_NOMBRE'] ?></td>
                    </tr>
                </table>
            </div>
            <br>
            <!-- Listado de productos -->
            <div class="row mb-3 align-items-center">
                <div class="col-12 col-md-4 mb-2 mb-md-0">
                    <h4 class="mb-0">Listado de Productos</h4>
                </div>
            </div>
            <?php
            if ($resarticulos == 0){
                echo '<div class="alert alert-warning text-center">No se encontraron productos</div>';
            }else{
                ?>
                <div class="card card-rounded">
                    <div class="card-body">
                    <div class="table table-responsive">
                        <table class="table table-bordered" style="font-size:9px !important">
                            <thead class="table-light">
                                <tr>
                                    <th>Cve</th>
                                    <th>Artículo</th>
                                    <th>Cantidad Sugerida</th>
                                    <th>Stock</th>
                                    <th>Porcentaje</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resarticulos as $p): ?>
                                <tr>
                                    <td><strong><?=$p['ARTICULO_CLAVE']?></strong></td>
                                    <td><?=$p['ARTICULO_NOMBRE']?></td>
                                    <td><?=$p['TIPOMALETADET_CANTIDADSUGERIDA']?></td>
                                    <td><?=$p['EXISTENCIA']?></td>
                                    <td>
                                        <div class="progress-bar-container">
                                          <div class="progress-bar" style="width: <?=$p['PORCENTAJE']?>%;" id="progress"></div>
                                        </div>
                                    </td>
                                    <td><?=$p['PORCENTAJE']?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>