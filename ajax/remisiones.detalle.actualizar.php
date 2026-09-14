<?php
include_once ("../includes/sesion.php"); 
include_once("../includes/includes.php");
$id       = (int)($_POST['id'] ?? 0);
$subtotal = (float)($_POST['subtotal'] ?? 0);
$iva      = (float)($_POST['iva'] ?? 0);
$total    = (float)($_POST['total'] ?? 0);

if ($id > 0) {
    $db = new FirebirdConnection(true);
    $db->execute("UPDATE AMPAR_HIS_REMISIONESARTICULOS SET REMISIONARTICULO_SUBTOTAL = ?, REMISIONARTICULO_IVA = ?, REMISIONARTICULO_TOTAL = ? WHERE REMISIONARTICULO_ID = ?", 
        [$subtotal, $iva, $total, $id]);
    
    // Opcional: Recalcular totales en AMPAR_HIS_REMISIONES cabecera
    // $db->execute("UPDATE AMPAR_HIS_REMISIONES SET ... WHERE ...");
    
    echo "OK";
} else {
    echo "Error";
}
