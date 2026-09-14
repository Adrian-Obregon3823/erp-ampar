<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

$action = $_GET['action'] ?? '';
$cat = $_GET['cat'] ?? '';

if ($action === 'template') {
    generateTemplate($cat);
} elseif ($action === 'import') {
    importExcel($cat);
}

function generateTemplate($cat)
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    if ($cat === 'hospitales') {
        $headers = ['HOSPITAL_NOMBRE', 'HOSPITAL_DIRECCION', 'Municipio (Nombre)', 'HOSPITAL_TELEFONO', 'HOSPITAL_CONTACTO', 'Almacén (Nombre)'];
        $filename = 'plantilla_hospitales.xlsx';
    } elseif ($cat === 'medicos') {
        $headers = ['MEDICO_NOMBRE', 'MEDICO_TELEFONO', 'MEDICO_CORREO', 'Subgrupo (Nombre)', 'Almacén (Nombre)'];
        $filename = 'plantilla_medicos.xlsx';
    } else {
        die("Catálogo no válido");
    }

    foreach ($headers as $i => $header) {
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
        $sheet->setCellValue($col . '1', $header);
        $sheet->getStyle($col . '1')->getFont()->setBold(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

function importExcel($cat)
{
    if (!isset($_FILES['file'])) {
        echo json_encode(['status' => 'error', 'message' => 'No se subió ningún archivo']);
        return;
    }

    $file = $_FILES['file']['tmp_name'];
    $db = new FirebirdConnection(false); // Transacción manual

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        unset($rows[0]); // Quitar encabezados

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        // Obtener sucursal de la sesión
        $sucursalId = $_SESSION['ampar']['usuario']['USUARIO_SUCURSAL'] ?? 19160;
        // Nota: en tus capturas vi 19.160, ajustaremos según el valor real de la sesión.

        foreach ($rows as $index => $row) {
            if (empty(trim($row[0]))) continue; // Saltar filas vacías (Nombre es requerido)

            if ($cat === 'hospitales') {
                $nombre = trim($row[0]);
                $direccion = trim($row[1] ?? '');
                $municipioNom = trim($row[2] ?? '');
                $telefono = trim($row[3] ?? '');
                $contacto = trim($row[4] ?? '');
                $almacenNom = trim($row[5] ?? '');

                $municipioId = lookupId($db, 'CIUDADES', 'CIUDAD_ID', 'NOMBRE', $municipioNom);
                $almacenId = lookupId($db, 'AMPAR_HIS_ALMACEN', 'ALMACEN_ID', 'ALMACEN_NOMBRE', $almacenNom);

                $sql = "INSERT INTO AMPAR_CAT_HOSPITALES (
                            HOSPITAL_NOMBRE, HOSPITAL_DIRECCION, HOSPITAL_MUNICIPIOID, 
                            HOSPITAL_TELEFONO, HOSPITAL_CONTACTO, HOSPITAL_ALMACEN, 
                            HOSPITAL_SUCURSAL, HOSPITAL_ACTIVO
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
                $db->execute($sql, [$nombre, $direccion, $municipioId, $telefono, $contacto, $almacenId, $sucursalId]);
                $successCount++;
            } elseif ($cat === 'medicos') {
                $nombre = trim($row[0]);
                $telefono = trim($row[1] ?? '');
                $correo = trim($row[2] ?? '');
                $subgrupoNom = trim($row[3] ?? '');
                $almacenNom = trim($row[4] ?? '');

                $subgrupoId = lookupId($db, 'AMPAR_CAT_TIPOEVENTOSUBGRUPO', 'TIPOEVENTOSUBGRUPO_ID', 'TIPOEVENTOSUBGRUPO_NOMBRE', $subgrupoNom);
                $almacenId = lookupId($db, 'AMPAR_HIS_ALMACEN', 'ALMACEN_ID', 'ALMACEN_NOMBRE', $almacenNom);

                $sql = "INSERT INTO AMPAR_CAT_MEDICOS (
                            MEDICO_NOMBRE, MEDICO_TELEFONO, MEDICO_CORREO, 
                            MEDICO_TIPOEVENTOID, MEDICO_ALMACENID, MEDICO_ACTIVO
                        ) VALUES (?, ?, ?, ?, ?, 1)";
                $db->executeconreturning($sql, 'MEDICO_ID', [$nombre, $telefono, $correo, $subgrupoId, $almacenId]);
                
                $successCount++;
            }
        }

        $db->commit();
        echo json_encode([
            'status' => 'success',
            'message' => "Proceso completado: $successCount exitosos, $errorCount errores.",
            'details' => ['success' => $successCount, 'errors' => $errorCount]
        ]);
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Error al procesar: ' . $e->getMessage()]);
    }
}

function lookupId($db, $table, $idCol, $nameCol, $nameVal)
{
    if (empty($nameVal)) return null;
    $sql = "SELECT $idCol FROM $table WHERE UPPER($nameCol) = UPPER(?)";
    $res = $db->query($sql, [$nameVal]);
    return $res[0][strtoupper($idCol)] ?? $res[0][$idCol] ?? null;
}
