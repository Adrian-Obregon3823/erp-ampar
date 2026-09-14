<?php
include_once("../includes/includes.php");
require '../vendor/autoload.php';

class MYPDF extends TCPDF {
    public $titulo = '';
    public $folio = '';

    // Pie de página
    public function Footer() {
        $this->SetY(-15);  // Posición a 15 mm desde abajo
        $this->SetFont('freesans', 'I', 8);
        
        // Texto con título y folio a la izquierda
        $footer_text = $this->titulo . ' - Folio: ' . $this->folio;
        $this->Cell(0, 10, $footer_text, 0, 0, 'L');

        // Número de página a la derecha
        $pageNum = 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages();
        $this->Cell(0, 10, $pageNum, 0, 0, 'R');
    }
}

$entradasalida = new entradasalida();
$res = $entradasalida->getinfoentradasalidabyid(base64_decode($_GET['esid']));

$pdf = new MYPDF();
$pdf->SetMargins(15, 15, 15);  // izquierda, arriba, derecha (en mm)
$pdf->SetAutoPageBreak(TRUE, 20); // activar salto automático con margen inferior de 20 mm
$pdf->setPrintHeader(false);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 9);

// Asigna valores para el pie de página
$pdf->titulo = ($res[0]['ES_TIPO'] == 'E') ? "Entrada de Almacén" : "Salida de Almacén";
$pdf->folio = $res[0]['ES_FOLIO'];

// Formatear fecha
$fechaSolicitud = date('d/m/Y H:i', strtotime($res[0]['ES_FECHA']));

// Título según tipo
if ($res[0]['ES_TIPO'] == 'E') {
    $titulo = "Solicitud de Entrada de Almacén";
} elseif ($res[0]['ES_TIPO'] == 'S') {
    $titulo = "Solicitud de Salida de Almacén";
} elseif ($res[0]['ES_TIPO'] == 'R') {
    $titulo = "Solicitud de Reposición";
} else {
    $titulo = "Indefinido";
}

// Primer renglón: Logo izquierda, Folio y fecha derecha
$html = '
<table width="100%" cellpadding="5" cellspacing="0" style="font-size:10pt;">
    <tr>
        <td width="40%" valign="middle">
            <img src="' . $GLOBALS['global_site'] . 'img/logo.png" width="150" />
        </td>
        <td width="60%" align="right" style="font-weight:bold; font-size:16pt;">
            ' . $res[0]['ES_FOLIO'] . '<br>
            <span style="font-size:10pt; font-weight:normal;">Fecha: ' . $fechaSolicitud . '</span>
        </td>
    </tr>
</table>
<br><br>';

// Segundo renglón: Título centrado
$html .= '
<table width="100%" cellpadding="5" cellspacing="0" style="font-size:14pt; font-weight:bold;">
    <tr>
        <td align="center">
            ' . $titulo . '
        </td>
    </tr>
</table>
<br><br><br>';

// Tercer renglón: Almacén y Sucursal mismo renglón, cada uno 50%
$html .= '
<table width="100%" cellpadding="5" cellspacing="0" style="font-size:10pt; border-collapse: collapse;">
    <tr>
        <td width="50%" style="border: 1px solid black; padding:5px;"><strong>Almacén:</strong> ' . $res[0]['ALMACEN_NOMBRE'] . '</td>
        <td width="50%" style="border: 1px solid black; padding:5px;"><strong>Sucursal:</strong> ' . $res[0]['SUCURSAL_NOMBRE'] . '</td>
    </tr>
</table>
<br>';

// Cuarto renglón: Motivo en renglón separado
$html .= '
<table width="100%" cellpadding="5" cellspacing="0" style="font-size:10pt; border-collapse: collapse;">
    <tr>
        <td style="border: 1px solid black; padding:5px;"><strong>Motivo:</strong> ' . nl2br(htmlspecialchars($res[0]['ES_MOTIVO'])) . '</td>
    </tr>
</table>
<br>';

// Quinto renglón: Concepto en renglón separado
$html .= '
<table width="100%" cellpadding="5" cellspacing="0" style="font-size:10pt; border-collapse: collapse;">
    <tr>
        <td style="border: 1px solid black; padding:5px;"><strong>Concepto:</strong> ' . $res[0]['CONCEPTO_NOMBRE'] . '</td>
    </tr>
</table>
<br><br>';


// Sexto renglón: Tabla detalle artículos
$html .= '
<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse: collapse; font-size: 9pt;">
    <thead>
        <tr style="background-color:#eeeeee;">
            <th width="10%" align="center"><strong>#</strong></th>
            <th width="20%" align="center"><strong>Clave</strong></th>
            <th width="70%"><strong>Artículo</strong></th>
        </tr>
    </thead>
    <tbody>';

$contador = 1;
foreach ($res as $re) {
    if ($re['ESDET_ACTIVO'] == 1) {
        $colorTexto = "#000000";
    } elseif ($re['ESDET_ACTIVO'] == 0) {
        $colorTexto = "#888888";
    } else {
        $colorTexto = "#ff0000";
    }

    $seguimiento = $re['SEGUIMIENTO'] ?? '';

    $detalle = '';
    if ($seguimiento == 'L') {
        $lote = $re['ESDET_LOTE'] ?: '-';
        $caducidad = $re['ESDET_CADUCIDAD'] ?: '-';
        $detalle = '<br><span style="font-size:8pt; color:#555555; float: right;"><strong>Lote:</strong> ' . $lote . ' &nbsp;&nbsp; <strong>Caducidad:</strong> ' . $caducidad . '</span>';
    } elseif ($seguimiento == 'S') {
        $serie = $re['ESDET_SERIE'] ?: '-';
        $detalle = '<br><span style="font-size:8pt; color:#555555; float: right;"><strong>Serie:</strong> ' . $serie . '</span>';
    } else {
        // 'N' o sin seguimiento: no mostrar detalles
        $detalle = '';
    }

    $html .= '
    <tr style="color:' . $colorTexto . '; page-break-inside: avoid;">
        <td width="10%" align="center">' . $contador . '</td>
        <td width="20%" align="center"><b>' . $re['CLAVE_ARTICULO'] . '</b><br>'.(($re['STOCK_FOLIO']<>"")?"(".$re['STOCK_FOLIO'].") <b>&#10132;</b>":"").'</td>
        <td width="70%">' . $re['ARTICULO_NOMBRE'] . $detalle . '</td>
    </tr>';

    $contador++;
}

$html .= '
    </tbody>
</table>';


// Escribir contenido en PDF
$pdf->writeHTML($html, true, false, true, false, '');

$pdf->Output('Solicitud_EntradaSalida.pdf', 'I');
?>