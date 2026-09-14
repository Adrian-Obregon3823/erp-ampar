<?php
require __DIR__ . '/../vendor/autoload.php';
use Picqer\Barcode\BarcodeGeneratorPNG;
$code = isset($_GET['code']) ? $_GET['code'] : 'SIN-CODIGO';
$generator = new BarcodeGeneratorPNG();
header('Content-type: image/png');
echo $generator->getBarcode($code, $generator::TYPE_CODE_128);
?>