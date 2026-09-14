<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$articulo_id = isset($_POST['articulo_id']) ? intval($_POST['articulo_id']) : 0;
$estatus = isset($_POST['estatus']) ? trim($_POST['estatus']) : '';

if ($articulo_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID de artículo inválido']);
    exit;
}

if (!in_array($estatus, ['A', 'B'])) {
    echo json_encode(['ok' => false, 'msg' => 'Estatus inválido (debe ser A o B)']);
    exit;
}

try {
    $db = new FirebirdConnection();
    
    // Verificar que el artículo existe
    $check = $db->query("SELECT ARTICULO_ID, NOMBRE FROM ARTICULOS WHERE ARTICULO_ID = ?", [$articulo_id]);
    
    if (!is_array($check) || empty($check)) {
        echo json_encode(['ok' => false, 'msg' => 'El artículo no existe']);
        $db->close();
        exit;
    }
    
    // Actualizar el estatus
    $sql = "UPDATE ARTICULOS SET ESTATUS = ? WHERE ARTICULO_ID = ?";
    $success = $db->execute($sql, [$estatus, $articulo_id]);
    
    $db->close();
    
    if ($success) {
        $accion = $estatus === 'A' ? 'activado' : 'desactivado';
        echo json_encode([
            'ok' => true,
            'msg' => "Artículo $accion correctamente"
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'msg' => 'No se pudo actualizar el estatus'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al actualizar: ' . $e->getMessage()
    ]);
}
?>
