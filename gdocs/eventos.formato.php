<?php
require_once '../vendor/autoload.php';
require_once "../includes/includes.php";

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

$eventoid = base64_decode($_GET['eventoid']);
$eventos = new eventos();
$result = $eventos->geteventobyid($eventoid);
$result2 = $eventos->getproveedoresbyeventoid($eventoid);
$concepto = $result[0]['EVENTO_CONCEPTO'];

// Nombre del almacén con salvavidas para eventos viejos
$nombreAlmacen = $result[0]['EVENTO_ALMACEN_NOMBRE'] ?? 'Almacén de ' . $result[0]['SUCURSAL_NOMBRE'];

$pdf = new MYPDF();
$pdf->SetMargins(15, 15, 15);  // izquierda, arriba, derecha (en mm)
$pdf->SetAutoPageBreak(TRUE, 20); // activar salto automático con margen inferior de 20 mm
$pdf->setPrintHeader(false);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 9);

// Asigna valores para el pie de página
$pdf->titulo = "Solicitud de Evento"; // Corregí un pequeño error tipográfico que decía "Soicitud"
$pdf->folio = $result[0]['EVENTO_FOLIO'];

// Formatear fecha
$fechaSolicitud = date('d/m/Y H:i', strtotime($result[0]['EVENTO_FECHACREACION']));

// 1) Generar contenido QR
$qrData = $GLOBALS['global_site']."scan/evento.php?eventoid=".base64_encode($result[0]['EVENTO_ID']);

// Primer renglón: Logo izquierda, Folio y fecha derecha
$htmlHeader = '
<table width="100%" cellpadding="5" cellspacing="0" style="font-size:10pt;">
    <tr>
        <td width="40%" valign="middle">
            <img src="' . $GLOBALS['global_site'] . 'img/logo.png" width="150" />
        </td>
        <td width="60%" align="right" style="font-weight:bold; font-size:16pt;">
            ' . $result[0]['EVENTO_FOLIO'] . '<br>
            <span style="font-size:9pt; font-weight:normal;">Fecha: ' . $fechaSolicitud . '</span>
            <br><span style="font-size:8pt; font-weight:normal;">Almacén: '.$nombreAlmacen.'</span>
        </td>
    </tr>
</table>
<br><br>';

// Segundo renglón: Título centrado
$htmlHeader .= '
<table width="100%" cellpadding="5" cellspacing="0" style="font-size:14pt; font-weight:bold;">
    <tr>
        <td align="center">
            Solicitud de Evento
        </td>
    </tr>
</table>
<br><br><br>';

$pdf->writeHTMLCell(0, 0, '', '', $htmlHeader, 0, 1, false, true, 'L', true);

// Construcción del contenido HTML con formato
$html = '';

// Tabla Información General
$html .= '
<table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; font-family: helvetica, sans-serif; font-size: 10pt;">
    <tr>
        <th colspan="2" style="background-color: #f2f2f2; font-size:12px; font-weight:bold">
                Información General
        </th>
    </tr>
    <tr>
        <th width="30%">Cliente</th>
        <td width="70%">'.(($result[0]['NOMBRE']=="")?'<b>Particular: </b>'.$result[0]['EVENTO_NOMBREPARTICULAR'].'<br>(Presupuesto: $'.number_format((float)$result[0]['EVENTO_PRESUPUESTOPARTICULAR'],2,'.',',').')':$result[0]['NOMBRE']).'</td>
    </tr>
    <tr>
        <th>Lugar</th>
        <td><b>'.$result[0]['HOSPITAL_NOMBRE'].'</b></td>
    </tr>
    <tr>
        <th>Fecha</th>
        <td><b>'.$result[0]['EVENTO_FECHAI'].' - '.$result[0]['EVENTO_FECHAF'].'</b> ('.$result[0]['DURACION_HORAS'].') hrs</td>
    </tr>
    <tr>
        <th>Grupo y Subgrupo</th>
        <td>'.$result[0]['TIPOEVENTOGRUPO_NOMBRE'].' - '.$result[0]['TIPOEVENTOSUBGRUPO_NOMBRE'].'</td>
    </tr>
    <tr>
        <th>Tipo de Evento</th>
        <td>'.$result[0]['TIPOEVENTO_NOMBRE'].'</td>
    </tr>
    <tr>
        <th>Concepto</th>
        <td>'.$concepto.'</td>
    </tr>
</table>';

