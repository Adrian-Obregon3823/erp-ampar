<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

try {
    $articuloId = isset($_GET['articulo_id']) ? (int)$_GET['articulo_id'] : 0;
    
    if ($articuloId <= 0) {
        throw new Exception("ID de artículo inválido.");
    }
    
    $db = new FirebirdConnection();
    $sql = "
        SELECT FIRST 1 
            P.ARTCOMPRA_PROVEEDORID AS PROVEEDOR_ID,
            PR.NOMBRE AS PROVEEDOR_NOMBRE,
            P.ARTCOMPRA_SUBTOTAL AS COSTO
        FROM AMPAR_CAT_ARTCOMPRA P
        JOIN PROVEEDORES PR ON PR.PROVEEDOR_ID = P.ARTCOMPRA_PROVEEDORID
        WHERE P.ARTCOMPRA_ARTICULOID = ?
        ORDER BY P.ARTCOMPRA_SUBTOTAL ASC
    ";
    
    $res = $db->query($sql, [$articuloId]);
    
    if ($res && count($res) > 0) {
        echo json_encode([
            'status' => 'success',
            'proveedor_id' => $res[0]['PROVEEDOR_ID'],
            'proveedor_nombre' => $res[0]['PROVEEDOR_NOMBRE'],
            'costo' => (float)$res[0]['COSTO']
        ]);
    } else {
        echo json_encode([
            'status' => 'not_found'
        ]);
    }
    $db->close();
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
