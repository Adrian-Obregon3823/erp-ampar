<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");

$articuloId = isset($_GET['articulo_id']) ? (int)$_GET['articulo_id'] : 0;
$almacenId = isset($_GET['almacen_id']) ? $_GET['almacen_id'] : 'global';
$esMaleta = isset($_GET['es_maleta']) ? (int)$_GET['es_maleta'] : 0;

if ($articuloId == 0) {
    echo "<tbody><tr><td colspan='6' class='text-center text-muted'>ID de artículo no válido.</td></tr></tbody>";
    exit;
}

$db = new FirebirdConnection(true);

$where = "ST.STOCK_STOCKSTATUSID IN (1, 2) AND ST.STOCK_ARTICULOID = " . $articuloId;

if ($almacenId === 'sin_almacen') {
    $where .= " AND (ST.STOCK_ALMACENIDACTUAL IS NULL OR ST.STOCK_ALMACENIDACTUAL = 0)";
} elseif ($almacenId !== 'global' && $almacenId != '') {
    $where .= " AND ST.STOCK_ALMACENIDACTUAL = " . (int)$almacenId;
}

$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
if (!empty($busqueda)) {
    // Escapar búsqueda
    $busqSql = str_replace("'", "''", $busqueda);
    $where .= " AND (UPPER(ST.STOCK_LOTE) LIKE UPPER('%{$busqSql}%') OR UPPER(ST.STOCK_SERIE) LIKE UPPER('%{$busqSql}%') OR UPPER(ST.STOCK_FOLIO) LIKE UPPER('%{$busqSql}%') OR UPPER(X.CLAVE_ARTICULO) LIKE UPPER('%{$busqSql}%') OR UPPER(AR.NOMBRE) LIKE UPPER('%{$busqSql}%'))";
}

$sql = "
    SELECT 
        ST.STOCK_SERIE,
        ST.STOCK_LOTE,
        ST.STOCK_CADUCIDAD,
        AL.ALMACEN_NOMBRE,
        AL.ALMACEN_TIPOALMACEN,
        PA.ALMACEN_NOMBRE AS PADRE_ALMACEN_NOMBRE,
        ST.STOCK_ALMACENIDACTUAL,
        SUM(CASE WHEN ST.STOCK_STOCKSTATUSID = 1 THEN 1 ELSE 0 END) AS CANTIDAD_FISICA,
        SUM(CASE WHEN ST.STOCK_STOCKSTATUSID = 2 THEN 1 ELSE 0 END) AS CANTIDAD_TRANSITO,
        COUNT(ST.STOCK_ID) AS CANTIDAD_TOTAL
    FROM AMPAR_HIS_STOCK ST
    LEFT JOIN AMPAR_HIS_ALMACEN AL ON AL.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
    LEFT JOIN AMPAR_HIS_ALMACEN PA ON PA.ALMACEN_ID = AL.ALMACEN_ALMACEN_MS
    LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
    LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM CLAVES_ARTICULOS WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
    WHERE " . $where . "
    GROUP BY 
        ST.STOCK_SERIE,
        ST.STOCK_LOTE,
        ST.STOCK_CADUCIDAD,
        AL.ALMACEN_NOMBRE,
        AL.ALMACEN_TIPOALMACEN,
        PA.ALMACEN_NOMBRE,
        ST.STOCK_ALMACENIDACTUAL
    ORDER BY ST.STOCK_CADUCIDAD ASC
";

$result = $db->query($sql);
$db->close();

$tieneSerie = false;
$tieneLote = false;
$groupedRows = [];
if (is_array($result) && count($result) > 0) {
    foreach ($result as $row) {
        if (!empty($row['STOCK_SERIE'])) {
            $tieneSerie = true;
        }
        if (!empty($row['STOCK_LOTE']) && trim(strtoupper($row['STOCK_LOTE'])) !== 'SIN LOTE') {
            $tieneLote = true;
        }
        
        $key = md5(($row['STOCK_SERIE'] ?? '') . '|' . ($row['STOCK_LOTE'] ?? '') . '|' . ($row['STOCK_CADUCIDAD'] ?? ''));
        
        if (!isset($groupedRows[$key])) {
            $groupedRows[$key] = $row;
            $groupedRows[$key]['ALMACENES_COUNTS'] = [];
        } else {
            $groupedRows[$key]['CANTIDAD_TOTAL'] += $row['CANTIDAD_TOTAL'];
        }
        
        if ($row['ALMACEN_TIPOALMACEN'] == 3) {
            $almacen_padre = !empty($row['PADRE_ALMACEN_NOMBRE']) ? $row['PADRE_ALMACEN_NOMBRE'] : 'N/A';
            $maleta = !empty($row['ALMACEN_NOMBRE']) ? $row['ALMACEN_NOMBRE'] : 'N/A';
            $ubicacion = "{$almacen_padre} <br> <small class='text-muted'>Maleta: {$maleta}</small>";
        } else {
            $ubicacion = !empty($row['ALMACEN_NOMBRE']) ? $row['ALMACEN_NOMBRE'] : 'N/A';
        }
        
        if (!isset($groupedRows[$key]['ALMACENES_COUNTS'][$ubicacion])) {
            $groupedRows[$key]['ALMACENES_COUNTS'][$ubicacion] = 0;
        }
        $groupedRows[$key]['ALMACENES_COUNTS'][$ubicacion] += $row['CANTIDAD_TOTAL'];
    }
}
$rows = array_values($groupedRows);

