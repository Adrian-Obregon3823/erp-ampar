<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once ("../includes/sesion.php"); 
include_once("../includes/includes.php");

try {
    $remisionid = (int)($_POST['remisionid'] ?? 0);
    $ampar      = $_POST['ampar'] ?? [];
    $prov       = $_POST['prov'] ?? [];

    if ($remisionid > 0) {
        $db = new FirebirdConnection(true);

        if (is_array($ampar)) {
            foreach ($ampar as $item) {
                $id = (int)($item['id'] ?? 0);
                $subtotal = (float)($item['subtotal'] ?? 0);
                $iva = (float)($item['iva'] ?? 0);
                $total = (float)($item['total'] ?? 0);
                if ($id > 0) {
                    $db->execute("UPDATE AMPAR_HIS_REMISIONESARTICULOS SET REMISIONARTICULO_SUBTOTAL = ?, REMISIONARTICULO_IVA = ?, REMISIONARTICULO_TOTAL = ? WHERE REMISIONARTICULO_ID = ?", 
                        [$subtotal, $iva, $total, $id]);
                }
            }
        }

        if (is_array($prov)) {
            foreach ($prov as $item) {
                $id = (int)($item['id'] ?? 0);
                $subtotal = (float)($item['subtotal'] ?? 0);
                $iva = (float)($item['iva'] ?? 0);
                $total = (float)($item['total'] ?? 0);
                if ($id > 0) {
                    $db->execute("UPDATE AMPAR_HIS_REMISIONESPARTICULOS SET REMISIONPROVARTICULO_SUBTOTAL = ?, REMISIONPROVARTICULO_IVA = ?, REMISIONPROVARTICULO_TOTAL = ? WHERE REMISIONPROVARTICULO_ID = ?", 
                        [$subtotal, $iva, $total, $id]);
                }
            }
        }

        ob_end_clean();
        echo "OK";
    } else {
        ob_end_clean();
        echo "Error: Falta ID de remision (" . print_r($_POST, true) . ")";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
