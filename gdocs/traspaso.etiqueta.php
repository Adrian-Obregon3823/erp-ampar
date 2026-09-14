<?php include_once("../includes/includes.php");?>
<?php
require '../vendor/autoload.php';

$traspasoid = base64_decode($_GET['traspasoid']);
$traspasos = new traspasos();
$res = $traspasos->getinfotraspasobyid($traspasoid);

if (!$res || empty($res) || $res === 0) {
    die("Traspaso no encontrado.");
}

// Tamaño Carta
$pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

$pdf->AddPage();

$folio = $res[0]['TRASPASO_FOLIO'];
$cantidad = count($res); 

// Agregamos saltos de línea para centrarlo verticalmente en la hoja carta
$html = '
    <div style="text-align: center;">
        <br><br><br><br><br><br><br><br><br><br>
        <h1 style="font-size: 80px; margin: 0; padding: 0; font-family: Helvetica, sans-serif;">'.$folio.'</h1>
        <br><br>
        <h2 style="font-size: 30px; font-weight: normal; margin: 0; padding: 0; font-family: Helvetica, sans-serif;">Artículos: '.$cantidad.'</h2>
    </div>
';

$pdf->writeHTML($html, true, false, true, false, '');

$pdf->Output('etiqueta_'.$folio.'.pdf', 'I');
?>
