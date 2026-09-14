<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");
include_once("../class/notificaciones.php");

$usuarioid = $_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0;
$traspasoid = isset($_POST['traspasoid']) ? (int)$_POST['traspasoid'] : 0;
$motivo = isset($_POST['motivo']) ? trim($_POST['motivo']) : '';
$detalle = isset($_POST['detalle']) ? trim($_POST['detalle']) : '';

if ($traspasoid <= 0 || empty($motivo)) {
    echo "Faltan datos obligatorios.";
    exit;
}

$db = new FirebirdConnection();

try {
    $db->beginTransaction();

    // Obtener creador del traspaso (Sender)
    $sqlT = "SELECT TRASPASO_USUARIOID, TRASPASO_FOLIO FROM AMPAR_HIS_TRASPASO WHERE TRASPASO_ID = ?";
    $resT = $db->query($sqlT, [$traspasoid]);
    if (empty($resT)) {
        throw new Exception("Traspaso no encontrado.");
    }
    $senderId = $resT[0]['TRASPASO_USUARIOID'];
    $folioTraspaso = $resT[0]['TRASPASO_FOLIO'];

    // Insertar disputa
    $sqlIns = "
        INSERT INTO AMPAR_DISPUTAS (DISPUTA_TRASPASOID, DISPUTA_USUARIO_CREADOR, DISPUTA_USUARIO_DESTINO, DISPUTA_MOTIVO, DISPUTA_DETALLE, DISPUTA_STATUS, DISPUTA_FECHA)
        VALUES (?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
    ";
    $disputaId = $db->executeconreturning($sqlIns, 'DISPUTA_ID', [
        $traspasoid,
        $usuarioid,
        $senderId,
        $motivo,
        $detalle
    ]);

    if (!$disputaId) {
        throw new Exception("Error al crear el registro de disputa.");
    }

    // Bloquear el traspaso (cambiar estatus a 28 = en Disputa)
    $db->execute("UPDATE AMPAR_HIS_TRASPASO SET TRASPASO_STATUS = 28 WHERE TRASPASO_ID = ?", [$traspasoid]);

    // Procesar Evidencias
    $folioCarpeta = 'DISP-' . $disputaId;
    $dirUploads = __DIR__ . '/../uploads/disputas/' . $folioCarpeta;
    
    for ($i = 0; $i < 5; $i++) {
        $campo = 'evidencia_' . $i;
        if (!empty($_FILES[$campo]['name']) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
            if (!is_dir($dirUploads)) {
                @mkdir($dirUploads, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'])) {
                $nombreArchivo = 'EVI_' . date('Ymd_His') . '_' . $i . '_' . uniqid() . '.' . $ext;
                $rutaDestino = $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo;
                if (move_uploaded_file($_FILES[$campo]['tmp_name'], $rutaDestino)) {
                    $rutaDB = 'uploads/disputas/' . $folioCarpeta . '/' . $nombreArchivo;
                    
                    $sqlEvid = "INSERT INTO AMPAR_DISPUTAS_EVIDENCIAS (EVIDENCIA_DISPUTAID, EVIDENCIA_RUTA, EVIDENCIA_FECHA) VALUES (?, ?, CURRENT_TIMESTAMP)";
                    $db->execute($sqlEvid, [$disputaId, $rutaDB]);
                }
            }
        }
    }

    // Notificar al sender
    $nombreReceptor = $_SESSION['ampar']['usuario']['USUARIO_NOMBRE'] ?? 'Alguien';
    if ($senderId > 0) {
        notificaciones::crear(
            $senderId,
            $traspasoid,
            'RECEPCION_TRASPASO', // Aprovechando el tipo de notificación genérica que agregamos
            'Disputa en Traspaso',
            "El traspaso Folio: $folioTraspaso ha entrado en DISPUTA por '$motivo' y ha sido bloqueado."
        );
    }
    
    // Notificar a todos los admins
    $sqlAdmins = "SELECT U.USUARIO_ID FROM AMPAR_CAT_USUARIOS U 
                  JOIN AMPAR_CAT_USUARIOSPERFILES UP ON UP.USUARIOP_USUARIOID = U.USUARIO_ID 
                  WHERE UP.USUARIOP_PERFILID = 1 AND U.USUARIO_ACTIVO = 1";
    $resAdmins = $db->query($sqlAdmins);
    if (!empty($resAdmins)) {
        foreach ($resAdmins as $adm) {
            notificaciones::crear(
                $adm['USUARIO_ID'],
                $disputaId,
                'RECEPCION_TRASPASO',
                'Nueva Disputa (Revisión Admin)',
                "Se ha iniciado una disputa por '$motivo' en el Traspaso $folioTraspaso."
            );
        }
    }

    $db->commit();
    echo "OK";

} catch (Exception $e) {
    try { $db->rollback(); } catch(Exception $ex) {}
    echo "Error: " . $e->getMessage();
}
$db->close();
?>
