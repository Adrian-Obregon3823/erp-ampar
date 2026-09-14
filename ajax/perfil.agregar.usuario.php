<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
$permisos = new Permisos();
$usuario = $_POST['usuario'] ?? 0;
$perfil = $_POST['perfil'] ?? 0;
if (!empty($usuario) && !empty($perfil)) {
    $permisos->asignarperfilusuario($usuario, $perfil);
}
?>
