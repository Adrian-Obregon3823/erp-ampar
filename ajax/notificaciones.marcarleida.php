<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

$notifid = intval($_POST['notifid'] ?? 0);

if ($notifid > 0) {
    notificaciones::marcarLeida($notifid, $usersesion['USUARIO_ID']);
    echo "ok";
} else {
    echo "error";
}
