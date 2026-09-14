<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
$permisos = new Permisos();
$nombre = $_POST['nombre'] ?? '';
if (!empty($nombre)) {
    $permisos->crearperfil($nombre);
}
?>
