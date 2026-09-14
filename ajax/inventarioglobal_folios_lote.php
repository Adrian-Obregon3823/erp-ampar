<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");

$articuloId = isset($_GET['articulo_id']) ? (int)$_GET['articulo_id'] : 0;
$almacenId = isset($_GET['almacen_id']) ? $_GET['almacen_id'] : 'global';
$esMaleta = isset($_GET['es_maleta']) ? (int)$_GET['es_maleta'] : 0;
$serie = isset($_GET['serie']) ? $_GET['serie'] : '';
$lote = isset($_GET['lote']) ? $_GET['lote'] : '';
$caducidad = isset($_GET['caducidad']) ? $_GET['caducidad'] : '';

if ($articuloId == 0) {
    echo "<tr><td colspan='6' class='text-center text-muted'>ID de artículo no válido.</td></tr>";
    exit;
}

$db = new FirebirdConnection(true);

$where = "ST.STOCK_STOCKSTATUSID IN (1, 2) AND ST.STOCK_ARTICULOID = " . $articuloId;

if ($almacenId === 'NA' || $almacenId === 'sin_almacen') {
    $where .= " AND (ST.STOCK_ALMACENIDACTUAL IS NULL OR ST.STOCK_ALMACENIDACTUAL = 0)";
} elseif ($almacenId !== 'global' && $almacenId != '') {
    $where .= " AND ST.STOCK_ALMACENIDACTUAL = " . (int)$almacenId;
}

if ($serie !== '' && $serie !== 'N/A') {
    $where .= " AND ST.STOCK_SERIE = '" . str_replace("'", "''", $serie) . "'";
} else {
    $where .= " AND (ST.STOCK_SERIE IS NULL OR ST.STOCK_SERIE = '')";
}

if ($lote !== '' && $lote !== 'SIN LOTE') {
    $where .= " AND ST.STOCK_LOTE = '" . str_replace("'", "''", $lote) . "'";
} else {
    $where .= " AND (ST.STOCK_LOTE IS NULL OR ST.STOCK_LOTE = '' OR UPPER(ST.STOCK_LOTE) = 'SIN LOTE')";
}

if ($caducidad !== '' && $caducidad !== 'N/A') {
    $where .= " AND ST.STOCK_CADUCIDAD = '" . str_replace("'", "''", $caducidad) . "'";
} else {
    $where .= " AND ST.STOCK_CADUCIDAD IS NULL";
}

$sql = "
    SELECT 
        ST.STOCK_FOLIO,
        X.CLAVE_ARTICULO,
        AR.NOMBRE,
        ST.STOCK_CADUCIDAD,
        AL.ALMACEN_NOMBRE,
        SU.NOMBRE AS SUCURSAL_NOMBRE,
        ST.STOCK_STOCKSTATUSID,
        STS.STOKSTATUS_NOMBRE AS STOCK_STOCKSTATUSNOMBRE
    FROM AMPAR_HIS_STOCK ST
    LEFT JOIN AMPAR_HIS_ALMACEN AL ON AL.ALMACEN_ID = ST.STOCK_ALMACENIDACTUAL
    LEFT JOIN AMPAR_CAT_SUCURSALES SU ON SU.SUCURSAL_ID = AL.ALMACEN_SUCURSAL_MS
    LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = ST.STOCK_ARTICULOID
    LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
    LEFT JOIN AMPAR_CONF_STOKSTATUS STS ON STS.STOKSTATUS_ID = ST.STOCK_STOCKSTATUSID
    WHERE " . $where . "
    ORDER BY ST.STOCK_FOLIO ASC
";

$result = $db->query($sql);
$db->close();

$tieneSerie = isset($_GET['tiene_serie']) && $_GET['tiene_serie'] == 1;
$tieneLote = isset($_GET['tiene_lote']) && $_GET['tiene_lote'] == 1;

$columnas = [];
if ($tieneSerie) $columnas[] = 'serie';
if ($tieneLote) $columnas[] = 'lote';
$columnas[] = 'caducidad';
if (!$esMaleta) $columnas[] = 'almacen';
$columnas[] = 'cantidad';

if (is_array($result) && count($result) > 0) {
    foreach ($result as $p) {
        $estado = $p['STOCK_STOCKSTATUSID'];
        $badge = 'badge-light border text-dark';
        if ($estado == 1) $badge = 'badge-success text-white';
        elseif ($estado == 2) $badge = 'badge-warning text-dark';
        elseif ($estado == 3) $badge = 'badge-secondary text-white';

        echo "<tr class='detalle-folios-item' style='background-color: #f8fbff;'>";
        
        $colIndex = 0;
        foreach ($columnas as $colName) {
            if ($colIndex == 0) {
                echo "<td style='padding-left:35px; border-top: 1px dashed #dce4ec;'><i class='mdi mdi-subdirectory-arrow-right text-muted mr-1'></i> <strong>{$p['STOCK_FOLIO']}</strong></td>";
            } elseif ($colName == 'almacen') {
                echo "<td style='border-top: 1px dashed #dce4ec;'><span class='text-muted' style='font-size:12px;'>{$p['ALMACEN_NOMBRE']}</span></td>";
            } elseif ($colName == 'cantidad') {
                echo "<td class='text-center' style='border-top: 1px dashed #dce4ec;'><span class='badge {$badge}' style='font-size:11px; padding: 4px 10px; font-weight: 600;'>{$p['STOCK_STOCKSTATUSNOMBRE']}</span></td>";
            } else {
                echo "<td style='border-top: 1px dashed #dce4ec;'></td>";
            }
            $colIndex++;
        }
        echo "</tr>";
    }
} else {
    $colspan = count($columnas);
    echo "<tr class='detalle-folios-item' style='background-color: #f8fbff;'><td colspan='{$colspan}' class='text-center text-muted py-2' style='border-top: 1px dashed #dce4ec;'>No se encontraron folios asociados a este lote.</td></tr>";
}
?>
