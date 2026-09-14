<?php include_once("../includes/includes.php");?>
<?php include_once("../includes/head.php");?>
<?php 
$almacen = new almacenes();
$res = $almacen->getinfoalmacen(base64_decode($_GET['almacenid']));
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Información de Almacén</h4>
        </div>
        <div class="card-body">
            <h4>Generales:<hr></h4>
            <table class="table table-striped">
                <tr>
                    <th>ID</th>
                    <td><?=$res[0]['ALMACEN_ID'];?></td>
                </tr>
                <tr>
                    <th colspan="2">
                        <?php QRcode::png(base64_encode($res[0]['ALMACEN_ID']), '../uploads/qr/ALMACENID_'.base64_encode($res[0]['ALMACEN_ID']), QR_ECLEVEL_L, 10, 2); ?>
                        <img src="../uploads/qr/ALMACENID_<?=base64_encode($res[0]['ALMACEN_ID'])?>">
                    </th>
                </tr>
                <tr>
                    <th>SUCURSAL</th>
                    <td><?=$res[0]['SUCURSAL_NOMBRE'];?></td>
                </tr>
                <tr>
                    <th>TIPO</th>
                    <td><?=$res[0]['TIPOALMACEN_NOMBRE'];?></td>
                </tr>
                <tr>
                    <th>FECHA</th>
                    <td><?=$res[0]['ALMACEN_FECHACREACION'];?></td>
                </tr>
                <tr>
                    <th>NOMBRE</th>
                    <td><?=$res[0]['ALMACEN_NOMBRE'];?></td>
                </tr>
                <tr>
                    <th>DESCRIPCIÓN</th>
                    <td><?=$res[0]['ALMACEN_DESCRIPCION'];?></td>
                </tr>
                <tr>
                    <th>TELÉFONO</th>
                    <td><?=$res[0]['ALMACEN_TELEFONO'];?></td>
                </tr>
            </table>
            <br><br>
            <h4>Dirección:<hr></h4>
            <table class="table table-striped">
                <tr>
                    <th>CALLE</th>
                    <td><?=$res[0]['ALMACEN_CALLE'];?></td>
                </tr>
                <tr>
                    <th>NÚMERO EXTERIOR</th>
                    <td><?=$res[0]['ALMACEN_NUMEXT'];?></td>
                </tr>
                <tr>
                    <th>NÚMERO INTERIOR</th>
                    <td><?=$res[0]['ALMACEN_NUMINT'];?></td>
                </tr>
                <tr>
                    <th>COLONIA</th>
                    <td><?=$res[0]['ALMACEN_COLONIA'];?></td>
                </tr>
                <tr>
                    <th>CP</th>
                    <td><?=$res[0]['ALMACEN_CP'];?></td>
                </tr>
                <tr>
                    <th>PAÍS</th>
                    <td><?=$res[0]['PAIS_NOMBRE'];?></td>
                </tr>
                <tr>
                    <th>ESTADO</th>
                    <td><?=$res[0]['ESTADO_NOMBRE'];?></td>
                </tr>
                <tr>
                    <th>MUNICIPIO</th>
                    <td><?=$res[0]['MUNICIPIO_NOMBRE'];?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php");?>