<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
header('Content-Type: application/json; charset=utf-8');

try {
    $remisionid = intval($_POST['remisionid'] ?? 0);

    if ($remisionid <= 0) {
        throw new Exception("ID de Remisión inválido");
    }

    $db = new FirebirdConnection();
    
    // Asegurar columna existe
    try { $db->execute("ALTER TABLE AMPAR_HIS_REMISIONES ADD REMISION_ARCHIVO VARCHAR(500)"); } catch (Throwable $e) {}

    $rows = $db->query("SELECT REMISION_ID, REMISION_FOLIO, REMISION_STATUS FROM AMPAR_HIS_REMISIONES WHERE REMISION_ID = {$remisionid}");
    if (!$rows || count($rows) === 0) {
        throw new Exception("No existe la remisión.");
    }

    $statusActual = (int)($rows[0]['REMISION_STATUS'] ?? 0);
    if ($statusActual === 3 || $statusActual === 5) {
        throw new Exception("No se puede modificar el archivo en este estado de la remisión.");
    }

    $folioRemision = trim($rows[0]['REMISION_FOLIO'] ?? "REM_{$remisionid}");
    $folioClean  = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $folioRemision);
    $baseDir     = __DIR__ . '/../uploads/remisiones_mostrador/' . $folioClean;
    
    if (!is_dir($baseDir)) {
        @mkdir($baseDir, 0775, true);
    }
    if (!is_dir($baseDir)) {
        throw new Exception("No se pudo crear la carpeta en el servidor.");
    }

    if (!isset($_FILES['archivo_remision']) || $_FILES['archivo_remision']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No se seleccionó ningún archivo o hubo un error al subirlo.");
    }

    $ext = strtolower(pathinfo($_FILES['archivo_remision']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
        throw new Exception("Formato no permitido. Solo PDF, JPG o PNG.");
    }

    $fnameRem = 'REM_MOSTRADOR_' . $remisionid . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $ext;
    $destRem  = $baseDir . DIRECTORY_SEPARATOR . $fnameRem;

    if (!move_uploaded_file($_FILES['archivo_remision']['tmp_name'], $destRem)) {
        throw new Exception("Error al mover el archivo subido al servidor.");
    }

    $rutaRelRem = 'uploads/remisiones_mostrador/' . $folioClean . '/' . $fnameRem;

    $db->execute("UPDATE AMPAR_HIS_REMISIONES SET REMISION_ARCHIVO = ? WHERE REMISION_ID = ?", [$rutaRelRem, $remisionid]);

    $usr   = $_SESSION['ampar']['usuario'] ?? null;
    $usrid = isset($usr['USUARIO_ID']) ? (int)$usr['USUARIO_ID'] : 0;
    $usrS  = str_replace("'", "''", $usr['USUARIO_CORREO'] ?? 'sistema');
    $comentario = "Factura subida para la remisión de mostrador ID #{$remisionid}";
    $db->execute("INSERT INTO AMPAR_BITACORA (BITACORA_FECHA, BITACORA_COMENTARIO, BITACORA_USUARIOID, BITACORA_USUARIO)
                  VALUES (CURRENT_TIMESTAMP, '{$comentario}', {$usrid}, '{$usrS}')");

    $db->close();
    
    echo json_encode([
        'success' => true,
        'msg' => "Factura guardada correctamente."
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
