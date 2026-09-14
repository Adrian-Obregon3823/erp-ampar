<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
$usuarioid = (isset($_GET['usuarioid']))?base64_decode($_GET['usuarioid']):"";
$eventos = new eventos();
$reseventos = $eventos->getinfocalendario($usuarioid);
echo json_encode($reseventos);
?>