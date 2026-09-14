<?php
include_once ("../includes/sesion.php"); 
include_once("../includes/includes.php");
$remisionid = (int)($_POST['remisionid'] ?? 0);
$stockid    = (int)($_POST['stockid'] ?? 0);
if (!$remisionid || !$stockid){ echo "Parámetros incompletos"; exit; }

$rem = new remisiones();
// usa tu método que calcula precio según cliente y catálogo
$rem->agregararticuloqr(null, null, $remisionid, $stockid, null);
?>