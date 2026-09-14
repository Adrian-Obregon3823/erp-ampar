<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

$ocId = $_POST['ocid'] ?? null;
$detId = $_POST['detid'] ?? null;

if (!$ocId || !$detId) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
    exit;
}

$db = new FirebirdConnection(false); // Usar transacción
try {
    // 1. Validar que la OC tenga más de 1 artículo
    $conteo = $db->query("SELECT COUNT(*) AS TOTAL FROM AMPAR_OCDET WHERE OCDET_OCID = ?", [$ocId]);
    if ($conteo && (int)$conteo[0]['TOTAL'] <= 1) {
        echo json_encode(['ok' => false, 'msg' => 'No puedes eliminar el único artículo de la Orden de Compra. Debe haber al menos un artículo.']);
        exit;
    }

    // 2. Eliminar el artículo
    $sqlDelete = "DELETE FROM AMPAR_OCDET WHERE OCDET_ID = ? AND OCDET_OCID = ?";
    $db->execute($sqlDelete, [$detId, $ocId]);

    // 3. Recalcular la cabecera
    $detalles = $db->query("SELECT OCDET_CANTIDAD, OCDET_PRECIO, OCDET_DESCUENTO_PCT, OCDET_IVA_PCT FROM AMPAR_OCDET WHERE OCDET_OCID = ?", [$ocId]);
    
    $subtotal = 0;
    $descuento = 0;
    $impuestos = 0;
    
    if ($detalles) {
        foreach ($detalles as $d) {
            $cant = (float)$d['OCDET_CANTIDAD'];
            $prec = (float)$d['OCDET_PRECIO'];
            $descPct = (float)($d['OCDET_DESCUENTO_PCT'] ?? 0);
            $ivaPct = (float)($d['OCDET_IVA_PCT'] ?? 16);
            
            $itemSub = $cant * $prec;
            $itemDesc = $itemSub * ($descPct / 100);
            $itemSubNeto = $itemSub - $itemDesc;
            $itemIva = $itemSubNeto * ($ivaPct / 100);
            
            $subtotal += $itemSub;
            $descuento += $itemDesc;
            $impuestos += $itemIva;
        }
    }
    
    $total = ($subtotal - $descuento) + $impuestos;
    
    // Actualizar cabecera
    $sqlCab = "UPDATE AMPAR_OC SET OC_SUBTOTAL = ?, OC_DESCUENTO = ?, OC_IMPUESTOS = ?, OC_TOTAL = ? WHERE OC_ID = ?";
    $db->execute($sqlCab, [$subtotal, $descuento, $impuestos, $total, $ocId]);
    
    $db->commit();
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    try { $db->rollback(); } catch (Exception $ex) {}
    echo json_encode(['ok' => false, 'msg' => 'Error al eliminar en la base de datos.']);
}
$db->close();
