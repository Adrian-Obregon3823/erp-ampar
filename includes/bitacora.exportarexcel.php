<?php include_once ("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php

require '../vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Font;

$bitacora = new bitacora();
$anioActual = date('Y');
$anio = $_GET['anio'] ?? $anioActual;
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$usuario = $_GET['usuario'] ?? '';
$ip = $_GET['ip'] ?? '';
$comentario = $_GET['comentario'] ?? '';
$sucursal = $_GET['sucursal'] ?? '';
$almacen = $_GET['almacen'] ?? '';
$articulo = $_GET['articulo'] ?? '';

$res = $bitacora->getbitacora($anio, $fecha_inicio, $fecha_fin, $usuario, $ip, $comentario, $sucursal, $almacen, $articulo);

// Verifica si hay resultados
if ($res  == 0) {
    echo "<script>alert('No hay registros para descarga.'); window.history.back();</script>";
    exit;
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headers = ['FECHA', 'USUARIO', 'IP', 'SUCURSAL', 'ALMACÉN','ARTÍCULO ALMACÉN','CVE ARTÍCULO','ARTÍCULO','COMENTARIO'];
$sheet->fromArray($headers, NULL, 'A1');

// Poner negrita en títulos (fila 1)
$sheet->getStyle('A1:I1')->getFont()->setBold(true);

$row = 2;
foreach ($res as $r) {
    $sheet->setCellValueExplicit("A$row", $r['BITACORA_FECHA'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("B$row", $r['BITACORA_USUARIO'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("C$row", $r['BITACORA_USUARIOIP'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("D$row", $r['SUCURSAL_NOMBRE'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("E$row", $r['ALMACEN_NOMBRE'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("F$row", $r['STOCK_FOLIO'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("G$row", $r['ARTICULO_CLAVE'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("H$row", $r['ARTICULO_NOMBRE'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("I$row", $r['BITACORA_COMENTARIO'], DataType::TYPE_STRING);
    $row++;
}

// Poner toda la columna A en negrita (desde fila 2 hasta la última fila con datos)
$lastRow = $row - 1;
$sheet->getStyle("A2:A$lastRow")->getFont()->setBold(true);

// Auto tamaño de columnas
foreach (range('A', 'I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=bitacora.".date("Y-m-d").".xlsx");
header("Pragma: no-cache");
header("Expires: 0");

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>