<?php include_once("../includes/includes.php"); ?>
<?php
require '../vendor/autoload.php';

$traspasoid = base64_decode($_GET['traspasoid']);
$traspasos = new traspasos();
$res = $traspasos->getinfotraspasobyid($traspasoid);

$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->AddPage();
$pdf->SetFont('freesans', '', 9);

$logoData = base64_encode(file_get_contents(__DIR__ . '/../img/logo.png'));
$logoSrc = '@' . $logoData;

$usuarioNombre = htmlspecialchars($res[0]['USUARIO_NOMBRE'] ?? '');

$html = '
<style>
    table { border-collapse: collapse; }
    .b1 { border: 1px solid #1a3a6b; }
    .b2 { border: 1.5px solid #1a3a6b; }
    .th { background-color: #f0f4fa; color: #1a3a6b; font-weight: bold; text-align: center; }
    .lbl { font-size: 8px; color: #333; }
</style>

<table width="100%" cellpadding="3">
    <tr>
        <td width="25%"><img src="' . $logoSrc . '" width="110"></td>
        <td width="75%" align="center">
            <span style="font-size:13px; color:#1a3a6b; font-weight:bold;">AMPAR DE MEXICO</span><br>
            <span style="font-size:11px; color:#1a3a6b; font-weight:bold;">DIVISIÓN CARDIOLOGÍA</span><br>
            <span style="font-size:10px; color:#1a3a6b; font-weight:bold;">AFILIACIÓN MÉDICA PRIVADA AVANZADA DE RECUPERACIÓN SA DE CV</span><br>
            <span style="font-size:10px; color:#c0392b; font-style:italic;">"Permítenos trabajar con usted y para usted"</span>
        </td>
    </tr>
</table>
<br>
<h3 align="center" style="color: #1a3a6b;">TRASPASO DE ALMACÉN</h3>
<br>

<table width="100%" cellpadding="3">
    <tr>
        <td width="15%" class="b2 th" align="left">FOLIO</td>
        <td width="85%" class="b2" style="font-size:11px;"><b>' . $res[0]['TRASPASO_FOLIO'] . '</b></td>
    </tr>
    <tr>
        <td width="15%" class="b2 th" align="left">DE ALMACÉN</td>
        <td width="85%" class="b2" style="font-size:11px;">' . $res[0]['DEALMACEN'] . ' - ' . $res[0]['DESUCURSAL'] . '</td>
    </tr>
    <tr>
        <td width="15%" class="b2 th" align="left">A ALMACÉN</td>
        <td width="85%" class="b2" style="font-size:11px;">' . $res[0]['AALMACEN'] . ' - ' . $res[0]['ASUCURSAL'] . '</td>
    </tr>
    <tr>
        <td width="15%" class="b2 th" align="left">MOTIVO</td>
        <td width="85%" class="b2" style="font-size:11px;">' . $res[0]['TRASPASO_MOTIVO'] . '</td>
    </tr>
</table>
<br><br>
<b>Detalle de Artículos</b>
<br><br>
<table width="100%" cellpadding="4">
    <tr class="th">
        <th width="20%" class="b2">FOLIO</th>
        <th width="80%" class="b2" align="left">DESCRIPCIÓN DEL ARTÍCULO</th>
    </tr>
';
foreach ($res as $re) {
    if ($re['TRASPASODET_ACTIVO'] == 1) {
        $colortexto = "#333333";
    } elseif ($re['TRASPASODET_ACTIVO'] == 0) {
        $colortexto = "#888888";
    } else {
        $colortexto = "#cc0000";
    }
    $html .= '
            <tr>
                <td width="20%" class="b1" align="center" style="color:' . $colortexto . '; vertical-align:middle;"><strong>' . $re['STOCK_FOLIO'] . '</strong></td>
                <td width="80%" class="b1" style="color:' . $colortexto . ';">
                    <span style="font-size: 10px;"><b>' . $re['CLAVE_ARTICULO'] . '</b> - ' . $re['ARTICULO_NOMBRE'] . '</span>
                    <br>
                    <table width="100%" border="0" cellpadding="1">
                        <tr>
                            <td width="35%" style="color:#666666; font-size:9px;"><strong>Lote:</strong> ' . $re['ESDET_LOTE'] . '</td>
                            <td width="35%" style="color:#666666; font-size:9px;"><strong>Caducidad:</strong> ' . $re['ESDET_CADUCIDAD'] . '</td>
                            <td width="30%" style="color:#666666; font-size:9px;"><strong>Serie:</strong> ' . $re['ESDET_SERIE'] . '</td>
                        </tr>
                    </table>
                </td>
            </tr>';
}
$html .= '
</table>
<br><br><br><br>
<table width="100%" border="0" cellpadding="4">
    <tr>
        <td width="15%"></td>
        <td width="30%" align="center">
            <br><br><br>
            <span style="font-size:11px; color:#333;"><b>' . $usuarioNombre . '</b></span>
        </td>
        <td width="10%"></td>
        <td width="30%" align="center">
            <br><br><br>
            <span style="font-size:11px; color:#333;">&nbsp;</span>
        </td>
        <td width="15%"></td>
    </tr>
    <tr>
        <td width="15%"></td>
        <td width="30%" align="center" style="border-top: 1px solid #333333;">
            <b>Responsable</b>
        </td>
        <td width="10%"></td>
        <td width="30%" align="center" style="border-top: 1px solid #333333;">
            <b>Recibió</b>
        </td>
        <td width="15%"></td>
    </tr>
</table>
';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('traspaso.pdf', 'I');
?>