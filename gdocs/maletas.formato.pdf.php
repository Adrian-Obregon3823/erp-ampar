<?php
include_once("../includes/includes.php");

require '../vendor/autoload.php';

$maletaid = base64_decode(rawurldecode($_GET['maletaid']));

// Obtener info de la maleta
$maletas  = new maletas();
$almacenes = new almacenes();
$info = $maletas->getinfomaleta($maletaid);
$res  = $almacenes->getarticulosalmacenbyalmacenid($maletaid);

$folio  = $info[0]['ALMACEN_FOLIO']  ?? 'MALETA';
$nombre = $info[0]['ALMACEN_NOMBRE'] ?? '';

// Filtrar solo registros con status
$articulos = [];
if ($res && is_array($res)) {
    foreach ($res as $r) {
        if ($r['STOCK_STOCKSTATUSID'] != '') {
            $articulos[] = $r;
        }
    }
}

// Logo
$logoData = base64_encode(file_get_contents(__DIR__ . '/../img/logo.png'));
$logoSrc  = '@' . $logoData;

// Filas de la tabla
$filas = '';
foreach ($articulos as $r) {
    $f    = htmlspecialchars($r['STOCK_FOLIO']     ?? '');
    $ref  = htmlspecialchars($r['CLAVE_ARTICULO']  ?? '');
    $nom  = htmlspecialchars($r['NOMBRE']          ?? '');
    $lote = htmlspecialchars($r['STOCK_LOTE']      ?? '');
    $cad  = htmlspecialchars($r['STOCK_CADUCIDAD'] ?? '');
    $filas .= "
        <tr>
            <td class=\"b1\" style=\"padding:3px 5px;\">{$f}</td>
            <td class=\"b1\" style=\"padding:3px 5px;\">{$ref}</td>
            <td class=\"b1\" style=\"padding:3px 5px;\">{$nom}</td>
            <td class=\"b1\" style=\"padding:3px 5px; text-align:center;\">{$lote}</td>
            <td class=\"b1\" style=\"padding:3px 5px; text-align:center;\">{$cad}</td>
        </tr>";
}

$total = count($articulos);
$fecha = date('d/m/Y');

$html = '
<style>
    table  { border-collapse: collapse; width: 100%; }
    .b1    { border: 1px solid #1a3a6b; }
    .th    { background-color: #1a3a6b; color: #fff; font-weight: bold; text-align: center; padding: 5px; font-size: 8px; }
    .lbl   { font-size: 8px; color: #555; }
    .val   { font-size: 9px; font-weight: bold; }
    .total { background-color: #1a3a6b; color: #fff; font-weight: bold; text-align: right; padding: 4px 6px; font-size: 9px; }
</style>

<!-- Encabezado -->
<table width="100%" cellpadding="4">
    <tr>
        <td width="22%"><img src="' . $logoSrc . '" width="100"></td>
        <td width="56%" style="text-align:center;">
            <span style="font-size:14px; font-weight:bold; color:#1a3a6b;">DETALLE DE MALETA</span><br>
            <span style="font-size:11px; color:#333;">' . htmlspecialchars($nombre) . '</span>
        </td>
        <td width="22%" style="text-align:right; font-size:8px; color:#555;">
            <b>Folio:</b> ' . htmlspecialchars($folio) . '<br>
            <b>Fecha:</b> ' . $fecha . '<br>
            <b>Total:</b> ' . $total . ' productos
        </td>
    </tr>
</table>
<br>

<!-- Tabla de productos -->
<table cellpadding="3">
    <tr>
        <td class="th" width="13%">FOLIO</td>
        <td class="th" width="18%">REFERENCIA</td>
        <td class="th" width="42%">NOMBRE</td>
        <td class="th" width="13%">LOTE</td>
        <td class="th" width="14%">CADUCIDAD</td>
    </tr>
    ' . $filas . '
    <tr>
        <td colspan="5" class="total">TOTAL: ' . $total . ' productos</td>
    </tr>
</table>
';

$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('AMPAR');
$pdf->SetAuthor('AMPAR');
$pdf->SetTitle('Detalle Maleta ' . $folio);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(8, 8, 8);
$pdf->AddPage();
$pdf->SetFont('freesans', '', 8);
$pdf->writeHTML($html, true, false, true, false, '');

$pdf->Output('Maleta.' . $folio . '.' . date('Y-m-d') . '.pdf', 'D');
