<?php
require '../includes/includes.php';
$db = new FirebirdConnection(true);
$db->execute("INSERT INTO AMPAR_CONF_TIPOALMACEN (TIPOALMACEN_ID, TIPOALMACEN_NOMBRE) VALUES (6, 'RENTA')");
echo 'OK';
