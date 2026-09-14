<?php include_once("../includes/includes.php");?>
<?php
require '../vendor/autoload.php';
$compras = new compras();
$res = $compras->getinfocomprabyid(base64_decode($_GET['compraid']));


$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->AddPage();
$pdf->SetFont('freesans', '', 9);


// Definir HTML con estilos
$html = '
    <img src="'.$GLOBALS['global_site'].'img/logo.png" width="150">
    <h2 align="center">Solicitud de Compra</h2>
    <br>
    <table>
        <tr>
            <th width="100px">FOLIO</th>
            <td>'.$res[0]['COMPRA_FOLIO'].'</td>
        </tr>
        <tr>
            <th width="100px">FECHA</th>
            <td>'.$res[0]['COMPRA_FECHA'].'</td>
        </tr>
        <tr>
            <th>SUCURSAL</th>
            <td>'.$res[0]['NOMBRE'].'</td>
        </tr>
        <tr>
            <th>CONCEPTO</th>
            <td>'.$res[0]['COMPRA_MOTIVO'].'</td>
        </tr>
    </table>
    <br><br>
    <table border="1" cellpadding="4" width="100%">
        <thead>
            <tr style="background-color:#f2f2f2;">
                <th width="135">Cve</th>
                <th width="300">Artículos</th>
                <th width="100">Cantidad</th>
            </tr>
        </thead>
        <tbody>';
            foreach ($res as $re) {
                $html .= '
                    <tr>
                        <td width="135"><b>'.$re['CLAVE_ARTICULO'].'</b></td>
                        <td width="300">'.$re['ARTICULO_NOMBRE'].'</td>
                        <td width="100"><strong>'.$re['COMPRADET_CANTIDAD'].'</strong></td>
                    </tr>';
            }
            $html .= '
        </tbody>
    </table>
';

// Usar writeHTML para renderizar el HTML en el PDF
$pdf->writeHTML($html, true, false, true, false, '');


// Generar y mostrar el PDF
$pdf->Output('archivo.pdf', 'I');
?>