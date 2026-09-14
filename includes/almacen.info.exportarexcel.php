<?php include_once("../includes/includes.php");?>
<?php
require '../vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Font;

include_once("../includes/includes.php");

$almacenid = base64_decode($_GET['almacenid']);

$almacenes = new almacenesv2();
$res = $almacenes->getarticulosalmacenbyalmacenid($almacenid);

// Verifica si hay resultados
if ($res  == 0) {
    echo "<script>alert('No hay registros para descarga.'); window.history.back();</script>";
    exit;
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$headers = ['ARTICULO', 'CLAVE', 'NOMBRE', 'LOTE', 'CADUCIDAD', 'SERIE'];
$sheet->fromArray($headers, NULL, 'A1');

// Poner negrita en títulos (fila 1)
$sheet->getStyle('A1:F1')->getFont()->setBold(true);

$row = 2;
foreach ($res as $r) {
    $articuloId = 'A' . str_pad($r['INVENTARIODET_ID'], 6, '0', STR_PAD_LEFT);

    $sheet->setCellValueExplicit("A$row", $articuloId, DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("B$row", $r['CLAVE_ARTICULO'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("C$row", $r['NOMBRE'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("D$row", $r['INVENTARIODET_LOTE'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("E$row", $r['INVENTARIODET_CADUCIDAD'], DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("F$row", $r['INVENTARIODET_SERIE'], DataType::TYPE_STRING);
    $row++;
}

// Poner toda la columna A en negrita (desde fila 2 hasta la última fila con datos)
$lastRow = $row - 1;
$sheet->getStyle("A2:A$lastRow")->getFont()->setBold(true);

// Auto tamaño de columnas
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="almacen_'.$res[0]["ALMACEN_NOMBRE"].'_'.date("y-m-d").'xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>