<?php include_once("../includes/includes.php");?>
<?php include_once("../includes/head.php");?>
<?php 
$almacen = new almacenesv2();
$res = $almacen->getsucursalalmacenbyid(base64_decode($_GET['sucalmid']));
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Información de Sucursal Almacén</h4>
        </div>
        <div class="card-body">
            <h4>Generales:<hr></h4>
            <table class="table table-striped">
                <tr>
                    <th>ID</th>
                    <td><?=$res[0]['SUCALMACEN_ID']?></td>
                </tr>
                <tr>
                    <th>NOMBRE</th>
                    <td><?=$res[0]['SUCALMACEN_NOMBRE']?></td>
                </tr>
                <tr>
                    <th>SUCURSAL</th>
                    <td><?=$res[0]['SUCURSAL_NOMBRE']?></td>
                </tr>
                <tr>
                    <th>ALMACÉN</th>
                    <td><?=$res[0]['ALMACEN_NOMBRE']?></td>
                </tr>
            </table>
            <br><br>
            <h4>Artículos:<hr></h4>
            <table class="table">
                <!-- Concultar información de artículos en existencia-->
                 <?php 
                 $resarticulos = $almacen->countinventariosbyalmacen(base64_decode($_GET['sucalmid']));
                 if ($resarticulos <> 0){
                    ?>
                    <table class="table">
                        <tr>
                            <th>Artículo</th>
                            <th>Cantidad</th>
                        </tr>
                        <?php foreach ($resarticulos as $rea){?>
                            <tr>
                                <td><?=$rea['ARTICULO_NOMBRE']?></td>
                                <td><?=$rea['CANTIDAD_TOTAL']?></td>
                            </tr>
                        <?php } ?>
                    </table>
                    <?php
                 }else{
                    echo '<div class="text-danger">No se encontrarn artículos registrados</div>';
                 }
                 ?>

            </table>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php");?>