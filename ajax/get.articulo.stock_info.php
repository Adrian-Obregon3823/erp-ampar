<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

try {
    $articuloId = isset($_GET['articulo_id']) ? (int)$_GET['articulo_id'] : 0;
    $almacenId = isset($_GET['almacen_id']) ? (int)$_GET['almacen_id'] : 0;
    
    if ($articuloId <= 0) {
        throw new Exception("ID de artículo inválido.");
    }
    
    $db = new FirebirdConnection();
    
    // 1. Stock Físico Actual in the warehouse
    $whereActual = " WHERE S.STOCK_ARTICULOID = ? AND S.STOCK_STOCKSTATUSID = 1 ";
    $paramsActual = [$articuloId];
    if ($almacenId > 0) {
        $whereActual .= " AND S.STOCK_ALMACENIDACTUAL = ? ";
        $paramsActual[] = $almacenId;
    }
    $resActual = $db->query("
        SELECT COUNT(*) AS N 
        FROM AMPAR_HIS_STOCK S
        $whereActual
    ", $paramsActual);
    $stockActual = (int)($resActual[0]['N'] ?? 0);
    
    // 2. Stock Mínimo in the warehouse
    $whereMin = " WHERE N.ARTICULO_ID = ? ";
    $paramsMin = [$articuloId];
    if ($almacenId > 0) {
        $whereMin .= " AND N.ALMACEN_ID = ? ";
        $paramsMin[] = $almacenId;
    }
    $resMin = $db->query("
        SELECT COALESCE(SUM(N.INVENTARIO_MINIMO), 0) AS N 
        FROM NIVELES_ARTICULOS N
        $whereMin
    ", $paramsMin);
    $stockMin = (float)($resMin[0]['N'] ?? 0);
    
    // 3. Stock en Tránsito for this warehouse
    $whereTrn = " WHERE OD.OCDET_ARTICULOID = ? AND O.OC_STATUS IN (2, 4, 7, 9) ";
    $paramsTrn = [$articuloId];
    if ($almacenId > 0) {
        $whereTrn .= " AND O.OC_ALMACENID = ? ";
        $paramsTrn[] = $almacenId;
    }
    $resTrn = $db->query("
        SELECT COALESCE(SUM(OD.OCDET_CANTIDAD), 0) AS N 
        FROM AMPAR_OCDET OD
        JOIN AMPAR_OC O ON OD.OCDET_OCID = O.OC_ID
        $whereTrn
    ", $paramsTrn);
    $stockTransit = (float)($resTrn[0]['N'] ?? 0);
    
    $db->close();
    
    echo json_encode([
        'status' => 'success',
        'stock_actual' => $stockActual,
        'stock_minimo' => $stockMin,
        'stock_transito' => $stockTransit
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
