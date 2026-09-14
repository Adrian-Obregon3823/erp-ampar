<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para guardar un usuario
*********************************************************************************
*/
?>
<?php
    // Debug: ver qué se está recibiendo
    error_log("POST usucursales: " . print_r($_POST['usucursales'], true));
    error_log("POST utipo: " . print_r($_POST['utipo'], true));

    $login = new login();

    // Asegurar que sean arrays
    $sucursales = isset($_POST['usucursales']) && is_array($_POST['usucursales']) ? $_POST['usucursales'] : [];
    $tipo = isset($_POST['utipo']) && is_array($_POST['utipo']) ? $_POST['utipo'] : [];

    $telefono = $_POST['utelefono'] ?? '';
    $telefonoLimpio = preg_replace('/[+\s-]/', '', (string)$telefono);
    if (strlen($telefonoLimpio) === 10) {
        $telefono = '52' . $telefonoLimpio;
    }

    try {
        $login->agregarusuario(
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
    } catch (Exception $e) {
        error_log("Error al agregar usuario: " . $e->getMessage());
        echo "Error: " . $e->getMessage();
    }
?>