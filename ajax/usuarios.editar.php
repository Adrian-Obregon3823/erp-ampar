<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para editar un usuario
*********************************************************************************
*/
?>
<?php
    $login = new login();

    // Asegurar que sean arrays
    $sucursales = isset($_POST['usucursales']) && is_array($_POST['usucursales']) ? $_POST['usucursales'] : [];
    $tipo = isset($_POST['utipo']) && is_array($_POST['utipo']) ? $_POST['utipo'] : [];

    $telefono = $_POST['utelefono'] ?? '';
    $telefonoLimpio = preg_replace('/[+\s-]/', '', (string)$telefono);
    if (strlen($telefonoLimpio) === 10) {
        $telefono = '52' . $telefonoLimpio;
    }

    $login->editarusuario(
        $_POST['usuario_id'],
        $sucursales,
        $tipo,
        array(),
        $_POST['uusuario'],
        $_POST['unombre'],
        $_POST['ualias'],
        $_POST['urpwd'],
        $_FILES,
        $telefono
    );
?>