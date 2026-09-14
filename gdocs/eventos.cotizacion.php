<?php
require_once '../vendor/autoload.php';
require_once "../includes/includes.php";

class MYPDF extends TCPDF {
    public $titulo = '';
    public $folio = '';

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('freesans', 'I', 8);
        $footer_text = $this->titulo . ' - Folio: ' . $this->folio;
        $this->Cell(0, 10, $footer_text, 0, 0, 'L');
        $pageNum = 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages();
        $this->Cell(0, 10, $pageNum, 0, 0, 'R');
    }
}

try{
    // ---------- Datos ----------
    $eventoid = intval(base64_decode($_GET['eventoid'] ?? '0'));
    if ($eventoid <= 0) throw new Exception('Evento inválido');

    $eventos = new eventos();
    $result  = $eventos->geteventobyid($eventoid);
    if (!$result || !is_array($result) || empty($result[0])) throw new Exception('No se encontró el evento');

    $ev = $result[0];

    // Campos usados
    $folio       = (string)$ev['EVENTO_FOLIO'];
    $sucursal    = (string)($ev['SUCURSAL_NOMBRE'] ?? '');
    $clienteNom  = (string)($ev['NOMBRE'] ?? '');
    $partNom     = (string)($ev['EVENTO_NOMBREPARTICULAR'] ?? '');
    $presupuesto = (float)($ev['EVENTO_PRESUPUESTOPARTICULAR'] ?? 0);
    $hospital    = (string)($ev['HOSPITAL_NOMBRE'] ?? '');
    $fIni        = (string)($ev['EVENTO_FECHAI'] ?? '');
    $fFin        = (string)($ev['EVENTO_FECHAF'] ?? '');
    $durHrs      = (string)($ev['DURACION_HORAS'] ?? '');
    $grupo       = (string)($ev['TIPOEVENTOGRUPO_NOMBRE'] ?? '');
    $subgrupo    = (string)($ev['TIPOEVENTOSUBGRUPO_NOMBRE'] ?? '');
    $tipo        = (string)($ev['TIPOEVENTO_NOMBRE'] ?? '');
    $concepto    = (string)($ev['EVENTO_CONCEPTO'] ?? '');

    // Si no hay presupuesto, aborta (para no generar PDF vacío)
    if ($presupuesto <= 0) throw new Exception('El evento no tiene monto aproximado (EVENTO_PRESUPUESTOPARTICULAR).');

    // Formateos
    $fechaSol = date('d/m/Y H:i', strtotime($ev['EVENTO_FECHACREACION']));
    $fechaRango = trim($fIni.' - '.$fFin);
    $clienteTexto = ($clienteNom === '')
        ? '<b>Particular: </b>'.htmlspecialchars($partNom).' <br>(Presupuesto: $'.number_format($presupuesto,2,'.',',').')'
        : htmlspecialchars($clienteNom);

    // ---------- PDF ----------
    $pdf = new MYPDF();
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 20);
    $pdf->setPrintHeader(false);
    $pdf->AddPage();
    $pdf->SetFont('dejavusans', '', 9);

    $pdf->titulo = "Cotización Preliminar";
    $pdf->folio  = $folio;

    // Header
    $htmlHeader = '
    <table width="100%" cellpadding="5" cellspacing="0" style="font-size:10pt;">
        <tr>
            <td width="40%" valign="middle">
                <img src="' . $GLOBALS['global_site'] . 'img/logo.png" width="150" />
            </td>
            <td width="60%" align="right" style="font-weight:bold; font-size:16pt;">
                ' . htmlspecialchars($folio) . '<br>
                <span style="font-size:9pt; font-weight:normal;">Fecha: ' . $fechaSol . '</span>
                <br><span style="font-size:8pt; font-weight:normal;">Sucursal: '.htmlspecialchars($sucursal).'</span>
            </td>
        </tr>
    </table>
    <br><br>
    <table width="100%" cellpadding="5" cellspacing="0" style="font-size:14pt; font-weight:bold;">
        <tr>
            <td align="center">Cotización Preliminar</td>
        </tr>
    </table>
    <br><br>';

    $pdf->writeHTML($htmlHeader, true, false, true, false, '');

    // Información general
    $html = '
    <table border="1" cellpadding="6" cellspacing="0" style="border-collapse: collapse; width: 100%; font-family: helvetica, sans-serif; font-size: 10pt;">
        <tr>
            <th colspan="2" style="background-color: #f2f2f2; font-size:12px; font-weight:bold">Información del Evento</th>
        </tr>
        <tr>
            <th width="30%">Cliente</th>
            <td width="70%">'.$clienteTexto.'</td>
        </tr>
        <tr>
            <th>Lugar</th>
            <td><b>'.htmlspecialchars($hospital).'</b></td>
        </tr>
        <tr>
            <th>Fecha</th>
            <td><b>'.htmlspecialchars($fechaRango).'</b>'.($durHrs!=='' ? ' ('.$durHrs.' hrs)' : '').'</td>
        </tr>
        <tr>
            <th>Grupo / Subgrupo</th>
            <td>'.htmlspecialchars($grupo).' - '.htmlspecialchars($subgrupo).'</td>
        </tr>
        <tr>
            <th>Tipo de Evento</th>
            <td>'.htmlspecialchars($tipo).'</td>
        </tr>
        <tr>
            <th>Concepto</th>
            <td>'.nl2br(htmlspecialchars($concepto)).'</td>
        </tr>
    </table>';

    // Monto aproximado destacado
    $html .= '
    <br><table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; width: 100%; font-family: helvetica, sans-serif;">
        <tr>
            <th width="50%" style="background-color:#f8f9fa; font-size:12px; text-align:left;">Monto aproximado</th>
            <td width="50%" style="font-size:16px; font-weight:bold; text-align:right;">
                $'.number_format($presupuesto,2,'.',',').' MXN
            </td>
        </tr>
    </table>';

    // Condiciones / notas
    $html .= '
    <br><table border="0" cellpadding="4" cellspacing="0" style="width:100%; font-size:9pt;">
        <tr><td><b>Notas y condiciones:</b></td></tr>
        <tr><td>
            • La presente es una <b>cotización preliminar</b> basada en la información del evento y puede variar según consumos reales, ajustes o requerimientos adicionales.<br>
            • Los precios están expresados en <b>MXN</b>. La vigencia de esta cotización es de <b>7 días</b> a partir de su emisión.<br>
            • En caso de ser necesario, se solicitará Orden de Compra y/o anticipo conforme a políticas vigentes.<br>
        </td></tr>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Salida
    $pdf->Output('cotizacion_'.$folio.'.pdf', 'I');

}catch(Throwable $e){
    header('Content-Type:text/plain; charset=utf-8', true, 200);
    echo 'Error al generar la cotización: '.$e->getMessage();
}
