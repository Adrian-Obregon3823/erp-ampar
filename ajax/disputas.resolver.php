<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");
include_once("../class/notificaciones.php");

$usuarioid = $_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0;
$esAdmin = false;
if (isset($_SESSION['ampar']['perfiles']) && is_array($_SESSION['ampar']['perfiles'])) {
    foreach ($_SESSION['ampar']['perfiles'] as $p) {
        if (isset($p['PERFIL_ID']) && $p['PERFIL_ID'] == 1) {
            $esAdmin = true;
            break;
        }
    }
}

if (!$esAdmin) {
    echo "No tienes permiso para resolver disputas.";
    exit;
}

$disputaid = isset($_POST['disputaid']) ? (int)$_POST['disputaid'] : 0;
$resolucion = isset($_POST['resolucion']) ? trim($_POST['resolucion']) : '';
$ganador = isset($_POST['ganador']) ? trim($_POST['ganador']) : '';

if ($disputaid <= 0 || empty($resolucion) || empty($ganador)) {
    echo "Faltan datos obligatorios.";
    exit;
}

$resolucionFinal = "[GANADOR: " . strtoupper($ganador) . "]\n" . $resolucion;

$db = new FirebirdConnection();

try {
    $db->beginTransaction();

    // Traer info
    $sql = "SELECT D.*, T.TRASPASO_FOLIO 
            FROM AMPAR_DISPUTAS D 
            LEFT JOIN AMPAR_HIS_TRASPASO T ON T.TRASPASO_ID = D.DISPUTA_TRASPASOID
            WHERE D.DISPUTA_ID = ?";
    $res = $db->query($sql, [$disputaid]);
    
    if (empty($res)) {
        throw new Exception("Disputa no encontrada.");
    }
    
    $d = $res[0];

    // Actualizar
    $sqlUpd = "
        UPDATE AMPAR_DISPUTAS 
        SET DISPUTA_STATUS = 2, 
            DISPUTA_RESOLUCION_ADMIN = ?, 
            DISPUTA_FECHA_RESOLUCION = CURRENT_TIMESTAMP 
        WHERE DISPUTA_ID = ?
    ";
    $db->execute($sqlUpd, [$resolucionFinal, $disputaid]);

    // Si gana el receptor (quien recibió y se quejó), el traspaso se cancela (status 5).
    // Si gana el emisor (quien envió), el traspaso se desbloquea para su recepción (status 9).
    $nuevoStatusTraspaso = ($ganador === 'RECEPTOR') ? 5 : 9;
    $db->execute("UPDATE AMPAR_HIS_TRASPASO SET TRASPASO_STATUS = ? WHERE TRASPASO_ID = ?", [$nuevoStatusTraspaso, $d['DISPUTA_TRASPASOID']]);

    // Notificar a creador (receptor) y destino (emisor)
    $msg = "La disputa del Traspaso " . $d['TRASPASO_FOLIO'] . " ha sido RESUELTA por Administración. Resolución: " . substr($resolucion, 0, 50) . "...";

    notificaciones::crear(
        $d['DISPUTA_USUARIO_CREADOR'],
        $disputaid,
        'RECEPCION_TRASPASO',
        'Disputa Resuelta',
        $msg
    );

    if ($d['DISPUTA_USUARIO_DESTINO'] != $d['DISPUTA_USUARIO_CREADOR']) {
        notificaciones::crear(
            $d['DISPUTA_USUARIO_DESTINO'],
            $disputaid,
            'RECEPCION_TRASPASO',
            'Disputa Resuelta',
            $msg
        );
    }

    $db->commit();
    echo "OK";

} catch (Exception $e) {
    try { $db->rollback(); } catch(Exception $ex) {}
    echo "Error: " . $e->getMessage();
}
$db->close();
?>
