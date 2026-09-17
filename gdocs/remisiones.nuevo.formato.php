<?php include_once("../includes/includes.php"); ?>
<?php
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

$medicoInt = '';
$medicoRef = '';
$nombrePacienteEvento = '';
$eventoid = $filaCab['REMISION_EVENTOID'] ?? null;
if (!empty($eventoid)) {
    require_once "../class/eventos.php";
    $evObj = new eventos();
    $evRes = $evObj->geteventobyid($eventoid);
    if ($evRes && count($evRes) > 0) {
        $medicoInt = esc($evRes[0]['DOCTORINTERVENCIONISTA_NOMBRE'] ?? '');
        $medicoRef = esc($evRes[0]['DOCTORREFERIDOR_NOMBRE'] ?? '');
        $nomEv = !empty($evRes[0]['NOMBRE']) ? $evRes[0]['NOMBRE'] : (!empty($evRes[0]['EVENTO_NOMBREPARTICULAR']) ? $evRes[0]['EVENTO_NOMBREPARTICULAR'] : ($evRes[0]['EVENTO_CLIENTEID'] ?? ''));
        $nombrePacienteEvento = $nomEv;
        $tipoEvento = esc($evRes[0]['TIPOEVENTO_NOMBRE'] ?? '');
    }
}

$fecha = !empty($filaCab['REMISION_FECHA']) ? strtotime($filaCab['REMISION_FECHA']) : time();
$dia = date('d', $fecha);
$mes = date('m', $fecha);
$anio = date('Y', $fecha);

$folio = esc($filaCab['REMISION_FOLIO'] ?? '');
$clienteVal = !empty($nombrePacienteEvento) ? $nombrePacienteEvento : (!empty($filaCab['CLIENTE_NOMBRE']) ? $filaCab['CLIENTE_NOMBRE'] : (!empty($filaCab['EVENTO_NOMBREPARTICULAR']) ? $filaCab['EVENTO_NOMBREPARTICULAR'] : ($filaCab['EVENTO_CLIENTEID'] ?? '')));
$cliente = esc($clienteVal);

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
    $folioArticulo = $r['STOCK_FOLIO'] ?? '';
    $referencia = $ref;
    if ($ref && $folioArticulo) {
        $referencia = $ref . ' / ' . $folioArticulo;
    } elseif ($folioArticulo) {
        $referencia = $folioArticulo;
    }

    $bag = $r['MALETA_FOLIO'] ?? '';
    if (empty($bag)) {
        $bag = $r['MALETA_NOMBRE'] ?? '';
    }

    $filas[] = [
        'bag' => $bag,
        'clave' => $ref,
        'folio' => $folioArticulo,
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
            'referencia' => $r['CLAVE_ARTICULO'] ?? '',
            'cantidad' => (int)$r['REMISIONPROVARTICULO_CANTIDAD'],
            'descripcion' => $r['NOMBRE_ARTICULO'] ?? '',
            'lote' => '',
            'precio_u' => ((int)$r['REMISIONPROVARTICULO_CANTIDAD'] > 0) ? ($subtotal / (int)$r['REMISIONPROVARTICULO_CANTIDAD']) : 0,
            'total' => $total,
            'clave' => $r['CLAVE_ARTICULO'] ?? '',
        ];
    }
}

