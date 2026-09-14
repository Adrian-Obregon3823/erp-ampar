<?php include_once("../includes/sesion.php");?>
<?php require '../vendor/autoload.php'; ?>
<?php include_once("../includes/includes.php");?>
<?php
$maletas = $_POST['emaletaid'] ?? [];
if (is_array($maletas)) {
    $proyectos = new proyectos();
    $proyecto_tipo = isset($_POST['proyecto_tipo']) ? (int)$_POST['proyecto_tipo'] : 1;
    $cumplimientos = isset($_POST['cumplimiento_art']) ? $_POST['cumplimiento_art'] : [];
    $psourcealmacenid = isset($_POST['ealmacen']) ? (int)$_POST['ealmacen'] : null;

    $response = $proyectos->nuevoproyecto(
        $_POST['esucalmacen'],
        $_POST['edescripcion'],
        $_POST['eclienteid'],
        $_POST['efechai'],
        $_POST['efechaf'],
        $_POST['eresponsableid'],
        $_POST['cumplimiento'],
        $maletas,
        $proyecto_tipo,
        $cumplimientos,
        $psourcealmacenid,
        $_POST['eplazo'] ?? null
    );
    echo $response;
} else {
    echo "Debes seleccionar al menos un artículo o equipo capital";
}
?>
