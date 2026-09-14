<?php include_once("../includes/includes.php");?>
<?php
require '../vendor/autoload.php';

$ticketid = base64_decode($_GET['ticketid']);
$tickets = new tickets();
$res = $tickets->getticketbyid($ticketid);

$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->AddPage();
$pdf->SetFont('freesans', '', 9);

$html = '
    <img src="'.$GLOBALS['global_site'].'img/logo.png" width="150">
    <h2 align="center">Detalle de Ticket</h2>
    <br>
    <table>
        <tr>
            <th width="100px">FOLIO</th>
            <td>'.$res[0]['TICKET_FOLIO'].'</td>
        </tr>
        <tr>
            <th width="100px">FECHA</th>
            <td>'.$res[0]['TICKET_FECHA'].'</td>
        </tr>
        <tr>
            <th>DE ALMACÉN</th>
            <td>-</td>
        </tr>
        <tr>
            <th>A ALMACÉN</th>
            <td>-</td>
        </tr>
        <tr>
            <th>CONCEPTO</th>
            <td>'.$res[0]['TICKET_CONCEPTO'].'</td>
        </tr>
    </table>
    <br><br>
    <table border="1" cellpadding="4" width="100%">
        <thead>
            <tr style="background-color:#f2f2f2;">
                <th colspan="4" align="left">Detalle de Artículos</th>
            </tr>
        </thead>
        <tbody>';
    foreach ($res as $re) {
        $articuloId = 'A' . str_pad($re['INVENTARIODET_ID'], 6, '0', STR_PAD_LEFT);
        $html .= '
            <tr>
                <td width="140">'.$re['TICKETDET_ETIQUETAESCANER'].'</td>
                <td width="140">'.$re['TICKETDET_ETIQUETABD'].'</td>
                <td width="70"><strong>'.$articuloId.'</strong></td>
                <td width="183"><b>'.$re['CLAVE_ARTICULO'].'</b> - '.$re['NOMBRE'].'</td>
            </tr>
        ';
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