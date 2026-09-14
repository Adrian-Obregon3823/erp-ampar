<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

$ids = isset($_POST['ids']) ? $_POST['ids'] : [];

if (!is_array($ids) || empty($ids)) {
    echo json_encode(['ok' => false, 'msg' => 'No se seleccionaron artículos válidos.']);
    exit;
}

// Convert all IDs to integers
$clean_ids = [];
foreach ($ids as $id) {
    $int_id = (int)$id;
    if ($int_id > 0) {
        $clean_ids[] = $int_id;
    }
}

if (empty($clean_ids)) {
    echo json_encode(['ok' => false, 'msg' => 'IDs inválidos.']);
    exit;
}

try {
    $db = new FirebirdConnection();
    
    // In Firebird, doing IN (...) is fine for a reasonable number of IDs.
    // If it's a huge list, it's better to chunk or do multiple updates. For a UI select, it's small enough.
    // But since Firebird limits IN clause size, we'll execute per ID to be safe, or build the query dynamically.
    // Let's just iterate and update since it's an admin operation.
    
    $successCount = 0;
    
    // Start transaction if supported
    $didBegin = false;
    if (method_exists($db, 'beginTransaction')) {
        try { $db->beginTransaction(); $didBegin = true; } catch (Throwable $e) {}
    }
    
    foreach ($clean_ids as $id) {
        $sql = "UPDATE ARTICULOS SET ESTATUS = 'B' WHERE ARTICULO_ID = ?";
        $db->execute($sql, [$id]);
        $successCount++;
    }
    
    if ($didBegin && method_exists($db, 'commit')) {
        $db->commit();
    }
    
    $db->close();
    
    echo json_encode([
        'ok' => true,
        'msg' => "$successCount artículos fueron desactivados."
    ]);
    
} catch (Exception $e) {
    if (isset($db) && method_exists($db, 'rollback')) {
        try { $db->rollback(); } catch (Throwable $t) {}
    }
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'msg' => 'Error al actualizar: ' . $e->getMessage()
    ]);
}
?>
