<?php 
include_once ("../includes/sesion.php");
include_once("../includes/includes.php");
$eventoid = (int)($_GET['eventoid'] ?? 0);
$ev = new eventos();
echo json_encode($ev->getcatalogoproveedoresbyevento($eventoid));
?>