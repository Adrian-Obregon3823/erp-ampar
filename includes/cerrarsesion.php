<?php
// 1. Iniciar o recuperar la sesión actual
session_start();

// 2. Limpiar todas las variables de sesión en memoria
$_SESSION = array();

// 3. Borrar la cookie de sesión del navegador
// Esto es vital para no dejar rastro del ID de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Destruir la sesión en el servidor
session_destroy();

// 5. Redirigir y DETENER el script
header("location: ../gui/login.php");
exit(); // <--- CRUCIAL para evitar que se ejecute código posterior
