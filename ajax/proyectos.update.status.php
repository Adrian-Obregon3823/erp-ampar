<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
    $proyecto = new proyectos();
    $proyecto->updatestatus($_POST['id'], $_POST['status']);
?>
