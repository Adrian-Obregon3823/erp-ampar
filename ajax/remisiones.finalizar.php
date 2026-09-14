<?php
include_once("../includes/includes.php");

$remisionid = isset($_POST['remisionid']) ? intval($_POST['remisionid']) : 0;

$rem = new remisiones();
// status 3 = finalizada (según tu lógica)
$rem->updatestatusremisiones($remisionid, 3);

echo "OK";
?>