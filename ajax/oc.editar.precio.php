<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

$ocId = $_POST['ocid'] ?? null;
$detId = $_POST['detid'] ?? null;
$artId = $_POST['artid'] ?? null;
$provId = $_POST['provid'] ?? null;
$nuevoPrecio = $_POST['precio'] ?? null;

if (!$ocId || !$detId || !$artId || !$nuevoPrecio) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
    exit;
}

$oc = new oc();
$precioOriginal = $oc->getCostForArticleByProvider($artId, $provId);

if ($precioOriginal > 0) {
    $diferenciaPct = (($nuevoPrecio - $precioOriginal) / $precioOriginal) * 100;
    
    // Si el nuevo precio es más de un 5% mayor al original
    if ($diferenciaPct > 5) {
        $usuarioActual = $_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0;
        
        // Buscar a los administradores para notificarles
        $db = new FirebirdConnection();
        $admins = $db->query("
            SELECT UP.USUARIOP_USUARIOID 
            FROM AMPAR_CAT_USUARIOSPERFILES UP
            JOIN AMPAR_CAT_PERFILES P ON P.PERFIL_ID = UP.USUARIOP_PERFILID
            WHERE UPPER(P.PERFIL_NOMBRE) LIKE '%ADMIN%'
        ");
        
        if ($admins) {
            $folioOC = $db->query("SELECT OC_FOLIO FROM AMPAR_OC WHERE OC_ID = ?", [$ocId]);
            $folioText = $folioOC ? $folioOC[0]['OC_FOLIO'] : $ocId;
            
            require_once '../class/notificaciones.php';
            require_once '../class/whatsapp.php';
            
            // Mensaje de WhatsApp
            $msgWA = "🚨 *Revisión de Precio en OC*\nSe intentó modificar un precio con un aumento mayor al 5% en la Orden de Compra *$folioText*. Favor de revisar y ajustar el precio maestro del artículo si es necesario.";
            $telAdmins = whatsapp::getTelefonosAdmins();
            if ($telAdmins) {
                whatsapp::enviarMultiple($telAdmins, $msgWA);
            }

            foreach ($admins as $admin) {
                notificaciones::crear(
                    $admin['USUARIOP_USUARIOID'], 
                    $artId, 
                    'REVISION_PRECIO', 
                    'Revisión de Precio en OC', 
                    "Se intentó modificar un precio con un aumento mayor al 5% en la Orden de Compra {$folioText}. Favor de revisar y ajustar el precio maestro del artículo si es necesario."
                );
            }
        }
        $db->close();
        
        echo json_encode([
            'ok' => false, 
            'bloqueado' => true, 
            'msg' => 'El nuevo precio excede el límite permitido del 5% respecto al precio maestro ($' . number_format($precioOriginal, 2) . ').<br><br><b>Se ha enviado una notificación a Administración</b> para que evalúen modificar el precio maestro de este artículo o sugerir un nuevo proveedor.'
        ]);
        exit;
    }
}

// Si pasa la validación (o no hay precio original definido), procedemos a actualizar
$db = new FirebirdConnection(false); // Usar transacción
try {
    // 1. Actualizar el detalle (Precio, Subtotal y Total)
    $infoDet = $db->query("SELECT OCDET_CANTIDAD, OCDET_DESCUENTO_PCT, OCDET_IVA_PCT FROM AMPAR_OCDET WHERE OCDET_ID = ?", [$detId]);
    if ($infoDet) {
        $cant = (float)$infoDet[0]['OCDET_CANTIDAD'];
        $descPct = (float)($infoDet[0]['OCDET_DESCUENTO_PCT'] ?? 0);
        $ivaPct = (float)($infoDet[0]['OCDET_IVA_PCT'] ?? 16);
        $itemSub = $cant * $nuevoPrecio;
        $itemDesc = $itemSub * ($descPct / 100);
        $itemSubNeto = $itemSub - $itemDesc;
        $itemIva = $itemSubNeto * ($ivaPct / 100);
        $itemTotal = $itemSubNeto + $itemIva;
        
        $sqlDet = "UPDATE AMPAR_OCDET SET OCDET_PRECIO = ?, OCDET_SUBTOTAL = ?, OCDET_TOTAL = ? WHERE OCDET_ID = ?";
        $db->execute($sqlDet, [$nuevoPrecio, $itemSub, $itemTotal, $detId]);
    } else {
        $sqlDet = "UPDATE AMPAR_OCDET SET OCDET_PRECIO = ? WHERE OCDET_ID = ?";
        $db->execute($sqlDet, [$nuevoPrecio, $detId]);
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
