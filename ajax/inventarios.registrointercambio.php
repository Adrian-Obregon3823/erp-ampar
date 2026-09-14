<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para guardar intercambios
*********************************************************************************
*/
?>
<?php
if (is_array($_POST['idarticulofiltro'])){
    $almacenes = new almacenes();
    $almacenes->guardarinventariomaleta($_POST['invnaturaleza'],$_POST['invmaleta'],$_POST['invalmacen'],$_POST['invmotivo'],$_POST['idarticulofiltro']);
}else{
    echo "Debes seleccionar al menos un artículo";
}
?>