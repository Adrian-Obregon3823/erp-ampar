<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* Ajax para guardar la edición de una familia
*********************************************************************************
*/
$maletas = new maletas();
$familiaid = isset($_POST['familiaid']) ? trim($_POST['familiaid']) : '';
$nombre = isset($_POST['famnombre']) ? trim($_POST['famnombre']) : '';
$descripcion = isset($_POST['famdescripcion']) ? trim($_POST['famdescripcion']) : '';

if ($familiaid == '') {
	echo "ID de familia no especificado";
} elseif ($nombre == '') {
	echo "El nombre de la familia es obligatorio";
} else {
	echo $maletas->editarfamilia($familiaid, $nombre, $descripcion);
}
?>
