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
    }
}

$fecha = !empty($filaCab['REMISION_FECHA']) ? strtotime($filaCab['REMISION_FECHA']) : time();
$dia = date('d', $fecha);
$mes = date('m', $fecha);
$anio = date('Y', $fecha);

$folio = esc($filaCab['REMISION_FOLIO'] ?? '');
$clienteVal = !empty($nombrePacienteEvento) ? $nombrePacienteEvento : (!empty($filaCab['CLIENTE_NOMBRE']) ? $filaCab['CLIENTE_NOMBRE'] : (!empty($filaCab['EVENTO_NOMBREPARTICULAR']) ? $filaCab['EVENTO_NOMBREPARTICULAR'] : ($filaCab['EVENTO_CLIENTEID'] ?? '')));
$cliente = esc($clienteVal);

$direccionCompleta = $filaCab['CLIENTE_DIRECCION_COMPLETA'] ?? '';
$partesDireccion = explode('|||', $direccionCompleta);
$calleNum = trim($partesDireccion[0] ?? '');
$colonia = trim($partesDireccion[1] ?? '');
$poblacion = trim($partesDireccion[2] ?? '');
$cp = trim($partesDireccion[3] ?? '');
$ciudad = trim($partesDireccion[4] ?? '');
$estado = trim($partesDireccion[5] ?? '');

// Direccion: Calle, Colonia, Ciudad/Estado, CP (cada uno en su linea)
$direccionFormateada = "";
// 1) Calle y numero
if ($calleNum != '') {
    $direccionFormateada .= '<span style="display:block;">' . esc($calleNum) . "</span>";
}
// 2) Colonia
if ($colonia != '') {
    $direccionFormateada .= '<span style="display:block;">Col. ' . esc($colonia) . "</span>";
}
// 3) Ciudad, Estado (debajo de colonia)
if ($ciudad != '' || $estado != '') {
    $direccionFormateada .= '<span style="display:block;">' . esc(trim($ciudad . ', ' . $estado, ', ')) . "</span>";
}
// 4) CP: (linea propia, sin guion)
if ($cp != '') {
    $direccionFormateada .= '<span style="display:block;">CP: ' . esc($cp) . "</span>";
}
// Municipio/Poblacion si difiere de ciudad
if ($poblacion != '' && strtolower(trim($poblacion)) !== strtolower(trim($ciudad))) {
    $direccionFormateada .= '<span style="display:block;">' . esc($poblacion) . "</span>";
}
if ($direccionFormateada == "") {
    $direccionFormateada = esc($filaCab['CLIENTE_DIRECCION'] ?? '');
}

// Quien atendio: el usuario que hizo la venta directa, luego fallback al representante/contacto
$repFormato = !empty($filaCab['VENDEDOR_NOMBRE']) ? $filaCab['VENDEDOR_NOMBRE'] : (!empty($filaCab['REMISION_REPRESENTANTE']) ? $filaCab['REMISION_REPRESENTANTE'] : ($filaCab['CLIENTE_CONTACTO'] ?? ''));
$rfcFormato = !empty($filaCab['REMISION_RFC']) ? $filaCab['REMISION_RFC'] : ($filaCab['CLIENTE_RFC_CURP'] ?? '');

$filasRaw = [];
$gransubtotal = 0.0;
$graniva = 0.0;
$grantotal = 0.0;

foreach ($res as $r) {
    if (empty($r['REMISIONARTICULO_ID'])) {
        continue;
    }

    $subtotal = (float)$r['REMISIONARTICULO_SUBTOTAL'];
    $iva     = (float)$r['REMISIONARTICULO_IVA'];
    $total   = (float)$r['REMISIONARTICULO_TOTAL'];

    $gransubtotal += $subtotal;
    $graniva      += $iva;
    $grantotal    += $total;

    // Solo la clave del artículo (sin STOCK_FOLIO, que es único por pieza)
    $refArticulo = trim($r['CLAVE_ARTICULO'] ?? '');

    $bag = trim($r['MALETA_FOLIO'] ?? '');
    if (empty($bag)) {
        $bag = trim($r['MALETA_NOMBRE'] ?? '');
    }

    // Lote o número de serie (el que tenga valor)
    $loteVal   = trim($r['ESDET_LOTE'] ?? '');
    $serieVal  = trim($r['STOCK_SERIE'] ?? '');
    $loteOSerie = $loteVal !== '' ? $loteVal : $serieVal;

    $filasRaw[] = [
        'bag'        => $bag,
        'referencia' => $refArticulo,
        'descripcion' => trim($r['ARTICULO_NOMBRE'] ?? ''),
        'lote'       => $loteOSerie,
        'precio_u'   => $subtotal,
        'total'      => $total,
    ];
}

