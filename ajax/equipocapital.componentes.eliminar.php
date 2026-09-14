<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$componente_id = isset($_POST['componente_id']) ? intval($_POST['componente_id']) : 0;
$equipocapital_id = isset($_POST['equipocapital_id']) ? intval($_POST['equipocapital_id']) : 0;

if ($componente_id <= 0 || $equipocapital_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'Parámetros inválidos']);
    exit;
}

try {
    $eqCapital = new equipocapital();
    $success = $eqCapital->eliminarComponente($componente_id, $equipocapital_id);
    
    if ($success) {
        echo json_encode([
            'ok' => true,
            'msg' => 'Componente eliminado correctamente'
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'msg' => 'No se pudo eliminar el componente'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al eliminar componente: ' . $e->getMessage()
    ]);
}
?>
