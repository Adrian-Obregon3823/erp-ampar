<?php include_once("../includes/includes.php");?>
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
if (is_array($_POST['idarticulo'])){
    $almacenes = new almacenes();
    $almacenes->guardarinventario($_POST['invtipo'],$_POST['invconcepto'],$_POST['invalmacen'],$_POST['invmotivo'],$_POST['idarticulo'],$_POST['cantidad'],$_POST['lote'],$_POST['serie']);
}else{
    echo "Debes seleccionar al menos un artículo";
}
?>