<?php include_once("../includes/includes.php");?>
<?php
require '../vendor/autoload.php';

$ocid = base64_decode($_GET['ocid']);
$oc = new oc();
$res = $oc->getocbyid($ocid);

if (empty($res)) {
    echo "No se encontró la orden de compra.";
    exit;
}

$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->AddPage();
$pdf->SetFont('freesans', '', 9);

// Read header values
$subtotalHeader = $res[0]['OC_SUBTOTAL'];
$descuentoHeader = $res[0]['OC_DESCUENTO'];
$ivaHeader = $res[0]['OC_IMPUESTOS'];
$totalHeader = $res[0]['OC_TOTAL'];

$hasHeaderCalculations = ($subtotalHeader !== null);

$logo = realpath(__DIR__ . '/../img/logo.png');
$html = '
<style>
    table { border-collapse: collapse; }
    .b1 { border: 1px solid #1a3a6b; }
    .b2 { border: 1.5px solid #1a3a6b; }
    .th { background-color: #f0f4fa; color: #1a3a6b; font-weight: bold; text-align: center; }
    .lbl { font-size: 8px; color: #333; text-align: center; }
    .totlbl { background-color: #f0f4fa; color: #1a3a6b; font-weight: bold; text-align: right; }
</style>

<table width="100%" cellpadding="3">
    <tr>
        <td width="20%" valign="middle"><img src="' . $logo . '" width="120"></td>
        <td width="60%" align="center">
            <span style="font-size:12px; color:#1a3a6b; font-weight:bold;">AMPAR DE MEXICO</span><br>
            <span style="font-size:12px; color:#1a3a6b; font-weight:bold;">DIVISIÓN CARDIOLOGÍA</span><br>
            <span style="font-size:12px; color:#1a3a6b; font-weight:bold;">AFILIACIÓN MÉDICA PRIVADA AVANZADA DE RECUPERACIÓN SA DE CV</span><br>
            <span style="font-size:10px; color:#c0392b; font-style:italic;">"Permítenos trabajar con usted y para usted"</span>
        </td>
        <td width="20%"></td>
    </tr>
</table>

<br>
<div align="center">
    <span style="font-size:12px; color:#1a3a6b; font-weight:bold; text-decoration:underline;">ORDEN DE COMPRA</span>
</div>
<br>

<table width="100%" cellpadding="2">
    <tr>
        <td width="60%" class="lbl">Sucursal</td>
        <td width="20%" class="lbl">Fecha</td>
        <td width="20%" class="lbl">Folio</td>
    </tr>
    <tr>
        <td width="60%" class="b2" align="left">' . htmlspecialchars((string)$res[0]['SUCURSAL_NOMBRE'], ENT_QUOTES, 'UTF-8') . '</td>
        <td width="20%" class="b2" align="center">' . $res[0]['OC_FECHA'] . '</td>
        <td width="20%" class="b2" align="center"><span style="color:#c0392b; font-weight:bold;">' . $res[0]['OC_FOLIO'] . '</span></td>
    </tr>
</table>

<table width="100%" cellpadding="3" style="margin-top:15px;">
    <thead>
        <tr>
            <td width="15%" class="b2 th">Clave</td>
            <td width="35%" class="b2 th">Artículo</td>
            <td width="10%" class="b2 th">Cant.</td>
            <td width="12%" class="b2 th">Costo</td>
            <td width="8%" class="b2 th">Desc. %</td>
            <td width="8%" class="b2 th">IVA</td>
            <td width="12%" class="b2 th">Total</td>
        </tr>
    </thead>
    <tbody>';
        
        $calcSubtotal = 0;
        $calcDescuento = 0;
        $calcIva = 0;
        $calcTotal = 0;

        foreach ($res as $re) {
            $cant = (float)$re['OCDET_CANTIDAD'];
            $precio = (float)$re['OCDET_PRECIO'];
            
            // Item level fields (if null, fallback for old records)
            $descPct = isset($re['OCDET_DESCUENTO_PCT']) ? (float)$re['OCDET_DESCUENTO_PCT'] : 0.00;
            $ivaPct = isset($re['OCDET_IVA_PCT']) ? (float)$re['OCDET_IVA_PCT'] : 16.00;
            
            if (isset($re['OCDET_SUBTOTAL']) && $re['OCDET_SUBTOTAL'] !== null) {
                $itemSubtotal = (float)$re['OCDET_SUBTOTAL'];
                $itemTotal = (float)$re['OCDET_TOTAL'];
                $itemIva = $itemTotal - $itemSubtotal;
                $itemDesc = ($cant * $precio) * ($descPct / 100);
            } else {
                // Fallback calculations for old records
                $itemSubtotal = $cant * $precio;
                $itemDesc = 0;
                $itemIva = $itemSubtotal * ($ivaPct / 100);
                $itemTotal = $itemSubtotal + $itemIva;
            }

            $calcSubtotal += ($cant * $precio);
            $calcDescuento += $itemDesc;
            $calcIva += $itemIva;
            $calcTotal += $itemTotal;

            $html .= '
                <tr>
                    <td width="15%" class="b1" align="center"><strong>' . htmlspecialchars((string)$re['CLAVE_ARTICULO'], ENT_QUOTES, 'UTF-8') . '</strong></td>
                    <td width="35%" class="b1">' . htmlspecialchars((string)$re['ARTICULO_NOMBRE'], ENT_QUOTES, 'UTF-8') . '</td>
                    <td width="10%" class="b1" align="center">' . $cant . '</td>
                    <td width="12%" class="b1" align="right">$' . number_format($precio, 2, '.', ',') . '</td>
                    <td width="8%" class="b1" align="center">' . number_format($descPct, 2, '.', ',') . '%</td>
                    <td width="8%" class="b1" align="center">' . number_format($ivaPct, 2, '.', ',') . '%</td>
                    <td width="12%" class="b1" align="right">$' . number_format($itemTotal, 2, '.', ',') . '</td>
                </tr>
            ';
        }
        $html .= '
    </tbody>
</table>
';

$finalSubtotal = $hasHeaderCalculations ? (float)$subtotalHeader : ($calcSubtotal - $calcDescuento);
$finalDescuento = $hasHeaderCalculations ? (float)$descuentoHeader : $calcDescuento;
$finalIva = $hasHeaderCalculations ? (float)$ivaHeader : $calcIva;
$finalTotal = $hasHeaderCalculations ? (float)$totalHeader : $calcTotal;

$html .= '<br><br>';
$html .= '<table align="right" cellpadding="4" style="width: 100%; border: none; font-size:9pt;">';
$html .= '<tr><td align="right" style="width: 80%; color: #1a3a6b;"><strong>SUBTOTAL:</strong></td><td align="right" style="width: 20%;">$' . number_format($finalSubtotal + $finalDescuento, 2, '.', ',') . '</td></tr>';
if ($finalDescuento > 0) {
    $html .= '<tr><td align="right" style="color: #c0392b;"><strong>DESCUENTO:</strong></td><td align="right" style="color: #c0392b;">-$' . number_format($finalDescuento, 2, '.', ',') . '</td></tr>';
}
$html .= '<tr><td align="right" style="color: #1a3a6b;"><strong>IVA:</strong></td><td align="right">$' . number_format($finalIva, 2, '.', ',') . '</td></tr>';
$html .= '<tr><td align="right" style="color: #1a3a6b; font-size: 10pt;"><strong>TOTAL:</strong></td><td align="right" style="color: #1a3a6b; font-size: 10pt; font-weight: bold;">$' . number_format($finalTotal, 2, '.', ',') . '</td></tr>';
$html .= '</table>';


// Usar writeHTML para renderizar el HTML en el PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Generar y mostrar el PDF
$pdf->Output('orden_compra_'.$res[0]['OC_FOLIO'].'.pdf', 'I');
?>