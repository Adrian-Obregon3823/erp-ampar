<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para guardar remision
*********************************************************************************
*/
?>
<?php
$remisiones = new remisiones();

$proveedorid = isset($_POST['proveedorid']) ? $_POST['proveedorid'] : [];
$idarticulosfiltro = isset($_POST['idarticulofiltro']) ? $_POST['idarticulofiltro'] : [];
$cantidades = isset($_POST['cantidad']) ? $_POST['cantidad'] : [];
$subtotalarticulosfiltro = isset($_POST['preciounitario_sub']) ? $_POST['preciounitario_sub'] : [];
$ivaarticulosfiltro = isset($_POST['preciounitario_iva']) ? $_POST['preciounitario_iva'] : [];
$totalarticulosfiltro = isset($_POST['preciounitario_total']) ? $_POST['preciounitario_total'] : [];
$subtotalproveedor = isset($_POST['subproveedor']) ? $_POST['subproveedor'] : [];
$ivaproveedor = isset($_POST['ivaproveedor']) ? $_POST['ivaproveedor'] : [];
$totalproveedor = isset($_POST['totalproveedor']) ? $_POST['totalproveedor'] : [];

$remarticulosid = isset($_POST['remarticulosid']) ? $_POST['remarticulosid'] : [];
$remarticulossubtotal = isset($_POST['remarticulossubtotal']) ? $_POST['remarticulossubtotal'] : [];
$remarticulosiva = isset($_POST['remarticulosiva']) ? $_POST['remarticulosiva'] : [];
$remarticulostotal = isset($_POST['remarticulostotal']) ? $_POST['remarticulostotal'] : [];
$remalmacen = isset($_POST['remalmacen']) ? $_POST['remalmacen'] : null;
$remproyectoid = isset($_POST['remproyectoid']) && $_POST['remproyectoid'] !== "" ? $_POST['remproyectoid'] : null;

$remisiones->nuevaremision(
    $_POST['remsucalmacen'],
    $_POST['remeventoid'],
    $remarticulosid,
    $remarticulossubtotal,
    $remarticulosiva,
    $remarticulostotal,
    $proveedorid,
    $idarticulosfiltro,
    $cantidades,
    $subtotalarticulosfiltro,
    $ivaarticulosfiltro,
    $totalarticulosfiltro,
    $subtotalproveedor,
    $ivaproveedor,
    $totalproveedor,
    $remalmacen,
    $remproyectoid
);
?>