<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

$traspasoid = isset($_POST['traspasoid']) ? (int)$_POST['traspasoid'] : 0;
if ($traspasoid <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID de traspaso inválido']);
    exit;
}

$db = new FirebirdConnection();

// 1. Obtener la información del Traspaso y el Folio del destino
$sqlInfo = "
    SELECT 
        t.TRASPASO_FECHACREACION,
        t.TRASPASO_AALMACENID,
        a.ALMACEN_FOLIO
    FROM AMPAR_HIS_TRASPASO t
    LEFT JOIN AMPAR_HIS_ALMACEN a ON a.ALMACEN_ID = t.TRASPASO_AALMACENID
    WHERE t.TRASPASO_ID = ?
";
$info = $db->query($sqlInfo, [$traspasoid]);
if (!$info || count($info) === 0) {
    echo json_encode(['ok' => false, 'error' => 'Traspaso no encontrado']);
    exit;
}

$fechaCreacion = $info[0]['TRASPASO_FECHACREACION'];
$destinoFolio = $info[0]['ALMACEN_FOLIO'];
$aalmacenId = (int)$info[0]['TRASPASO_AALMACENID'];

// Obtener datos para reglas de excepción (como en get.requisitos.php)
$sqlReglas = "
    SELECT 
        A1.ALMACEN_TIPOALMACEN AS TIPO_DE,
        A1.ALMACEN_ALMACEN_MS AS PADRE_DE,
        A2.ALMACEN_TIPOALMACEN AS TIPO_A
    FROM AMPAR_HIS_TRASPASO t
    LEFT JOIN AMPAR_HIS_ALMACEN A1 ON A1.ALMACEN_ID = t.TRASPASO_DEALMACENID
    LEFT JOIN AMPAR_HIS_ALMACEN A2 ON A2.ALMACEN_ID = t.TRASPASO_AALMACENID
    WHERE t.TRASPASO_ID = ?
";
$infoReglas = $db->query($sqlReglas, [$traspasoid]);
if ($infoReglas && count($infoReglas) > 0) {
    $isMaletaDe = ((int)$infoReglas[0]['TIPO_DE'] === 3);
    $isMaletaA = ((int)$infoReglas[0]['TIPO_A'] === 3);
    $padreDe = (int)$infoReglas[0]['PADRE_DE'];

    // Regla: Maleta a Almacén (exime escaneo)
    if ($isMaletaDe && !$isMaletaA) {
        // Excepción: Si el destino es Material Caducado (ID 10)
        if ($aalmacenId === 10) {
            // Solo eximir (y permitir) si viene de Saltillo CEDIS (ID 1)
            if ($padreDe === 1) {
                echo json_encode(['ok' => true, 'bypassed' => true]);
                exit;
            }
        } else {
            // Para cualquier otro traspaso entre maleta y almacén
            echo json_encode(['ok' => true, 'bypassed' => true]);
            exit;
        }
    }
}

if (empty($destinoFolio)) {
    echo json_encode(['ok' => false, 'error' => 'El destino no tiene un folio válido asignado']);
    exit;
}

// 2. Buscar escaneos para este folio después de la fecha de creación del traspaso (agregando un pequeño margen o simplemente >)
// Se hace un query para traer los escaneos recientes
$sqlEscaneos = "
    SELECT ESCANEO_ID, ESCANEO_FECHA, ESCANEO_DETALLES
    FROM AMPAR_HIS_ESCANEO
    WHERE ESCANEO_FOLIO = ? 
      AND ESCANEO_FECHA > ?
    ORDER BY ESCANEO_FECHA DESC
";
$escaneos = $db->query($sqlEscaneos, [$destinoFolio, $fechaCreacion]);

if (!$escaneos || count($escaneos) === 0) {
    echo json_encode(['ok' => false, 'error' => 'No se encontró un escaneo reciente para el folio destino (' . $destinoFolio . ') posterior a la creación del traspaso.']);
    exit;
}

// 3. Obtener los EPCs y Folios de los artículos del traspaso
$sqlDetalles = "
    SELECT st.STOCK_TEMPETIQUETA, st.STOCK_FOLIO
    FROM AMPAR_HIS_TRASPASODET td
    JOIN AMPAR_HIS_STOCK st ON st.STOCK_ID = td.TRASPASODET_STOCKID
    WHERE td.TRASPASODET_TRASPASOID = ?
";
$detalles = $db->query($sqlDetalles, [$traspasoid]);
if (!$detalles || count($detalles) === 0) {
    echo json_encode(['ok' => false, 'error' => 'El traspaso no tiene artículos asignados']);
    exit;
}

$epcsTraspaso = [];
foreach ($detalles as $d) {
    $epcsTraspaso[] = [
        'epc' => $d['STOCK_TEMPETIQUETA'] ?? '',
        'folio' => $d['STOCK_FOLIO'] ?? ''
    ];
}

// 4. Verificar si alguno de los escaneos contiene todos los artículos
$escaneoValido = false;
foreach ($escaneos as $esc) {
    $detallesJson = json_decode($esc['ESCANEO_DETALLES'], true);
    if ($detallesJson && is_array($detallesJson)) {
        // En detallesJson puede haber 'correctos', 'extras', 'faltantes'
        // Extraemos todos los epcs/folios escaneados de 'correctos' y 'extras'
        $epcsEscaneados = [];
        if (isset($detallesJson['correctos']) && is_array($detallesJson['correctos'])) {
            foreach ($detallesJson['correctos'] as $c) {
                $epcsEscaneados[] = is_array($c) ? ($c['EPC'] ?? '') : $c;
            }
        }
        if (isset($detallesJson['extras']) && is_array($detallesJson['extras'])) {
            foreach ($detallesJson['extras'] as $e) {
                $epcsEscaneados[] = is_array($e) ? ($e['EPC'] ?? '') : $e;
            }
        }

        $todosEncontrados = true;
        foreach ($epcsTraspaso as $req) {
            // Verificamos si el EPC o el FOLIO escaneado está en la lista de escaneados
            $found = false;
            $reqEpc = $req['epc'];
            $reqFolio = $req['folio'];
            
            foreach ($epcsEscaneados as $escStr) {
                if ($reqEpc !== '' && (trim($reqEpc) === trim($escStr) || strpos($escStr, $reqEpc) !== false)) {
                    $found = true;
                    break;
                }
                if ($reqFolio !== '' && (trim($reqFolio) === trim($escStr) || strpos($escStr, $reqFolio) !== false)) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $todosEncontrados = false;
                break;
            }
        }

        if ($todosEncontrados) {
            $escaneoValido = true;
            break;
        }
    }
}

if ($escaneoValido) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Hay escaneos recientes, pero ninguno contiene todos los artículos de este traspaso.']);
}
