<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$componente_id = isset($_POST['componente_id']) ? intval($_POST['componente_id']) : 0;
$equipocapital_id = isset($_POST['equipocapital_id']) ? intval($_POST['equipocapital_id']) : 0;
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;
$serie = isset($_POST['serie']) ? trim($_POST['serie']) : '';
$referencia = isset($_POST['referencia']) ? trim($_POST['referencia']) : '';
$marca = isset($_POST['marca']) ? trim($_POST['marca']) : '';

if ($equipocapital_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID de equipo capital inválido']);
    exit;
}

if (empty($nombre)) {
    echo json_encode(['ok' => false, 'msg' => 'El nombre del componente es requerido']);
    exit;
}

if ($cantidad < 1) {
    $cantidad = 1;
}

try {
    $eqCapital = new equipocapital();
    $success = $eqCapital->guardarComponente($componente_id, $equipocapital_id, $nombre, $cantidad, $serie, $referencia, $marca);
    
    if ($success) {
        echo json_encode([
            'ok' => true,
            'msg' => $componente_id > 0 ? 'Componente actualizado correctamente' : 'Componente registrado correctamente'
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'msg' => 'No se pudo guardar el componente'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al guardar componente: ' . $e->getMessage()
    ]);
}
?>
