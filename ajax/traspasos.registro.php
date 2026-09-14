<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para guardar salidas y entradas de inventarios
*********************************************************************************
*/
?>
<?php
// Agregamos isset para evitar el warning de PHP
if (isset($_POST['idarticulofiltro']) && is_array($_POST['idarticulofiltro'])) {
    $reqId = isset($_POST['requerimientomaterialid']) ? (int)$_POST['requerimientomaterialid'] : null;
    $paqueteria = isset($_POST['paqueteria']) ? trim($_POST['paqueteria']) : null;
    $numero_guia = isset($_POST['numero_guia']) ? trim($_POST['numero_guia']) : null;
    $traspasos = new traspasos();
    try {
        $traspasos->guardartraspaso($_POST['deinvalmacenaux'], $_POST['ainvalmacen'], $_POST['invmotivo'], $_POST['idarticulofiltro'], $_FILES, $reqId, $paqueteria, $numero_guia);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
} else {
    echo "Debes seleccionar al menos un artículo";
}
?>