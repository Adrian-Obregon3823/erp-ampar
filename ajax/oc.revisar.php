<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

$ocId = $_POST['ocid'] ?? null;
$accion = $_POST['accion'] ?? null;
$motivo = $_POST['motivo'] ?? null;

if (!$ocId || !$accion) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
    exit;
}

$db = new FirebirdConnection();
$nuevoStatus = null;
$motivoText  = null;

if ($accion === 'autorizar') {
    $nuevoStatus = 19; // Confirmada
} elseif ($accion === 'rechazar') {
    if ($motivo === 'precios') {
        $nuevoStatus = 6;
        $motivoText  = "Precios incorrectos";
    } elseif ($motivo === 'abastecimiento') {
        $nuevoStatus = 6;
        $motivoText  = "Falta de abastecimiento del proveedor";
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Motivo de rechazo inválido.']);
        exit;
    }
} else {
    echo json_encode(['ok' => false, 'msg' => 'Acción inválida.']);
    exit;
}

// Obtener datos de la OC antes de actualizar
$ocInfo = $db->query(
    "SELECT OC_FOLIO, OC_USUARIOID, OC_ALMACENID FROM AMPAR_OC WHERE OC_ID = ?",
    [$ocId]
);

if ($accion === 'rechazar') {
    $sql = "UPDATE AMPAR_OC SET OC_STATUS = ?, OC_MOTIVORECHAZO = ? WHERE OC_ID = ?";
    $res = $db->execute($sql, [$nuevoStatus, $motivoText, $ocId]);
} else {
    $sql = "UPDATE AMPAR_OC SET OC_STATUS = ? WHERE OC_ID = ?";
    $res = $db->execute($sql, [$nuevoStatus, $ocId]);
}
$db->close();

if ($res !== false) {

    // ===== NOTIFICACIONES =====
    if ($ocInfo && !empty($ocInfo[0])) {
        $folio      = $ocInfo[0]['OC_FOLIO']    ?? $ocId;
        $creadorId  = (int)($ocInfo[0]['OC_USUARIOID']  ?? 0);
        $almacenId  = (int)($ocInfo[0]['OC_ALMACENID']  ?? 0);

        require_once '../class/notificaciones.php';
        require_once '../class/whatsapp.php';

        $dbN = new FirebirdConnection(true);

        if ($accion === 'autorizar') {

            // --- 1. Notificar al CREADOR de la OC ---
            if ($creadorId > 0) {
                $titulo  = "✅ OC Confirmada";
                $mensaje = "Tu Orden de Compra *{$folio}* fue CONFIRMADA. El almacén procederá a recibirla.";

                notificaciones::crear($creadorId, $ocId, 'OC_CONFIRMADA', $titulo, $mensaje);

                $telCreador = whatsapp::getTelefonoUsuario($creadorId);
                if ($telCreador) {
                    whatsapp::enviar($telCreador, "✅ *OC Confirmada*\n{$mensaje}");
                }
            }

            // --- 2. Notificar a los ALMACENISTAS del almacén de la OC ---
            if ($almacenId > 0) {
                $almacenistas = $dbN->query(
                    "SELECT DISTINCT U.USUARIO_ID, U.USUARIO_NOMBRE, U.USUARIO_TELEFONO
                     FROM AMPAR_CAT_USUARIOS U
                     JOIN AMPAR_CAT_USUARIOSPERFILES UP ON UP.USUARIOP_USUARIOID = U.USUARIO_ID
                     JOIN AMPAR_CAT_PERFILES P         ON P.PERFIL_ID = UP.USUARIOP_PERFILID
                     JOIN AMPAR_CAT_USUARIOSALMACENES UA ON UA.USUARIOSALMACENES_USUARIOID = U.USUARIO_ID
                     WHERE UPPER(P.PERFIL_NOMBRE) CONTAINING 'ALMACEN'
                       AND UA.USUARIOSALMACENES_ALMACENID = ?
                       AND U.USUARIO_ACTIVO = 1",
                    [$almacenId]
                );

                if ($almacenistas) {
                    $tituloAlm  = "📦 Nueva OC Confirmada";
                    $mensajeAlm = "La Orden de Compra *{$folio}* ha sido confirmada y está lista para ser recibida en tu almacén.";

                    foreach ($almacenistas as $alm) {
                        notificaciones::crear(
                            (int)$alm['USUARIO_ID'],
                            $ocId,
                            'OC_CONFIRMADA_ALMACEN',
                            $tituloAlm,
                            $mensajeAlm
                        );

                        if (!empty(trim($alm['USUARIO_TELEFONO'] ?? ''))) {
                            whatsapp::enviar($alm['USUARIO_TELEFONO'], "📦 *{$tituloAlm}*\n{$mensajeAlm}");
                        }
                    }
                }
            }
        } elseif ($accion === 'rechazar') {

            // --- 3. Notificar al CREADOR sobre el rechazo ---
            if ($creadorId > 0) {
                $titulo  = "❌ OC Rechazada";
                $mensaje = "Tu Orden de Compra *{$folio}* fue RECHAZADA. Motivo: {$motivoText}.";

                notificaciones::crear($creadorId, $ocId, 'OC_RECHAZADA', $titulo, $mensaje);

                $telCreador = whatsapp::getTelefonoUsuario($creadorId);
                if ($telCreador) {
                    whatsapp::enviar($telCreador, "❌ *OC Rechazada*\n{$mensaje}");
                }
            }
        }

        $dbN->close();
    }
    // ===== FIN NOTIFICACIONES =====

    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Error al actualizar estatus en la base de datos.']);
}
