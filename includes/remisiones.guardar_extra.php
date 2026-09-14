<?php
include_once("includes.php");
header('Content-Type: application/json');

$remisionid = isset($_POST['remisionid']) ? intval($_POST['remisionid']) : 0;
if ($remisionid == 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
    exit;
}

$db = new FirebirdConnection();

$resChk = $db->query("SELECT REMISION_STATUS, REMISION_EVENTOID FROM AMPAR_HIS_REMISIONES WHERE REMISION_ID = {$remisionid}");
if ($resChk && isset($resChk[0]['REMISION_STATUS']) && (int)$resChk[0]['REMISION_STATUS'] !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'No se puede modificar una remisión que ya fue enviada a recepción o finalizada.']);
    exit;
}
$eventoid = (int)($resChk[0]['REMISION_EVENTOID'] ?? 0);

// Actualizar nombre del paciente en el evento (para que se refleje en todas las remisiones)
if (isset($_POST['nombrepaciente']) && $eventoid > 0) {
    $nombrePac = str_replace("'", "''", trim($_POST['nombrepaciente']));
    $sqlEv = "UPDATE AMPAR_HIS_EVENTOS SET EVENTO_NOMBREPARTICULAR = '{$nombrePac}' WHERE EVENTO_ID = {$eventoid}";
    try {
        $db->execute($sqlEv);
    } catch(Throwable $e) {}
}

// Campos permitidos y mapeo de columnas
$fields = [
    'numpaciente' => 'REMISION_NUMPACIENTE',
    'numclientehos' => 'REMISION_NUMCLIENTEHOS',
    'anticipo' => 'REMISION_ANTICIPO',
    'resto' => 'REMISION_RESTO',
    'representante' => 'REMISION_REPRESENTANTE',
    'medico2' => 'REMISION_MEDICO2',
    'enfermeria' => 'REMISION_ENFERMERIA',
    'rfc' => 'REMISION_RFC',
    'observaciones' => 'REMISION_OBSERVACIONES'
];

$updates = [];
foreach ($fields as $post_key => $db_col) {
    if (isset($_POST[$post_key])) {
        $val = str_replace("'", "''", $_POST[$post_key]);
        
        // Handle empty strings for decimal columns
        if ($val === '' && ($db_col == 'REMISION_ANTICIPO' || $db_col == 'REMISION_RESTO')) {
            $updates[] = "{$db_col} = NULL";
        } else {
            $updates[] = "{$db_col} = '{$val}'";
        }
    }
}

if (count($updates) > 0) {
    $sql = "UPDATE AMPAR_HIS_REMISIONES SET " . implode(", ", $updates) . " WHERE REMISION_ID = {$remisionid}";
    try {
        $db->execute($sql);
        echo json_encode(['status' => 'success', 'message' => 'Actualizado']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'success', 'message' => 'Nada que actualizar']);
}
?>
