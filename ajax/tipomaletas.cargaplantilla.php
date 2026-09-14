<?php 
/**
 * Procesa plantilla Excel para agregar artículos a tipo de maleta
 * 
 * Formato esperado:
 * - Columna A: CLAVE (se busca en catálogo de artículos)
 * - Columna B: ARTICULO (opcional, solo referencia)
 * - Columna C: CANTIDAD (entero mayor a 0)
 */

// Iniciar output buffering para capturar cualquier output no deseado
ob_start();

include_once("../includes/sesion.php");
include_once("../includes/includes.php");
require '../vendor/autoload.php';

// Limpiar cualquier output generado por los includes
ob_clean();

// Establecer header de JSON
header('Content-Type: application/json');

use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    if (!isset($_FILES['archivo'])) {
        throw new Exception("Archivo no recibido");
    }

    $inputFileName = $_FILES['archivo']['tmp_name'];
    $spreadsheet = IOFactory::load($inputFileName);
    $sheet = $spreadsheet->getActiveSheet();
    
    $highestRow = $sheet->getHighestRow();
    
    // Leer encabezados (usar getCalculatedValue para soportar fórmulas)
    $header = [
        'A' => strtoupper(trim((string)$sheet->getCell('A1')->getCalculatedValue())),
        'B' => strtoupper(trim((string)$sheet->getCell('B1')->getCalculatedValue())),
        'C' => strtoupper(trim((string)$sheet->getCell('C1')->getCalculatedValue()))
    ];

    // Validar encabezados
    if ($header['A'] !== 'CLAVE' || $header['B'] !== 'ARTICULO' || $header['C'] !== 'CANTIDAD') {
        throw new Exception("Encabezados incorrectos. Se esperaban: CLAVE, ARTICULO, CANTIDAD");
    }

    $encontrados = [];
    $no_encontrados = [];
    $errores = [];

    $db = new FirebirdConnection();

    for ($i = 2; $i <= $highestRow; $i++) {
        // Usar getCalculatedValue para soportar fórmulas
        $clave = trim((string)$sheet->getCell("A$i")->getCalculatedValue());
        $articuloExcel = trim((string)$sheet->getCell("B$i")->getCalculatedValue());
        $cantidadRaw = $sheet->getCell("C$i")->getCalculatedValue();
        $cantidad = intval($cantidadRaw);

        // Saltar filas vacías
        if ($clave === "" && $articuloExcel === "" && empty($cantidadRaw)) {
            continue;
        }

        // Validar clave obligatoria
        if ($clave === "") {
            $errores[] = "Fila $i: La columna CLAVE es obligatoria.";
            continue;
        }

        // Validar cantidad
        if ($cantidad <= 0) {
            $errores[] = "Fila $i: CANTIDAD debe ser un número mayor a 0.";
            continue;
        }

        // Buscar artículo por clave usando prepared statement
        $sql = "
            SELECT AR.ARTICULO_ID AS ID, CLAVE_ARTICULO, AR.NOMBRE
            FROM ARTICULOS AR
            LEFT JOIN (
                SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID 
                FROM CLAVES_ARTICULOS 
                WHERE ROL_CLAVE_ART_ID = 17
            ) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            WHERE CLAVE_ARTICULO = ?
        ";
        $result = $db->query($sql, [$clave]);

        if ($result <> 0 && count($result) > 0) {
            $encontrados[] = [
                'id' => $result[0]['ID'],
                'clave' => $result[0]['CLAVE_ARTICULO'],
                'nombre' => $result[0]['NOMBRE'],
                'cantidad' => $cantidad
            ];
        } else {
            $no_encontrados[] = [
                'fila' => $i, 
                'clave' => $clave,
                'articulo' => $articuloExcel
            ];
        }
    }

    $db->close();

    // Limpiar buffer y enviar JSON
    ob_end_clean();
    echo json_encode([
        'encontrados' => $encontrados,
        'no_encontrados' => $no_encontrados,
        'errores' => $errores
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['error' => $e->getMessage()]);
}
exit;
