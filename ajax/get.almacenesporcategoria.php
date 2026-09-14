<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Ajax para obtener los almacenes de una sucursal (categoría)
*********************************************************************************
*/
$almacenes = new almacenes();
$res = $almacenes->getalmacenesporcategoria($_GET['categoriaid']);

if ($res <> 0 && is_array($res)) {
    echo json_encode($res);
} else {
    echo json_encode(array());
}
?>