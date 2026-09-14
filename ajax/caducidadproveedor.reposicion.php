<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para crear una reposición de almacén
*********************************************************************************
*/
?>
<?php
if (isset($_POST['articulos']) && is_array($_POST['articulos'])) {
    $cpid = $_POST['cpid'];
    $almacenid = $_POST['almacenid'];
    $provid = $_POST['proveedorid'];
    $provcorreo = $_POST['proveedorcorreo'];
    $articulos = $_POST['articulos'];
    $entradasalida = new entradasalida();
    $entradasalida->guardarentradasalida('R', 5, $almacenid, 'Reposición', $provid, $provcorreo, $articulos, '', '', '', '', 0, '');
    echo "Se procesaron correctamente los artículos seleccionados.";
} else {
    echo "No se recibieron artículos.";
}
?>