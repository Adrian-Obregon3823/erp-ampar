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
            <th>RESPONSABLE</th>
            <td>'.($res[0]['USUARIO_NOMBRE'] ?: '-').'</td>
        </tr>
        <tr>
            <th>MALETA</th>
            <td>'.($res[0]['ESCANEO_FOLIO'] ?: '-').'</td>
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
                <th width="80%" align="left"><b>Artículos Faltantes</b></th>
                <th width="20%" align="center"><b>Cant.</b></th>
            </tr>
        </thead>
        <tbody>';
    
    $agrupados = [];
    foreach ($res as $re) {
        $nombre = $re['NOMBRE'] ?: $re['TICKETDET_ETIQUETABD'];
        if ($nombre) {
            if (!isset($agrupados[$nombre])) {
                $agrupados[$nombre] = 0;
            }
            $agrupados[$nombre]++;
        }
    }
    
    foreach ($agrupados as $nombre => $cantidad) {
        $html .= '
            <tr>
                <td width="80%">'.$nombre.'</td>
                <td width="20%" align="center"><b>'.$cantidad.'</b></td>
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