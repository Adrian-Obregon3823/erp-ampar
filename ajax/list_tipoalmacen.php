<?php
require '../includes/includes.php';
$db = new FirebirdConnection(true);
$res = $db->query("SELECT * FROM AMPAR_CONF_TIPOALMACEN");
echo json_encode($res);
