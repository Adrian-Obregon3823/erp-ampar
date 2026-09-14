<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
$almacenid = isset($_GET['almacenid']) ? $_GET['almacenid'] : 0;

$db = new FirebirdConnection(true);

// Check if it's a project warehouse
$sqlAlm = "SELECT ALMACEN_TIPOALMACEN FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_ID = ?";
$resAlm = $db->query($sqlAlm, [$almacenid]);
$isProject = false;
if ($resAlm <> 0 && count($resAlm) > 0) {
    if (in_array($resAlm[0]['ALMACEN_TIPOALMACEN'], [4, 5, 6])) {
        $isProject = true;
    }
}

$data = array();
if ($isProject) {
    $sql = "
        SELECT m.PROYECTOMALETA_MALETAID AS ARTICULO_ID 
        FROM AMPAR_HIS_PROYECTOS p 
        JOIN AMPAR_HIS_PROYECTOSMALETAS m ON p.PROYECTO_ID = m.PROYECTOMALETA_PROYECTOID 
        WHERE p.PROYECTO_ALMACEN_CONSIGNA_ID = ?
    ";
    
    $res = $db->query($sql, [$almacenid]);
    if ($res <> 0){
        foreach ($res as $row) {
            $data[] = $row['ARTICULO_ID'];
        }
    }
}

echo json_encode(["is_project" => $isProject, "allowed" => $data]);
?>
