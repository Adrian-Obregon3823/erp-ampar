<?php
include_once ("../includes/sesion.php"); 
include_once("../includes/includes.php");
$id       = (int)($_POST['id'] ?? 0);
$subtotal = (float)($_POST['subtotal'] ?? 0);
$iva      = (float)($_POST['iva'] ?? 0);
$total    = (float)($_POST['total'] ?? 0);

if ($id > 0) {
    $db = new FirebirdConnection(true);
    // Update subtotal, iva, total in DB
    $db->execute("UPDATE AMPAR_HIS_REMISIONESPARTICULOS SET REMISIONPROVARTICULO_SUBTOTAL = ?, REMISIONPROVARTICULO_IVA = ?, REMISIONPROVARTICULO_TOTAL = ? WHERE REMISIONPROVARTICULO_ID = ?", 
        [$subtotal, $iva, $total, $id]);
    
    // Opcional: Recalcular totales en AMPAR_HIS_REMISIONES cabecera
    echo "OK";
} else {
    echo "Error";
}
