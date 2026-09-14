<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* Ajax para guardar una nueva división
*********************************************************************************
*/
$maletas = new maletas();
$nombre = isset($_POST['divnombre']) ? trim($_POST['divnombre']) : '';
$descripcion = isset($_POST['divdescripcion']) ? trim($_POST['divdescripcion']) : '';
$familiaid = isset($_POST['divfamilia']) ? trim($_POST['divfamilia']) : '';

if ($nombre == '') {
    echo "El nombre de la división es obligatorio";
} elseif ($familiaid == '') {
    echo "Seleccionar una Familia es obligatorio";
} else {
    $maletas->nuevadivision($nombre, $descripcion, $familiaid);
}
?>
