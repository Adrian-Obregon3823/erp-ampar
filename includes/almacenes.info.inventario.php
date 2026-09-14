<?php include_once("../includes/includes.php");?>
<?php include_once("../includes/head.php");?>
<?php 
$almacen = new almacenes();
$res = $almacen->getinfoalmacen(base64_decode($_GET['almacenid']));
$articulos = $almacen->countinventariosbyalmacen(base64_decode($_GET['almacenid']));
?>
<div class="row">
    <div class="col-4">
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
            </div>
        </div>
    </div>
    <div class="col-8">
        <div class="card">
            <div class="card-header">
                <h4>Artículos</h4>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <?php if ($articulos <> 0){ ?>
                        <tr>
                            <th>ID</th>
                            <th>CANTIDAD</th>
                        </tr>
                        <?php foreach ($articulos as $art){ ?>
                            <tr>
                                <th><?=$art['ARTICULO_NOMBRE']?></th>
                                <th><?=$art['CANTIDAD_TOTAL']?></th>
                            </tr>
                        <?php } ?>
                    <?php }else{ ?>
                        Este inventario no cuenta con artículos registrados
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php");?>