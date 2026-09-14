<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$reqId = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($reqId <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID de requerimiento inválido']);
    exit;
}

try {
    $rm = new requerimientosmaterial();
    // Update status to 8 (En Revisión)
    $success = $rm->actualizarStatus($reqId, 8);

    if ($success) {
        echo json_encode(['ok' => true, 'msg' => 'Requerimiento confirmado correctamente']);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'No se pudo confirmar el requerimiento']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al confirmar requerimiento: ' . $e->getMessage()]);
}
