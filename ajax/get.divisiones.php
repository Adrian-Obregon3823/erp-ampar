<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* Ajax para obtener el catálogo de divisiones
*********************************************************************************
*/
$maletas = new maletas();
$familiaid = isset($_GET['familiaid']) ? $_GET['familiaid'] : "";
$res = $maletas->getcatalogodivisiones($familiaid);
if ($res <> 0) {
    echo json_encode($res);
} else {
    $array[0]['ID'] = '';
    $array[0]['NOMBRE'] = '';
    echo json_encode($array);
}
?>