// Render Header
$thead = '<thead>';
if ($esMaleta) {
    $thead .= '<tr>';
    if ($tieneSerie) {
        $thead .= '<th>Serie</th>';
    }
    if ($tieneLote) {
        $thead .= '<th>Lote</th>';
    }
    $thead .= '<th>Caducidad</th>';
    $thead .= '<th class="text-center">Cantidad</th>';
    $thead .= '</tr>';
} else {
    $thead .= '<tr style="border-bottom: 1px solid #e0e0e0;">';
    if ($tieneSerie) {
        $thead .= '<th class="text-dark text-uppercase pb-2" style="font-size: 11px; letter-spacing: 0.5px; padding-left: 15px;">Serie</th>';
    }
    if ($tieneLote) {
        $thead .= '<th class="text-dark text-uppercase pb-2" style="font-size: 11px; letter-spacing: 0.5px; padding-left: 15px;">Lote</th>';
    }
    $thead .= '<th class="text-dark text-uppercase pb-2" style="font-size: 11px; letter-spacing: 0.5px;">Caducidad</th>';
    $thead .= '<th class="text-dark text-uppercase pb-2" style="font-size: 11px; letter-spacing: 0.5px;">Almacén</th>';
    $thead .= '<th class="text-dark text-uppercase pb-2 text-center" style="font-size: 11px; letter-spacing: 0.5px;">Cantidad</th>';
    $thead .= '</tr>';
}
$thead .= '</thead>';

// Render Body
$tbody = '<tbody>';
if (count($rows) > 0) {
    $hoy = strtotime(date('Y-m-d'));
    foreach ($rows as $row) {
        $caducidad = htmlspecialchars(!empty($row['STOCK_CADUCIDAD']) ? $row['STOCK_CADUCIDAD'] : 'N/A');

        $badgeClass = 'badge-secondary';
        $textClass = 'text-white';

        if (!empty($row['STOCK_CADUCIDAD'])) {
            $fechaCad = strtotime($row['STOCK_CADUCIDAD']);
            $diffDays = ($fechaCad - $hoy) / (86400); // 60*60*24

            if ($diffDays <= 30) {
                $badgeClass = 'badge-danger';
            } elseif ($diffDays <= 90) {
                $badgeClass = 'badge-warning';
                $textClass = 'text-dark';
            } else {
                $badgeClass = 'badge-success';
            }
        }

        arsort($row['ALMACENES_COUNTS']);
        $majorityAlmacen = key($row['ALMACENES_COUNTS']);
        $numAlmacenes = count($row['ALMACENES_COUNTS']);
        if ($numAlmacenes > 1) {
            $ubicacion = "{$majorityAlmacen} <span class='text-muted' style='font-size:10px;'>(+" . ($numAlmacenes - 1) . " más)</span>";
        } else {
            $ubicacion = "{$majorityAlmacen}";
        }

        $serieData = htmlspecialchars(!empty($row['STOCK_SERIE']) ? $row['STOCK_SERIE'] : '');
        $loteData = htmlspecialchars(!empty($row['STOCK_LOTE']) ? $row['STOCK_LOTE'] : '');
        $cadData = htmlspecialchars(!empty($row['STOCK_CADUCIDAD']) ? $row['STOCK_CADUCIDAD'] : '');

        $hasSerie = $tieneSerie ? 1 : 0;
        $hasLote = $tieneLote ? 1 : 0;
        
        $tbody .= "<tr class='fila-lote' style='cursor:pointer;' data-articulo='{$articuloId}' data-almacen='{$almacenId}' data-esmaleta='{$esMaleta}' data-serie='{$serieData}' data-lote='{$loteData}' data-caducidad='{$cadData}' data-tieneserie='{$hasSerie}' data-tienelote='{$hasLote}'>";
        if ($tieneSerie) {
            $serie = htmlspecialchars(!empty($row['STOCK_SERIE']) ? $row['STOCK_SERIE'] : 'N/A');
            $tbody .= "  <td class='text-dark border-0 py-2' style='padding-left: 15px;'><b>{$serie}</b></td>";
        }
        if ($tieneLote) {
            $lote = htmlspecialchars(!empty($row['STOCK_LOTE']) ? $row['STOCK_LOTE'] : 'SIN LOTE');
            $tbody .= "  <td class='text-dark border-0 py-2' style='padding-left: 15px;'><b>{$lote}</b></td>";
        }
        $tbody .= "  <td class='text-dark border-0 py-2'>{$caducidad}</td>";
        if (!$esMaleta) {
            $tbody .= "  <td class='text-dark border-0 py-2'>{$ubicacion}</td>";
        }
        $tbody .= "  <td class='text-center border-0 py-2'><span class='badge {$badgeClass} {$textClass}' style='font-size:12px;'><b>{$row['CANTIDAD_TOTAL']}</b></span></td>";
        $tbody .= "</tr>";
    }
} else {
    $columnasMaleta = 2;
    if ($tieneSerie) $columnasMaleta++;
    if ($tieneLote) $columnasMaleta++;

    $columnasNormal = 3;
    if ($tieneSerie) $columnasNormal++;
    if ($tieneLote) $columnasNormal++;

    $colspan = $esMaleta ? $columnasMaleta : $columnasNormal;
    $tbody .= "<tr><td colspan='{$colspan}' class='border-0 text-center py-2 text-muted'>No se encontraron lotes activos para este artículo.</td></tr>";
}
$tbody .= '</tbody>';

echo $thead . $tbody;
