<?php include_once("../includes/includes.php"); ?>
<?php
require '../vendor/autoload.php';

$remisionid = base64_decode($_GET['remisionid']);
$remisiones = new remisiones();
$res = $remisiones->getremisionbyid(null, $remisionid);
$resp = $remisiones->getremisionprovinfobyid($remisionid);

if (!$res || $res == 0) {
    die('No se encontro la remision.');
}

function esc($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$filaCab = $res[0];

if (isset($filaCab['REMISION_STATUS']) && (int)$filaCab['REMISION_STATUS'] === 1) {
    die('<div style="font-family:sans-serif; padding:40px; text-align:center;"><h3>Formato no disponible</h3><p>La remisión debe ser enviada a recepción de chofer antes de poder imprimir su formato.</p></div>');
}
$fecha = !empty($filaCab['REMISION_FECHA']) ? strtotime($filaCab['REMISION_FECHA']) : time();
$dia = date('d', $fecha);
$mes = date('m', $fecha);
$anio = date('Y', $fecha);

$filas = [];
$gransubtotal = 0.0;
$graniva = 0.0;
$grantotal = 0.0;

$esUap = (isset($filaCab['HOSPITAL_ID']) && $filaCab['HOSPITAL_ID'] == 25);

foreach ($res as $r) {
    if (empty($r['REMISIONARTICULO_ID'])) {
        continue;
    }

    $subtotal = (float)$r['REMISIONARTICULO_SUBTOTAL'];
    $iva = (float)$r['REMISIONARTICULO_IVA'];
    $total = (float)$r['REMISIONARTICULO_TOTAL'];

    if (!$esUap) {
        $gransubtotal += $subtotal;
        $graniva += $iva;
        $grantotal += $total;
    }

    $ref = $r['CLAVE_ARTICULO'] ?? '';
    $folio = $r['STOCK_FOLIO'] ?? '';
    $referencia = $ref;
    if ($ref && $folio) {
        $referencia = $ref . ' / ' . $folio;
    } elseif ($folio) {
        $referencia = $folio;
    }

    $bag = $r['MALETA_FOLIO'] ?? '';
    if (empty($bag)) {
        $bag = $r['MALETA_NOMBRE'] ?? '';
    }

    $filas[] = [
        'bag' => $bag,
        'clave' => $ref,
        'folio' => $folio,
        'referencia' => $referencia,
        'cantidad' => 1,
        'descripcion' => $r['ARTICULO_NOMBRE'] ?? '',
        'lote' => $r['ESDET_LOTE'] ?? '',
        'precio_u' => $subtotal,
        'total' => $total,
    ];
}

if ($resp && $resp != 0) {
    foreach ($resp as $r) {
        $subtotal = (float)$r['REMISIONPROVARTICULO_SUBTOTAL'];
        $iva = (float)$r['REMISIONPROVARTICULO_IVA'];
        $total = (float)$r['REMISIONPROVARTICULO_TOTAL'];

        $esPaqueteUap = false;
        if ($esUap) {
            $refProv = strtoupper($r['CLAVE_ARTICULO'] ?? '');
            $esPaqueteUap = (substr($refProv, -4) === '-UAP');
            if ($esPaqueteUap) {
                $gransubtotal += $subtotal;
                $graniva += $iva;
                $grantotal += $total;
            }
        } else {
            $gransubtotal += $subtotal;
            $graniva += $iva;
            $grantotal += $total;
        }

        $filas[] = [
            'bag' => '',
            'clave' => $r['CLAVE_ARTICULO'] ?? '',
            'folio' => '',
            'referencia' => $r['CLAVE_ARTICULO'] ?? '',
            'cantidad' => (int)$r['REMISIONPROVARTICULO_CANTIDAD'],
            'descripcion' => $r['NOMBRE_ARTICULO'] ?? '',
            'lote' => '',
            'precio_u' => ((int)$r['REMISIONPROVARTICULO_CANTIDAD'] > 0) ? ($subtotal / (int)$r['REMISIONPROVARTICULO_CANTIDAD']) : 0,
            'total' => $total,
        ];
    }
}

// Lógica especial UAP
if ($esUap) {
    $agrupado = [];
    foreach ($filas as $f) {
        $cve = $f['clave'];
        // Si no tiene clave, usar la descripcion como fallback
        $key = !empty($cve) ? $cve : $f['descripcion']; 
        
        if (!isset($agrupado[$key])) {
            $agrupado[$key] = $f;
            $agrupado[$key]['folios'] = !empty($f['folio']) ? [$f['folio']] : [];
            $agrupado[$key]['lote'] = ''; // Clear lote for grouped items
        } else {
            $agrupado[$key]['cantidad'] += $f['cantidad'];
            if (!empty($f['folio'])) {
                $agrupado[$key]['folios'][] = $f['folio'];
            }
        }
    }
    
    // Reconstruir referencias
    $filas = [];
    $paqueteFilas = [];
    $otrasFilas = [];
    foreach ($agrupado as $k => $f) {
        if (!empty($f['folios'])) {
            $f['referencia'] = $f['clave'] . ' / ' . implode(', ', $f['folios']);
        } else {
            $f['referencia'] = $f['clave'];
        }
        
        if (substr(strtoupper($f['clave']), -4) === '-UAP') {
            $paqueteFilas[] = $f;
        } else {
            $otrasFilas[] = $f;
        }
    }
    $filas = array_merge($paqueteFilas, $otrasFilas);
}

$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->SetFont('freesans', '', 9);

$logo = $GLOBALS['global_site'] . 'img/logo.png';
$folio = esc($filaCab['REMISION_FOLIO'] ?? '');
$clienteVal = !empty($filaCab['CLIENTE_NOMBRE']) ? $filaCab['CLIENTE_NOMBRE'] : (!empty($filaCab['EVENTO_NOMBREPARTICULAR']) ? $filaCab['EVENTO_NOMBREPARTICULAR'] : ($filaCab['EVENTO_CLIENTEID'] ?? ''));
$cliente = esc($clienteVal);

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
        <td width="25%"><img src="' . $logo . '" width="120"></td>
        <td width="75%" align="center">
            <span style="font-size:11px; color:#1a3a6b; font-weight:bold;">AMPAR DE MEXICO</span><br>
            <span style="font-size:10px; color:#1a3a6b; font-weight:bold;">DIVISION CARDIOLOGIA</span><br>
            <span style="font-size:10px; color:#1a3a6b; font-weight:bold;">NOTA DE REMISION</span>
        </td>
    </tr>
</table>

<table width="100%" cellpadding="2">
    <tr>
        <td width="42%" class="lbl">Paciente</td>
        <td width="18%" class="lbl"># de Paciente</td>
        <td width="8%" class="lbl">Dia</td>
        <td width="8%" class="lbl">Mes</td>
        <td width="9%" class="lbl">Ano</td>
        <td width="15%" class="lbl">Folio de remision</td>
    </tr>
    <tr>
        <td width="42%" class="b2" align="left">' . $cliente . '</td>
        <td width="18%" class="b2"></td>
        <td width="8%" class="b2" align="center">' . $dia . '</td>
        <td width="8%" class="b2" align="center">' . $mes . '</td>
        <td width="9%" class="b2" align="center">' . $anio . '</td>
        <td width="15%" class="b2" align="center"><span style="color:#c0392b; font-weight:bold;">' . $folio . '</span></td>
    </tr>
</table>

<table width="100%" cellpadding="3" style="margin-top:5px;">
    <tr>
        <td width="6%" class="b2 th">Nota(s) adicionales</td>
        <td width="6%" class="b2 th">Bag</td>
        <td width="14%" class="b2 th">Referencia/Folio</td>
        <td width="30%" class="b2 th">Descripcion</td>
        <td width="8%" class="b2 th">Cantidad</td>
        <td width="12%" class="b2 th">Lote</td>
        <td width="12%" class="b2 th">Precio U.</td>
        <td width="12%" class="b2 th">Total</td>
    </tr>';

foreach ($filas as $f) {
    $html .= '
    <tr>
        <td width="6%" class="b1" align="center">' . esc($f['notas_adicionales'] ?? '') . '</td>
        <td width="6%" class="b1" align="center">' . esc($f['bag']) . '</td>
        <td width="14%" class="b1" align="center"><strong>' . esc($f['referencia']) . '</strong></td>
        <td width="30%" class="b1">' . esc($f['descripcion']) . '</td>
        <td width="8%" class="b1" align="center">' . esc($f['cantidad']) . '</td>
        <td width="12%" class="b1" align="center">' . esc($f['lote']) . '</td>';
    if (isset($_GET['noprecios']) && $_GET['noprecios'] == '1') {
        $html .= '
        <td width="12%" class="b1" align="right"></td>
        <td width="12%" class="b1" align="right"></td>';
    } else {
        if ($esUap && substr(strtoupper($f['clave']), -4) !== '-UAP') {
            $html .= '
            <td width="12%" class="b1" align="right"></td>
            <td width="12%" class="b1" align="right"></td>';
        } else {
            $html .= '
            <td width="12%" class="b1" align="right">$' . number_format($f['precio_u'], 2, '.', ',') . '</td>
            <td width="12%" class="b1" align="right">$' . number_format($f['total'], 2, '.', ',') . '</td>';
        }
    }
    $html .= '
    </tr>';
}

$minFilas = 24;
$faltan = max(0, $minFilas - count($filas));
for ($i = 0; $i < $faltan; $i++) {
    $html .= '
    <tr>
        <td width="6%" class="b1">&nbsp;</td>
        <td width="6%" class="b1">&nbsp;</td>
        <td width="14%" class="b1">&nbsp;</td>
        <td width="8%" class="b1">&nbsp;</td>
        <td width="30%" class="b1">&nbsp;</td>
        <td width="12%" class="b1">&nbsp;</td>
        <td width="12%" class="b1">&nbsp;</td>
        <td width="12%" class="b1">&nbsp;</td>
    </tr>';
}

';

if ((!isset($_GET['noprecios']) || $_GET['noprecios'] != '1') && !$esUap) {
    $html .= '
    <tr>
        <td colspan="5" rowspan="3" style="border:0;"></td>
        <td colspan="2" class="b2 totlbl">Subtotal</td>
        <td class="b2" align="right">$' . number_format($gransubtotal, 2, '.', ',') . '</td>
    </tr>
    <tr>
        <td colspan="2" class="b2 totlbl">IVA</td>
        <td class="b2" align="right">$' . number_format($graniva, 2, '.', ',') . '</td>
    </tr>
    <tr>
        <td colspan="2" class="b2 totlbl">Total</td>
        <td class="b2" align="right">$' . number_format($grantotal, 2, '.', ',') . '</td>
    </tr>
</table>
';
} else {
    $html .= '
    <tr>
        <td colspan="8" style="border:0; padding:10px;"></td>
    </tr>
</table>
';
}

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('archivo.pdf', 'I');
?>