<?php
/**
 * Cron Job para verificar actividades de almacenistas
 * Debe configurarse para ejecutarse a las 17:00 (5 PM) y a las 19:00 (7 PM).
 */

// Simular el entorno web para poder cargar las clases
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
chdir(__DIR__);
require_once("../includes/includes.php");

// Instanciar clases necesarias
$db = new FirebirdConnection();
require_once("../class/actividades.php");
require_once("../class/notificaciones.php");
$actividades = new actividades();

$horaActual = (int)date('H');
$diaSemana = (int)date('w'); // 0 (domingo) a 6 (sábado)

// 1. Abortar si es fin de semana
if ($diaSemana == 0 || $diaSemana == 6) {
    echo "No se ejecuta en fin de semana.\n";
    exit;
}

// Asegurar que exista el tipo de ticket "Actividad"
$sqlCheckTipo = "SELECT TICKETTIPO_ID FROM AMPAR_TICKETTIPO WHERE UPPER(TICKETTIPO_NOMBRE) = 'ACTIVIDAD'";
$resTipo = $db->query($sqlCheckTipo);
$ticketTipoId = 0;
if (empty($resTipo)) {
    // Si no existe, lo insertamos
    $db->execute("INSERT INTO AMPAR_TICKETTIPO (TICKETTIPO_NOMBRE) VALUES ('Actividad')");
    $resTipo2 = $db->query($sqlCheckTipo);
    if (!empty($resTipo2)) {
        $ticketTipoId = $resTipo2[0]['TICKETTIPO_ID'];
    }
} else {
    $ticketTipoId = $resTipo[0]['TICKETTIPO_ID'];
}

// Obtener a todos los almacenistas
// Buscamos usuarios cuyo perfil contenga "ALMACEN"
$sqlAlmacenistas = "
    SELECT DISTINCT U.USUARIO_ID, U.USUARIO_NOMBRE
    FROM AMPAR_CAT_USUARIOS U
    JOIN AMPAR_CAT_USUARIOSPERFILES UP ON UP.USUARIOP_USUARIOID = U.USUARIO_ID
    JOIN AMPAR_CAT_PERFILES P ON P.PERFIL_ID = UP.USUARIOP_PERFILID
    WHERE UPPER(P.PERFIL_NOMBRE) LIKE '%ALMACEN%'
    AND U.USUARIO_STATUS = 17
";
$almacenistas = $db->query($sqlAlmacenistas);

if (empty($almacenistas)) {
    echo "No se encontraron almacenistas activos.\n";
    exit;
}

$fechaHoy = date('Y-m-d');
$log = "Ejecución Cron Actividades: " . date('Y-m-d H:i:s') . "\n";

foreach ($almacenistas as $alm) {
    $uid = $alm['USUARIO_ID'];
    $nombre = $alm['USUARIO_NOMBRE'];
    
    $meses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    $dias = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
    $fechaEs = $dias[date('w')] . " " . date('d') . " de " . $meses[(int)date('m')];

    // --- LÓGICA DE LAS 4:00 PM (16:00) ---
    if ($horaActual == 16) {
        $faltantesMaletas = $actividades->getMaletasFaltantes($uid);
        $faltantesAlmacenes = $actividades->getAlmacenesFaltantes($uid);
        
        $msgList = [];
        foreach ($faltantesMaletas as $f) {
            $msgList[] = $f['FOLIO'] . " (" . $f['NOMBRE'] . ")";
        }
        foreach ($faltantesAlmacenes as $f) {
            $msgList[] = $f['FOLIO'] . " (" . $f['NOMBRE'] . ")";
        }
        
        if (count($msgList) > 0) {
            $titulo = "Actividad Requerida - $fechaEs";
            $mensaje = "Te falta realizar el escaneo diario de:\n" . implode(", ", $msgList);
            notificaciones::crear($uid, 0, 'SISTEMA', $titulo, $mensaje);
            $log .= "- [16:00] Notificación enviada a $nombre con detalles\n";
        } else {
            $log .= "- [16:00] $nombre ya había completado todo, sin notificación.\n";
        }
    }

    // --- LÓGICA DE LAS 7:00 PM (19:00) ---
    if ($horaActual == 19) {
        $stMaletas = $actividades->getEscaneoStatus($uid);
        $stAlmacenes = $actividades->getEscaneoAlmacenesStatus($uid);

        $completadoMaletas = $stMaletas['completado'];
        $completadoAlmacenes = $stAlmacenes['completado'];
        
        $sqlTicket = "
            INSERT INTO AMPAR_TICKETS (
                TICKET_STATUS, TICKET_TIPO, TICKET_FOLIO, TICKET_CONCEPTO, TICKET_FECHA
            ) VALUES (1, ?, ?, ?, CURRENT_TIMESTAMP)
        ";

        if (!$completadoMaletas) {
            $conceptoMaletas = "Incidencia en actividad no completada ($fechaEs).\nResponsable: $nombre\nMaletas: Escaneadas " . $stMaletas['escaneos_hoy'] . " de " . $stMaletas['total_maletas'] . ".";
            $folioMaletas = "ACT-MAL-" . date('Ymd') . "-" . $uid;
            
            try {
                $db->execute($sqlTicket, [$ticketTipoId, $folioMaletas, $conceptoMaletas]);
                $log .= "- [19:00] Incidencia Maletas generada para $nombre ($folioMaletas)\n";
            } catch (Exception $e) {
                $log .= "- [19:00] Error al generar incidencia Maletas para $nombre: " . $e->getMessage() . "\n";
            }
            
            $tituloNotif = "Actividad Maletas No Completada";
            $msgNotif = "No completaste el escaneo de maletas obligatorio. Se generó la incidencia $folioMaletas.";
            notificaciones::crear($uid, 0, 'SISTEMA', $tituloNotif, $msgNotif);
        }

        if (!$completadoAlmacenes) {
            $conceptoAlmacenes = "Incidencia en actividad no completada ($fechaEs).\nResponsable: $nombre\nAlmacenes: Escaneados " . $stAlmacenes['escaneos_hoy'] . " de " . $stAlmacenes['total_almacenes'] . ".";
            $folioAlmacenes = "ACT-ALM-" . date('Ymd') . "-" . $uid;
            
            try {
                $db->execute($sqlTicket, [$ticketTipoId, $folioAlmacenes, $conceptoAlmacenes]);
                $log .= "- [19:00] Incidencia Almacenes generada para $nombre ($folioAlmacenes)\n";
            } catch (Exception $e) {
                $log .= "- [19:00] Error al generar incidencia Almacenes para $nombre: " . $e->getMessage() . "\n";
            }
            
            $tituloNotif = "Actividad Almacenes No Completada";
            $msgNotif = "No completaste el escaneo de almacenes fijos obligatorio. Se generó la incidencia $folioAlmacenes.";
            notificaciones::crear($uid, 0, 'SISTEMA', $tituloNotif, $msgNotif);
        }

        if ($completadoMaletas && $completadoAlmacenes) {
            $log .= "- [19:00] $nombre completó su actividad satisfactoriamente.\n";
        }
    }
}

// Guardar un log local (opcional)
file_put_contents(__DIR__ . '/cron_actividades.log', $log, FILE_APPEND);
echo "Cron ejecutado exitosamente.\n";
$db->close();
?>
