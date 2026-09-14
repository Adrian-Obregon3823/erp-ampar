<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

$ocId = $_POST['ocid'] ?? null;
$detId = $_POST['detid'] ?? null;
$nuevaCantidad = $_POST['cantidad'] ?? null;

if (!$ocId || !$detId || !$nuevaCantidad) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
    exit;
}

$db = new FirebirdConnection(false); // Usar transacción
try {
    // 1. Actualizar el detalle (Cantidad, Subtotal y Total)
    $infoDet = $db->query("SELECT OCDET_PRECIO, OCDET_DESCUENTO_PCT, OCDET_IVA_PCT FROM AMPAR_OCDET WHERE OCDET_ID = ?", [$detId]);
    if ($infoDet) {
        $precio = (float)$infoDet[0]['OCDET_PRECIO'];
        $descPct = (float)($infoDet[0]['OCDET_DESCUENTO_PCT'] ?? 0);
        $ivaPct = (float)($infoDet[0]['OCDET_IVA_PCT'] ?? 16);
        $itemSub = $nuevaCantidad * $precio;
        $itemDesc = $itemSub * ($descPct / 100);
        $itemSubNeto = $itemSub - $itemDesc;
        $itemIva = $itemSubNeto * ($ivaPct / 100);
        $itemTotal = $itemSubNeto + $itemIva;
        
        $sqlDet = "UPDATE AMPAR_OCDET SET OCDET_CANTIDAD = ?, OCDET_SUBTOTAL = ?, OCDET_TOTAL = ? WHERE OCDET_ID = ?";
        $db->execute($sqlDet, [$nuevaCantidad, $itemSub, $itemTotal, $detId]);
    } else {
        $sqlDet = "UPDATE AMPAR_OCDET SET OCDET_CANTIDAD = ? WHERE OCDET_ID = ?";
        $db->execute($sqlDet, [$nuevaCantidad, $detId]);
    }

    // 2. Recalcular y actualizar la cabecera
    // Primero obtenemos todos los detalles para recalcular
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
    echo json_encode(['ok' => false, 'msg' => 'Error al guardar en la base de datos.']);
}
$db->close();
