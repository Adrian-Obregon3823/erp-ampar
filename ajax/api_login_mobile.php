<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

include_once("../includes/includes.php");

$data = json_decode(file_get_contents("php://input"), true);

$usuario = trim($data["ulusuario"] ?? "");
$pwd = $data["ulpwd"] ?? "";

if ($usuario === "" || $pwd === "") {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Faltan usuario o contraseña"
    ]);
    exit;
}

$login = new login();

ob_start();
$login->autenticar($usuario, $pwd);
$salida = trim(ob_get_clean());

if (
    isset($_SESSION['ampar']) &&
    isset($_SESSION['ampar']['usuario']) &&
    isset($_SESSION['ampar']['usuario']['USUARIO_ID'])
) {
    $usuarioSesion = $_SESSION['ampar']['usuario'];

    echo json_encode([
        "success" => true,
        "usuario_id" => (string) $usuarioSesion['USUARIO_ID'],
        "usuario_nombre" => (string) ($usuarioSesion['USUARIO_NOMBRE'] ?? ""),
        "usuario_correo" => (string) ($usuarioSesion['USUARIO_CORREO'] ?? "")
    ]);
    exit;
}

http_response_code(401);
echo json_encode([
    "success" => false,
    "message" => $salida ?: "Usuario o contraseña incorrectos"
]);
exit;
