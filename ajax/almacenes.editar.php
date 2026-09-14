<?php include_once ("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para editar un almacen
*********************************************************************************
*/
?>
<?php
    $almacenes = new almacenes();
    $almacenes->editar(
        $_POST['almaceneditid'],
        $_POST['almaceneditnom'],
        $_POST['aleditdescripcion'],
        $_POST['aleditcalle'],
        $_POST['aleditnumext'],
        $_POST['aleditnumint'],
        $_POST['aleditcolonia'],
        $_POST['aleditcp'],
        $_POST['aledittel'],
        (($_POST['aleditpais']!="")?$_POST['aleditpais']:'NULL'),
        (isset($_POST['aleditestados'])?$_POST['aleditestados']:'NULL'),
        (isset($_POST['aleditmunicipios'])?$_POST['aleditmunicipios']:'NULL'),
        $_POST['aleditstatus'],
        $_POST['aledittipo'],       // <-- NUEVO CAMPO TIPO
        $_POST['aleditsucursal']    // <-- NUEVO CAMPO CATEGORIA (SUCURSAL)
    );
?>