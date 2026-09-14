<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$reqId = isset($_POST['req_id']) ? intval($_POST['req_id']) : 0;
$motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';

if ($reqId <= 0 || empty($motivo)) {
    echo json_encode(['ok' => false, 'msg' => 'Datos inválidos o motivo vacío']);
    exit;
}

try {
    $db = new FirebirdConnection(true);
    
    // Verificar que exista
    $res = $db->query("SELECT REQMATERIAL_OBSERVACIONES FROM AMPAR_HIS_REQUERIMIENTOMATERIAL WHERE REQMATERIAL_ID = ?", [$reqId]);
    if (empty($res)) {
        throw new Exception("Requerimiento no encontrado.");
    }
    
    $obsActual = $res[0]['REQMATERIAL_OBSERVACIONES'] ?? '';
    $nuevaObs = trim($obsActual . "\n\nRechazado: " . $motivo);

    // 6 = Rechazado
    $db->execute("UPDATE AMPAR_HIS_REQUERIMIENTOMATERIAL SET REQMATERIAL_STATUS = 6, REQMATERIAL_OBSERVACIONES = ? WHERE REQMATERIAL_ID = ?", [$nuevaObs, $reqId]);
    
    $db->close();
    
    echo json_encode(['ok' => true, 'msg' => 'Requerimiento rechazado correctamente.']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
?>