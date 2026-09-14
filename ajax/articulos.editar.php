<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$articulo_id = isset($_POST['articulo_id']) ? intval($_POST['articulo_id']) : 0;
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$clave = isset($_POST['clave']) ? trim($_POST['clave']) : '';
$categoria_id = isset($_POST['categoria_id']) && $_POST['categoria_id'] !== '' ? intval($_POST['categoria_id']) : null;
$familia_id = isset($_POST['familia_id']) && $_POST['familia_id'] !== '' ? intval($_POST['familia_id']) : null;

error_log("=== EDITAR ARTICULO ===");
error_log(print_r($_POST, true));

if ($articulo_id <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID de artículo inválido']);
    exit;
}

if (empty($nombre)) {
    echo json_encode(['ok' => false, 'msg' => 'El nombre es requerido']);
    exit;
}

if ($familia_id !== null && $categoria_id === null) {
    echo json_encode(['ok' => false, 'msg' => 'Si cambia la familia, debe seleccionar una categoría obligatoriamente.']);
    exit;
}

try {
    $db = new FirebirdConnection();
    
    // Verificar que el artículo existe
    $check = $db->query("SELECT ARTICULO_ID FROM ARTICULOS WHERE ARTICULO_ID = ?", [$articulo_id]);
    
    if (!is_array($check) || empty($check)) {
        echo json_encode(['ok' => false, 'msg' => 'El artículo no existe']);
        $db->close();
        exit;
    }
    
    // Actualizar el nombre y la categoría del artículo
    if ($categoria_id !== null && $categoria_id > 0) {
        $sql = "UPDATE ARTICULOS SET NOMBRE = ?, LINEA_ARTICULO_ID = ? WHERE ARTICULO_ID = ?";
        $success = $db->execute($sql, [$nombre, $categoria_id, $articulo_id]);
    } else {
        $sql = "UPDATE ARTICULOS SET NOMBRE = ? WHERE ARTICULO_ID = ?";
        $success = $db->execute($sql, [$nombre, $articulo_id]);
    }
    
    if (!$success) {
        echo json_encode([
            'ok' => false,
            'msg' => 'No se pudo actualizar el nombre'
        ]);
        $db->close();
        exit;
    }
    
    // Actualizar o insertar la clave en CLAVES_ARTICULOS (ROL_CLAVE_ART_ID = 17)
    $checkClave = $db->query(
        "SELECT CLAVE_ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ARTICULO_ID = ? AND ROL_CLAVE_ART_ID = 17",
        [$articulo_id]
    );
    
    if (!empty($clave)) {
        // Si hay clave para guardar
        if (is_array($checkClave) && !empty($checkClave)) {
            // Ya existe, actualizar
            $sqlClave = "UPDATE CLAVES_ARTICULOS SET CLAVE_ARTICULO = ? WHERE ARTICULO_ID = ? AND ROL_CLAVE_ART_ID = 17";
            $db->execute($sqlClave, [$clave, $articulo_id]);
        } else {
            // No existe, insertar
            $sqlClave = "INSERT INTO CLAVES_ARTICULOS (CLAVE_ARTICULO, ARTICULO_ID, ROL_CLAVE_ART_ID) VALUES (?, ?, 17)";
            $db->execute($sqlClave, [$clave, $articulo_id]);
        }
    } else {
        // Si la clave está vacía, eliminar el registro si existe
        if (is_array($checkClave) && !empty($checkClave)) {
            $sqlDel = "DELETE FROM CLAVES_ARTICULOS WHERE ARTICULO_ID = ? AND ROL_CLAVE_ART_ID = 17";
            $db->execute($sqlDel, [$articulo_id]);
        }
    }
    
    $db->close();
    
    echo json_encode([
        'ok' => true,
        'msg' => 'Datos actualizados correctamente'
    ]);
    
} catch (Exception $e) {
    $msg = $e->getMessage();
    if (stripos($msg, 'violation of PRIMARY or UNIQUE KEY constraint') !== false) {
        $msg = 'La referencia (clave) ya está siendo utilizada por otro artículo.';
    }
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al actualizar: ' . $msg
    ]);
}
?>
