<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

$ocId = $_POST['ocid'] ?? null;
$artId = $_POST['artid'] ?? null;
$cantidad = $_POST['cantidad'] ?? null;
$precio = $_POST['precio'] ?? null;
$descPct = $_POST['descuento'] ?? 0;
$ivaPct = $_POST['iva'] ?? 16;

if (!$ocId || !$artId || !$cantidad || $precio === null) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
    exit;
}

$db = new FirebirdConnection(false); // Usar transacción
try {
    // 1. Validar que el artículo no esté ya en la OC
    $existe = $db->query("SELECT OCDET_ID FROM AMPAR_OCDET WHERE OCDET_OCID = ? AND OCDET_ARTICULOID = ?", [$ocId, $artId]);
    if ($existe) {
        echo json_encode(['ok' => false, 'msg' => 'Este artículo ya está en la Orden de Compra. Si deseas modificarlo, edita la cantidad en su fila correspondiente.']);
        exit;
    }

    $cantidad = (float)$cantidad;
    $precio = (float)$precio;
    $descPct = (float)$descPct;
    $ivaPct = (float)$ivaPct;

    $itemSub = $cantidad * $precio;
    $itemDesc = $itemSub * ($descPct / 100);
    $itemSubNeto = $itemSub - $itemDesc;
    $itemIva = $itemSubNeto * ($ivaPct / 100);
    $itemTotal = $itemSubNeto + $itemIva;

    // 2. Insertar el artículo
    $sqlInsert = "INSERT INTO AMPAR_OCDET (OCDET_OCID, OCDET_ARTICULOID, OCDET_CANTIDAD, OCDET_PRECIO, OCDET_DESCUENTO_PCT, OCDET_IVA_PCT, OCDET_SUBTOTAL, OCDET_TOTAL) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $db->execute($sqlInsert, [$ocId, $artId, $cantidad, $precio, $descPct, $ivaPct, $itemSub, $itemTotal]);

    // 3. Recalcular la cabecera
    $detalles = $db->query("SELECT OCDET_CANTIDAD, OCDET_PRECIO, OCDET_DESCUENTO_PCT, OCDET_IVA_PCT FROM AMPAR_OCDET WHERE OCDET_OCID = ?", [$ocId]);
    
    $subtotal = 0;
    $descuento = 0;
    $impuestos = 0;
    
    if ($detalles) {
        foreach ($detalles as $d) {
            $cant = (float)$d['OCDET_CANTIDAD'];
            $prec = (float)$d['OCDET_PRECIO'];
            $dPct = (float)($d['OCDET_DESCUENTO_PCT'] ?? 0);
            $iPct = (float)($d['OCDET_IVA_PCT'] ?? 16);
            
            $iSub = $cant * $prec;
            $iDesc = $iSub * ($dPct / 100);
            $iSubNeto = $iSub - $iDesc;
            $iIva = $iSubNeto * ($iPct / 100);
            
            $subtotal += $iSub;
            $descuento += $iDesc;
            $impuestos += $iIva;
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
    echo json_encode(['ok' => false, 'msg' => 'Error al agregar el artículo en la base de datos.']);
}
$db->close();