// Agrupar por mismo artículo + mismo lote/serie
$filasAgrupadas = [];
foreach ($filasRaw as $fila) {
    // Clave: solo descripción + lote/serie (la referencia puede variar por pieza)
    $clave = $fila['descripcion'] . '|||' . $fila['lote'];
    if (isset($filasAgrupadas[$clave])) {
        $filasAgrupadas[$clave]['cantidad']++;
        $filasAgrupadas[$clave]['total'] += $fila['total'];
    } else {
        $filasAgrupadas[$clave] = array_merge($fila, ['cantidad' => 1]);
    }
}
$filas = array_values($filasAgrupadas);


if ($resp && $resp != 0) {
    foreach ($resp as $r) {
        $subtotal = (float)$r['REMISIONPROVARTICULO_SUBTOTAL'];
        $iva = (float)$r['REMISIONPROVARTICULO_IVA'];
        $total = (float)$r['REMISIONPROVARTICULO_TOTAL'];

        $gransubtotal += $subtotal;
        $graniva += $iva;
        $grantotal += $total;

        $filas[] = [
            'bag' => '',
            'referencia' => $r['CLAVE_ARTICULO'] ?? '',
            'cantidad' => (int)$r['REMISIONPROVARTICULO_CANTIDAD'],
            'descripcion' => $r['NOMBRE_ARTICULO'] ?? '',
            'lote' => '',
            'precio_u' => ((int)$r['REMISIONPROVARTICULO_CANTIDAD'] > 0) ? ($subtotal / (int)$r['REMISIONPROVARTICULO_CANTIDAD']) : 0,
            'total' => $total,
        ];
    }
}
$granCantidad = 0;
foreach ($filas as $f) {
    $granCantidad += (int)$f['cantidad'];
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
            font-size: 10px;
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
            font-size: 11px;
            font-weight: bold;
            color: #1a3a6b;
            letter-spacing: 1px;
        }

        .header-text .division {
            font-size: 10px;
            font-weight: bold;
            color: #1a3a6b;
        }

        .header-text .subtitle {
            font-size: 11px;
            font-weight: bold;
            color: #1a3a6b;
            margin-top: 2px;
        }

        .header-text .slogan {
            font-size: 9px;
            color: #c0392b;
            font-style: italic;
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
            font-size: 8px;
            color: #333;
            margin-bottom: 1px;
            text-align: center;
        }

        .field-group .field-box {
            border: 1.5px solid #1a3a6b;
            min-height: 18px;
            flex: 1;
            background: #fff;
            min-width: 20px;
        }

        .fg-paciente {
            flex: 3;
        }

        .fg-numPaciente {
            flex: 1.5;
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
            min-height: 18px;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 4px;
        }

        .folio-num {
            color: #c0392b;
            font-weight: bold;
            font-size: 10px;
        }

        .folio-code {
            font-size: 9px;
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
            padding: 3px 4px;
            font-size: 9px;
            font-weight: bold;
            color: #1a3a6b;
            text-align: center;
        }

        .main-table td {
            border: 1px solid #1a3a6b;
            height: 13px;
            padding: 0 2px;
        }

        .col-bag {
            width: 5%;
        }

        .col-ref {
            width: 9%;
        }

        .col-cant {
            width: 8%;
        }

        .col-desc {
            width: 40%;
        }

        .col-lote {
            width: 9%;
        }

        .col-precio {
            width: 10%;
        }

        .col-total {
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
            font-size: 9px;
            color: #1a3a6b;
            text-align: right;
            padding-right: 4px !important;
        }

        .totals-value {
            border: 1.5px solid #1a3a6b !important;
        }

        /* SIGNATURES */
        .signatures-section {
            margin-top: 6px;
        }

        .sig-labels {
            display: flex;
            gap: 4px;
            margin-bottom: 1px;
        }

        .sig-labels span {
            flex: 1;
            text-align: center;
            font-size: 8px;
            color: #333;
        }

        .sig-boxes {
            display: flex;
            gap: 4px;
        }

        .sig-box {
            flex: 1;
            height: 20px;
            border: 1.5px solid #1a3a6b;
        }

        /* BOTTOM SECTION */
        .bottom-section {
            margin-top: 5px;
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
            font-size: 9px;
            font-weight: bold;
            text-align: center;
            color: #000;
        }

        .hospital-rfc .hr-box {
            display: flex;
            gap: 4px;
            margin-top: 1px;
        }

        .hospital-rfc .hr-box .hbox {
            flex: 1;
            min-height: 40px;
            height: auto;
            border: 1.5px solid #1a3a6b;
        }

        .firma-section {
            flex: 3;
        }

        .firma-inner {
            display: flex;
            border: 1.5px solid #1a3a6b;
            height: 55px;
        }

        .firma-cell {
            flex: 1;
            border-right: 1.5px solid #1a3a6b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            text-align: center;
            padding: 3px;
        }

        .firma-cell:last-child {
            border-right: none;
        }

        .obs-section {
            flex: 1;
            border: 1.5px solid #1a3a6b;
            display: flex;
            align-items: flex-start;
            padding: 3px;
            font-size: 8px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-all;
            white-space: normal;
        }

        .footer-url {
            text-align: center;
            font-size: 9px;
            color: #1a3a6b;
            margin-top: 6px;
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
                <label>Cliente</label>
                <div class="field-box" style="padding:0 4px; display:flex; align-items:center; font-weight:bold;"><?= $cliente ?></div>
            </div>
            <div class="field-group fg-dia" style="flex: 0.8">
                <label>Día</label>
                <div class="field-box" style="display:flex; justify-content:center; align-items:center; font-weight:bold;"><?= $dia ?></div>
            </div>
            <div class="field-group fg-mes" style="flex: 0.8">
                <label>Mes</label>
                <div class="field-box" style="display:flex; justify-content:center; align-items:center; font-weight:bold;"><?= $mes ?></div>
            </div>
            <div class="field-group fg-anio" style="flex: 0.9">
                <label>Año</label>
                <div class="field-box" style="display:flex; justify-content:center; align-items:center; font-weight:bold;"><?= $anio ?></div>
            </div>
            <div class="field-group fg-folio" style="flex: 1.5">
                <label>Folio de remisión</label>
                <div class="folio-box">
                    <span class="folio-num" style="width:100%; text-align:center; font-size:12px;"><?= $folio ?></span>
                </div>
            </div>
        </div>

        <!-- MAIN TABLE -->
        <table class="main-table">
            <thead>
                <tr>
                    <th class="col-bag">Bag</th>
                    <th class="col-ref">Referencia/Folio</th>
                    <th class="col-cant">Cantidad</th>
                    <th class="col-desc">Descripción</th>
                    <th class="col-lote">Lote/Serie</th>
                    <th class="col-precio">Precio U.</th>
                    <th class="col-total">Total</th>
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
                        echo '<td class="col-cant" style="text-align:center;">' . esc($f['cantidad']) . '</td>';
                        echo '<td class="col-desc" style="padding-left:4px;">' . esc($f['descripcion']) . '</td>';
                        echo '<td class="col-lote" style="text-align:center;">' . esc($f['lote']) . '</td>';
                        echo '<td class="col-precio" style="text-align:right; padding-right:2px;">$' . number_format($f['precio_u'], 2, '.', ',') . '</td>';
                        echo '<td class="col-total" style="text-align:right; padding-right:2px;">$' . number_format($f['precio_u'] * $f['cantidad'], 2, '.', ',') . '</td>';
                        echo '</tr>';
                    } else {
                        echo '<tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
                    }
                }
                ?>
                <!-- TOTALS ROWS -->
                <tr>
                    <td colspan="2" class="totals-label">Total Artículos:</td>
                    <td class="totals-value" style="text-align:center; font-weight:bold;"><?= $granCantidad ?></td>
                    <td style="border:none;"></td>
                    <td colspan="2" class="totals-label">Subtotal</td>
                    <td class="totals-value" style="text-align:right; padding-right:2px; font-weight:bold;">$<?= number_format($gransubtotal, 2, '.', ',') ?></td>
                </tr>
                <tr>
                    <td colspan="4" rowspan="2" style="border:none;"></td>
                    <td colspan="2" class="totals-label">IVA</td>
                    <td class="totals-value" style="text-align:right; padding-right:2px; font-weight:bold;">$<?= number_format($graniva, 2, '.', ',') ?></td>
                </tr>
                <tr>
                    <td colspan="2" class="totals-label">Total</td>
                    <td class="totals-value" style="text-align:right; padding-right:2px; font-weight:bold;">$<?= number_format($grantotal, 2, '.', ',') ?></td>
                </tr>
            </tbody>
        </table>

        <!-- SIGNATURES -->
        <div class="signatures-section" style="display:flex; justify-content:space-between; gap:8px; margin-top:6px;">
            <div style="flex:1;">
                <div style="font-size:8px; color:#333; text-align:center; margin-bottom:2px; font-weight:bold;">Quien Atendió</div>
                <div style="border:1.5px solid #1a3a6b; height:20px; display:flex; align-items:center; justify-content:center; font-size:8px; font-weight:bold;"><?= esc($repFormato) ?></div>
            </div>
            <div style="flex:1;">
                <div style="font-size:8px; color:#333; text-align:center; margin-bottom:2px; font-weight:bold;">RFC</div>
                <div style="border:1.5px solid #1a3a6b; height:20px; display:flex; align-items:center; justify-content:center; font-size:8px; font-weight:bold;"><?= esc($rfcFormato) ?></div>
            </div>
        </div>

        <!-- BOTTOM -->
        <div class="bottom-section" style="margin-top:5px; display:flex; gap:4px; align-items:flex-start;">

            <!-- Direccion del Cliente -->
            <div class="hospital-rfc">
                <div style="font-size:8px; color:#333; text-align:center; margin-bottom:2px; font-weight:bold;">Dirección del Cliente</div>
                <div style="border:1.5px solid #1a3a6b; padding:5px 7px; min-height:38px; height:auto;">
                    <div style="font-size:8.5px; line-height:1.6; color:#000;"><?= $direccionFormateada ?></div>
                </div>
            </div>

            <!-- Firmas -->
            <div class="firma-section">
                <!-- Fila de labels encima -->
                <div style="display:flex; gap:0;">
                    <div style="flex:1; font-size:8px; color:#333; text-align:center; margin-bottom:2px; font-weight:bold;">Firma de Cliente</div>
                    <div style="flex:1; font-size:8px; color:#333; text-align:center; margin-bottom:2px; font-weight:bold;">Firma de Representante</div>
                    <div style="flex:1; font-size:8px; color:#333; text-align:center; margin-bottom:2px; font-weight:bold;">Etiquetas</div>
                </div>
                <!-- Fila de cuadros -->
                <div class="firma-inner" style="height:50px;">
                    <div style="flex:1; border-right:1.5px solid #1a3a6b;"></div>
                    <div style="flex:1; border-right:1.5px solid #1a3a6b;"></div>
                    <div style="flex:1; display:flex; align-items:center; justify-content:center; font-size:7.5px; font-weight:bold; text-align:center; padding:3px;">
                        * FAVOR DE PONER<br>ETIQUETAS AL REVERSO<br>DE LA REMISIÓN *
                    </div>
                </div>
                <!-- Condicion de Pago: cuadro aparte debajo de Firma de Cliente -->
                <div style="display:flex; margin-top:4px;">
                    <div style="flex:1;">
                        <div style="font-size:8px; color:#333; text-align:center; margin-bottom:2px; font-weight:bold;">Condición de Pago</div>
                        <div style="border:1.5px solid #1a3a6b; padding:3px 6px; min-height:20px; display:flex; align-items:center; justify-content:center; font-size:9px; font-weight:bold;"><?= esc($filaCab['CLIENTE_CONDICION_PAGO'] ?? '') ?></div>
                    </div>
                    <!-- espacio vacío alineado con las otras 2 celdas -->
                    <div style="flex:2;"></div>
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