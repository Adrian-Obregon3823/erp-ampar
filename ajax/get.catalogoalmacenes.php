<?php include_once ("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener almacenes para los select
*********************************************************************************
*/
?>
<?php
    $almacenes = new almacenes();
    
    //We will allow filtering by SUCURSAL_ID, TIPOALMACEN, and STATUS
    $sucursalid = isset($_GET['sucursalid']) ? $_GET['sucursalid'] : "";
    $tipoalmacen = isset($_GET['tipoalmacen']) ? $_GET['tipoalmacen'] : "";
    $todos = isset($_GET['todos']) ? $_GET['todos'] : "";

    $res = $almacenes->getcatalogoalmacenes($sucursalid, $tipoalmacen, $todos);
    $data = array();
    
    if ($res <> 0){
        foreach ($res as $row) {
            $data[] = array(
                "ID" => $row['ID'],
                "NOMBRE" => $row['NOMBRE'],
                "TIPOALMACEN" => $row['ALMACEN_TIPOALMACEN'],
                "SUCURSAL_MS" => $row['ALMACEN_SUCURSAL_MS']
            );
        }
    }
    
    echo json_encode($data);
?>
