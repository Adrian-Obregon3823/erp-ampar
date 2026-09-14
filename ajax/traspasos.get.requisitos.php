<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$traspasoid = isset($_POST['traspasoid']) ? (int)$_POST['traspasoid'] : 0;
if ($traspasoid <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID de traspaso inválido']);
    exit;
}

$db = new FirebirdConnection();
$sql = "
    SELECT 
    t.TRASPASO_DEALMACENID,
    A1.ALMACEN_TIPOALMACEN AS TIPO_DE,
    A1.ALMACEN_ALMACEN_MS AS PADRE_DE,
    A1.ALMACEN_SUCURSAL_MS AS SUCURSAL_DE,
    t.TRASPASO_AALMACENID,
    A2.ALMACEN_TIPOALMACEN AS TIPO_A,
    A2.ALMACEN_ALMACEN_MS AS PADRE_A,
    A2.ALMACEN_SUCURSAL_MS AS SUCURSAL_A,
    t.TRASPASO_EVIDENCIA1,
    t.TRASPASO_EVIDENCIA2,
    t.TRASPASO_EVIDENCIA3,
    t.TRASPASO_EVIDENCIA4,
    t.TRASPASO_LISTAEMPAQUE
    FROM AMPAR_HIS_TRASPASO t
    LEFT JOIN AMPAR_HIS_ALMACEN A1 ON A1.ALMACEN_ID = t.TRASPASO_DEALMACENID
    LEFT JOIN AMPAR_HIS_ALMACEN A2 ON A2.ALMACEN_ID = t.TRASPASO_AALMACENID
    WHERE t.TRASPASO_ID = ?
";
$result = $db->query($sql, [$traspasoid]);
$db->close();

if (!$result || count($result) === 0) {
    echo json_encode(['ok' => false, 'error' => 'Traspaso no encontrado']);
    exit;
}

$data = $result[0];

// Determinar reglas
$isMaletaDe = ((int)$data['TIPO_DE'] === 3);
$isMaletaA = ((int)$data['TIPO_A'] === 3);

$requiereTodo = true;
$requiereFotosSolamente = false;
$eximeTodo = false;

// Regla 1: Traspaso que involucre una Maleta (ya sea origen o destino)
if ($isMaletaDe || $isMaletaA) {
    $eximeTodo = true;
    $requiereTodo = false;
}

// Regla 2: Almacén a Material Caducado (ID 10) desde Saltillo CEDIS (ID Sucursal 1)
// Y "si es saltillo CEDIS no pedir nada de eso solo fotos de los articulos"
if (!$isMaletaDe && !$isMaletaA) {
    if ((int)$data['TRASPASO_AALMACENID'] === 10 && (int)$data['SUCURSAL_DE'] === 1) {
        $requiereFotosSolamente = true;
        $requiereTodo = false;
    }
}

// Regla 3: Maleta a Material Caducado (ID 10) desde Saltillo CEDIS (ID 1)
if ($isMaletaDe && !$isMaletaA) {
    if ((int)$data['TRASPASO_AALMACENID'] === 10) {
        // Asumiendo que Saltillo CEDIS tiene ID de almacén principal 1 (PADRE_DE = 1)
        if ((int)$data['PADRE_DE'] === 1) {
            $eximeTodo = true; // No pide escaneo ni fotos
            $requiereTodo = false;
        } else {
            // "si es una maleta de otro almacen que no es saltillo CEDIS (id 1) no deje"
            echo json_encode(['ok' => false, 'error' => 'No está permitido traspasar maletas a Material Caducado que no pertenezcan a Saltillo CEDIS (ID 1).']);
            exit;
        }
    }
}

// Calcular número de páginas del PDF
$numPages = 1;
try {
    include_once("../class/traspasos.php");
    require_once '../vendor/autoload.php';
    $traspasosCls = new traspasos();
    $resT = $traspasosCls->getinfotraspasobyid($traspasoid);
    if (!empty($resT)) {
        $pdf = new TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 9);
        $html = '<br><h3 align="center" style="color: #1a3a6b;">TRASPASO DE ALMACÉN</h3><br>
        <table width="100%" cellpadding="3"><tr><td></td><td></td></tr><tr><td></td><td></td></tr><tr><td></td><td></td></tr><tr><td></td><td></td></tr></table>
        <br><br><b>Detalle de Artículos</b><br><br>
        <table width="100%" cellpadding="4">
            <tr class="th"><th width="20%">FOLIO</th><th width="80%">DESCRIPCIÓN</th></tr>';
        
        foreach ($resT as $re) {
            $html .= '
            <tr>
                <td width="20%" class="b1" align="center"><strong>' . $re['STOCK_FOLIO'] . '</strong></td>
                <td width="80%" class="b1">
                    <span style="font-size: 10px;"><b>' . $re['CLAVE_ARTICULO'] . '</b> - ' . $re['ARTICULO_NOMBRE'] . '</span>
                    <br>
                    <table width="100%" border="0" cellpadding="1">
                        <tr>
                            <td width="35%" style="font-size:9px;"><strong>Lote:</strong> ' . $re['ESDET_LOTE'] . '</td>
                            <td width="35%" style="font-size:9px;"><strong>Caducidad:</strong> ' . $re['ESDET_CADUCIDAD'] . '</td>
                            <td width="30%" style="font-size:9px;"><strong>Serie:</strong> ' . $re['ESDET_SERIE'] . '</td>
                        </tr>
                    </table>
                </td>
            </tr>';
        }
        $html .= '</table><br><br><br><br><table width="100%" border="0" cellpadding="4"><tr><td><br><br><br></td></tr></table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $numPages = $pdf->getNumPages();
    }
} catch (Exception $e) {
    // Default to 1 if error
}

echo json_encode([
    'ok' => true,
    'data' => [
        'isMaletaDe' => $isMaletaDe,
        'isMaletaA' => $isMaletaA,
        'requiereTodo' => $requiereTodo,
        'requiereFotosSolamente' => $requiereFotosSolamente,
        'eximeTodo' => $eximeTodo,
        'evidencia1_subida' => !empty($data['TRASPASO_EVIDENCIA1']),
        'evidencia2_subida' => !empty($data['TRASPASO_EVIDENCIA2']),
        'evidencia3_subida' => !empty($data['TRASPASO_EVIDENCIA3']),
        'evidencia4_subida' => !empty($data['TRASPASO_EVIDENCIA4']),
        'listaempaque_subida' => !empty($data['TRASPASO_LISTAEMPAQUE']),
        'pdfPages' => $numPages
    ]
]);
?>
