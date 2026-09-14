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
$res = $entradasalida->getinfogarantiaproveedorbyid(base64_decode($_GET['cpid']));

$pdf = new MYPDF();
$pdf->SetMargins(15, 15, 15);  // izquierda, arriba, derecha (en mm)
$pdf->SetAutoPageBreak(TRUE, 20); // activar salto automático con margen inferior de 20 mm
$pdf->setPrintHeader(false);
$pdf->AddPage();
$pdf->SetFont('freesans', '', 9);

// Asigna valores para el pie de página
$pdf->titulo = "Garantía de Proveedor";
$pdf->folio = $res[0]['CADUCIDADPROV_FOLIO'];

// Formatear fecha
$fechaSolicitud = date('d/m/Y H:i', strtotime($res[0]['CADUCIDADPROV_FECHA']));

// Título
    $titulo = "Garantía de Proveedor";

// Primer renglón: Logo izquierda, Folio y fecha derecha
$html = '
<table width="100%" cellpadding="5" cellspacing="0" style="font-size:10pt;">
    <tr>
        <td width="40%" valign="middle">
            <img src="' . $GLOBALS['global_site'] . 'img/logo.png" width="110" />
        </td>
        <td width="60%" align="right" style="font-weight:bold; font-size:16pt;">
            ' . $res[0]['CADUCIDADPROV_FOLIO'] . '<br>
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
        <td width="20%" style="border: 1px solid black; padding:5px;"><strong>Status:</strong></td>
        <td width="80%" style="border: 1px solid black; padding:5px; color:#'.$res[0]['STATUS_COLOR'].'">' . $res[0]['STATUS_NOMBRE'] . '</td>
    </tr>
    <tr>
        <td width="20%" style="border: 1px solid black; padding:5px;"><strong>Almacén:</strong></td>
        <td width="80%" style="border: 1px solid black; padding:5px;">' . $res[0]['ALMACEN_NOMBRE'] . ' ('.$res[0]['SUCURSAL_NOMBRE'].')</td>
    </tr>
    <tr>
        <td width="20%" style="border: 1px solid black; padding:5px;"><strong>Proveedor:</strong></td>
        <td width="80%" style="border: 1px solid black; padding:5px;">' . $res[0]['NOMBREPROVEEDOR'] . '</td>
    </tr>
    <tr>
        <td width="20%" style="border: 1px solid black; padding:5px;"><strong>Contácto:</strong></td>
        <td width="80%" style="border: 1px solid black; padding:5px;">' . $res[0]['CADUCIDADPROV_PROVCORREO'] . '</td>
    </tr>
</table>
<br><br>';

// Sexto renglón: Tabla detalle artículos
$html .= '
<b>Detalle de Artículos</b><br><br>
<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse: collapse; font-size: 9pt;">
    <thead>
        <tr style="background-color:#eeeeee;">
            <th width="10%" align="center"><strong>#</strong></th>
            <th width="20%" align="center"><strong>Clave</strong></th>
            <th width="60%"><strong>Artículo</strong></th>
            <th width="10%" align="center"></th>
        </tr>
    </thead>
    <tbody>';

$contador = 1;
foreach ($res as $re) {

    $lote = $re['STOCK_LOTE'] ?: '-';
    $caducidad = $re['STOCK_CADUCIDAD'] ?: '-';
    $detalle = '<br><span style="font-size:8pt; color:#555555; float: right;"><strong>Lote:</strong> ' . $lote . ' &nbsp;&nbsp; <strong>Caducidad:</strong> ' . $caducidad . '</span>';

    // Si está reemplazado, muestra el angulito
    $icono = '';
    if ($re['CADUCIDADPROVDET_REEMPLAZADO'] == 1) {
        $icono = '<span style="font-size:14pt; color:#007bff;">&#10003;</span>'; // ► azul
    }

    $html .= '
    <tr style="color:black; page-break-inside: avoid;">
        <td width="10%" align="center">' . $contador . '</td>
        <td width="20%" align="center"><b>' . $re['STOCK_FOLIO'] . '</b><br>' . $re['CLAVE_ARTICULO'] . '</td>
        <td width="60%">' . $re['ARTICULO_NOMBRE'] . $detalle . '</td>
        <td width="10%" align="center">' . $icono . '</td>
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