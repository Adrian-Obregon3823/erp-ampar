<?php
/**
 * Enviar evento a revisión (status 29 → 30)
 * Valida que existan: archivo de remisión + evidencias de AMPAR_ENTREGAEVENTO
 */
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: text/html; charset=utf-8');

try {
    $eventoid = intval($_POST['eventoid'] ?? 0);
    if ($eventoid <= 0) {
        throw new Exception("Evento inválido.");
    }

    $db = new FirebirdConnection();

    // 1. Verificar que el evento exista y tenga el archivo de remisión
    $ev = $db->query("SELECT EVENTO_ID, EVENTO_STATUSGENERAL, EVENTO_ARCHIVO_REMISION FROM AMPAR_HIS_EVENTOS WHERE EVENTO_ID = {$eventoid}");
    if (!$ev || count($ev) === 0) {
        throw new Exception("No se encontró el evento.");
    }
    $evento = $ev[0];

    if ((int)$evento['EVENTO_STATUSGENERAL'] !== 29) {
        throw new Exception("El evento no está en estado 'En Recepción'. No se puede enviar a revisión.");
    }

    // 1.5. Verificar que el evento tenga remisiones y que todas tengan archivo
    $rr = $db->query("
        SELECT REMISION_ID, REMISION_ARCHIVO
        FROM AMPAR_HIS_REMISIONES 
        WHERE REMISION_EVENTOID = {$eventoid}
          AND REMISION_STATUS IN (1, 3)
    ");
    
    if (!$rr || count($rr) === 0) {
        throw new Exception("Debes generar al menos una remisión antes de poder enviar el evento a revisión.");
    }
    
    $faltanArchivos = 0;
    foreach ($rr as $r) {
        if (empty(trim($r['REMISION_ARCHIVO'] ?? ''))) {
            $faltanArchivos++;
        }
    }
    
    if ($faltanArchivos > 0) {
        throw new Exception("Existen {$faltanArchivos} remisión(es) sin archivo subido. Debes subir el archivo de remisión firmado para todas las remisiones antes de enviar a revisión.");
    }

    // 2. Verificar que existan evidencias de recepción en AMPAR_ENTREGAEVENTO
    $rm = $db->query("SELECT COUNT(*) AS T FROM AMPAR_HIS_EVENTOSMALETAS WHERE EVENTOMALETA_EVENTOID = {$eventoid} AND EVENTOMALETA_MALETAID > 0");
    $maletasCount = intval($rm[0]['T'] ?? 0);

    if ($maletasCount > 0) {
        $evidencias = $db->query("SELECT COUNT(DISTINCT MALETA_ID) AS C FROM AMPAR_ENTREGAEVENTO WHERE EVENTO_ID = {$eventoid} AND MALETA_ID IS NOT NULL");
        $maletasEscaneadas = intval($evidencias[0]['C'] ?? 0);
        
        if ($maletasEscaneadas < $maletasCount) {
            throw new Exception("No existen evidencias de recepción completas registradas para este evento. Tienes {$maletasEscaneadas} de {$maletasCount} maletas escaneadas. El chofer/especialista debe subir las fotos restantes desde la app móvil.");
        }
    }

    // 3. El status 8 (En Revisión) ya existe en AMPAR_CONF_STATUS
    // No es necesario crearlo.

    // 4. Actualizar status del evento a 8 (En Revisión)
    $db->execute("UPDATE AMPAR_HIS_EVENTOS SET EVENTO_STATUSGENERAL = 8 WHERE EVENTO_ID = {$eventoid}");

    require_once("../class/whatsapp.php");
    try {
        $telAdmins = whatsapp::getTelefonosAdmins();
        if (!empty($telAdmins)) {
            $folioStr = $evento['EVENTO_FOLIO'] ?? $eventoid;
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $ctaUrl = $proto . $host . '/ampar/evento.php?id=' . intval($eventoid);
            
            $msgAdmin = "👀 *Evento en Revisión*\nFolio: *{$folioStr}*\nEl evento ha sido enviado a revisión y requiere tu atención.\n\nVer evento:\n" . $ctaUrl;
            whatsapp::enviarMultiple($telAdmins, $msgAdmin);
        }
    } catch (Throwable $eWA) {
        error_log("[WhatsApp] Error notificación revisión (manual): " . $eWA->getMessage());
    }

    // 5. Bitácora
    $usr = $_SESSION['ampar']['usuario'] ?? [];
    $usrid = isset($usr['USUARIO_ID']) ? (int)$usr['USUARIO_ID'] : 0;
    $usrCorreo = str_replace("'", "''", $usr['USUARIO_CORREO'] ?? 'sistema');
    $db->execute("INSERT INTO AMPAR_BITACORA 
        (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_EVENTOID)
        VALUES (CURRENT_TIMESTAMP, 'Evento enviado a revisión con archivos y evidencias', {$usrid}, '{$usrCorreo}', {$eventoid})");

    $db->close();
    echo ""; // Éxito → cadena vacía

} catch (Throwable $e) {
    http_response_code(200);
    echo $e->getMessage();
}
