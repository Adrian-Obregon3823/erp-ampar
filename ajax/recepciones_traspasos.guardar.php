<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");
include_once("../class/notificaciones.php");

$usuarioid = $_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0;

$traspasoid = isset($_POST['traspasoid']) ? (int)$_POST['traspasoid'] : 0;
$observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
$detalles = isset($_POST['detalles']) ? $_POST['detalles'] : [];

if ($traspasoid == 0 || empty($detalles)) {
    echo "Faltan datos obligatorios.";
    exit;
}

$db = new FirebirdConnection();

// Obtener el almacen destino y creador del traspaso
$sqlTraspaso = "SELECT TRASPASO_AALMACENID, TRASPASO_USUARIOID, TRASPASO_FOLIO FROM AMPAR_HIS_TRASPASO WHERE TRASPASO_ID = ?";
$resTraspaso = $db->query($sqlTraspaso, [$traspasoid]);
$almacenid = $resTraspaso[0]['TRASPASO_AALMACENID'] ?? 0;
$creadorTraspasoId = $resTraspaso[0]['TRASPASO_USUARIOID'] ?? 0;
$folioTraspaso = $resTraspaso[0]['TRASPASO_FOLIO'] ?? '';

try {
    $db->beginTransaction();

    // 1. Insertar Cabecera de Recepción
    $sqlRecep = "
        INSERT INTO AMPAR_RECEPCION (RECEPCION_OCID, RECEPCION_TRASPASOID, RECEPCION_USUARIOID, RECEPCION_FECHA, RECEPCION_OBSERVACIONES, RECEPCION_STATUS)
        VALUES (0, ?, ?, CURRENT_TIMESTAMP, ?, 1)
    ";
    $idRecepcion = $db->executeconreturning($sqlRecep, 'RECEPCION_ID', [$traspasoid, $usuarioid, $observaciones]);

    if (!$idRecepcion) {
        throw new Exception("No se pudo generar el ID de la Recepción.");
    }

    // 2. Procesar Archivo Evidencia
    $folioCarpeta = 'REC-' . $idRecepcion;
    $dirUploads = __DIR__ . '/../uploads/recepciones/' . $folioCarpeta;
    if (!is_dir($dirUploads)) {
        @mkdir($dirUploads, 0777, true);
    }

    $ruta_evidencia = null;
    if (!empty($_FILES['archivo_evidencia_general']['name']) && $_FILES['archivo_evidencia_general']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['archivo_evidencia_general']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $nombreArchivo = 'EVI_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
            $rutaDestino = $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo;
            if (move_uploaded_file($_FILES['archivo_evidencia_general']['tmp_name'], $rutaDestino)) {
                $ruta_evidencia = 'uploads/recepciones/' . $folioCarpeta . '/' . $nombreArchivo;
            }
        }
    }

    if ($ruta_evidencia) {
        $db->execute("UPDATE AMPAR_RECEPCION SET RECEPCION_EVIDENCIA = ? WHERE RECEPCION_ID = ?", [$ruta_evidencia, $idRecepcion]);
    }

    $rutas_empaque = [];
    if (!empty($_FILES['archivo_empaque']['name'][0])) {
        foreach ($_FILES['archivo_empaque']['name'] as $key => $name) {
            if ($_FILES['archivo_empaque']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreArchivo = 'EMP_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                    $rutaDestino = $dirUploads . DIRECTORY_SEPARATOR . $nombreArchivo;
                    if (move_uploaded_file($_FILES['archivo_empaque']['tmp_name'][$key], $rutaDestino)) {
                        $rutas_empaque[] = 'uploads/recepciones/' . $folioCarpeta . '/' . $nombreArchivo;
                    }
                }
            }
        }
    }

    if (!empty($rutas_empaque)) {
        $rutas_json = json_encode($rutas_empaque);
        $db->execute("UPDATE AMPAR_RECEPCION SET RECEPCION_DOCUMENTO2 = ? WHERE RECEPCION_ID = ?", [$rutas_json, $idRecepcion]);
    }

    // 3. Procesar Detalles y Actualizar Stock (Confirmar Entrada)
    // Primero, obtener info del traspaso
    $sqlT = "SELECT * FROM AMPAR_HIS_TRASPASODET WHERE TRASPASODET_TRASPASOID = ?";
    $resT = $db->query($sqlT, [$traspasoid]);
    $traspasoDetalles = [];
    foreach ($resT as $r) {
        $traspasoDetalles[(int)$r['TRASPASODET_ID']] = $r;
    }

    $idsSeleccionados = [];
    foreach ($detalles as $det) {
        if (isset($det['recibida']) && $det['recibida'] == 1) {
            $tdId = (int)$det['traspasodetid'];
            $idsSeleccionados[] = $tdId;

            // Actualizar el stock al nuevo almacén (Entrada oficial al destino)
            if (isset($traspasoDetalles[$tdId])) {
                $stockId = $traspasoDetalles[$tdId]['TRASPASODET_STOCKID'];
                $almacenDestino = $traspasoDetalles[$tdId]['TRASPASODET_AALMACENID'];

                // Obtener Articulo ID
                $sqlArt = "SELECT STOCK_ARTICULOID FROM AMPAR_HIS_STOCK WHERE STOCK_ID = ?";
                $resArt = $db->query($sqlArt, [$stockId]);
                $artId = $resArt[0]['STOCK_ARTICULOID'] ?? null;

                // Insertar en AMPAR_RECEPCIONDET
                $db->execute("
                    INSERT INTO AMPAR_RECEPCIONDET (RECDET_RECEPCIONID, RECDET_TRASPASODETID, RECDET_ARTICULOID, RECDET_COSTO_UNITARIO, RECDET_CANTIDAD_RECIBIDA) 
                    VALUES (?, ?, ?, 0, 1)
                ", [$idRecepcion, $tdId, $artId]);

                $db->execute("
                    UPDATE AMPAR_HIS_STOCK
                    SET STOCK_ALMACENIDACTUAL = ?, STOCK_STOCKSTATUSID = 1
                    WHERE STOCK_ID = ?
                ", [$almacenDestino, $stockId]);

                $db->execute("
                    UPDATE AMPAR_HIS_TRASPASODET
                    SET TRASPASODET_ACTIVO = 1
                    WHERE TRASPASODET_ID = ?
                ", [$tdId]);
            }
        }
    }

    // 4. Actualizar status del Traspaso si ya se recibió todo
    // Contar total de detalles del traspaso
    $sqlTotalT = "SELECT COUNT(*) AS TOTAL FROM AMPAR_HIS_TRASPASODET WHERE TRASPASODET_TRASPASOID = ?";
    $resTotalT = $db->query($sqlTotalT, [$traspasoid]);
    $totalPedido = $resTotalT[0]['TOTAL'] ?? 0;

    // Contar total ya recibido en todas las recepciones de este traspaso
    $sqlTotalRec = "
        SELECT SUM(RD.RECDET_CANTIDAD_RECIBIDA) AS TOTAL_RECIBIDO 
        FROM AMPAR_RECEPCIONDET RD
        JOIN AMPAR_RECEPCION R ON R.RECEPCION_ID = RD.RECDET_RECEPCIONID
        WHERE R.RECEPCION_TRASPASOID = ?
    ";
    $resTotalRec = $db->query($sqlTotalRec, [$traspasoid]);
    $totalRecibido = $resTotalRec[0]['TOTAL_RECIBIDO'] ?? 0;

    if ($totalRecibido >= $totalPedido) {
        $db->execute("UPDATE AMPAR_HIS_TRASPASO SET TRASPASO_STATUS = 3 WHERE TRASPASO_ID = ?", [$traspasoid]);
    } else {
        $db->execute("UPDATE AMPAR_HIS_TRASPASO SET TRASPASO_STATUS = 27 WHERE TRASPASO_ID = ?", [$traspasoid]); // 27 = Parcial
    }

    // Prepara Query Bitácora
    $ipuser = $_SERVER['REMOTE_ADDR'] ?? null;
    $comentBit = 'Se recepcionó el traspaso FOLIO: ' . $traspasoid . ' en recepción REC-' . $idRecepcion;
    $db->execute("
        INSERT INTO AMPAR_BITACORA
        (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP,
        BITACORA_ALMACENID)
        VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?)
    ", [
        $comentBit,
        $usuarioid,
        $_SESSION['ampar']['usuario']['USUARIO_CORREO'] ?? '',
        $ipuser,
        $almacenid
    ]);

    if ($creadorTraspasoId > 0) {
        $nombreReceptor = $_SESSION['ampar']['usuario']['USUARIO_NOMBRE'] ?? 'Alguien';
        notificaciones::crear(
            $creadorTraspasoId,
            $traspasoid,
            'RECEPCION_TRASPASO',
            'Traspaso Recibido',
            "Tu Traspaso Folio: $folioTraspaso ha sido recibido en el almacén de destino por $nombreReceptor."
        );
    }

    $db->commit();
    echo "OK";
} catch (Exception $e) {
    try {
        $db->rollback();
    } catch (Exception $ex) {
    }
    echo "Error al registrar la recepción de traspaso: " . $e->getMessage();
}
$db->close();
