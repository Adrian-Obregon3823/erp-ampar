<?php 
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
use PhpOffice\PhpSpreadsheet\Shared\Date;

try {
    if (!isset($_FILES['archivo'])) throw new Exception("Archivo no recibido");

    $inputFileName = $_FILES['archivo']['tmp_name'];
    $spreadsheet = IOFactory::load($inputFileName);
    $sheet = $spreadsheet->getActiveSheet();
    
    $highestRow = $sheet->getHighestRow(); // Número total de filas con contenido
    $header = [
        'A' => trim((string)$sheet->getCell('A1')->getCalculatedValue()),
        'B' => trim((string)$sheet->getCell('B1')->getCalculatedValue()),
        'C' => trim((string)$sheet->getCell('C1')->getCalculatedValue()),
        'D' => trim((string)$sheet->getCell('D1')->getCalculatedValue()),
        'E' => trim((string)$sheet->getCell('E1')->getCalculatedValue()),
        'F' => trim((string)$sheet->getCell('F1')->getCalculatedValue())
    ];


    // Validar encabezados
    if (
        strtoupper($header['A']) !== 'CLAVE' ||
        strtoupper($header['B']) !== 'ARTICULO' ||
        strtoupper($header['C']) !== 'CANTIDAD' ||
        strtoupper($header['D']) !== 'LOTE' ||
        strtoupper($header['E']) !== 'CADUCIDAD' ||
        strtoupper($header['F']) !== 'SERIE'
    ) {
        throw new Exception("Encabezados incorrectos. Se esperaban: CLAVE, ARTICULO, CANTIDAD, LOTE, CADUCIDAD, SERIE");
    }

    $encontrados = [];
    $no_encontrados = [];
    $errores = [];

    $db = new FirebirdConnection();

    for ($i = 2; $i <= $highestRow; $i++) {
        $clave     = trim((string)$sheet->getCell("A$i")->getCalculatedValue());
        $articulo  = trim((string)$sheet->getCell("B$i")->getCalculatedValue());
        $cantidad  = trim((string)$sheet->getCell("C$i")->getCalculatedValue());
        $lote      = trim((string)$sheet->getCell("D$i")->getCalculatedValue());
        
        // Obtener valor calculado de caducidad (si es fórmula, obtiene el resultado)
        $caducidadRaw = $sheet->getCell("E$i")->getCalculatedValue();
        $caducidad = trim((string)$caducidadRaw);

        // Normalizar y validar formato de CADUCIDAD
        if (!empty($caducidad)) {
            $caducidad = trim($caducidad);
        
            // Primero verificar si es formato compacto YYMMDD (exactamente 6 dígitos)
            if (is_numeric($caducidad) && strlen($caducidad) == 6) {
                // Formato compacto YYMMDD (ejemplo: 250905 -> 2025-09-05)
                $yy = substr($caducidad, 0, 2);
                $mm = substr($caducidad, 2, 2);
                $dd = substr($caducidad, 4, 2);
                
                // Convertir año de 2 dígitos a 4 dígitos (asumiendo 20XX)
                $anio = 2000 + (int)$yy;
                $mes = (int)$mm;
                $dia = (int)$dd;
                
                if (checkdate($mes, $dia, $anio)) {
                    $caducidad = sprintf("%04d-%02d-%02d", $anio, $mes, $dia);
                } else {
                    $errores[] = "Fila $i: CADUCIDAD '$caducidad' no es una fecha válida (YYMMDD: año=$anio, mes=$mes, día=$dia).";
                    $caducidad = ""; // Limpiar para evitar errores
                }
            } elseif (is_numeric($caducidad)) {
                // Serial de Excel (fecha válida)
                $fechaDateTime = Date::excelToDateTimeObject($caducidad);
                $caducidad = $fechaDateTime->format('Y-m-d');
            } elseif (preg_match('/^\d{4}[-\/]\d{2}[-\/]\d{2}$/', $caducidad)) {
                // Texto en formato YYYY-MM-DD o YYYY/MM/DD -> Validar
                $fechaPartes = preg_split('/[-\/]/', $caducidad);
                $anio = (int)$fechaPartes[0];
                $mes  = (int)$fechaPartes[1];
                $dia  = (int)$fechaPartes[2];
        
                if (checkdate($mes, $dia, $anio)) {
                    $caducidad = sprintf("%04d-%02d-%02d", $anio, $mes, $dia);
                } else {
                    $errores[] = "Fila $i: CADUCIDAD inválida, la fecha no existe.";
                }
            } elseif (preg_match('/^\d{2}[-\/]\d{2}[-\/]\d{4}$/', $caducidad)) {
                // Texto en formato DD-MM-YYYY o MM-DD-YYYY
                $fechaPartes = preg_split('/[-\/]/', $caducidad);
                $parte1 = (int)$fechaPartes[0];
                $parte2 = (int)$fechaPartes[1];
                $anio   = (int)$fechaPartes[2];
        
                // Inteligencia para identificar qué es día y qué es mes
                if ($parte1 > 12 && $parte2 <= 12) {
                    $dia = $parte1;
                    $mes = $parte2;
                } elseif ($parte2 > 12 && $parte1 <= 12) {
                    $mes = $parte1;
                    $dia = $parte2;
                } else {
                    // Si ambos son <=12, asumimos que viene como MM/DD/YYYY
                    $mes = $parte1;
                    $dia = $parte2;
                }
        
                if (checkdate($mes, $dia, $anio)) {
                    $caducidad = sprintf("%04d-%02d-%02d", $anio, $mes, $dia);
                } else {
                    $errores[] = "Fila $i: CADUCIDAD inválida, la fecha no existe.";
                }
            } else {
                $errores[] = "Fila $i: CADUCIDAD debe tener un formato de fecha válido.";
            }
        }        

        $serie     = trim((string)$sheet->getCell("F$i")->getCalculatedValue());
        
        $cantidadNum = (int)$cantidad;
        if ($cantidadNum <= 0) $cantidadNum = 1;

        // SALTAR FILAS TOTALMENTE VACÍAS
        if ($clave === "" && $articulo === "" && $cantidad === "" && $lote === "" && $caducidad === "" && $serie === "") {
            continue;
        }

        // VALIDAR CLAVE
        if ($clave === "") {
            $errores[] = "Fila $i: La columna A (CLAVE) es obligatoria.";
            continue;
        }

        $sql = "
            SELECT AR.ARTICULO_ID AS ID, CLAVE_ARTICULO, AR.NOMBRE, SEGUIMIENTO
            FROM ARTICULOS AR
            LEFT JOIN (
                SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID 
                FROM CLAVES_ARTICULOS 
                WHERE ROL_CLAVE_ART_ID = 17
            ) X ON X.ARTICULO_ID = AR.ARTICULO_ID
            WHERE CLAVE_ARTICULO = '".$clave."'
        ";
        $result = $db->query($sql);

        if ($result <> 0) {
            $seguimiento = strtoupper(trim($result[0]['SEGUIMIENTO']));
            $valido = true;

            if ($seguimiento == 'L') {
                if ($lote == "" || $caducidad == "") {
                    $errores[] = "Fila $i: LOTE y CADUCIDAD son obligatorios para artículos con seguimiento 'L'.";
                    $valido = false;
                } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $caducidad)) {
                    $errores[] = "Fila $i: CADUCIDAD debe tener formato YYYY-MM-DD.";
                    $valido = false;
                }
            } elseif ($seguimiento == 'S') {
                if ($serie == "") {
                    $errores[] = "Fila $i: SERIE es obligatoria para artículos con seguimiento 'S'.";
                    $valido = false;
                }
            }

            $advertencia = "";


            if ($seguimiento == 'L') {
                if ($lote == "" || $caducidad == "") {
                    $advertencia = "Fila $i: LOTE y CADUCIDAD son obligatorios para seguimiento 'L'.";
                } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $caducidad)) {
                    $advertencia = "Fila $i: CADUCIDAD debe tener formato YYYY-MM-DD.";
                }
            } elseif ($seguimiento == 'S') {
                if ($serie == "") {
                    $advertencia = "Fila $i: SERIE es obligatoria para seguimiento 'S'.";
                }
            }


            $encontrados[] = [
                'id' => $result[0]['ID'],
                'clave' => $clave,
                'nombre' => $result[0]['NOMBRE'],
                'seguimiento' => $seguimiento,
                'cantidad' => $cantidadNum,
                'lote' => $lote,
                'caducidad' => $caducidad,
                'serie' => $serie,
                'advertencia' => $advertencia
            ];

        } else {
            $no_encontrados[] = ['fila' => $i, 'clave' => $clave];
        }
    }

    $db->close();

    // Limpiar cualquier output buffer y enviar solo JSON
    ob_end_clean();
    echo json_encode([
        'encontrados' => $encontrados,
        'no_encontrados' => $no_encontrados,
        'errores' => $errores
    ]);

} catch (Exception $e) {
    // Limpiar cualquier output buffer y enviar solo JSON
    ob_end_clean();
    echo json_encode(['error' => $e->getMessage()]);
}
exit;