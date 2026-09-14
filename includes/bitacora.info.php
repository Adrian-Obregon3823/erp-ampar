<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php 
    $bitacoraid = base64_decode($_GET['bitacoraid']);
    $bitacora = new bitacora();
    $res = $bitacora->getbitacorabyid($bitacoraid);
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Movimiento</h4>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th width="180px">Fecha</th>
                    <td><?=$res[0]['BITACORA_FECHA']?></td>
                </tr>
                <tr>
                    <th>Comentario</th>
                    <td><?=$res[0]['BITACORA_COMENTARIO']?></td>
                </tr>
                <tr>
                    <th>Usuario</th>
                    <td><?=$res[0]['BITACORA_USUARIO']?></td>
                </tr>
                <tr>
                    <th>IP Usuario</th>
                    <td><?=$res[0]['BITACORA_USUARIOIP']?></td>
                </tr>
                <tr>
                    <th>Sucursal</th>
                    <td><?=$res[0]['SUCURSAL_NOMBRE']?></td>
                </tr>
                <tr>
                    <th>Almacen</th>
                    <td><?=$res[0]['ALMACEN_NOMBRE']?></td>
                </tr>
                <tr>
                    <th>Artículo Almacén</th>
                    <td><?=$res[0]['STOCK_FOLIO']?></td>
                </tr>
                <tr>
                    <th>Artículo</th>
                    <td><b><?=$res[0]['ARTICULO_CLAVE']?></b> - <?=$res[0]['ARTICULO_NOMBRE']?></td>
                </tr>
            </table>
        </div>
    </div>
</div>