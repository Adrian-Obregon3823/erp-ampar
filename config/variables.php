<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA 
* Variables Globales de la página
*********************************************************************************
*/
?>
<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(realpath(__DIR__ . '/../'));
$dotenv->load();

//Generales
$GLOBALS['date_default_timezone_set'] = $_ENV['TIMEZONE'];
$GLOBALS['global_site'] = $_ENV['SITE_URL'];

//BD Firebird
// En Docker, usa host.docker.internal para acceder al host
$GLOBALS['host'] = $_ENV['DB_HOST'];
$GLOBALS['dbname'] = $_ENV['DB_PATH'];
$GLOBALS['username'] = $_ENV['DB_USERNAME'];
$GLOBALS['password'] = $_ENV['DB_PASSWORD'];

//Correo
$GLOBALS['correo_host'] = getenv('MAIL_HOST') ?: "mail.evotek.com.mx";
$GLOBALS['correo_usuario'] = getenv('MAIL_USER') ?: "monica.rodriguez@evotek.com.mx";
$GLOBALS['correo_pwd'] = getenv('MAIL_PASSWORD') ?: 'moni123.$_';
$GLOBALS['correo_nombre'] = getenv('MAIL_NAME') ?: "Ampar";

// API RFID
$GLOBALS['rfid_api_base_url'] = rtrim($_ENV['RFID_API_BASE_URL']);
$GLOBALS['rfid_api_timeout_seconds'] = (int)($_ENV['RFID_API_TIMEOUT_SECONDS']);
?>