<?php include_once("../includes/includes.php");?>
<?php 
$remisionid = base64_decode($_GET['remisionid']);
$remisiones = new remisiones();
$res = $remisiones->getremisionbyid($remisionid);
$resp = $remisiones->getremisionprovinfobyid($remisionid);
?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Nota de Remisión</h4>
        </div>
        <div class="card-body">
            <h4>Generales:<hr></h4>
            <table class="table table-bordered">
                <tr>
                    <th width="200px">FOLIO</th>
                    <td><?=$res[0]['REMISION_FOLIO'];?></td>
                </tr>
                <tr>
                    <th width="200px">FECHA</th>
                    <td><?=$res[0]['REMISION_FECHA'];?></td>
                </tr>
                <tr>
                    <th>CONCEPTO</th>
                    <td><?=$res[0]['EVENTO_CONCEPTO'];?></td>
                </tr>
                <tr>
                    <th>CLIENTE</th>
                    <td><?=$res[0]['CLIENTE_NOMBRE'];?></td>
                </tr>
                <tr>
                    <th>HOSPITAL</th>
                    <td><?=$res[0]['HOSPITAL_NOMBRE'];?></td>
                </tr>
                <tr>
                    <th>ESPECIALISTA</th>
                    <td><?=$res[0]['ESPECIALISTA_NOMBRE'];?></td>
                </tr>
                <tr>
                    <th>CHOFER</th>
                    <td><?=$res[0]['CHOFER_NOMBRE'];?></td>
                </tr>
            </table>
            <br><br>
            <h4>Detalle de Artículos:<hr></h4>
            <?php if ($res[0]['ESDET_ID']=='' and $resp==0){?>
                No se encontraron artículos
            <?php }else{ ?>
                <table class="table table-bordered">
                    <tr>
                        <th>CLAVE</th>
                        <th>ARTICULO</th>
                        <th>PRECIO</th>
                        <th></th>
                    </tr>
                    <?php $grantotal = 0; ?>
                    <?php foreach ($res as $re){ ?>
                        <?php $grantotal += $re['REMISIONARTICULO_PRECIO']; ?>
                        <tr>
                            <td><?=$re['STOCK_FOLIO']?></td>
                            <td><b><?=$re['CLAVE_ARTICULO']?></b><?=$re['ARTICULO_NOMBRE']?></td>
                            <td>$<?=number_format($re['REMISIONARTICULO_PRECIO'],2,'.',',')?></td>
                            <td><img src="../img/delete.png" width="28px" heigth="28px" onclick="eliminar('AMPAR','<?=$re['REMISIONARTICULO_ID']?>')"></td>
                        </tr>
                    <?php } ?>
                    <?php
                    if ($resp <> 0){
                        foreach ($resp as $re){?>
                            <?php $grantotal += $re['PRECIO']*$re['REMISIONPROVARTICULO_CANTIDAD']; ?>
                            <tr>
                                <td></td>
                                <td><b><?=$re['CLAVE_ARTICULO']?></b><?=$re['NOMBRE_ARTICULO']?> (<?=$re['NOMBREPROVEEDOR']?>)</td>
                                <td>$<?=number_format($re['PRECIO'],2,'.',',')?></td>
                                <td><img src="../img/delete.png" width="28px" heigth="28px" onclick="eliminar('PROVEEDOR','<?=$re['REMISIONPROVARTICULO_ID']?>')"></td>
                            </tr>
                            ';
                        <?php }
                    }
                    ?>
                </table>
                <br>
                <h4 align="right">
                    SUBTOTAL $<?=number_format($grantotal,2,'.',',')?>
                    <br>IVA $<?=number_format(($grantotal*.16),2,'.',',')?>
                    <br>TOTAL $<?=number_format(($grantotal*1.16),2,'.',',')?>
                </h4>
            <?php } ?>
        </div>
    </div>
</div>
<?php include_once("../includes/foot.php");?>
<script>
    function eliminar(tipo,id){
        $.ajax({
            url: '../ajax/remisiones.eliminardetalle.php',
            type: 'POST',
            data: {
                tipo : tipo,
                id, id
            },
            dataType: 'html',
            beforeSend: function() {
                $("#loading").show();
            },
            success: function(response) {
                if (response.trim() === ""){
                    alert("test");
                    location.reload();
                }else{
                    alert (response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en la solicitud:', error);
            },
            complete: function() {
                $("#loading").hide();
            }
        });
    }
</script>