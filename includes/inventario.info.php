<?php include_once("../includes/includes.php");?>
<?php include_once("../includes/head.php");?>
<?php 
$almacen = new almacenes();
$res = $almacen->getinfosolicitudinventario(base64_decode($_GET['inventarioid']));
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Solicitud de Inventario</h4>
        </div>
        <div class="card-body">
            <h4>Generales:<hr></h4>
            <table class="table table-bordered">
                <tr>
                    <th width="200px">FOLIO</th>
                    <td><?=$res[0]['INVENTARIO_FOLIO'];?></td>
                </tr>
                <tr>
                    <th>TIPO</th>
                    <td><?=$res[0]['INVENTARIO_TIPO'];?></td>
                </tr>
                <tr>
                    <th>ALMACEN</th>
                    <td><?=$res[0]['SUCALMACEN_NOMBRE'];?></td>
                </tr>
                <tr>
                    <th>SUCURSAL</th>
                    <td><?=$res[0]['NOMBRE'];?></td>
                </tr>
                <tr>
                    <th>CONCEPTO</th>
                    <td><?=$res[0]['CONCEPTO_NOMBRE'];?></td>
                </tr>
                <tr>
                    <th>MOTIVO</th>
                    <td><?=$res[0]['INVENTARIO_MOTIVO'];?></td>
                </tr>
            </table>
            <br><br>
            <h4>Detalle:<hr></h4>
            <table class="table table-bordered">
                <tr>
                    <th>ARTICULO</th>
                    <th>CANTIDAD</th>
                    <th>LOTE</th>
                    <th>SERIE</th>
                </tr>
                <?php foreach ($res as $re){ ?>
                    <tr>
                        <td><?=$re['ARTICULO_NOMBRE']?></td>
                        <td><?=$re['INVENTARIODET_CANTIDAD']?></td>
                        <td><?=$re['INVENTARIODET_LOTE']?></td>
                        <td><?=$re['INVENTARIODET_SERIE']?></td>
                    </tr>
                <?php } ?>
            </table>

        </div>
    </div>
</div>
<?php include_once("../includes/foot.php");?>