<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Ajax para obtener los artículos de un proyecto
*********************************************************************************
*/
header('Content-Type: application/json');
try {
    $proyectoid = isset($_GET['proyectoid']) ? $_GET['proyectoid'] : "";
    $almacenid = isset($_GET['almacenid']) && $_GET['almacenid'] !== '' ? (int)$_GET['almacenid'] : null;
    $proyectos = new Proyectos();
    $res = $proyectos->getarticulosbyproyecto($proyectoid, $almacenid);
    if (is_array($res) && !empty($res)){
        echo json_encode($res);
    }else{
        echo json_encode([]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
