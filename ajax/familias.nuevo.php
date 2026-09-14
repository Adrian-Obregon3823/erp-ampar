<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* Ajax para guardar una nueva familia
*********************************************************************************
*/
$maletas = new maletas();
$nombre = isset($_POST['famnombre']) ? trim($_POST['famnombre']) : '';
$descripcion = isset($_POST['famdescripcion']) ? trim($_POST['famdescripcion']) : '';

if ($nombre == '') {
	echo "El nombre de la familia es obligatorio";
} else {
	echo $maletas->nuevafamilia($nombre, $descripcion);
}
?>
