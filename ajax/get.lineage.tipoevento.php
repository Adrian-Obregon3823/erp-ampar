<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* Ajax para obtener el linaje completo (Grupo -> Subgrupo -> Tipo Evento)
* Se usa al Editar un médico para precargar los selects en cascada
*********************************************************************************
*/
$tipoeventoid = isset($_GET['tipoeventoid']) ? (int)$_GET['tipoeventoid'] : 0;

try {
    $db = new FirebirdConnection();
    
    $sql = "SELECT 
                G.TIPOEVENTOGRUPO_ID AS grupo_id,
                SG.TIPOEVENTOSUBGRUPO_ID AS subgrupo_id,
                TE.TIPOEVENTO_ID AS tipoevento_id
            FROM AMPAR_CAT_TIPOEVENTO TE
            INNER JOIN AMPAR_CAT_TIPOEVENTOSUBGRUPO SG ON TE.TIPOEVENTO_SUBGRUPOID = SG.TIPOEVENTOSUBGRUPO_ID
            INNER JOIN AMPAR_CAT_TIPOEVENTOGRUPO G ON SG.TIPOEVENTOSUBGRUPO_GRUPOID = G.TIPOEVENTOGRUPO_ID
            WHERE TE.TIPOEVENTO_ID = ?";
            
    $res = $db->query($sql, [$tipoeventoid]);
    
    if ($res && count($res) > 0) {
        // Aquí devolvemos solo la primera fila (objeto directo), no un arreglo
        echo json_encode($res[0]);
    } else {
        echo json_encode(['grupo_id' => null, 'subgrupo_id' => null, 'tipoevento_id' => null]);
    }
} catch (Throwable $e) {
    echo json_encode(['grupo_id' => null, 'subgrupo_id' => null, 'tipoevento_id' => null]);
}
?>