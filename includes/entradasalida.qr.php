<?php include_once("../includes/includes.php");?>
<?php
require_once '../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

// Recibe el valor por GET
//$code = $_GET['code'] ?? 'Texto vacío';
$code = $GLOBALS['global_site']."gui/remisiones.carrito.php?stockid=".$_GET['code'];
// Genera el QR y lo imprime directamente
header('Content-Type: image/png');
$result = Builder::create()
    ->writer(new PngWriter())
    ->data($code)
    ->encoding(new Encoding('UTF-8'))
    ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
    ->size(200)
    ->margin(10)
    ->build();

echo $result->getString();
?>