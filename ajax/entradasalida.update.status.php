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
    $entradasalida = new entradasalida();
    $motivo = isset($_POST['motivo_rechazo']) ? trim($_POST['motivo_rechazo']) : null;
    $entradasalida->updatestatusentradasalida($_POST['id'], $_POST['status'], $motivo);
    
    // Invalidar cache del dashboard cuando cambia el estado de una entrada
    if (function_exists('inv_dashboard_cache_delete')) {
        inv_dashboard_cache_delete();  // Elimina TODO el cache del dashboard
    }
?>