// Lógica especial UAP
if ($esUap) {
    $agrupado = [];
    foreach ($filas as $f) {
        $cve = $f['clave'] ?? $f['referencia'];
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
            $f['referencia'] = ($f['clave'] ?? $f['referencia']) . ' / ' . implode(', ', $f['folios']);
        } else {
            $f['referencia'] = $f['clave'] ?? $f['referencia'];
        }
        
        if (substr(strtoupper($f['clave'] ?? $f['referencia']), -4) === '-UAP') {
            $paqueteFilas[] = $f;
        } else {
            $otrasFilas[] = $f;
        }
    }
    $filas = array_merge($paqueteFilas, $otrasFilas);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remisión AMPAR - División Cardiología</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            background: #fff;
            color: #000;
        }

        .page {
            width: 216mm;
            min-height: 279mm;
            margin: 0 auto;
            padding: 8mm 10mm;
            background: #fff;
        }

        /* HEADER */
        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin-bottom: 4px;
        }

        .logo-area {
            position: absolute;
            left: 0;
            top: 0;
        }

        .logo-area img {
            width: 80px;
        }

        /* Placeholder si no hay logo */
        .logo-placeholder {
            width: 90px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7px;
            color: #1a3a6b;
            text-align: center;
            padding: 3px;
            border-radius: 3px;
        }

        .header-text {
            text-align: center;
        }

        .header-text .company {
            font-size: 12px;
            font-weight: bold;
            color: #1a3a6b;
            letter-spacing: 1px;
        }

        .header-text .division {
            font-size: 11px;
            font-weight: bold;
            color: #1a3a6b;
        }

        .header-text .subtitle {
            font-size: 12px;
            font-size: 13px;
            font-weight: bold;
            color: #1a3a6b;
            margin-top: 2px;
        }

        .header-text .slogan {
            font-size: 10px;
            color: #c0392b;
            font-style: italic;
            margin-top: 1px;
        }

        /* PATIENT INFO ROW */
        .patient-row {
            display: flex;
            gap: 4px;
            margin-top: 6px;
            margin-bottom: 0;
        }

        .field-group {
            display: flex;
            flex-direction: column;
        }

        .field-group label {
            font-size: 9px;
            color: #333;
            margin-bottom: 1px;
            text-align: center;
        }

        .field-group .field-box {
            border: 1.5px solid #1a3a6b;
            min-height: 19px;
            flex: 1;
            background: #fff;
            min-width: 20px;
        }

        .fg-paciente {
            flex: 2;
        }

        .fg-numPaciente {
            flex: 1.5;
        }

        .fg-procedimiento {
            flex: 2;
        }

        .fg-dia {
            flex: 0.6;
        }

        .fg-mes {
            flex: 0.6;
        }

        .fg-anio {
            flex: 0.7;
        }

        .fg-folio {
            flex: 1.5;
        }

        .folio-box {
            border: 1.5px solid #1a3a6b;
            min-height: 19px;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 4px;
        }

        .folio-num {
            color: #c0392b;
            font-weight: bold;
            font-size: 11px;
        }

        .folio-code {
            font-size: 11px;
            color: #1a3a6b;
        }

        /* TABLE */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .main-table th {
            background: #f0f4fa;
            border: 1.5px solid #1a3a6b;
            padding: 4px 5px;
            font-size: 10px;
            font-weight: bold;
            color: #1a3a6b;
            text-align: center;
        }

        .main-table td {
            border: 1px solid #1a3a6b;
            height: 14px;
            padding: 1px 3px;
            font-size: 10px;
        }

        .col-bag {
            width: 5%;
        }

        .col-ref {
            width: 9%;
        }

        .col-cantidad {
            width: 8%;
            text-align: center;
        }

        .col-desc {
            width: 50%;
        }

        .col-lote {
            width: 9%;
        }

        .col-precio {
            width: 10%;
        }

        .col-label {
            width: 9%;
        }

        /* TOTALS SECTION */
        .totals-row td {
            border: none !important;
        }

        .totals-label {
            background: #f0f4fa;
            border: 1.5px solid #1a3a6b !important;
            font-weight: bold;
            font-size: 11px;
            color: #1a3a6b;
            text-align: right;
            padding-right: 5px !important;
        }

        .totals-value {
            border: 1.5px solid #1a3a6b !important;
            font-size: 11px;
        }

        /* SIGNATURES */
        .signatures-section {
            margin-top: 7px;
        }

        .sig-labels {
            display: flex;
            gap: 4px;
            margin-bottom: 2px;
        }

        .sig-labels span {
            flex: 1;
            text-align: center;
            font-size: 9px;
            color: #333;
        }

        .sig-boxes {
            display: flex;
            gap: 4px;
        }

        .sig-box {
            flex: 1;
            height: 21px;
            border: 1.5px solid #1a3a6b;
        }

        /* BOTTOM SECTION */
        .bottom-section {
            margin-top: 6px;
            display: flex;
            gap: 4px;
            align-items: stretch;
        }

        .hospital-rfc {
            flex: 2;
        }

        .hospital-rfc .hr-labels {
            display: flex;
            gap: 4px;
        }

        .hospital-rfc .hr-labels span {
            flex: 1;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            color: #000;
        }

        .hospital-rfc .hr-box {
            display: flex;
            gap: 4px;
            margin-top: 2px;
        }

        .hospital-rfc .hr-box .hbox {
            flex: 1;
            height: 41px;
            border: 1.5px solid #1a3a6b;
        }

        .firma-section {
            flex: 3;
        }

        .firma-inner {
            display: flex;
            border: 1.5px solid #1a3a6b;
            height: 56px;
        }

        .firma-cell {
            flex: 1;
            border-right: 1.5px solid #1a3a6b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            text-align: center;
            padding: 4px;
        }

        .firma-cell:last-child {
            border-right: none;
        }

        .obs-section {
            flex: 1;
            border: 1.5px solid #1a3a6b;
            display: flex;
            align-items: flex-start;
            padding: 4px;
            font-size: 9px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-all;
            white-space: normal;
        }

        .footer-url {
            text-align: center;
            font-size: 10px;
            color: #1a3a6b;
            margin-top: 7px;
            font-weight: bold;
        }

        @media print {
            body {
                margin: 0;
            }

            .page {
                margin: 0;
                padding: 8mm 10mm;
            }

            @page {
                size: letter;
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="page">

        <!-- HEADER -->
        <div class="header">
            <div class="logo-area">
                <div class="logo-placeholder">
                    <img src="../img/logo.png" alt="AMPAR Logo">
                </div>
            </div>

            <div class="header-text">
                <div class="company">AMPAR DE MEXICO</div>
                <div class="division">DIVISIÓN CARDIOLOGÍA</div>
                <div class="subtitle">AFILIACIÓN MÉDICA PRIVADA AVANZADA DE RECUPERACIÓN SA DE CV</div>
                <div class="slogan">"Permítenos trabajar con usted y para usted"</div>
            </div>
        </div>

        <!-- PATIENT INFO -->
        <div class="patient-row">
            <div class="field-group fg-paciente">
                <label>Paciente</label>
                <div class="field-box" style="padding:0 4px; display:flex; align-items:center; font-weight:bold;"><?= $cliente ?></div>
            </div>
            <div class="field-group fg-numPaciente">
                <label># de Paciente</label>
                <div class="field-box" style="padding:0 4px; display:flex; align-items:center; justify-content:center; text-align:center; font-weight:bold;"><?= esc($filaCab['REMISION_NUMPACIENTE'] ?? '') ?></div>
            </div>
            <div class="field-group fg-procedimiento">
                <label>Nombre de procedimiento</label>
                <div class="field-box" style="padding:0 4px; display:flex; align-items:center; justify-content:center; text-align:center; font-weight:bold; font-size: 9px;"><?= $tipoEvento ?? '' ?></div>
            </div>
            <div class="field-group fg-dia">
                <label>Día</label>
                <div class="field-box" style="display:flex; justify-content:center; align-items:center; font-weight:bold;"><?= $dia ?></div>
            </div>
            <div class="field-group fg-mes">
                <label>Mes</label>
                <div class="field-box" style="display:flex; justify-content:center; align-items:center; font-weight:bold;"><?= $mes ?></div>
            </div>
            <div class="field-group fg-anio">
                <label>Año</label>
                <div class="field-box" style="display:flex; justify-content:center; align-items:center; font-weight:bold;"><?= $anio ?></div>
            </div>
            <div class="field-group fg-folio">
                <label>Folio de remisión</label>
                <div class="folio-box">
                    <span class="folio-num" style="width:100%; text-align:center; font-size:13px;"><?= $folio ?></span>
                </div>
            </div>
        </div>

        <!-- MAIN TABLE -->
        <table class="main-table">
            <thead>
                <tr>
                    <th class="col-bag">Bag</th>
                    <th class="col-ref">Referencia/Folio</th>
                    <th class="col-desc">Descripción</th>
                    <th class="col-cantidad">Cantidad</th>
                    <th class="col-lote">Lote</th>
                    <th class="col-precio">Precio U.</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $maxRows = 28;
                for ($i = 0; $i < $maxRows; $i++) {
                    if (isset($filas[$i])) {
                        $f = $filas[$i];
                        echo '<tr>';
                        echo '<td class="col-bag" style="text-align:center;">' . esc($f['bag']) . '</td>';
                        echo '<td class="col-ref" style="text-align:center; font-weight:bold;">' . esc($f['referencia']) . '</td>';
                        echo '<td class="col-desc" style="padding-left:4px;">' . esc($f['descripcion']) . '</td>';
                        echo '<td class="col-cantidad" style="text-align:center;">' . esc($f['cantidad']) . '</td>';
                        echo '<td class="col-lote" style="text-align:center;">' . esc($f['lote']) . '</td>';
                        if (isset($_GET['noprecios']) && $_GET['noprecios'] == '1') {
                            echo '<td class="col-precio" style="text-align:right; padding-right:2px;"></td>';
                        } else {
                            if ($esUap && substr(strtoupper($f['clave'] ?? $f['referencia']), -4) !== '-UAP') {
                                echo '<td class="col-precio" style="text-align:right; padding-right:2px;"></td>';
                            } else {
                                echo '<td class="col-precio" style="text-align:right; padding-right:2px;">$' . number_format($f['precio_u'], 2, '.', ',') . '</td>';
                            }
                        }
                        echo '</tr>';
                    } else {
                        echo '<tr><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
                    }
                }
                ?>
            <?php if ((!isset($_GET['noprecios']) || $_GET['noprecios'] != '1') && !$esUap): ?>
                <!-- TOTALS ROWS -->
                <tr>
                    <td colspan="4" rowspan="5" style="border:none;"></td>
                    <td colspan="1" class="totals-label">Subtotal</td>
                    <td class="totals-value" style="text-align:right; padding-right:2px; font-weight:bold;">$<?= number_format($gransubtotal, 2, '.', ',') ?></td>
                </tr>
                <tr>
                    <td colspan="1" class="totals-label">IVA</td>
                    <td class="totals-value" style="text-align:right; padding-right:2px; font-weight:bold;">$<?= number_format($graniva, 2, '.', ',') ?></td>
                </tr>
                <tr>
                    <td colspan="1" class="totals-label">Total</td>
                    <td class="totals-value" style="text-align:right; padding-right:2px; font-weight:bold;">$<?= number_format($grantotal, 2, '.', ',') ?></td>
                </tr>
                <tr>
                    <td colspan="1" class="totals-label">Anticipo</td>
                    <td class="totals-value" style="text-align:right; padding-right:2px; font-weight:bold;">$<?= number_format((float)($filaCab['REMISION_ANTICIPO'] ?? 0), 2, '.', ',') ?></td>
                </tr>
                <tr>
                    <td colspan="1" class="totals-label">Resto</td>
                    <td class="totals-value" style="text-align:right; padding-right:2px; font-weight:bold;">$<?= number_format((float)($filaCab['REMISION_RESTO'] ?? 0), 2, '.', ',') ?></td>
                </tr>
            <?php else: ?>
                <!-- EMPTY TOTALS ROWS to maintain structure if needed -->
                <tr><td colspan="6" style="border:none;"></td></tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- SIGNATURES -->
        <div class="signatures-section">
            <div class="sig-labels">
                <span>Representante</span>
                <span>Médico Intervencionista</span>
                <span>Medico 2do. Op.</span>
                <span>Medico Refendor</span>
                <span>Enfermería</span>
            </div>
            <div class="sig-boxes">
                <div class="sig-box" style="font-size:9px; text-align:center; display:flex; align-items:center; justify-content:center; font-weight:bold;"><?= esc($filaCab['REMISION_REPRESENTANTE'] ?? '') ?></div>
                <div class="sig-box" style="font-size:9px; text-align:center; display:flex; align-items:center; justify-content:center; font-weight:bold;"><?= $medicoInt ?></div>
                <div class="sig-box" style="font-size:9px; text-align:center; display:flex; align-items:center; justify-content:center; font-weight:bold;"><?= esc($filaCab['REMISION_MEDICO2'] ?? '') ?></div>
                <div class="sig-box" style="font-size:9px; text-align:center; display:flex; align-items:center; justify-content:center; font-weight:bold;"><?= $medicoRef ?></div>
                <div class="sig-box" style="font-size:9px; text-align:center; display:flex; align-items:center; justify-content:center; font-weight:bold;"><?= esc($filaCab['REMISION_ENFERMERIA'] ?? '') ?></div>
            </div>
        </div>

        <!-- BOTTOM -->
        <div class="bottom-section">
            <div class="hospital-rfc">
                <div class="hr-labels">
                    <span>Nombre del Hospital</span>
                    <span>RFC</span>
                </div>
                <div class="hr-box">
                    <div class="hbox" style="padding:0 4px; display:flex; align-items:center; justify-content:center; text-align:center; font-size:10px; font-weight:bold;"><?= esc($filaCab['HOSPITAL_NOMBRE'] ?? '') ?></div>
                    <div class="hbox" style="padding:0 4px; display:flex; align-items:center; justify-content:center; text-align:center; font-size:10px; font-weight:bold;"><?= esc($filaCab['REMISION_RFC'] ?? '') ?></div>
                </div>
                <div class="hr-labels" style="margin-top:4px;">
                    <span>No. de Proveedor AMPAR</span>
                </div>
                <div class="hr-box">
                    <div class="hbox" style="padding:0 4px; display:flex; align-items:center; justify-content:center; text-align:center; font-size:10px; font-weight:bold;"><?= esc($filaCab['REMISION_NUMCLIENTEHOS'] ?? '') ?></div>
                </div>
            </div>

            <div class="firma-section">
                <div class="firma-inner">
                    <div class="firma-cell">Firma de Cliente</div>
                    <div class="firma-cell">Firma de Representante</div>
                    <div class="firma-cell" style="font-size:8.5px; font-weight:bold;">
                        * FAVOR DE PONER<br>ETIQUETAS AL REVERSO<br>DE LA REMISIÓN *
                    </div>
                </div>
            </div>

            <div class="obs-section">Observaciones: <?= esc($filaCab['REMISION_OBSERVACIONES'] ?? '') ?></div>
        </div>

        <div class="footer-url">www.ampardemexico.com</div>

    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>

</html>