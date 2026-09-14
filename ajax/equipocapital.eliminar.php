<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$equipocapital_id = isset($_POST['equipocapital_id']) ? intval($_POST['equipocapital_id']) : 0;

if ($equipocapital_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID de equipo capital inválido']);
    exit;
}

try {
    $eqCapital = new equipocapital();
    $success = $eqCapital->eliminarEquipoCapital($equipocapital_id);
    
    if ($success) {
        echo json_encode([
            'ok' => true,
            'msg' => 'Equipo capital eliminado correctamente'
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'msg' => 'No se pudo eliminar el equipo capital'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al eliminar: ' . $e->getMessage()
    ]);
}
?>
