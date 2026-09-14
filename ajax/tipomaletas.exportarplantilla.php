<?php
require '../vendor/autoload.php'; // Ajusta ruta según tu estructura
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$res = json_decode($_POST['data'], true); // Recibe los datos del form

// Validar que existan registros
if ($res[0]['TIPOMALETADET_ID'] == '') {
    echo "No hay datos para exportar.";
    exit;
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados
$sheet->setCellValue('A1', 'CLAVE');
$sheet->setCellValue('B1', 'ARTICULO');
$sheet->setCellValue('C1', 'CANTIDAD');

// Datos
$row = 2;
foreach ($res as $re) {
    $sheet->setCellValue('A' . $row, $re['CLAVE_ARTICULO']);
    $sheet->setCellValue('B' . $row, $re['ARTICULO_NOMBRE']);
    $sheet->setCellValue('C' . $row, $re['TIPOMALETADET_CANTIDADSUGERIDA']);
    $row++;
}

// Descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$res[0]['TIPOMALETA_NOMBRE'].'_'.$res[0]['SUCURSAL_NOMBRE'].'_export.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>