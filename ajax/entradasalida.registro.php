<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php include_once("../includes/services/dashboard.inventario.service.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para guardar salidas y entradas de inventarios
*********************************************************************************
*/
?>
<?php
if (is_array($_POST['idarticuloarray'])){
    $entradasalida = new entradasalida();
    $entradasalida->guardarentradasalida($_POST['invtipo'],$_POST['invconcepto'],$_POST['invalmacen'],$_POST['invmotivo'],$_POST['idproveedor'],$_POST['correoproveedor'],$_POST['idarticuloarray'],((isset($_POST['invdetidarray']))?$_POST['invdetidarray']:""),((isset($_POST['cantidadarray']))?$_POST['cantidadarray']:""),((isset($_POST['lotearray']))?$_POST['lotearray']:""),((isset($_POST['caducidadarray']))?$_POST['caducidadarray']:""),((isset($_POST['caducidadmenor1anio']))?$_POST['caducidadmenor1anio']:""),((isset($_POST['seriearray']))?$_POST['seriearray']:""),((isset($_POST['invdetidarray']))?$_POST['invdetidarray']:""));
    
    // Invalidar cache del dashboard cuando se agrega un nuevo artículo
    if (function_exists('inv_dashboard_cache_delete')) {
        inv_dashboard_cache_delete();  // Elimina TODO el cache del dashboard
    }
}else{
    echo "Debes seleccionar al menos un artículo";
}
?>