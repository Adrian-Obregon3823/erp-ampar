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

$html = '
    <img src="'.$GLOBALS['global_site'].'img/logo.png" width="150">
    <h2 align="center">ORDEN DE COMPRA</h2>
    <br>
    <table>
        <tr>
            <th width="100px">FOLIO</th>
            <td>'.$res[0]['OC_FOLIO'].'</td>
        </tr>
        <tr>
            <th>SUCURSAL</th>
            <td>'.$res[0]['SUCURSAL_NOMBRE'].'</td>
        </tr>
        <tr>
            <th>FECHA</th>
            <td>'.$res[0]['OC_FECHA'].'</td>
        </tr>
    </table>
    <br><br>
    <b>Detalle de Artículos</b>
    <br><br>
    <table border="1" cellpadding="4" width="100%">
        <thead>
            <tr style="background-color: #f2f2f2; font-weight: bold;">
                <th width="70">Clave</th>
                <th width="200">Artículo</th>
                <th width="40" align="center">Cant.</th>
                <th width="60" align="right">Costo</th>
                <th width="50" align="center">Desc. %</th>
                <th width="40" align="center">IVA</th>
                <th width="80" align="right">Total</th>
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
                        <td width="70"><strong>'.$re['CLAVE_ARTICULO'].'</strong></td>
                        <td width="200">'.$re['ARTICULO_NOMBRE'].'</td>
                        <td width="40" align="center">'.$cant.'</td>
                        <td width="60" align="right">$'.number_format($precio, 2, '.', ',').'</td>
                        <td width="50" align="center">'.number_format($descPct, 2, '.', ',').'%</td>
                        <td width="40" align="center">'.number_format($ivaPct, 2, '.', ',').'%</td>
                        <td width="80" align="right">$'.number_format($itemTotal, 2, '.', ',').'</td>
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
$html .= '<table align="right" cellpadding="2" style="width: 100%; border: none;">';
$html .= '<tr><td align="right" style="width: 80%;"><strong>SUBTOTAL:</strong></td><td align="right" style="width: 20%;">$'.number_format($finalSubtotal + $finalDescuento, 2, '.', ',').'</td></tr>';
if ($finalDescuento > 0) {
    $html .= '<tr><td align="right" style="color: red;"><strong>DESCUENTO:</strong></td><td align="right" style="color: red;">-$'.number_format($finalDescuento, 2, '.', ',').'</td></tr>';
}
$html .= '<tr><td align="right"><strong>IVA:</strong></td><td align="right">$'.number_format($finalIva, 2, '.', ',').'</td></tr>';
$html .= '<tr><td align="right" style="font-size: 1.1em; color: #007bff;"><strong>TOTAL:</strong></td><td align="right" style="font-size: 1.1em; color: #007bff; font-weight: bold;">$'.number_format($finalTotal, 2, '.', ',').'</td></tr>';
$html .= '</table>';

// Usar writeHTML para renderizar el HTML en el PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Generar y mostrar el PDF
$pdf->Output('orden_compra_'.$res[0]['OC_FOLIO'].'.pdf', 'I');
?>