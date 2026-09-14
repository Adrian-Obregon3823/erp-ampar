<?php
/**
 * Guarda el Archivo de Remisión por cada remisión del evento (o por evento).
 * Pasa automáticamente a Estatus 'En Revisión' (8) si ya se subieron todas las remisiones y hay evidencias de recepción.
 */
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

try {
    $eventoid = intval($_POST['eventoid'] ?? 0);
    $remisionid = intval($_POST['remisionid'] ?? 0);

    if ($eventoid <= 0) {
        throw new Exception("ID de Evento inválido");
    }

    $db = new FirebirdConnection();
    $rows = $db->query("SELECT EVENTO_ID, EVENTO_FOLIO, EVENTO_STATUSGENERAL FROM AMPAR_HIS_EVENTOS WHERE EVENTO_ID = {$eventoid}");
    if (!$rows || count($rows) === 0) {
        throw new Exception("No existe el evento.");
    }

    // Solo se puede modificar si el evento NO está en revisión ni finalizado
    $statusActual = (int)($rows[0]['EVENTO_STATUSGENERAL'] ?? 0);
    if ($statusActual === 8 || $statusActual === 3) {
        throw new Exception("No se puede modificar el archivo en este estado del evento.");
    }

    // Asegurar columnas existen
    try { $db->execute("ALTER TABLE AMPAR_HIS_EVENTOS ADD EVENTO_ARCHIVO_REMISION VARCHAR(500)"); } catch (Throwable $e) {}
    try { $db->execute("ALTER TABLE AMPAR_HIS_REMISIONES ADD REMISION_ARCHIVO VARCHAR(500)"); } catch (Throwable $e) {}

    $folioEvento = trim($rows[0]['EVENTO_FOLIO'] ?? "EVENTO_{$eventoid}");
    $folioClean  = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $folioEvento);
    $baseDir     = __DIR__ . '/../uploads/eventos_recepcion/' . $folioClean;
    if (!is_dir($baseDir)) {
        @mkdir($baseDir, 0775, true);
    }
    if (!is_dir($baseDir)) {
        throw new Exception("No se pudo crear la carpeta del evento en el servidor.");
    }

    if (!isset($_FILES['archivo_remision']) || $_FILES['archivo_remision']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No se seleccionó ningún archivo o hubo un error al subirlo.");
    }

    $ext = strtolower(pathinfo($_FILES['archivo_remision']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
        throw new Exception("Formato no permitido. Solo PDF, JPG o PNG.");
    }

    $fnameRem = 'REM_' . ($remisionid > 0 ? $remisionid : $eventoid) . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $destRem  = $baseDir . DIRECTORY_SEPARATOR . $fnameRem;

    if (!move_uploaded_file($_FILES['archivo_remision']['tmp_name'], $destRem)) {
        throw new Exception("Error al mover el archivo subido al servidor.");
    }

    $rutaRelRem = 'uploads/eventos_recepcion/' . $folioClean . '/' . $fnameRem;

    if ($remisionid > 0) {
        $db->execute("UPDATE AMPAR_HIS_REMISIONES SET REMISION_ARCHIVO = ? WHERE REMISION_ID = ?", [$rutaRelRem, $remisionid]);
        // Si EVENTO_ARCHIVO_REMISION en cabecera está vacío, llenarlo como referencia general
        $evCheck = $db->query("SELECT EVENTO_ARCHIVO_REMISION FROM AMPAR_HIS_EVENTOS WHERE EVENTO_ID = {$eventoid}");
        if (empty(trim($evCheck[0]['EVENTO_ARCHIVO_REMISION'] ?? ''))) {
            $db->execute("UPDATE AMPAR_HIS_EVENTOS SET EVENTO_ARCHIVO_REMISION = ? WHERE EVENTO_ID = ?", [$rutaRelRem, $eventoid]);
        }
    } else {
        $db->execute("UPDATE AMPAR_HIS_EVENTOS SET EVENTO_ARCHIVO_REMISION = ? WHERE EVENTO_ID = ?", [$rutaRelRem, $eventoid]);
        // Si hay remisiones sin archivo, asignarles este archivo por compatibilidad si no tienen
        $db->execute("UPDATE AMPAR_HIS_REMISIONES SET REMISION_ARCHIVO = ? WHERE REMISION_EVENTOID = ? AND (REMISION_ARCHIVO IS NULL OR TRIM(REMISION_ARCHIVO) = '')", [$rutaRelRem, $eventoid]);
    }

    // Bitácora
    $usr   = $_SESSION['ampar']['usuario'] ?? null;
    $usrid = isset($usr['USUARIO_ID']) ? (int)$usr['USUARIO_ID'] : 0;
    $usrS  = str_replace("'", "''", $usr['USUARIO_CORREO'] ?? 'sistema');
    $comentario = $remisionid > 0 ? "Archivo subido para la remisión ID #{$remisionid}" : "Archivo de Remisión general subido";
    $db->execute("INSERT INTO AMPAR_BITACORA (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_EVENTOID)
                  VALUES (CURRENT_TIMESTAMP, '{$comentario}', {$usrid}, '{$usrS}', {$eventoid})");

    // Verificar si se subieron las remisiones y si hay evidencias para pasar automáticamente a revisión (estatus 8)
    $autoRevision = false;
    $msgAuto = "";

    if ($statusActual == 29) {
        $remTotal = $db->query("SELECT COUNT(*) AS TOTAL FROM AMPAR_HIS_REMISIONES WHERE REMISION_EVENTOID = {$eventoid}");
        $remSubidas = $db->query("SELECT COUNT(*) AS SUBIDAS FROM AMPAR_HIS_REMISIONES WHERE REMISION_EVENTOID = {$eventoid} AND REMISION_ARCHIVO IS NOT NULL AND TRIM(REMISION_ARCHIVO) <> ''");

        $totalRem = intval($remTotal[0]['TOTAL'] ?? 0);
        $subidasRem = intval($remSubidas[0]['SUBIDAS'] ?? 0);

        $todasSubidas = false;
        if ($totalRem > 0 && $subidasRem >= $totalRem) {
            $todasSubidas = true;
        } elseif ($totalRem === 0) {
            $evCheck = $db->query("SELECT EVENTO_ARCHIVO_REMISION FROM AMPAR_HIS_EVENTOS WHERE EVENTO_ID = {$eventoid}");
            if (!empty(trim($evCheck[0]['EVENTO_ARCHIVO_REMISION'] ?? ''))) {
                $todasSubidas = true;
            }
        }

        $evCheckCount = $db->query("SELECT COUNT(DISTINCT MALETA_ID) AS CANTEV FROM AMPAR_ENTREGAEVENTO WHERE EVENTO_ID = {$eventoid} AND MALETA_ID IS NOT NULL");
        $cantEv = intval($evCheckCount[0]['CANTEV'] ?? 0);

        $rm = $db->query("SELECT COUNT(*) AS T FROM AMPAR_HIS_EVENTOSMALETAS WHERE EVENTOMALETA_EVENTOID = {$eventoid} AND EVENTOMALETA_MALETAID > 0");
        $maletasCount = intval($rm[0]['T'] ?? 0);
        
        $evidenciasCumplidas = ($maletasCount > 0) ? ($cantEv >= $maletasCount) : true;

        if ($todasSubidas && $evidenciasCumplidas) {
            $db->execute("UPDATE AMPAR_HIS_EVENTOS SET EVENTO_STATUSGENERAL = 8 WHERE EVENTO_ID = {$eventoid}");
            $autoRevision = true;
            $msgMotivo = ($maletasCount > 0) ? "y el evento cuenta con evidencias de recepción" : "y el evento no requiere evidencias (sin maletas)";
            $msgAuto = "Se han cargado los archivos de todas las remisiones (" . ($totalRem > 0 ? $subidasRem . '/' . $totalRem : '1/1') . ") {$msgMotivo}.<br><br><b>¡El evento ha pasado automáticamente a 'En Revisión' (8)!</b>";
            
            $db->execute("INSERT INTO AMPAR_BITACORA (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_EVENTOID)
                          VALUES (CURRENT_TIMESTAMP, 'Paso automático a Revisión al completar las remisiones {$msgMotivo}', {$usrid}, '{$usrS}', {$eventoid})");
                          
            require_once("../class/whatsapp.php");
            try {
                $telAdmins = whatsapp::getTelefonosAdmins();
                if (!empty($telAdmins)) {
                    $folioStr = $folioEvento;
                    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    $ctaUrl = $proto . $host . '/ampar/evento.php?id=' . intval($eventoid);
                    
                    $msgAdmin = "👀 *Evento en Revisión*\nFolio: *{$folioStr}*\nEl evento ha pasado automáticamente a revisión al contar con todas sus remisiones y evidencias.\n\nVer evento:\n" . $ctaUrl;
                    whatsapp::enviarMultiple($telAdmins, $msgAdmin);
                }
            } catch (Throwable $eWA) {
                error_log("[WhatsApp] Error notificación revisión (auto): " . $eWA->getMessage());
            }
        }
    }

    $db->close();
    echo json_encode([
        'success' => true,
        'auto_revision' => $autoRevision,
        'msg' => $autoRevision ? $msgAuto : "Archivo guardado correctamente."
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
