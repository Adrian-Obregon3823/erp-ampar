<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<?php include_once("../includes/sesion.php"); ?>
<?php
include_once("../includes/includes.php");
$traspasoid = isset($_POST['traspasoid_revision']) ? (int)$_POST['traspasoid_revision'] : 0;
$paqueteria = isset($_POST['paqueteria_revision']) ? trim($_POST['paqueteria_revision']) : '';
$link_rastreo = isset($_POST['link_rastreo_revision']) ? trim($_POST['link_rastreo_revision']) : '';
$numero_guia = isset($_POST['numero_guia_revision']) ? trim($_POST['numero_guia_revision']) : '';
$is_avance = isset($_POST['is_avance']) ? (bool)$_POST['is_avance'] : false;

if ($traspasoid > 0) {
    $traspasos = new traspasos();
    $traspasos->enviarRevision($traspasoid, $_FILES, $paqueteria, $numero_guia, $link_rastreo, $is_avance);
} else {
    echo "ID de traspaso no válido.";
}
?>
