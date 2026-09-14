<?php
/**
 * Ajax para obtener divisiones filtradas por familia
 */
require_once("../includes/sesion.php");
require_once("../includes/includes.php");

header('Content-Type: application/json; charset=utf-8');

$familiaid = isset($_GET['familiaid']) ? intval($_GET['familiaid']) : 0;

try {
    $db = new FirebirdConnection();
    
    $sql = "SELECT DIVISION_ID AS ID, DIVISION_NOMBRE AS NOMBRE 
            FROM AMPAR_CAT_DIVISION 
            WHERE DELETED_AT IS NULL";
            
    if ($familiaid > 0) {
        $sql .= " AND DIVISION_FAMILIAID = " . $familiaid;
    }
    
    $sql .= " ORDER BY DIVISION_NOMBRE";
    
    file_put_contents('debug.txt', date('Y-m-d H:i:s') . ' - SQL: ' . $sql . ' - GET: ' . json_encode($_GET) . PHP_EOL, FILE_APPEND);
    
    $res = $db->query($sql) ?: [];
    
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
