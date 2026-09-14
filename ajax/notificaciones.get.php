<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

header('Content-Type: application/json');

$notifs = notificaciones::getPendientes($usersesion['USUARIO_ID']);
echo json_encode($notifs);
