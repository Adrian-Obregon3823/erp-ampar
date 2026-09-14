<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/traspaso_debug.log');
error_log("=== EMPEZANDO TRASPASO DIRECTO ===");

try {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);

    $proyectoid = (int)($body['proyectoid'] ?? 0);
    $dealmacen  = (int)($body['dealmacen']  ?? 0);
    $aalmacen   = (int)($body['aalmacen']   ?? 0);
    $stockids   = array_map('intval', $body['stockids'] ?? []);

    if (!$proyectoid || !$dealmacen || !$aalmacen || empty($stockids)) {
        echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
        exit;
    }

    // Verificar que almacenDestino pertenece al proyecto
    $db = new FirebirdConnection(true);
    $check = $db->query(
        "SELECT PROYECTO_FOLIO FROM AMPAR_HIS_PROYECTOS
         WHERE PROYECTO_ID = ? AND PROYECTO_ALMACEN_CONSIGNA_ID = ? AND PROYECTO_SUCURSALID = ?",
        [$proyectoid, $aalmacen, $dealmacen]
    );
    if (!$check) {
        $db->close();
        echo json_encode(['ok' => false, 'msg' => 'Los almacenes no coinciden con el proyecto.']);
        exit;
    }
    $proyFolio = $check[0]['PROYECTO_FOLIO'];
    $db->close();

    // Usar la clase traspasos para crear el traspaso
    require_once '../class/traspasos.php';
    $t = new traspasos();

    // guardartraspaso() hace echo directamente ("OK|id" o mensaje de error)
    // lo capturamos con output buffering
    ob_start();
    $t->guardartraspaso(
        $dealmacen,
        $aalmacen,
        'Traspaso al almacén del proyecto ' . $proyFolio,
        $stockids,   // array de STOCK_IDs
        [],          // sin archivos
        null,        // sin requerimiento material
        null,        // sin paquetería
        null         // sin guía
    );
    $resultado = ob_get_clean();

    // guardartraspaso() devuelve "OK|id" o "OK_SPECIAL|id" o mensaje de error
    if (strpos($resultado, 'OK') === 0) {
        $parts  = explode('|', $resultado);
        $trasId = (int)($parts[1] ?? 0);

        // Obtener folio del traspaso generado
        $db2    = new FirebirdConnection(true);
        $folioR = $db2->query("SELECT TRASPASO_FOLIO FROM AMPAR_HIS_TRASPASO WHERE TRASPASO_ID = ?", [$trasId]);
        $folio  = $folioR[0]['TRASPASO_FOLIO'] ?? $trasId;
        
        error_log("Traspaso generado con exito: " . $trasId);
        
        // --- AUTO RECEPCIÓN DIRECTA (PROYECTOS) ---
        $usersesion = $_SESSION['ampar']['usuario'] ?? [];
        $ipuser = $_SERVER['REMOTE_ADDR'] ?? '';
        $uid = $usersesion['USUARIO_ID'] ?? 0;
        
        error_log("1. Actualizando status del traspaso a 3");
        // 1. Marcar el traspaso como FINALIZADO (Status 3)
        $db2->execute("UPDATE AMPAR_HIS_TRASPASO SET TRASPASO_STATUS = 3 WHERE TRASPASO_ID = ?", [$trasId]);
        
        error_log("2. Insertando Recepcion");
        // 2. Insertar Recepción
        $sqlInsRec = "INSERT INTO AMPAR_RECEPCION (RECEPCION_FECHA, RECEPCION_USUARIOID, RECEPCION_TRASPASOID, RECEPCION_STATUS, RECEPCION_OCID) VALUES (CURRENT_TIMESTAMP, ?, ?, 1, 0)";
        $idRecepcion = $db2->executeconreturning($sqlInsRec, 'RECEPCION_ID', [$uid, $trasId]);
        
        error_log("3. Procesando detalle, idRecepcion=" . $idRecepcion);
        // 3. Procesar detalle y actualizar stock
        $detalle = $db2->query("SELECT TRASPASODET_ID, TRASPASODET_STOCKID, S.STOCK_ARTICULOID FROM AMPAR_HIS_TRASPASODET TD JOIN AMPAR_HIS_STOCK S ON S.STOCK_ID = TD.TRASPASODET_STOCKID WHERE TRASPASODET_TRASPASOID = ?", [$trasId]);
        
        if ($detalle) {
            foreach ($detalle as $det) {
                error_log("Procesando det: " . $det['TRASPASODET_ID']);
                // Marcar como activo en el traspaso
                $db2->execute("UPDATE AMPAR_HIS_TRASPASODET SET TRASPASODET_ACTIVO = 1 WHERE TRASPASODET_ID = ?", [$det['TRASPASODET_ID']]);
                
                // Generar recepción detalle
                $db2->execute("
                    INSERT INTO AMPAR_RECEPCIONDET (RECDET_RECEPCIONID, RECDET_TRASPASODETID, RECDET_ARTICULOID, RECDET_COSTO_UNITARIO, RECDET_CANTIDAD_ESPERADA, RECDET_CANTIDAD_RECIBIDA, RECDET_CANTIDAD_RECHAZADA) 
                    VALUES (?, ?, ?, 0, 1, 1, 0)
                ", [$idRecepcion, $det['TRASPASODET_ID'], $det['STOCK_ARTICULOID']]);

                // Liberar el stock y cambiarlo de almacén
                $db2->execute("
                    UPDATE AMPAR_HIS_STOCK
                    SET STOCK_ALMACENIDACTUAL = ?, STOCK_STOCKSTATUSID = 1
                    WHERE STOCK_ID = ?
                ", [$aalmacen, $det['TRASPASODET_STOCKID']]);
            }
        }
        
        error_log("4. Escribiendo en bitacora");
        // 4. Bitácora de auto-recepción
        $comentBitRec = 'Se auto-recepcionó DIRECTAMENTE el traspaso FOLIO: ' . $folio . ' en recepción REC-' . $idRecepcion . ' (Proyecto Consigna)';
        $db2->execute("
            INSERT INTO AMPAR_BITACORA (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO, BITACORA_USUARIOIP, BITACORA_ALMACENID)
            VALUES (CURRENT_TIMESTAMP, ?, ?, ?, ?, ?)
        ", [$comentBitRec, $uid, $usersesion['USUARIO_CORREO'] ?? '', $ipuser, $aalmacen]);
        
        error_log("5. Terminando proceso");
        $db2->close();
        // ------------------------------------------

        echo json_encode(['ok' => true, 'folio' => $folio, 'traspasoid' => $trasId]);
    } else {
        echo json_encode(['ok' => false, 'msg' => $resultado ?: 'Error al crear el traspaso.']);
    }

} catch (Throwable $e) {
    error_log("Error capturado: " . $e->getMessage());
    echo json_encode(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
}