// Sección Participantes
$html .= '<br><br><table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; font-family: helvetica, sans-serif; font-size: 10pt;">
    <tr>
        <th colspan="2" style="background-color: #f2f2f2; font-size:12px; font-weight:bold">Especialista y Chofer</th>
    </tr>
    <tr>
        <th width="30%">ESPECIALISTA</th>
        <td width="70%">'.(($result[0]['ESPECIALISTA_NOMBRE']=="")?'<span style="color:red; font-weight:bold">No asignado</span>':$result[0]['ESPECIALISTA_NOMBRE'].' <span style="color:#'.$result[0]['STATUSCOLORESPECIALISTA'].'">('.$result[0]['STATUSESPECIALISTA'].')</span>').'</td>
    </tr>
    <tr>
        <th>CHOFER</th>
        <td>'.(($result[0]['CHOFER_NOMBRE']=="")?'<span style="color:red; font-weight:bold">No asignado</span>':$result[0]['CHOFER_NOMBRE'].' <span style="color:#'.$result[0]['STATUSCOLORCHOFER'].'">('.$result[0]['STATUSCHOFER'].')</span>').'</td>
    </tr>
</table>';

// Sección Maletas
$html .= '<br><br><table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; font-family: helvetica, sans-serif; font-size: 10pt;">';
$html .= '<tr><td colspan="2" style="background-color: #f2f2f2; font-size:12px; font-weight:bold">Maletas</td></tr>';

$maletasImpresas = [];
$equiposImpresos = [];
foreach ($result as $res) {
    if (!empty($res['ALMACEN_FOLIO']) && (int)($res['ALMACEN_ID'] ?? 0) > 0 && !in_array($res['ALMACEN_FOLIO'], $maletasImpresas)) {
        $html .= '<tr>
                <td colspan="2"><b>'.$res['ALMACEN_FOLIO'].'</b> - '.$res['ALMACEN_NOMBRE'].'</td>
        </tr>';
        $maletasImpresas[] = $res['ALMACEN_FOLIO'];
    } elseif (!empty($res['ALMACEN_FOLIO']) && (int)($res['ALMACEN_ID'] ?? 0) < 0 && !in_array($res['ALMACEN_FOLIO'], array_column($equiposImpresos, 'folio'))) {
        $equiposImpresos[] = ['folio' => $res['ALMACEN_FOLIO'], 'nombre' => $res['ALMACEN_NOMBRE']];
    }
}
if (empty($maletasImpresas)) {
    $html .= '<tr><td colspan="2">Sin maletas asignadas</td></tr>';
}
$html .= '</table>';

if (!empty($equiposImpresos)) {
    $html .= '<br><br><table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; font-family: helvetica, sans-serif; font-size: 10pt;">';
    $html .= '<tr><td colspan="2" style="background-color: #f2f2f2; font-size:12px; font-weight:bold">Equipo Capital</td></tr>';
    foreach ($equiposImpresos as $eq) {
        $html .= '<tr><td colspan="2"><b>'.$eq['folio'].'</b> - '.$eq['nombre'].'</td></tr>';
    }
    $html .= '</table>';
}

// Sección Proveedores Invitados
if ($result2 <> 0) {
    // Tabla principal con borde
    $html .= '<br><br><table cellpadding="4" border="1" cellspacing="0" width="100%" 
                style="border-collapse: collapse; font-family: helvetica, sans-serif; font-size: 10pt;">';
    
    // Encabezado
    $html .= '<tr>
                <th colspan="1" style="background-color: #f2f2f2; font-size:12px; font-weight:bold">
                    Proveedores Invitados
                </th>
              </tr>';
    
    // Segunda fila que contendrá la tabla sin bordes
    $html .= '<tr><td>';
    
    // Tabla interna sin bordes
    $html .= '<table cellpadding="4" border="0" cellspacing="0" width="100%" 
                style="font-family: helvetica, sans-serif; font-size: 10pt;">';

        $html .= '<tr>';
        $html .= '<td width="30" valign="top" style="font-weight: bold;"></td>';
        $html .= '<td valign="top"><b>Proveedor</b></td>';
        $html .= '<td><b>Observaciones</b></td>';
        $html .= '</tr>';

    foreach ($result2 as $r2) {
        $html .= '<tr>';
        $html .= '<td width="30" valign="top" style="font-weight: bold;">•</td>';
        $html .= '<td valign="top">' . htmlspecialchars($r2['NOMBREPROVEEDOR']) . '</td>';
        $html .= '<td>';
        if (!empty(trim($r2['EVENTOPROVEEDOR_OBSERVACIONES']))) {
            $html .=  nl2br(htmlspecialchars($r2['EVENTOPROVEEDOR_OBSERVACIONES']));
        }
        $html .= '</td>';
        $html .= '</tr>';
    }

    $html .= '</table>'; // Cierra tabla interna sin bordes
    $html .= '</td></tr>'; // Cierra celda y fila en tabla principal
    $html .= '</table>'; // Cierra tabla principal
}

// Renderizar el contenido HTML en el PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Salida del PDF en navegador
$pdf->Output('evento_solicitud.pdf', 'I');
?>