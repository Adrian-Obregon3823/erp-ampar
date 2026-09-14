<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

$ocId = $_POST['ocid'] ?? null;
$statusId = $_POST['status'] ?? null;

if (!$ocId || !$statusId) {
    echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
    exit;
}

$db = new FirebirdConnection();
$sql = "UPDATE AMPAR_OC SET OC_STATUS = ? WHERE OC_ID = ?";
$res = $db->execute($sql, [$statusId, $ocId]);
$db->close();

if ($res !== false) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'Error al actualizar estatus en la base de datos.']);
}
