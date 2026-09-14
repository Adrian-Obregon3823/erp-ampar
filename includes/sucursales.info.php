<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* INTERFAZ DE INFORMACIÓN DE SUCURSAL
*********************************************************************************
*/
?>
<?php
$sucursales = new sucursales();
$res = $sucursales->getsucursalbyid(base64_decode($_GET['sucursalid']));
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Sucursal <strong><?=$res[0]["SUCURSAL_NOMBRE"]?></strong> </h4>
        </div>
        <div class="card-body">
            <?php
            if ($res == 0){
                echo "No se encontró información";
            }else{
                ?>
                <div class="table-responsive">
                        <table class="table">
                            <tr>
                                <th  width="100px">Id</th>
                                <td><?= $res[0]['SUCURSAL_FOLIO'] ?></td>
                            </tr>
                            <tr>
                                <th>Matriz</th>
                                <td><?=($res[0]['ES_MATRIZ'])?'SI':'NO'?></td>
                            </tr>
                            <tr>
                                <th>Dirección</th>
                                <td><?= $res[0]['NOMBRE_CALLE']?> <?= $res[0]['NUM_EXTERIOR'] ?> <?=($res[0]['NUM_INTERIOR'])?'interior '.$res[0]['NUM_INTERIOR']:''?><?=($res[0]['COLONIA'])?', colonia '.$res[0]['COLONIA']:''?><?=($res[0]['CODIGO_POSTAL'])?', C.P '.$res[0]['CODIGO_POSTAL']:''?></td>
                            </tr>
                            <tr>
                                <th  width="100px">Población</th>
                                <td><?= $res[0]['POBLACION'] ?></td>
                            </tr>
                            <tr>
                                <th  width="100px">Ciudad</th>
                                <td><?= $res[0]['CIUDAD_NOMBRE'] ?></td>
                            </tr>
                            <tr>
                                <th  width="100px">Estado</th>
                                <td><?= $res[0]['ESTADO_NOMBRE'] ?></td>
                            </tr>
                            <tr>
                                <th  width="100px">País</th>
                                <td><?= $res[0]['PAIS_NOMBRE'] ?></td>
                            </tr>
                            <tr>
                                <th  width="100px">Referencia</th>
                                <td><?= $res[0]['REFERENCIA'] ?></td>
                            </tr>
                            <tr>
                                <th  width="100px">Lugar de Expedición</th>
                                <td><?= $res[0]['LUGAR_EXPEDICION'] ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>