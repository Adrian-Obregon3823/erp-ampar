<?php //include_once ("../includes/sesion.php"); NO SE AGREGA SESION POR QUE SE USA EN LOGIN ?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para obtener Eventos
*********************************************************************************
*/
header('Content-Type: application/json; charset=utf-8');
try {
    $eventos = new eventos();
    $res = $eventos->getcatalogoeventos((isset($_GET['sucalmacenid'])?$_GET['sucalmacenid']:""));
    if (is_array($res) && !empty($res)){
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }else{
        // Retornar array vacío si no hay eventos
        echo json_encode([], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>