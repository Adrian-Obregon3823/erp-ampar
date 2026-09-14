<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* Ajax para guardar la edición de una división
*********************************************************************************
*/
$maletas = new maletas();
$divisionid = isset($_POST['divisionid']) ? trim($_POST['divisionid']) : '';
$nombre = isset($_POST['divnombre']) ? trim($_POST['divnombre']) : '';
$descripcion = isset($_POST['divdescripcion']) ? trim($_POST['divdescripcion']) : '';
$familiaid = isset($_POST['divfamilia']) ? trim($_POST['divfamilia']) : '';

if ($divisionid == '') {
    echo "ID de división no especificado";
} elseif ($nombre == '') {
    echo "El nombre de la división es obligatorio";
} elseif ($familiaid == '') {
    echo "Seleccionar una Familia es obligatorio";
} else {
    $maletas->editardivision($divisionid, $nombre, $descripcion, $familiaid);
}
?>
