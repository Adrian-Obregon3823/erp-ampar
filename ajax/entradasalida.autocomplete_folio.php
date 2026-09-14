<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

$resultados = [];
if (strlen($term) >= 2) {
    $db = new FirebirdConnection();
    $sql = "SELECT FIRST 20 STOCK_FOLIO FROM AMPAR_HIS_STOCK WHERE UPPER(STOCK_FOLIO) LIKE UPPER(?) ORDER BY STOCK_FOLIO DESC";
    $res = $db->query($sql, ['%' . $term . '%']);
    
    if ($res !== 0 && is_array($res)) {
        foreach ($res as $row) {
            $resultados[] = $row['STOCK_FOLIO'];
        }
    }
    $db->close();
}

header('Content-Type: application/json');
echo json_encode($resultados);
?>
