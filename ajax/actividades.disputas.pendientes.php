<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");
include_once("../class/actividades.php");

// Solo accesible para administrador
$esAdmin = false;
if (isset($_SESSION['ampar']['perfiles']) && is_array($_SESSION['ampar']['perfiles'])) {
    foreach ($_SESSION['ampar']['perfiles'] as $p) {
        if (isset($p['PERFIL_ID']) && $p['PERFIL_ID'] == 1) {
            $esAdmin = true;
            break;
        }
    }
}

if (!$esAdmin) {
    echo json_encode([]);
    exit;
}

$db = new FirebirdConnection();
$sql = "
    SELECT D.DISPUTA_ID, D.DISPUTA_MOTIVO, T.TRASPASO_FOLIO
    FROM AMPAR_DISPUTAS D
    LEFT JOIN AMPAR_HIS_TRASPASO T ON T.TRASPASO_ID = D.DISPUTA_TRASPASOID
    WHERE D.DISPUTA_STATUS = 1
    ORDER BY D.DISPUTA_FECHA ASC
";

$res = $db->query($sql);
$db->close();

echo json_encode($res ?: []);
?>
