<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php 
$entradasalida = new entradasalida();
$res = $entradasalida->getinfogarantiaproveedorbyid(base64_decode($_GET['cpid']));
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Garantía de Proveedor</h4>
        </div>
        <div class="card-body">
            <h4>Generales:<hr></h4>
            <table class="table table-bordered">
                <tr>
                    <th width="200px">Folio</th>
                    <td><?=$res[0]['CADUCIDADPROV_FOLIO'];?> <span style="font-size:10px; color:#<?=$res[0]['STATUS_COLOR']?>">(<?=$res[0]['STATUS_NOMBRE']?>)</span></td>
                </tr>
                <tr>
                    <th>Fecha</th>
                    <td><?=$res[0]['CADUCIDADPROV_FECHA'];?></td>
                </tr>
                <tr>
                    <th>Almacén</th>
                    <td><?=$res[0]['ALMACEN_NOMBRE'];?> (Sucursal:<?=$res[0]['SUCURSAL_NOMBRE'];?>)</td>
                </tr>
                <tr>
                    <th>Usuario</th>
                    <td><?=$res[0]['USUARIO_NOMBRE'];?></td>
                </tr>
            </table>
            <br><br>
            <h4>Detalle:<hr></h4>
            <table class="table table-bordered">
                <tr>
                    <th>#</th>
                    <th>CVE</th>
                    <th>ARTICULO</th>
                    <th>LOTE</th>
                    <th>CADUCIDAD</th>
                    <th>REEMPLAZADO</th>
                </tr>
                <?php foreach ($res as $index => $re) { ?>
                    <?php 
                        $contador = $index + 1; 

                        // Si está reemplazado, muestra el angulito
                        $icono = '';
                        if ($re['CADUCIDADPROVDET_REEMPLAZADO'] == 1) {
                            $icono = '<span style="font-size:14pt; color:#007bff;">&#10003;</span>'; // ► azul
                        }

                    ?>
                    <tr>
                        <td><?=$contador?></td>
                        <td><b><?=$re['STOCK_FOLIO']?></b></td>
                        <td><?=$re['ARTICULO_NOMBRE']?> (<?=$re['CLAVE_ARTICULO']?>)</td>
                        <td><?=$re['STOCK_LOTE']?></td>
                        <td><?=$re['STOCK_CADUCIDAD']?></td>
                        <td><?=$icono?></td>
                    </tr>
                <?php } ?>
            </table>

        </div>
    </div>
</div>