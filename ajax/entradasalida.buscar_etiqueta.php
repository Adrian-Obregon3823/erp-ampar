<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

$entradasalida = new entradasalida();
$folio = isset($_GET['folio']) ? trim($_GET['folio']) : '';

if ($folio !== '') {
    $res = $entradasalida->getinfostockbyfolio($folio);
    
    if (!empty($res) && $res !== 0) {
        $re = $res[0]; // Tomamos el primer resultado
        ?>
        <tr id="row_<?= $re['STOCK_FOLIO'] ?>">
            <td style="padding: 10px; vertical-align: middle;">
                <input type="checkbox" class="etiqueta-checkbox" value="<?= $re['STOCK_FOLIO'] ?>" checked onchange="updateSelectAll()">
            </td>
            <td style="padding: 10px;">
                <img src="../includes/entradasalida.cb.php?code=<?= $re['STOCK_FOLIO'] ?>" style="height:110px; width:300px; display:block; margin:0 auto;"><br>
            </td>
            <td style="padding: 10px;">
                <img src="../includes/entradasalida.qr.php?code=<?= base64_encode($re['STOCK_ID']) ?>" style="height:110px; width:110px; display:block; margin:0 auto;"><br>
            </td>
            <td style="padding: 10px;">
                <b style="font-size:20px"><?= $re['STOCK_FOLIO']; ?></b><br>
                <?= $re['ALMACEN_NOMBRE']; ?> (<?= $re['SUCURSAL_NOMBRE']; ?>)<br>
                <?= $re['CLAVE_ARTICULO'] ?><br>
                <?= $re['ARTICULO_NOMBRE'] ?><br>
                <?= $re['ESDET_LOTE'] ?><br>
                <?= $re['ESDET_CADUCIDAD'] ?><br>
                <?= $re['ESDET_SERIE'] ?>
            </td>
            <td style="padding: 10px; vertical-align: middle; text-align: center;">
                <button type="button" class="btn btn-danger btn-sm" onclick="removerFila('row_<?= $re['STOCK_FOLIO'] ?>')"><i class="mdi mdi-delete"></i></button>
            </td>
        </tr>
        <?php
    } else {
        echo "NOT_FOUND";
    }
}
?>
