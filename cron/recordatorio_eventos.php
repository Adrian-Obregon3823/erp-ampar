<?php
/**
 * Cron Job para recordar a los especialistas de los eventos que tienen programados para el día siguiente.
 * Debe configurarse en el Task Scheduler para ejecutarse todos los días a las 08:00 AM.
 */

// Simular el entorno web para poder cargar las clases
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
chdir(__DIR__);
require_once("../includes/includes.php");

$db = new FirebirdConnection();
require_once("../class/notificaciones.php");

$horaActual = (int)date('H');

$log = "Ejecución Cron Recordatorio Eventos: " . date('Y-m-d H:i:s') . "\n";

// Buscar eventos donde la fecha inicial es MAÑANA y el especialista o chofer (ESTATUS = 19 Aceptado)
// Nota: en Firebird, DATEADD(1 DAY TO CURRENT_DATE) o CURRENT_DATE + 1 funciona.
// También nos aseguramos que el status general del evento no sea Cancelado (15) ni Terminado (3).
$sqlEventosManana = "
    SELECT 
        E.EVENTO_ID, 
        E.EVENTO_FOLIO, 
        E.EVENTO_ESPECIALISTAID, 
        E.EVENTO_ESPECIALISTAIDSTATUS,
        UE.USUARIO_NOMBRE AS ESPECIALISTA_NOMBRE,
        E.EVENTO_CHOFERID,
        E.EVENTO_CHOFERIDSTATUS,
        UC.USUARIO_NOMBRE AS CHOFER_NOMBRE,
        E.EVENTO_FECHAI
    FROM AMPAR_HIS_EVENTOS E
    LEFT JOIN AMPAR_CAT_USUARIOS UE ON UE.USUARIO_ID = E.EVENTO_ESPECIALISTAID
    LEFT JOIN AMPAR_CAT_USUARIOS UC ON UC.USUARIO_ID = E.EVENTO_CHOFERID
    WHERE CAST(E.EVENTO_FECHAI AS DATE) = DATEADD(1 DAY TO CURRENT_DATE)
    AND (E.EVENTO_ESPECIALISTAIDSTATUS = 19 OR E.EVENTO_CHOFERIDSTATUS = 19)
    AND E.EVENTO_STATUSGENERAL NOT IN (3, 15)
";

require_once("../class/whatsapp.php");

try {
    $eventosManana = $db->query($sqlEventosManana);
    
    if (empty($eventosManana)) {
        $log .= "- No hay eventos programados para mañana con personal aceptado.\n";
    } else {
        foreach ($eventosManana as $evento) {
            $folio = $evento['EVENTO_FOLIO'];
            
            // Construir fecha en español para mañana
            $mananaTimestamp = strtotime('+1 day');
            $meses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
            $dias = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
            $fechaEs = $dias[date('w', $mananaTimestamp)] . " " . date('d', $mananaTimestamp) . " de " . $meses[(int)date('m', $mananaTimestamp)];
            $fechaProgramada = !empty($evento['EVENTO_FECHAI']) ? date('d/m/Y H:i', strtotime($evento['EVENTO_FECHAI'])) : 'N/A';
            
            $titulo = "Recordatorio de Evento";

            // Especialista
            if ($evento['EVENTO_ESPECIALISTAIDSTATUS'] == 19 && !empty($evento['EVENTO_ESPECIALISTAID'])) {
                $uid = $evento['EVENTO_ESPECIALISTAID'];
                $nombre = $evento['ESPECIALISTA_NOMBRE'];
                $mensaje = "Hola $nombre, te recordamos que tienes el evento folio $folio programado para mañana ($fechaEs). Por favor prepárate.";
                
                notificaciones::crear($uid, 0, 'SISTEMA', $titulo, $mensaje);
                
                $telUsuario = whatsapp::getTelefonoUsuario((int)$uid);
                if ($telUsuario) {
                    $msgWA  = "⏰ *Recordatorio de Evento*\n";
                    $msgWA .= "Hola *$nombre*, te recordamos que mañana participas como *Especialista*.\n";
                    $msgWA .= "Folio: *$folio*\n";
                    $msgWA .= "Fecha Prog.: *$fechaProgramada*\n";
                    $msgWA .= "Por favor, ten todo listo.";
                    whatsapp::enviar($telUsuario, $msgWA);
                }
                
                $log .= "- Notificación y WA enviados al Especialista $nombre para evento $folio.\n";
            }

            // Chofer
            if ($evento['EVENTO_CHOFERIDSTATUS'] == 19 && !empty($evento['EVENTO_CHOFERID'])) {
                $uid = $evento['EVENTO_CHOFERID'];
                $nombre = $evento['CHOFER_NOMBRE'];
                $mensaje = "Hola $nombre, te recordamos que tienes el evento folio $folio programado para mañana ($fechaEs). Por favor prepárate.";
                
                notificaciones::crear($uid, 0, 'SISTEMA', $titulo, $mensaje);
                
                $telUsuario = whatsapp::getTelefonoUsuario((int)$uid);
                if ($telUsuario) {
                    $msgWA  = "⏰ *Recordatorio de Evento*\n";
                    $msgWA .= "Hola *$nombre*, te recordamos que mañana participas como *Chofer*.\n";
                    $msgWA .= "Folio: *$folio*\n";
                    $msgWA .= "Fecha Prog.: *$fechaProgramada*\n";
                    $msgWA .= "Por favor, ten todo listo.";
                    whatsapp::enviar($telUsuario, $msgWA);
                }
                
                $log .= "- Notificación y WA enviados al Chofer $nombre para evento $folio.\n";
            }
        }
    }
} catch (Exception $e) {
    $log .= "- ERROR: " . $e->getMessage() . "\n";
}

file_put_contents(__DIR__ . '/cron_recordatorios.log', $log, FILE_APPEND);
echo "Cron ejecutado. Revisa cron_recordatorios.log.\n";

$db->close();
?>
