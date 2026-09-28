<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
include_once("../class/inventarioglobal.php");
$invGlobal = new InventarioGlobal();
$almcls = new almacenes();
$listaResult = $almcls->getcatalogoalmacenes("", "1,2", "17");

$esAdmin = !empty($GLOBALS['isAdmin']);
$listaAlmacenes = [];
$almacenes_ids = [];

if (!$esAdmin) {
    $db_alm = new FirebirdConnection();
    $sqlAlm = "SELECT USUARIOSALMACENES_ALMACENID FROM AMPAR_CAT_USUARIOSALMACENES WHERE USUARIOSALMACENES_USUARIOID = " . (int)($_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0);
    $resAlm = $db_alm->query($sqlAlm);
    if ($resAlm && is_array($resAlm)) {
        foreach ($resAlm as $row) {
            $almacenes_ids[] = (int)$row['USUARIOSALMACENES_ALMACENID'];
        }
    }
    $db_alm->close();
}

$allowedIds = [];
$firstAllowed = null;

if ($listaResult && is_array($listaResult)) {
    foreach ($listaResult as $al) {
        if (!$esAdmin) {
            if (!in_array((int)$al['ID'], $almacenes_ids)) {
                 continue;
            }
        }
        $listaAlmacenes[] = $al;
        $allowedIds[] = (string)$al['ID'];
        if ($firstAllowed === null) $firstAllowed = (string)$al['ID'];
    }
}

$listaGrupos = $invGlobal->getGruposLineas();

// Filtros
$almacen_id = $_GET['almacen_id'] ?? ($_POST['almacen_id'] ?? 'global');

if (!$esAdmin) {
    if (!in_array($almacen_id, $allowedIds)) {
        $almacen_id = $firstAllowed ?? 'global';
    }
}
$grupo_linea_id = $_GET['grupo_linea_id'] ?? ($_POST['grupo_linea_id'] ?? '');
$division_id = $_GET['division_id'] ?? ($_POST['division_id'] ?? '');
$categoria_id = $_GET['categoria_id'] ?? ($_POST['categoria_id'] ?? '');
$busqueda = $_GET['busqueda'] ?? ($_POST['busqueda'] ?? '');

// Dinámicas basadas en selección
$listaDivisiones = $invGlobal->getDivisiones($grupo_linea_id);
$listaCategorias = $invGlobal->getCategorias($grupo_linea_id, $division_id);

// KPIs globales
$kpis = $invGlobal->getKPIs($grupo_linea_id, $division_id, $categoria_id);
$total_unidades = $kpis[0]['TOTAL_UNIDADES'] ?? 0;
$total_articulos = $kpis[0]['TOTAL_ARTICULOS_UNIFICADOS'] ?? 0;

// Variables de datos
$almacenSeleccionado = null;
$maletas = [];
$articulosAlmacen = [];
$resumen = null;
$esGlobal = ($almacen_id === 'global');

if ($esGlobal) {
    // Vista Global: todos los artículos y maletas
    $maletas = $invGlobal->getMaletasGlobal($grupo_linea_id, $division_id, $categoria_id);
    $articulosAlmacen = $invGlobal->getArticulosGlobal($busqueda, $grupo_linea_id, $division_id, $categoria_id);
    $articulosSinStock = $invGlobal->getArticulosSinStock('global', $busqueda, $grupo_linea_id, $division_id, $categoria_id);
    $resumenData = $invGlobal->getResumenGlobal($grupo_linea_id, $division_id, $categoria_id);
    $resumen = $resumenData[0] ?? null;
} elseif ($almacen_id != '') {
    // Vista por almacén específico
    if ($almacen_id === 'sin_almacen') {
        $almacenSeleccionado = ['ALMACEN_NOMBRE' => 'Sin Almacén', 'ALMACEN_ID' => 'sin_almacen'];
    } else {
        $almacenInfo = $almcls->getinfoalmacen($almacen_id);
        $almacenSeleccionado = $almacenInfo[0] ?? null;
    }
    $maletas = $invGlobal->getMaletasByAlmacen($almacen_id, $grupo_linea_id, $division_id, $categoria_id);
    $articulosAlmacen = $invGlobal->getArticulosByAlmacen($almacen_id, $busqueda, $grupo_linea_id, $division_id, $categoria_id);
    $articulosSinStock = $invGlobal->getArticulosSinStock($almacen_id, $busqueda, $grupo_linea_id, $division_id, $categoria_id);
    $resumenData = $invGlobal->getResumenAlmacen($almacen_id, $grupo_linea_id, $division_id, $categoria_id);
    $resumen = $resumenData[0] ?? null;
}

$tieneSeleccion = ($almacen_id != '');
$nombreVista = $esGlobal ? 'Global (Todos los Almacenes)' : htmlspecialchars($almacenSeleccionado['ALMACEN_NOMBRE'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include_once("../includes/head.php"); ?>
    <style>
        .kpi-card {
            border-left: 4px solid #1f3bb3;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 20px;
            background: white;
        }

        .kpi-title {
            font-size: 13px;
            color: #8d8d8d;
            font-weight: bold;
            text-transform: uppercase;
        }

        .kpi-value {
            font-size: 24px;
            font-weight: 900;
            color: #1f3bb3;
        }

        .maleta-card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .maleta-card:hover {
            border-color: #1f3bb3;
            box-shadow: 0 4px 12px rgba(31, 59, 179, 0.15);
            transform: translateY(-2px);
        }

        .maleta-card.active {
            border-color: #1f3bb3;
            background: #f0f4ff;
        }

        .maleta-count {
            font-size: 20px;
            font-weight: 800;
            color: #1f3bb3;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            border-bottom: 2px solid #1f3bb3;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }

        .maleta-almacen-badge {
            font-size: 10px;
            background: #e3f2fd;
            color: #1565c0;
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
            margin-top: 2px;
        }

        .maleta-familia-badge {
            font-size: 10px;
            background: #f3e5f5;
            color: #7b1fa2;
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
            margin-top: 2px;
        }

        .tipo-maleta-card {
            border: 1px solid #d9e2ff;
            border-radius: 12px;
            background: #fbfcff;
        }

        .tipo-maleta-header {
            border-bottom: 1px solid #e9efff;
            padding: 12px 16px;
            background: linear-gradient(180deg, #f5f8ff 0%, #ffffff 100%);
            border-radius: 12px 12px 0 0;
        }

        .tipo-maleta-title {
            font-size: 14px;
            font-weight: 700;
            color: #1f3bb3;
            margin: 0;
        }

        .tipo-maleta-body {
            padding: 14px;
        }

        /* ─── Barra de búsqueda mejorada ─── */
        .search-row {
            background: #f8f9fa;
            border-top: 1px solid #eee;
            padding: 16px 20px;
            margin: 0 -20px -20px -20px;
            border-radius: 0 0 8px 8px;
        }

        .search-input-group {
            position: relative;
            flex: 1;
        }

        .search-input-group .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 18px;
        }

        .search-input-group input {
            padding-left: 38px !important;
            border-radius: 8px !important;
            border: 1px solid #ddd !important;
        }

        .search-input-group input:focus {
            border-color: #1f3bb3 !important;
            box-shadow: 0 0 0 2px rgba(31, 59, 179, 0.15) !important;
        }

        /* Expansor icon */
        tr.fila-articulo {
            cursor: pointer;
            transition: background-color 0.2s;
        }

        tr.fila-articulo:hover {
            background-color: #f8f9fa !important;
        }

        .status-circle {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .status-red {
            background-color: #f44336;
        }

        .status-yellow {
            background-color: #ffeb3b;
        }

        .status-green {
            background-color: #4caf50;
        }

        .status-gray {
            background-color: #e0e0e0;
        }

        .maleta-detalle-wrap {
            border: 1px solid #e8ecff;
            border-radius: 10px;
            background: #fbfcff;
            padding: 10px;
        }

        .maleta-detalle-resumen .badge {
            font-size: 11px;
            font-weight: 600;
            border-radius: 14px;
            padding: 6px 10px;
        }

        .maleta-detalle-table thead th {
            background: #f3f6ff;
            color: #344054;
            border-bottom: 1px solid #dde5ff;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .maleta-detalle-table td {
            vertical-align: middle;
            border-top: 1px solid #edf1ff;
        }

        .maleta-articulo-nombre {
            color: #1f2937;
            line-height: 1.25;
            word-break: break-word;
        }

        .maleta-detalle-vacio {
            background: #fafafa;
            border: 1px dashed #d6dbe8;
            border-radius: 8px;
        }

        .maleta-progress-track {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 8px;
        }

        .maleta-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #1f3bb3 0%, #52cdff 100%);
            border-radius: 999px;
            transition: width .3s ease;
        }

        .maleta-progress-label {
            font-size: 11px;
            color: #4b5563;
            margin-top: 4px;
            display: block;
        }

        .maleta-detalle-table tbody tr.fila-articulo-maleta {
            cursor: pointer;
        }

        .maleta-detalle-table tbody tr.fila-articulo-maleta:hover {
            background: #f8fbff;
        }

        .lotes-maleta-wrap {
            padding: 10px 14px 14px;
            background: #f8fbff;
            border-top: 1px solid #e5ecff;
        }

        .lotes-maleta-table thead th {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .3px;
            color: #475467;
            border-bottom: 1px solid #dde5ff;
        }

        .lotes-maleta-table td {
            border-top: 1px solid #edf1ff;
        }

        /* Mobile Cards Styles */
        .mobile-articulo-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 12px;
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            cursor: pointer;
            transition: all 0.2s;
        }

        .mobile-articulo-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.08);
        }

        .mobile-articulo-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 8px;
        }

        .mobile-articulo-sku {
            font-weight: 700;
            color: #1e293b;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
        }

        .mobile-articulo-desc {
            font-size: 0.9rem;
            color: #475569;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .mobile-articulo-body {
            display: flex;
            justify-content: space-between;
            background: #f8fafc;
            padding: 10px;
            border-radius: 8px;
        }

        .mobile-articulo-stat {
            text-align: center;
        }

        .mobile-articulo-stat span {
            display: block;
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .mobile-articulo-stat strong {
            font-size: 1.1rem;
            color: #0f172a;
        }
    </style>
</head>

<body>
    <div class="container-scroller">
        <?php include_once("../includes/header.php"); ?>
        <div class="container-fluid page-body-wrapper">
            <?php include_once("../includes/menu.sidebar.php"); ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="home-tab">
                                <h3>Inventario Global - Almacenes</h3>
                                <p class="text-muted">Seleccione un almacén para ver sus maletas y artículos.</p>
                            </div>

                            <!-- FILTROS -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <form method="GET" action="inventarioglobal.php" id="formFiltros">
                                        <div class="row align-items-end">
                                            <!-- ALMACÉN -->
                                            <div class="col-md-2 mb-3 mb-md-0">
                                                <label><strong>Almacén</strong></label>
                                                <select name="almacen_id" class="form-control" onchange="this.form.submit()">
                                                    <?php if ($esAdmin): ?>
                                                        <option value="global" <?= ($almacen_id === 'global') ? 'selected' : '' ?>>Global</option>
                                                        <option value="sin_almacen" <?= ($almacen_id === 'sin_almacen') ? 'selected' : '' ?>>Sin almacén</option>
                                                    <?php endif; ?>
                                                    <?php
                                                    foreach ($listaAlmacenes as $alm) {
                                                        $selected = ($almacen_id == $alm['ID']) ? 'selected' : '';
                                                        echo "<option value='" . $alm['ID'] . "' $selected>" . htmlspecialchars($alm['NOMBRE']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <!-- GRUPO MÉDICO -->
                                            <div class="col-md-3 mb-3 mb-md-0">
                                                <label><strong>Familia</strong></label>
                                                <select name="grupo_linea_id" class="form-control" onchange="this.form.submit()">
                                                    <option value="">Todas las Familias</option>
                                                    <?php
                                                    if (is_array($listaGrupos) && count($listaGrupos) > 0) {
                                                        foreach ($listaGrupos as $grp) {
                                                            $selected = ($grupo_linea_id == $grp['GRUPO_LINEA_ID']) ? 'selected' : '';
                                                            echo "<option value='" . $grp['GRUPO_LINEA_ID'] . "' $selected>" . htmlspecialchars($grp['NOMBRE']) . "</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <!-- DIVISION -->
                                            <div class="col-md-2 mb-3 mb-md-0">
                                                <label><strong>División</strong></label>
                                                <select name="division_id" class="form-control" onchange="this.form.submit()">
                                                    <option value="">Todas las Divisiones</option>
                                                    <?php
                                                    if (is_array($listaDivisiones) && count($listaDivisiones) > 0) {
                                                        foreach ($listaDivisiones as $div) {
                                                            $selected = ($division_id == $div['DIVISION_ID']) ? 'selected' : '';
                                                            echo "<option value='" . $div['DIVISION_ID'] . "' $selected>" . htmlspecialchars($div['NOMBRE']) . "</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <!-- CATEGORIA -->
                                            <div class="col-md-3 mb-3 mb-md-0">
                                                <label><strong>Categoría</strong></label>
                                                <select name="categoria_id" class="form-control" onchange="this.form.submit()">
                                                    <option value="">Todas las Categorías</option>
                                                    <?php
                                                    if (is_array($listaCategorias) && count($listaCategorias) > 0) {
                                                        foreach ($listaCategorias as $cat) {
                                                            $selected = ($categoria_id == $cat['LINEA_ARTICULO_ID']) ? 'selected' : '';
                                                            echo "<option value='" . $cat['LINEA_ARTICULO_ID'] . "' $selected>" . htmlspecialchars($cat['NOMBRE']) . "</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <!-- LIMPIAR -->
                                            <div class="col-md-2 mb-3 mb-md-0">
                                                <a href="inventarioglobal.php" class="btn btn-outline-primary w-100 mt-md-4">Limpiar</a>
                                            </div>
                                        </div>

                                        <!-- BARRA DE BÚSQUEDA -->
                                        <div class="search-row mt-3">
                                            <label class="text-muted mb-2" style="font-size:13px;"><strong>Buscar Artículo</strong> (Referencia o Descripción)</label>
                                            <div class="d-flex flex-column flex-sm-row">
                                                <div class="input-group mb-2 mb-sm-0 w-100" style="flex-grow: 1;">
                                                    <input type="text" name="busqueda" class="form-control" placeholder="Escriba para buscar..." value="<?= htmlspecialchars($busqueda) ?>" style="height: auto; padding: 10px 15px;">
                                                    <div class="input-group-append">
                                                        <button type="submit" class="btn btn-primary text-white" style="padding-left: 15px; padding-right: 15px; border-top-right-radius: 4px; border-bottom-right-radius: 4px;">
                                                            <i class="mdi mdi-magnify"></i> <span class="d-none d-sm-inline ml-1">Buscar</span>
                                                        </button>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn btn-success text-white ml-sm-2" onclick="exportarExcel()" style="padding-left: 20px; padding-right: 20px; border-radius: 4px; white-space: nowrap;">
                                                    <i class="mdi mdi-file-excel"></i> Exportar
                                                </button>
                                            </div>

                                        </div>
                                    </form>
                                </div>
                            </div>

                            <?php if (!$tieneSeleccion): ?>
                                <!-- SIN ALMACÉN SELECCIONADO: MOSTRAR KPIs GLOBALES -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="kpi-card">
                                            <div class="kpi-title">Total Unidades en Sistema</div>
                                            <div class="kpi-value"><?= number_format($total_unidades) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="kpi-card" style="border-left-color: #52cdff;">
                                            <div class="kpi-title">Variedad de Artículos</div>
                                            <div class="kpi-value"><?= number_format($total_articulos) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="kpi-card" style="border-left-color: #28a745;">
                                            <div class="kpi-title">Total Equipo Capital</div>
                                            <div class="kpi-value"><?= number_format($kpis[0]['TOTAL_EQUIPO_CAPITAL'] ?? 0) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center py-5">
                                    <i class="mdi mdi-domain" style="font-size: 80px; color: #ddd;"></i>
                                    <h4 class="text-muted mt-3">Seleccione un almacén para ver el desglose</h4>
                                    <p class="text-muted">Verá las maletas asignadas y los artículos de cada ubicación.</p>
                                </div>

                            <?php else: ?>
                                <!-- ALMACÉN SELECCIONADO O GLOBAL -->

                                <!-- KPIs del almacén -->
                                <div class="row">
                                    <div class="col-6 col-md-4 col-lg-2 mb-3">
                                        <div class="kpi-card">
                                            <div class="kpi-title">Unidades Totales</div>
                                            <div class="kpi-value"><?= number_format($resumen['TOTAL_UNIDADES'] ?? 0) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-2 mb-3">
                                        <div class="kpi-card" style="border-left-color: #8b5cf6;">
                                            <div class="kpi-title">Unidades <?= $esGlobal ? 'en Almacenes' : htmlspecialchars($almacenSeleccionado['ALMACEN_NOMBRE'] ?? '') ?></div>
                                            <div class="kpi-value"><?= number_format($resumen['UNIDADES_ALMACEN'] ?? 0) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-2 mb-3">
                                        <div class="kpi-card" style="border-left-color: #f43f5e;">
                                            <div class="kpi-title">Unidades en Maletas</div>
                                            <div class="kpi-value"><?= number_format($resumen['UNIDADES_MALETAS'] ?? 0) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-2 mb-3">
                                        <div class="kpi-card" style="border-left-color: #52cdff;">
                                            <div class="kpi-title">Claves de Art. Activos</div>
                                            <div class="kpi-value"><?= number_format($resumen['TOTAL_ARTICULOS'] ?? 0) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-2 mb-3">
                                        <div class="kpi-card" style="border-left-color: #fbd66e;">
                                            <div class="kpi-title">Maletas Asignadas</div>
                                            <div class="kpi-value"><?= number_format($resumen['TOTAL_MALETAS'] ?? 0) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-2 mb-3">
                                        <div class="kpi-card" style="border-left-color: #28a745;">
                                            <div class="kpi-title">Equipo Capital</div>
                                            <div class="kpi-value"><?= number_format($resumen['TOTAL_EQUIPO_CAPITAL'] ?? 0) ?></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- MALETAS Bloque -->
                                <div class="section-title mt-3">
                                    <i class="mdi mdi-medical-bag"></i> Maletas <?= $esGlobal ? '- Vista Global' : 'de "' . $nombreVista . '"' ?>
                                </div>

                                <?php if (is_array($maletas) && count($maletas) > 0): ?>
                                    <?php
                                    $tiposMaletaFiltro = [];
                                    foreach ($maletas as $m) {
                                        $tipo = trim($m['TIPOMALETA_NOMBRE'] ?? '');
                                        if ($tipo === '') {
                                            $tipo = 'Sin tipo de maleta';
                                        }

                                        $tipoNormalizado = preg_replace('/\s+/', ' ', mb_strtoupper($tipo, 'UTF-8'));
                                        $tipoKey = 'tm_' . md5($tipoNormalizado);
                                        if (!isset($tiposMaletaFiltro[$tipoKey])) {
                                            $tiposMaletaFiltro[$tipoKey] = $tipo;
                                        }
                                    }

                                    asort($tiposMaletaFiltro, SORT_NATURAL | SORT_FLAG_CASE);

                                    usort($maletas, function ($a, $b) {
                                        $cadA = !empty($a['PROXIMA_CADUCIDAD']) ? strtotime($a['PROXIMA_CADUCIDAD']) : null;
                                        $cadB = !empty($b['PROXIMA_CADUCIDAD']) ? strtotime($b['PROXIMA_CADUCIDAD']) : null;

                                        if ($cadA !== null && $cadB !== null && $cadA !== $cadB) {
                                            return $cadA <=> $cadB;
                                        }

                                        if ($cadA !== null && $cadB === null) {
                                            return -1;
                                        }

                                        if ($cadA === null && $cadB !== null) {
                                            return 1;
                                        }

                                        $tipoA = trim($a['TIPOMALETA_NOMBRE'] ?? 'Sin tipo de maleta');
                                        $tipoB = trim($b['TIPOMALETA_NOMBRE'] ?? 'Sin tipo de maleta');
                                        $cmpTipo = strnatcasecmp($tipoA, $tipoB);
                                        if ($cmpTipo !== 0) {
                                            return $cmpTipo;
                                        }

                                        return strnatcasecmp($a['ALMACEN_NOMBRE'] ?? '', $b['ALMACEN_NOMBRE'] ?? '');
                                    });
                                    ?>

                                    <div class="card mb-3">
                                        <div class="card-body py-3">
                                            <div class="row align-items-end">
                                                <div class="col-md-6">
                                                    <label for="filtroTipoMaletaInventario"><strong>Tipo de maleta</strong></label>
                                                    <select id="filtroTipoMaletaInventario" class="form-control">
                                                        <option value="">Global</option>
                                                        <?php foreach ($tiposMaletaFiltro as $tipoKey => $tipoLabel): ?>
                                                            <option value="<?= htmlspecialchars($tipoKey) ?>"><?= htmlspecialchars($tipoLabel) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 text-md-right mt-2 mt-md-0">
                                                    <small class="text-muted" id="contadorMaletasFiltradas">Mostrando <?= count($maletas) ?> de <?= count($maletas) ?> maletas</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-4" id="listadoMaletasContinuo">
                                        <?php foreach ($maletas as $m): ?>
                                            <?php
                                            $tipoItem = trim($m['TIPOMALETA_NOMBRE'] ?? '');
                                            if ($tipoItem === '') {
                                                $tipoItem = 'Sin tipo de maleta';
                                            }
                                            $porcentaje = (float)($m['PORCENTAJE'] ?? 0);
                                            if ($porcentaje < 0) {
                                                $porcentaje = 0;
                                            }
                                            if ($porcentaje > 100) {
                                                $porcentaje = 100;
                                            }
                                            $estadoBadgeClass = 'badge';
                                            $estadoBadgeStyle = 'background-color: #' . ($m['STATUS_COLOR'] ?? 'ccc') . '; color: white;';
                                            $estadoBadgeTitle = htmlspecialchars($m['STATUS_NOMBRE'] ?? '');
                                            if (!empty($m['PROXIMA_CADUCIDAD'])) {
                                                $fechaCad = strtotime($m['PROXIMA_CADUCIDAD']);
                                                if ($fechaCad !== false) {
                                                    $hoyMaleta = strtotime(date('Y-m-d'));
                                                    $diffDays = ($fechaCad - $hoyMaleta) / (60 * 60 * 24);

                                                    if ($diffDays <= 30) {
                                                        $estadoBadgeStyle = 'background-color: #dc3545; color: white;';
                                                    } elseif ($diffDays <= 90) {
                                                        $estadoBadgeStyle = 'background-color: #ffc107; color: #212529;';
                                                    } else {
                                                        $estadoBadgeStyle = 'background-color: #28a745; color: white;';
                                                    }
                                                    $estadoBadgeTitle .= ' | Próxima caducidad: ' . $m['PROXIMA_CADUCIDAD'];
                                                }
                                            }
                                            $tipoItemNormalizado = preg_replace('/\s+/', ' ', mb_strtoupper($tipoItem, 'UTF-8'));
                                            $tipoItemKey = 'tm_' . md5($tipoItemNormalizado);
                                            ?>
                                            <div class="col-md-3 mb-3 maleta-item" data-tipo-maleta="<?= htmlspecialchars($tipoItemKey) ?>">
                                                <div class="maleta-card card p-3" data-maleta-id="<?= $m['ALMACEN_ID'] ?>" onclick="cargarMaleta(this, <?= $m['ALMACEN_ID'] ?>)">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong class="text-dark"><?= htmlspecialchars($m['ALMACEN_NOMBRE']) ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?= htmlspecialchars($tipoItem) ?></small>
                                                            <br>
                                                            <small><span class="<?= $estadoBadgeClass ?>" style="<?= $estadoBadgeStyle ?>" title="<?= $estadoBadgeTitle ?>"><?= htmlspecialchars($m['STATUS_NOMBRE'] ?? '') ?></span></small>
                                                            <?php if (isset($m['FAMILIA_NOMBRE']) && !empty($m['FAMILIA_NOMBRE'])): ?>
                                                                <br><span class="maleta-familia-badge"><?= htmlspecialchars($m['FAMILIA_NOMBRE']) ?></span>
                                                            <?php endif; ?>
                                                            <?php if ($esGlobal && !empty($m['PADRE_ALMACEN_NOMBRE'])): ?>
                                                                <br><span class="maleta-almacen-badge"><i class="mdi mdi-domain"></i> <?= htmlspecialchars($m['PADRE_ALMACEN_NOMBRE']) ?></span>
                                                            <?php endif; ?>
                                                            <div class="maleta-progress-track">
                                                                <div class="maleta-progress-fill" style="width: <?= $porcentaje ?>%;"></div>
                                                            </div>
                                                            <span class="maleta-progress-label">Progreso: <?= number_format($porcentaje, 1) ?>%</span>
                                                        </div>
                                                        <div class="text-center">
                                                            <div class="maleta-count"><?= $m['TOTAL_ARTICULOS'] ?? 0 ?></div>
                                                            <small class="text-muted">uds.</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <?= $esGlobal ? 'No se encontraron maletas en el sistema.' : 'Este almacén no tiene maletas asignadas.' ?>
                                        <?= ($grupo_linea_id != '' || $categoria_id != '') ? ' con los filtros seleccionados.' : '' ?>
                                    </div>
                                <?php endif; ?>

                                <?php
                                $equiposCapitales = [];
                                $articulosNormales = [];
                                if (is_array($articulosAlmacen) && count($articulosAlmacen) > 0) {
                                    foreach ($articulosAlmacen as $row) {
                                        if (stripos($row['UNIDAD_VENTA'] ?? '', 'UNIDAD DE SERVICIO') !== false) {
                                            // Se ignora aquí porque se cargarán TODOS más abajo
                                        } else {
                                            $articulosNormales[] = $row;
                                        }
                                    }
                                }
                                $articulosAlmacen = $articulosNormales;

                                // Cargar TODOS los equipos capitales (tengan stock o no)
                                $eqDirecto = $invGlobal->getEquipoCapitalDirecto($almacen_id, $busqueda, $grupo_linea_id, $division_id, $categoria_id);
                                if (is_array($eqDirecto)) {
                                    $equiposCapitales = $eqDirecto;
                                }

                                $hoy = strtotime(date('Y-m-d'));
                                ?>

                                <!-- EQUIPO CAPITAL -->
                                <div class="section-title mt-4">
                                    <i class="mdi mdi-monitor-dashboard"></i> Equipo Capital <?= $esGlobal ? '- Vista Global' : 'de "' . $nombreVista . '"' ?>
                                </div>
                                <div id="equipos-container">
                                <?php if (count($equiposCapitales) > 0): ?>
                                    <?php
                                    // Construir árbol: Familia > Categoría > Artículos
                                    $arbolEquipos = [];
                                    foreach ($equiposCapitales as $row) {
                                        $fam = $row['FAMILIA_NOMBRE'] ?? 'Sin Familia';
                                        $cat = $row['CATEGORIA_NOMBRE'] ?? 'Sin Categoría';
                                        if (empty(trim($fam))) $fam = 'Sin Familia';
                                        if (empty(trim($cat))) $cat = 'Sin Categoría';

                                        if (!isset($arbolEquipos[$fam])) {
                                            $arbolEquipos[$fam] = ['items' => 0, 'unidades' => 0, 'categorias' => []];
                                        }
                                        if (!isset($arbolEquipos[$fam]['categorias'][$cat])) {
                                            $arbolEquipos[$fam]['categorias'][$cat] = ['items' => 0, 'unidades' => 0, 'rows' => []];
                                        }
                                        $arbolEquipos[$fam]['items']++;
                                        $arbolEquipos[$fam]['unidades'] += (int)$row['CANTIDAD_TOTAL'];
                                        $arbolEquipos[$fam]['categorias'][$cat]['items']++;
                                        $arbolEquipos[$fam]['categorias'][$cat]['unidades'] += (int)$row['CANTIDAD_TOTAL'];
                                        $arbolEquipos[$fam]['categorias'][$cat]['rows'][] = $row;
                                    }

                                    $eqFamIdx = 0;
                                    foreach ($arbolEquipos as $famNombre => $famData):
                                        $famId = 'eq_fam_' . $eqFamIdx;
                                        $eqFamIdx++;
                                    ?>
                                        <!-- NIVEL 1: FAMILIA -->
                                        <div class="card mb-3" style="border: 1px solid #d0d5dd; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); overflow: hidden;">
                                            <div class="card-header bg-white border-bottom-0 py-3 d-flex align-items-center justify-content-between" style="cursor: pointer;" onclick="document.getElementById('<?= $famId ?>').style.display = document.getElementById('<?= $famId ?>').style.display === 'none' ? 'block' : 'none'; this.querySelector('.fam-chevron').classList.toggle('mdi-chevron-down'); this.querySelector('.fam-chevron').classList.toggle('mdi-chevron-up');">
                                                <div class="d-flex align-items-center">
                                                    <h5 class="m-0 font-weight-bold text-dark" style="text-transform: uppercase; font-size: 15px;"><?= htmlspecialchars($famNombre) ?></h5>
                                                    <span class="badge ml-3" style="background: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600; padding: 5px 12px; border-radius: 20px;"><?= $famData['items'] ?> artículos</span>
                                                    <span class="badge ml-2" style="background: #f0fdf4; color: #16a34a; font-size: 11px; font-weight: 600; padding: 5px 12px; border-radius: 20px;"><?= number_format($famData['unidades']) ?> unidades</span>
                                                </div>
                                                <i class="mdi mdi-chevron-down fam-chevron text-muted" style="font-size: 24px; transition: transform 0.2s;"></i>
                                            </div>
                                            <div id="<?= $famId ?>" style="display: none;">
                                                <?php
                                                $eqCatIdx = 0;
                                                foreach ($famData['categorias'] as $catNombre => $catData):
                                                    $catId = $famId . '_cat_' . $eqCatIdx;
                                                    $eqCatIdx++;
                                                ?>
                                                    <!-- NIVEL 2: CATEGORÍA -->
                                                    <div class="inv-tree-cat">
                                                        <div class="d-flex align-items-center justify-content-between py-2 px-3" style="cursor: pointer; background: #f3f4f6; border-radius: 8px 8px 0 0;" onclick="document.getElementById('<?= $catId ?>').style.display = document.getElementById('<?= $catId ?>').style.display === 'none' ? 'block' : 'none'; this.querySelector('.cat-chevron').classList.toggle('mdi-chevron-down'); this.querySelector('.cat-chevron').classList.toggle('mdi-chevron-up');">
                                                            <div class="d-flex align-items-center">
                                                                <i class="mdi mdi-tag-multiple mr-2" style="font-size: 18px; color: #6366f1;"></i>
                                                                <span class="font-weight-bold text-dark" style="font-size: 14px;"><?= htmlspecialchars($catNombre) ?></span>
                                                                <span class="badge ml-3" style="background: #e2e8f0; color: #334155; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 16px;"><?= $catData['items'] ?> artículos</span>
                                                                <span class="badge ml-2" style="background: #e2e8f0; color: #334155; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 16px;"><?= number_format($catData['unidades']) ?> uds</span>
                                                            </div>
                                                            <i class="mdi mdi-chevron-down cat-chevron text-muted" style="font-size: 22px;"></i>
                                                        </div>
                                                        <div id="<?= $catId ?>" style="display: none; padding-top: 12px;">
                                                            <!-- NIVEL 3: TABLA DE ARTÍCULOS -->
                                                            <div class="table-responsive d-none d-md-block" style="border-top: 1px solid #e5e7eb;">
                                                                <table class="table table-striped table-hover mb-0 tablaCategoria">
                                                                    <thead class="bg-light">
                                                                        <tr class="text-muted" style="font-size: 11px; text-transform: uppercase;">
                                                                            <th>FOLIO / REF</th>
                                                                            <th>UBICACIÓN</th>
                                                                            <th>MARCA</th>
                                                                            <th>EQUIPO / ARTÍCULO</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($catData['rows'] as $row): ?>
                                                                            <tr data-articulo-id="<?= htmlspecialchars($row['EQUIPOCAPITAL_ID']) ?>">
                                                                                <td style="vertical-align: top;">
                                                                                    <b><?= htmlspecialchars($row['FOLIO'] ?? '') ?></b><br>
                                                                                    <small class="text-muted">Ref: <?= htmlspecialchars($row['REFERENCIA'] ?? 'N/A') ?></small>
                                                                                </td>
                                                                                <td style="vertical-align: top;">
                                                                                    <span class="badge badge-light border text-dark"><?= htmlspecialchars($row['UBICACION'] ?? 'N/A') ?></span>
                                                                                </td>
                                                                                <td style="vertical-align: top;">
                                                                                    <span class="text-dark"><?= htmlspecialchars($row['MARCA'] ?? 'N/A') ?></span>
                                                                                </td>
                                                                                <td class="maleta-articulo-nombre" style="vertical-align: top;">
                                                                                    <b><?= htmlspecialchars($row['ARTICULO_NOMBRE'] ?? '') ?></b><br>
                                                                                    <small class="text-muted">SKU: <?= htmlspecialchars($row['SKU'] ?? '') ?></small>
                                                                                    
                                                                                    <?php if(isset($row['COMPONENTES']) && count($row['COMPONENTES']) > 0): ?>
                                                                                        <?php $compId = 'comp_desk_' . htmlspecialchars($row['EQUIPOCAPITAL_ID']); ?>
                                                                                        <div class="mt-2" style="background: #f8f9fa; border-radius: 6px; border: 1px solid #e2e8f0; overflow: hidden;">
                                                                                            <div style="font-size: 11px; font-weight: bold; color: #475569; padding: 6px 8px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; transition: background 0.2s;" onmouseover="this.style.background='#f1f5f9';" onmouseout="this.style.background='transparent';" onclick="document.getElementById('<?= $compId ?>').style.display = document.getElementById('<?= $compId ?>').style.display === 'none' ? 'block' : 'none'; this.querySelector('.mdi-chevron-down').classList.toggle('mdi-chevron-up');">
                                                                                                <span><i class="mdi mdi-puzzle-outline text-info mr-1" style="font-size: 13px;"></i> Ver Componentes (<?= count($row['COMPONENTES']) ?>)</span>
                                                                                                <i class="mdi mdi-chevron-down text-muted" style="font-size: 14px; transition: transform 0.2s;"></i>
                                                                                            </div>
                                                                                            <div id="<?= $compId ?>" style="display: none; padding: 8px; border-top: 1px solid #e2e8f0; background: #fff;">
                                                                                                <ul style="list-style: none; padding: 0; margin: 0; font-size: 11px;">
                                                                                                    <?php foreach($row['COMPONENTES'] as $comp): ?>
                                                                                                        <li style="border-bottom: 1px dashed #e2e8f0; padding: 6px 0;">
                                                                                                            <span class="badge badge-info text-white mr-1" style="font-size: 10px;"><?= htmlspecialchars($comp['CANTIDAD']) ?></span>
                                                                                                            <b class="text-dark"><?= htmlspecialchars($comp['NOMBRE']) ?></b> 
                                                                                                            <span class="text-muted ml-1">S/N: <?= htmlspecialchars($comp['SERIE'] ?: 'N/A') ?></span>
                                                                                                        </li>
                                                                                                    <?php endforeach; ?>
                                                                                                </ul>
                                                                                            </div>
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>

                                                            <!-- Mobile View (Cards) para Equipo Capital -->
                                                            <div class="d-block d-md-none mt-2 px-2 pb-2">
                                                                <?php foreach ($catData['rows'] as $row): ?>
                                                                    <div class="mobile-articulo-card" data-articulo-id="<?= htmlspecialchars($row['EQUIPOCAPITAL_ID']) ?>">
                                                                        <div class="mobile-articulo-header">
                                                                            <div class="mobile-articulo-sku">
                                                                                <b><?= htmlspecialchars($row['FOLIO'] ?? '') ?></b>
                                                                            </div>
                                                                            <span class="badge badge-light border text-dark"><?= htmlspecialchars($row['UBICACION'] ?? 'N/A') ?></span>
                                                                        </div>
                                                                        <div class="mobile-articulo-desc mt-1">
                                                                            <b><?= htmlspecialchars($row['ARTICULO_NOMBRE'] ?? '') ?></b>
                                                                        </div>
                                                                        <div class="text-muted mt-1" style="font-size: 11px;">
                                                                            Ref: <?= htmlspecialchars($row['REFERENCIA'] ?? 'N/A') ?> | Marca: <?= htmlspecialchars($row['MARCA'] ?? 'N/A') ?>
                                                                        </div>
                                                                        
                                                                        <?php if(isset($row['COMPONENTES']) && count($row['COMPONENTES']) > 0): ?>
                                                                            <?php $compIdMob = 'comp_mob_' . htmlspecialchars($row['EQUIPOCAPITAL_ID']); ?>
                                                                            <div class="mt-2" style="background: #f8f9fa; border-radius: 6px; border: 1px solid #e2e8f0; overflow: hidden; font-size: 11px;">
                                                                                <div style="font-weight: bold; color: #475569; padding: 6px 8px; cursor: pointer; display: flex; align-items: center; justify-content: space-between;" onclick="document.getElementById('<?= $compIdMob ?>').style.display = document.getElementById('<?= $compIdMob ?>').style.display === 'none' ? 'block' : 'none'; this.querySelector('.mdi-chevron-down').classList.toggle('mdi-chevron-up');">
                                                                                    <span><i class="mdi mdi-puzzle-outline text-info mr-1" style="font-size: 13px;"></i> Componentes (<?= count($row['COMPONENTES']) ?>)</span>
                                                                                    <i class="mdi mdi-chevron-down text-muted" style="font-size: 14px; transition: transform 0.2s;"></i>
                                                                                </div>
                                                                                <div id="<?= $compIdMob ?>" style="display: none; padding: 8px; border-top: 1px solid #e2e8f0; background: #fff;">
                                                                                    <?php foreach($row['COMPONENTES'] as $comp): ?>
                                                                                        <div class="mt-1 text-muted" style="border-bottom: 1px dashed #e2e8f0; padding-bottom: 4px; margin-bottom: 4px;">
                                                                                            <span class="badge badge-info text-white mr-1" style="font-size: 9px; padding: 2px 4px;"><?= htmlspecialchars($comp['CANTIDAD']) ?></span>
                                                                                            <b class="text-dark"><?= htmlspecialchars($comp['NOMBRE']) ?></b><br>
                                                                                            <small>S/N: <?= htmlspecialchars($comp['SERIE'] ?: 'N/A') ?></small>
                                                                                        </div>
                                                                                    <?php endforeach; ?>
                                                                                </div>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-secondary text-center py-4">
                                        <p class="mb-0 text-muted">No se encontraron equipos capitales en el sistema.</p>
                                    </div>
                                <?php endif; ?>
                                </div>

                                <!-- ARTÍCULOS OBloque movido arriba (stock directo) -->
                                <div class="section-title mt-3">
                                    <i class="mdi mdi-package-variant-closed"></i> Artículos <?= $esGlobal ? '- Vista Global' : 'en Almacén "' . $nombreVista . '"' ?>
                                </div>
                                <div id="articulos-container">
                                    <?php
                                    if (is_array($articulosAlmacen) && count($articulosAlmacen) > 0) {

                                        // Construir árbol: Familia > División > Categoría > Artículos
                                        $arbolFamilias = [];
                                        foreach ($articulosAlmacen as $row) {
                                            $fam = $row['FAMILIA_NOMBRE'] ?? 'Sin Familia';
                                            $div = $row['DIVISION_NOMBRE'] ?? 'Sin División';
                                            $cat = $row['CATEGORIA_NOMBRE'] ?? 'Sin Categoría';
                                            if (empty(trim($fam))) $fam = 'Sin Familia';
                                            if (empty(trim($div))) $div = 'Sin División';
                                            if (empty(trim($cat))) $cat = 'Sin Categoría';

                                            if (!isset($arbolFamilias[$fam])) {
                                                $arbolFamilias[$fam] = ['items' => 0, 'unidades' => 0, 'divisiones' => []];
                                            }
                                            if (!isset($arbolFamilias[$fam]['divisiones'][$div])) {
                                                $arbolFamilias[$fam]['divisiones'][$div] = ['items' => 0, 'unidades' => 0, 'categorias' => []];
                                            }
                                            if (!isset($arbolFamilias[$fam]['divisiones'][$div]['categorias'][$cat])) {
                                                $arbolFamilias[$fam]['divisiones'][$div]['categorias'][$cat] = ['items' => 0, 'unidades' => 0, 'rows' => []];
                                            }
                                            $arbolFamilias[$fam]['items']++;
                                            $arbolFamilias[$fam]['unidades'] += (int)$row['CANTIDAD_TOTAL'];
                                            $arbolFamilias[$fam]['divisiones'][$div]['items']++;
                                            $arbolFamilias[$fam]['divisiones'][$div]['unidades'] += (int)$row['CANTIDAD_TOTAL'];
                                            $arbolFamilias[$fam]['divisiones'][$div]['categorias'][$cat]['items']++;
                                            $arbolFamilias[$fam]['divisiones'][$div]['categorias'][$cat]['unidades'] += (int)$row['CANTIDAD_TOTAL'];
                                            $arbolFamilias[$fam]['divisiones'][$div]['categorias'][$cat]['rows'][] = $row;
                                        }

                                        $famIdx = 0;
                                        foreach ($arbolFamilias as $famNombre => $famData):
                                            $famId = 'fam_' . $famIdx;
                                            $famIdx++;
                                    ?>
                                            <!-- NIVEL 1: FAMILIA -->
                                            <div class="card mb-3" style="border: 1px solid #d0d5dd; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); overflow: hidden;">
                                                <div class="card-header bg-white border-bottom-0 py-3 d-flex align-items-center justify-content-between" style="cursor: pointer;" onclick="document.getElementById('<?= $famId ?>').style.display = document.getElementById('<?= $famId ?>').style.display === 'none' ? 'block' : 'none'; this.querySelector('.fam-chevron').classList.toggle('mdi-chevron-down'); this.querySelector('.fam-chevron').classList.toggle('mdi-chevron-up');">
                                                    <div class="d-flex align-items-center">
                                                        <h5 class="m-0 font-weight-bold text-dark" style="text-transform: uppercase; font-size: 15px;"><?= htmlspecialchars($famNombre) ?></h5>
                                                        <span class="badge ml-3" style="background: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 600; padding: 5px 12px; border-radius: 20px;"><?= $famData['items'] ?> artículos</span>
                                                        <span class="badge ml-2" style="background: #f0fdf4; color: #16a34a; font-size: 11px; font-weight: 600; padding: 5px 12px; border-radius: 20px;"><?= number_format($famData['unidades']) ?> unidades</span>
                                                    </div>
                                                    <i class="mdi mdi-chevron-down fam-chevron text-muted" style="font-size: 24px; transition: transform 0.2s;"></i>
                                                </div>
                                                <div id="<?= $famId ?>" style="display: none;">
                                                    <?php
                                                    $divIdx = 0;
                                                    foreach ($famData['divisiones'] as $divNombre => $divData):
                                                        $divId = $famId . '_div_' . $divIdx;
                                                        $divIdx++;
                                                    ?>
                                                        <!-- NIVEL 2: DIVISIÓN -->
                                                        <div class="inv-tree-div">
                                                            <div class="d-flex align-items-center justify-content-between py-2 px-3" style="cursor: pointer; background: #f3f4f6; border-radius: 8px 8px 0 0;" onclick="document.getElementById('<?= $divId ?>').style.display = document.getElementById('<?= $divId ?>').style.display === 'none' ? 'block' : 'none'; this.querySelector('.div-chevron').classList.toggle('mdi-chevron-down'); this.querySelector('.div-chevron').classList.toggle('mdi-chevron-up');">
                                                                <div class="d-flex align-items-center">
                                                                    <i class="mdi mdi-domain mr-2" style="font-size: 18px; color: #4f46e5;"></i>
                                                                    <span class="font-weight-bold text-dark" style="font-size: 14px;"><?= htmlspecialchars($divNombre) ?></span>
                                                                    <span class="badge ml-3" style="background: #e2e8f0; color: #334155; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 16px;"><?= $divData['items'] ?> artículos</span>
                                                                    <span class="badge ml-2" style="background: #e2e8f0; color: #334155; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 16px;"><?= number_format($divData['unidades']) ?> uds</span>
                                                                </div>
                                                                <i class="mdi mdi-chevron-down div-chevron text-muted" style="font-size: 22px;"></i>
                                                            </div>
                                                            <div id="<?= $divId ?>" style="display: none; padding-top: 12px;">
                                                                <?php
                                                                $catIdx = 0;
                                                                foreach ($divData['categorias'] as $catNombre => $catData):
                                                                    $catId = $divId . '_cat_' . $catIdx;
                                                                    $catIdx++;
                                                                ?>
                                                                    <!-- NIVEL 3: CATEGORÍA -->
                                                                    <div class="inv-tree-cat">
                                                                        <div class="d-flex align-items-center justify-content-between py-2 px-3" style="cursor: pointer;" onclick="document.getElementById('<?= $catId ?>').style.display = document.getElementById('<?= $catId ?>').style.display === 'none' ? 'block' : 'none'; this.querySelector('.cat-chevron').classList.toggle('mdi-chevron-down'); this.querySelector('.cat-chevron').classList.toggle('mdi-chevron-up');">
                                                                            <div class="d-flex align-items-center">
                                                                                <i class="mdi mdi-tag-multiple mr-2" style="font-size: 18px; color: #6366f1;"></i>
                                                                                <span class="font-weight-bold text-dark" style="font-size: 13.5px;"><?= htmlspecialchars($catNombre) ?></span>
                                                                                <span class="badge ml-2" style="background: #f1f5f9; color: #475569; font-size: 10.5px; font-weight: 600; padding: 3px 10px; border-radius: 14px; border: 1px solid #e2e8f0;"><?= $catData['items'] ?> artículos</span>
                                                                                <span class="badge ml-1" style="background: #f1f5f9; color: #475569; font-size: 10.5px; font-weight: 600; padding: 3px 10px; border-radius: 14px; border: 1px solid #e2e8f0;"><?= number_format($catData['unidades']) ?> uds</span>
                                                                            </div>
                                                                            <i class="mdi mdi-chevron-down cat-chevron text-muted" style="font-size: 20px;"></i>
                                                                        </div>
                                                                        <div id="<?= $catId ?>" style="display: none;">
                                                                            <!-- NIVEL 4: TABLA DE ARTÍCULOS -->
                                                                            <div class="table-responsive d-none d-md-block" style="border-top: 1px solid #e5e7eb;">
                                                                                <table class="table table-striped table-hover mb-0 tablaCategoria">
                                                                                    <thead class="bg-light">
                                                                                        <tr class="text-muted" style="font-size: 11px; text-transform: uppercase;">
                                                                                            <th>ESTADO</th>
                                                                                            <th>REFERENCIA</th>
                                                                                            <th>CLAVE SAT</th>
                                                                                            <th>DESCRIPCIÓN</th>
                                                                                            <th class="text-center">EXISTENCIA</th>
                                                                                            <th class="text-center">EN TRÁNSITO</th>
                                                                                            <th class="text-center">TOTAL</th>
                                                                                        </tr>
                                                                                    </thead>
                                                                                    <tbody>
                                                                                        <?php foreach ($catData['rows'] as $row):
                                                                                            $colorClass = 'status-gray';
                                                                                            $sortValue = '9999999999';
                                                                                            if (!empty($row['PROXIMA_CADUCIDAD'])) {
                                                                                                $fechaCad = strtotime($row['PROXIMA_CADUCIDAD']);
                                                                                                $sortValue = $fechaCad;
                                                                                                $diffDays = ($fechaCad - $hoy) / (60 * 60 * 24);
                                                                                                if ($diffDays <= 30) {
                                                                                                    $colorClass = 'status-red';
                                                                                                } elseif ($diffDays <= 90) {
                                                                                                    $colorClass = 'status-yellow';
                                                                                                } else {
                                                                                                    $colorClass = 'status-green';
                                                                                                }
                                                                                            }
                                                                                        ?>
                                                                                            <tr data-articulo-id="<?= htmlspecialchars($row['STOCK_ARTICULOID']) ?>" class="fila-articulo" title="Clic para ver lotes">
                                                                                                <td class="text-center" data-sort="<?= $sortValue ?>">
                                                                                                    <div class="status-circle <?= $colorClass ?>"></div>
                                                                                                </td>
                                                                                                <td><b><?= htmlspecialchars($row['SKU'] ?? '') ?></b></td>
                                                                                                <td><span class="badge badge-light border text-dark"><?= htmlspecialchars($row['CLAVE_SAT'] ?? 'N/A') ?></span></td>
                                                                                                <td><?= htmlspecialchars($row['ARTICULO_NOMBRE'] ?? '') ?></td>
                                                                                                <td class="text-center">
                                                                                                    <span class="badge badge-success text-white"><?= $row['CANTIDAD_FISICA'] ?></span>
                                                                                                </td>
                                                                                                <td class="text-center">
                                                                                                    <span class="badge <?= ($row['CANTIDAD_TRANSITO'] > 0) ? 'badge-warning text-dark' : 'badge-secondary text-white' ?>"><?= $row['CANTIDAD_TRANSITO'] ?></span>
                                                                                                </td>
                                                                                                <td class="text-center"><strong><?= $row['CANTIDAD_TOTAL'] ?></strong></td>
                                                                                            </tr>
                                                                                        <?php endforeach; ?>
                                                                                    </tbody>
                                                                                </table>
                                                                            </div>

                                                                            <!-- Mobile View (Cards) -->
                                                                            <div class="d-block d-md-none mt-2 px-2 pb-2">
                                                                                <?php foreach ($catData['rows'] as $row):
                                                                                    $colorClass = 'status-gray';
                                                                                    if (!empty($row['PROXIMA_CADUCIDAD'])) {
                                                                                        $fechaCad = strtotime($row['PROXIMA_CADUCIDAD']);
                                                                                        $diffDays = ($fechaCad - $hoy) / (60 * 60 * 24);
                                                                                        if ($diffDays <= 30) {
                                                                                            $colorClass = 'status-red';
                                                                                        } elseif ($diffDays <= 90) {
                                                                                            $colorClass = 'status-yellow';
                                                                                        } else {
                                                                                            $colorClass = 'status-green';
                                                                                        }
                                                                                    }
                                                                                ?>
                                                                                    <div class="mobile-articulo-card fila-articulo-mobile" data-articulo-id="<?= htmlspecialchars($row['STOCK_ARTICULOID']) ?>">
                                                                                        <div class="mobile-articulo-header">
                                                                                            <div class="mobile-articulo-sku">
                                                                                                <div class="status-circle <?= $colorClass ?> mr-2"></div>
                                                                                                <?= htmlspecialchars($row['SKU'] ?? '') ?>
                                                                                            </div>
                                                                                            <span class="badge badge-light border text-dark"><?= htmlspecialchars($row['CLAVE_SAT'] ?? 'N/A') ?></span>
                                                                                        </div>
                                                                                        <div class="mobile-articulo-desc">
                                                                                            <?= htmlspecialchars($row['ARTICULO_NOMBRE'] ?? '') ?>
                                                                                        </div>
                                                                                        <div class="mobile-articulo-body">
                                                                                            <div class="mobile-articulo-stat">
                                                                                                <span>Física</span>
                                                                                                <strong><span class="badge badge-success text-white" style="font-size: 0.9rem;"><?= $row['CANTIDAD_FISICA'] ?></span></strong>
                                                                                            </div>
                                                                                            <div class="mobile-articulo-stat">
                                                                                                <span>Tránsito</span>
                                                                                                <strong><span class="badge <?= ($row['CANTIDAD_TRANSITO'] > 0) ? 'badge-warning text-dark' : 'badge-secondary text-white' ?>" style="font-size: 0.9rem;"><?= $row['CANTIDAD_TRANSITO'] ?></span></strong>
                                                                                            </div>
                                                                                            <div class="mobile-articulo-stat">
                                                                                                <span>Total</span>
                                                                                                <strong style="font-size: 1.2rem;"><?= $row['CANTIDAD_TOTAL'] ?></strong>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                    <?php
                                        endforeach;
                                    } else {
                                        echo "<div class='card mb-4'><div class='card-body text-center py-4 text-muted'>Sin artículos " . ($esGlobal ? "en el sistema" : "directos en este almacén") . ".</div></div>";
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>

                            <!-- ARTÍCULOS SIN STOCK -->
                            <div class="section-title mt-4 text-danger border-danger">
                                <i class="mdi mdi-alert-circle-outline text-danger"></i> Artículos sin stock (Existencia 0)
                            </div>
                            <div id="articulos-sinstock-container">
                                <?php
                                if (is_array($articulosSinStock) && count($articulosSinStock) > 0) {
                                    $arbolFamilias = [];
                                    foreach ($articulosSinStock as $row) {
                                        $fam = $row['FAMILIA_NOMBRE'] ?? 'Sin Familia';
                                        $div = $row['DIVISION_NOMBRE'] ?? 'Sin División';
                                        $cat = $row['CATEGORIA_NOMBRE'] ?? 'Sin Categoría';
                                        if (empty(trim($fam))) $fam = 'Sin Familia';
                                        if (empty(trim($div))) $div = 'Sin División';
                                        if (empty(trim($cat))) $cat = 'Sin Categoría';

                                        if (!isset($arbolFamilias[$fam])) $arbolFamilias[$fam] = ['items' => 0, 'divisiones' => []];
                                        if (!isset($arbolFamilias[$fam]['divisiones'][$div])) $arbolFamilias[$fam]['divisiones'][$div] = ['items' => 0, 'categorias' => []];
                                        if (!isset($arbolFamilias[$fam]['divisiones'][$div]['categorias'][$cat])) $arbolFamilias[$fam]['divisiones'][$div]['categorias'][$cat] = ['items' => 0, 'rows' => []];
                                        
                                        $arbolFamilias[$fam]['items']++;
                                        $arbolFamilias[$fam]['divisiones'][$div]['items']++;
                                        $arbolFamilias[$fam]['divisiones'][$div]['categorias'][$cat]['items']++;
                                        $arbolFamilias[$fam]['divisiones'][$div]['categorias'][$cat]['rows'][] = $row;
                                    }

                                    $famIdx = 0;
                                    foreach ($arbolFamilias as $famNombre => $famData):
                                        $famId = 'fam0_' . $famIdx;
                                        $famIdx++;
                                ?>
                                        <div class="card mb-3" style="border: 1px solid #fecaca; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.04); overflow: hidden;">
                                            <div class="card-header bg-white border-bottom-0 py-3 d-flex align-items-center justify-content-between" style="cursor: pointer;" onclick="document.getElementById('<?= $famId ?>').style.display = document.getElementById('<?= $famId ?>').style.display === 'none' ? 'block' : 'none'; this.querySelector('.fam-chevron').classList.toggle('mdi-chevron-down'); this.querySelector('.fam-chevron').classList.toggle('mdi-chevron-up');">
                                                <div class="d-flex align-items-center">
                                                    <h5 class="m-0 font-weight-bold text-dark" style="text-transform: uppercase; font-size: 15px;"><?= htmlspecialchars($famNombre) ?></h5>
                                                    <span class="badge ml-3" style="background: #fef2f2; color: #ef4444; font-size: 11px; font-weight: 600; padding: 5px 12px; border-radius: 20px;"><?= $famData['items'] ?> artículos sin stock</span>
                                                </div>
                                                <i class="mdi mdi-chevron-down fam-chevron text-muted" style="font-size: 24px; transition: transform 0.2s;"></i>
                                            </div>
                                            <div id="<?= $famId ?>" style="display: none;">
                                                <?php
                                                $divIdx = 0;
                                                foreach ($famData['divisiones'] as $divNombre => $divData):
                                                    $divId = $famId . '_div_' . $divIdx;
                                                    $divIdx++;
                                                ?>
                                                    <div class="inv-tree-div" style="border-color: #fca5a5;">
                                                        <div class="d-flex align-items-center justify-content-between py-2 px-3" style="cursor: pointer; background: #fef2f2; border-radius: 8px 8px 0 0;" onclick="document.getElementById('<?= $divId ?>').style.display = document.getElementById('<?= $divId ?>').style.display === 'none' ? 'block' : 'none'; this.querySelector('.div-chevron').classList.toggle('mdi-chevron-down'); this.querySelector('.div-chevron').classList.toggle('mdi-chevron-up');">
                                                            <div class="d-flex align-items-center">
                                                                <i class="mdi mdi-domain mr-2" style="font-size: 18px; color: #dc2626;"></i>
                                                                <span class="font-weight-bold text-dark" style="font-size: 14px;"><?= htmlspecialchars($divNombre) ?></span>
                                                                <span class="badge ml-3" style="background: #fee2e2; color: #b91c1c; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 16px;"><?= $divData['items'] ?> artículos sin stock</span>
                                                            </div>
                                                            <i class="mdi mdi-chevron-down div-chevron text-muted" style="font-size: 22px;"></i>
                                                        </div>
                                                        <div id="<?= $divId ?>" style="display: none; padding-top: 12px;">
                                                            <?php
                                                            $catIdx = 0;
                                                            foreach ($divData['categorias'] as $catNombre => $catData):
                                                                $catId = $divId . '_cat_' . $catIdx;
                                                                $catIdx++;
                                                            ?>
                                                                <div class="inv-tree-cat" style="border-color: #fecaca;">
                                                                    <div class="d-flex align-items-center justify-content-between py-2 px-3" style="cursor: pointer;" onclick="document.getElementById('<?= $catId ?>').style.display = document.getElementById('<?= $catId ?>').style.display === 'none' ? 'block' : 'none'; this.querySelector('.cat-chevron').classList.toggle('mdi-chevron-down'); this.querySelector('.cat-chevron').classList.toggle('mdi-chevron-up');">
                                                                        <div class="d-flex align-items-center">
                                                                            <i class="mdi mdi-tag-multiple mr-2" style="font-size: 18px; color: #ef4444;"></i>
                                                                            <span class="font-weight-bold text-dark" style="font-size: 13.5px;"><?= htmlspecialchars($catNombre) ?></span>
                                                                            <span class="badge ml-2" style="background: #fff5f5; color: #991b1b; font-size: 10.5px; font-weight: 600; padding: 3px 10px; border-radius: 14px; border: 1px solid #fecaca;"><?= $catData['items'] ?> artículos sin stock</span>
                                                                        </div>
                                                                        <i class="mdi mdi-chevron-down cat-chevron text-muted" style="font-size: 20px;"></i>
                                                                    </div>
                                                                    <div id="<?= $catId ?>" style="display: none;">
                                                                        <div class="table-responsive d-none d-md-block" style="border-top: 1px solid #fecaca;">
                                                                            <table class="table table-striped table-hover mb-0 tablaCategoria">
                                                                                <thead style="background-color: #fef2f2;">
                                                                                    <tr class="text-danger" style="font-size: 11px; text-transform: uppercase;">
                                                                                        <th>REFERENCIA</th>
                                                                                        <th>CLAVE SAT</th>
                                                                                        <th>DESCRIPCIÓN</th>
                                                                                        <th class="text-center">EXISTENCIA</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    <?php foreach ($catData['rows'] as $row): ?>
                                                                                        <tr class="text-muted">
                                                                                            <td><b><?= htmlspecialchars($row['SKU'] ?? '') ?></b></td>
                                                                                            <td><span class="badge badge-light border text-dark"><?= htmlspecialchars($row['CLAVE_SAT'] ?? 'N/A') ?></span></td>
                                                                                            <td><?= htmlspecialchars($row['ARTICULO_NOMBRE'] ?? '') ?></td>
                                                                                            <td class="text-center"><span class="badge badge-secondary text-white">0</span></td>
                                                                                        </tr>
                                                                                    <?php endforeach; ?>
                                                                                </tbody>
                                                                            </table>
                                                                        </div>

                                                                        <div class="d-block d-md-none mt-2 px-2 pb-2">
                                                                            <?php foreach ($catData['rows'] as $row): ?>
                                                                                <div class="mobile-articulo-card">
                                                                                    <div class="mobile-articulo-header bg-light">
                                                                                        <div class="mobile-articulo-sku">
                                                                                            <div class="status-circle status-gray mr-2"></div>
                                                                                            <span class="text-muted"><?= htmlspecialchars($row['SKU'] ?? '') ?></span>
                                                                                        </div>
                                                                                        <span class="badge badge-light border text-dark"><?= htmlspecialchars($row['CLAVE_SAT'] ?? 'N/A') ?></span>
                                                                                    </div>
                                                                                    <div class="mobile-articulo-desc text-muted">
                                                                                        <?= htmlspecialchars($row['ARTICULO_NOMBRE'] ?? '') ?>
                                                                                    </div>
                                                                                    <div class="mobile-articulo-body bg-light">
                                                                                        <div class="mobile-articulo-stat text-muted">
                                                                                            <span>Existencia</span>
                                                                                            <strong><span class="badge badge-secondary text-white" style="font-size: 0.9rem;">0</span></strong>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                <?php
                                    endforeach;
                                } else {
                                    echo "<div class='card mb-4'><div class='card-body text-center py-4 text-muted'>No hay artículos sin stock.</div></div>";
                                }
                                ?>
                            </div>

                        </div>
                    </div>
                </div>
                <?php include_once("../includes/footer.php"); ?>
            </div>
        </div>
    </div>
    <?php include_once("../includes/foot.php"); ?>

    <script>
        function exportarExcel() {
            var form = document.getElementById('formFiltros');
            var params = new URLSearchParams(new FormData(form)).toString();
            window.location.href = 'exportar_inventario_excel.php?' + params;
        }

        $(document).ready(function() {
            // Lógica de despliegue de lotes y caducidades (Dropdown rows de forma nativa)
            $(document).on('click', '.tablaCategoria tbody tr.fila-articulo', function() {
                var tr = $(this);
                var articuloId = tr.data('articulo-id');
                var almacenId = '<?= addslashes($almacen_id) ?>';

                // Si ya está abierto, lo cerramos
                if (tr.next().hasClass('detalle-lotes')) {
                    tr.next().remove();
                    tr.removeClass('shown-dropdown');
                    tr.css('background-color', '');
                    return;
                }

                // Cerrar otros abiertos (opcional, para mantener limpia la vista)
                $('.detalle-lotes').remove();
                $('tr.fila-articulo.shown-dropdown').removeClass('shown-dropdown').css('background-color', '');

                // Generar HTML contenedor sin bordes y con fondo integrado
                var childRow = $('<tr class="detalle-lotes"><td colspan="7" class="p-0 border-0" style="background-color: #f4f6f9;"></td></tr>');
                var childContainer = $('<div class="py-2 px-4 text-left"></div>');
                childContainer.html('<div class="text-center py-3 text-primary"><i class="mdi mdi-loading mdi-spin" style="font-size:20px;"></i> Leyendo lotes y caducidad...</div>');
                childRow.find('td').append(childContainer);

                tr.after(childRow);
                tr.addClass('shown-dropdown');
                tr.css('background-color', '#f4f6f9'); // Color indicador sin contornos fuertes

                $.ajax({
                    url: '../ajax/inventarioglobal_lotes_articulo.php',
                    type: 'GET',
                    cache: false,
                    data: {
                        articulo_id: articuloId, almacen_id: almacenId, busqueda: '<?= addslashes($busqueda) ?>' },
                    success: function(res) {
                        var resHTML = '<div class="table-responsive col-md-9 px-0 ml-3"><table class="table table-sm table-borderless mb-0" style="background-color: transparent;">' +
                            res +
                            '</table></div>';
                        childContainer.html(resHTML);
                    },
                    error: function() {
                        childContainer.html('<div class="alert alert-danger m-0 py-2"><i class="mdi mdi-alert-circle"></i> Ocurrió un error leyendo los lotes correspondientes al producto seleccionado.</div>');
                    }
                });
            });

            // Lógica de despliegue de lotes para mobile
            $(document).on('click', '.fila-articulo-mobile', function() {
                var card = $(this);
                var articuloId = card.data('articulo-id');
                var almacenId = '<?= addslashes($almacen_id) ?>';

                if (card.next().hasClass('detalle-lotes-mobile')) {
                    card.next().remove();
                    card.removeClass('shown-dropdown');
                    card.css('border-color', '');
                    return;
                }

                $('.detalle-lotes-mobile').remove();
                $('.fila-articulo-mobile.shown-dropdown').removeClass('shown-dropdown').css('border-color', '');

                var childContainer = $('<div class="detalle-lotes-mobile p-2 p-md-3 mb-3 border rounded shadow-sm" style="background-color: #f4f6f9; margin-top:-10px;"></div>');
                childContainer.html('<div class="text-center py-3 text-primary"><i class="mdi mdi-loading mdi-spin" style="font-size:20px;"></i> Leyendo lotes y caducidad...</div>');

                card.after(childContainer);
                card.addClass('shown-dropdown');
                card.css('border-color', '#1f3bb3');

                $.ajax({
                    url: '../ajax/inventarioglobal_lotes_articulo.php',
                    type: 'GET',
                    cache: false,
                    data: {
                        articulo_id: articuloId, almacen_id: almacenId, busqueda: '<?= addslashes($busqueda) ?>' },
                    success: function(res) {
                        var resHTML = '<div class="table-responsive px-0"><table class="table table-sm table-borderless mb-0 lotes-table-mobile" style="background-color: transparent;">' +
                            res +
                            '</table></div>';
                        childContainer.html(resHTML);
                    },
                    error: function() {
                        childContainer.html('<div class="alert alert-danger m-0 py-2"><i class="mdi mdi-alert-circle"></i> Ocurrió un error leyendo los lotes correspondientes al producto seleccionado.</div>');
                    }
                });
            });

            // Lógica para desglosar folios dentro de un lote
            $(document).on('click', '.fila-lote', function() {
                var tr = $(this);
                var articuloId = tr.data('articulo');
                var almacenId = tr.data('almacen');
                var esMaleta = tr.data('esmaleta');
                var serie = tr.data('serie');
                var lote = tr.data('lote');
                var caducidad = tr.data('caducidad');
                var tieneSerie = tr.data('tieneserie');
                var tieneLote = tr.data('tienelote');

                // Si ya están abiertos los folios de esta fila, los cerramos
                if (tr.next().hasClass('detalle-folios-item')) {
                    tr.nextUntil(':not(.detalle-folios-item)').remove();
                    tr.removeClass('shown-folios');
                    tr.css('background-color', '');
                    return;
                }

                var colspan = tr.children('td').length;
                var childRow = $('<tr class="detalle-folios-item temp-loading"><td colspan="' + colspan + '" class="p-0 border-0" style="background-color: #f8fbff;"><div class="text-center py-2 text-primary"><i class="mdi mdi-loading mdi-spin" style="font-size:18px;"></i> Cargando folios...</div></td></tr>');

                tr.after(childRow);
                tr.addClass('shown-folios');
                tr.css('background-color', '#eaf2f8');

                $.ajax({
                    url: '../ajax/inventarioglobal_folios_lote.php',
                    type: 'GET',
                    cache: false,
                    data: {
                        articulo_id: articuloId,
                        almacen_id: almacenId,
                        es_maleta: esMaleta,
                        serie: serie,
                        lote: lote,
                        caducidad: caducidad,
                        tiene_serie: tieneSerie,
                        tiene_lote: tieneLote, busqueda: '<?= addslashes($busqueda) ?>' },
                    success: function(res) {
                        tr.next('.temp-loading').remove();
                        tr.after(res);
                    },
                    error: function() {
                        tr.next('.temp-loading').find('.text-center').html('<div class="alert alert-danger m-0 py-2"><i class="mdi mdi-alert-circle"></i> Ocurrió un error leyendo los folios del lote.</div>');
                    }
                });
            });

            // Filtro de visualización por tipo de maleta (select)
            $(document).on('change', '#filtroTipoMaletaInventario', function() {
                aplicarFiltroTipoMaleta($(this).val() || '');
            });

            // Lotes dentro del detalle de una maleta
            $(document).on('click', '.maleta-detalle-table tbody tr.fila-articulo-maleta', function() {
                var tr = $(this);
                var articuloId = tr.data('articulo-id');
                var maletaId = $('#modalMaleta').data('maleta-id');

                if (!articuloId) {
                    return;
                }

                if (tr.next().hasClass('detalle-lotes-maleta-row') && tr.next().is(':visible')) {
                    tr.next().remove();
                    tr.removeClass('shown-lotes-maleta');
                    return;
                }

                tr.closest('tbody').find('.detalle-lotes-maleta-row').remove();
                tr.closest('tbody').find('tr.fila-articulo-maleta.shown-lotes-maleta').removeClass('shown-lotes-maleta');

                var childRow = $('<tr class="detalle-lotes-maleta-row"><td colspan="5" class="p-0 border-0"></td></tr>');
                var childContainer = $('<div class="lotes-maleta-wrap"></div>');
                childContainer.html('<div class="text-center py-2 text-primary"><i class="mdi mdi-loading mdi-spin" style="font-size:18px;"></i> Cargando lotes...</div>');
                childRow.find('td').append(childContainer);

                tr.after(childRow);
                tr.addClass('shown-lotes-maleta');

                $.ajax({
                    url: '../ajax/inventarioglobal_lotes_articulo.php',
                    type: 'GET',
                    cache: false,
                    data: {
                        articulo_id: articuloId, almacen_id: maletaId, es_maleta: 1, busqueda: '<?= addslashes($busqueda) ?>' },
                    success: function(res) {
                        var html = '<div class="table-responsive"><table class="table table-sm table-borderless mb-0 lotes-maleta-table">' +
                            res + '</table></div>';
                        childContainer.html(html);
                    },
                    error: function() {
                        childContainer.html('<div class="alert alert-danger m-0 py-2">No se pudieron cargar los lotes de este artículo.</div>');
                    }
                });
            });

            // Lotes dentro del detalle de una maleta para mobile
            $(document).on('click', '.fila-articulo-maleta-mobile', function() {
                var card = $(this);
                var articuloId = card.data('articulo-id');
                var maletaId = $('#modalMaleta').data('maleta-id');

                if (!articuloId) {
                    return;
                }

                if (card.next().hasClass('detalle-lotes-maleta-mobile')) {
                    card.next().remove();
                    card.removeClass('shown-lotes-maleta');
                    card.css('border-color', '');
                    return;
                }

                $('#modalMaletaBody').find('.detalle-lotes-maleta-mobile').remove();
                $('#modalMaletaBody').find('.fila-articulo-maleta-mobile.shown-lotes-maleta').removeClass('shown-lotes-maleta').css('border-color', '');

                var childContainer = $('<div class="detalle-lotes-maleta-mobile lotes-maleta-wrap p-3 mb-3 border rounded shadow-sm" style="margin-top:-10px;"></div>');
                childContainer.html('<div class="text-center py-2 text-primary"><i class="mdi mdi-loading mdi-spin" style="font-size:18px;"></i> Cargando lotes...</div>');

                card.after(childContainer);
                card.addClass('shown-lotes-maleta');
                card.css('border-color', '#1f3bb3');

                $.ajax({
                    url: '../ajax/inventarioglobal_lotes_articulo.php',
                    type: 'GET',
                    cache: false,
                    data: {
                        articulo_id: articuloId, almacen_id: maletaId, es_maleta: 1, busqueda: '<?= addslashes($busqueda) ?>' },
                    success: function(res) {
                        var html = '<div class="table-responsive px-0"><table class="table table-sm table-borderless mb-0 lotes-maleta-table">' +
                            res + '</table></div>';
                        childContainer.html(html);
                    },
                    error: function() {
                        childContainer.html('<div class="alert alert-danger m-0 py-2">No se pudieron cargar los lotes de este artículo.</div>');
                    }
                });
            });
        });

        // Lógica de Maletas
        function cargarMaleta(card, maletaId) {
            var nombre = $(card).find('strong.text-dark').first().text() || 'Maleta';
            $('#modalMaletaLabel').html('<i class="mdi mdi-medical-bag mr-2"></i>' + nombre);
            $('#modalMaleta').data('maleta-id', maletaId);
            $('#modalMaletaBody').html('<div class="text-center py-4"><i class="mdi mdi-loading mdi-spin" style="font-size: 28px;"></i><br><span class="text-muted mt-2 d-block">Cargando artículos...</span></div>');
            $('#modalMaleta').modal('show');

            var busqueda = '<?= addslashes($busqueda) ?>';
            $.ajax({
                url: '../ajax/inventarioglobal_maleta.php',
                type: 'GET',
                data: {
                    maleta_id: maletaId,
                    busqueda: busqueda,
                    grupo_linea_id: '<?= addslashes($grupo_linea_id) ?>',
                    categoria_id: '<?= addslashes($categoria_id) ?>'
                },
                success: function(response) {
                    $('#modalMaletaBody').html(response);
                },
                error: function() {
                    $('#modalMaletaBody').html('<p class="text-danger text-center py-3"><i class="mdi mdi-alert-circle mr-1"></i>Error al cargar los artículos.</p>');
                }
            });
        }

        function cerrarDetalle(maletaId) {
            $('#modalMaleta').modal('hide');
        }

        function aplicarFiltroTipoMaleta(tipoKey) {
            var items = $('.maleta-item');
            var total = items.length;

            if (!tipoKey) {
                items.show();
                $('#contadorMaletasFiltradas').text('Mostrando ' + total + ' de ' + total + ' maletas');
                return;
            }

            items.hide();
            var visibles = $('.maleta-item[data-tipo-maleta="' + tipoKey + '"]');
            visibles.show();

            $('#contadorMaletasFiltradas').text('Mostrando ' + visibles.length + ' de ' + total + ' maletas');
        }
    </script>


    <style>
        @media (max-width: 768px) {
            .inv-tree-div {
                margin: 0 4px 8px 4px !important;
            }

            .inv-tree-cat {
                margin: 0 4px 8px 12px !important;
            }

            .detalle-lotes-mobile {
                padding: 6px !important;
            }

            .mobile-articulo-card {
                margin-bottom: 8px !important;
            }

            .lotes-table-mobile th,
            .lotes-table-mobile td {
                padding-left: 2px !important;
                padding-right: 2px !important;
                font-size: 10.5px !important;
            }
        }

        .inv-tree-div {
            margin: 0 16px 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fdfdfd;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .inv-tree-cat {
            margin: 0 16px 12px 36px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fafbfc;
        }

        #modalMaleta {
            overflow-y: auto !important;
        }

        #modalMaleta .modal-dialog {
            max-width: 800px;
        }

        #modalMaleta .modal-body {
            max-height: 70vh;
            overflow-y: auto;
            overflow-x: hidden;
        }

        #modalMaleta .close {
            color: #fff !important;
            opacity: 1 !important;
            font-size: 28px;
            text-shadow: none;
        }

        body.modal-open {
            overflow: hidden !important;
        }

        @media (max-width: 768px) {
            #modalMaleta .modal-dialog {
                max-width: 95%;
                margin: 10px auto;
            }
        }
    </style>
    <!-- Modal compartido para detalle de maleta (a nivel body) -->
    <div class="modal fade" id="modalMaleta" tabindex="-1" role="dialog" aria-labelledby="modalMaletaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 12px;">
                <div class="modal-header" style="background: linear-gradient(135deg, #1f3bb3, #4361ee); border: none; padding: 16px 24px; border-radius: 12px 12px 0 0;">
                    <h5 class="modal-title text-white" id="modalMaletaLabel" style="font-weight: 700;"><i class="mdi mdi-medical-bag mr-2"></i>Detalle de Maleta</h5>
                    <button type="button" class="close text-white" onclick="$('#modalMaleta').modal('hide');" aria-label="Cerrar" style="opacity: 0.9;">
                        <span aria-hidden="true" class="text-white">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3" id="modalMaletaBody">
                    <div class="text-center py-4"><i class="mdi mdi-loading mdi-spin" style="font-size: 28px;"></i><br><span class="text-muted mt-2 d-block">Cargando artículos...</span></div>
                </div>
            </div>
        </div>
    </div>
<script>
(function(){
<?php if (!empty(trim($busqueda))): ?>
    ['seccion-kpis','seccion-maletas','bloque-maletas-listado', 'listadoMaletasContinuo'].forEach(function(id){
        var el = document.getElementById(id); if (el) el.style.display = 'none';
    });
    var banner = document.getElementById('banner-busqueda');
    if (banner) {
        banner.style.display = 'block';
        banner.innerHTML = '<i class="mdi mdi-magnify mr-1"></i> Resultados para: <strong>' + <?= json_encode(htmlspecialchars(trim($busqueda))) ?> + '</strong>&nbsp;<a href="?almacen_id=<?= rawurlencode($almacen_id) ?>" class="text-danger ml-3" style="font-size:12px;"><i class="mdi mdi-close-circle"></i> Limpiar</a>';
    }
    document.querySelectorAll('[id^="fam_"],[id*="_div_"],[id*="_cat_"]').forEach(function(el){ el.style.display='block'; });
    document.querySelectorAll('.fam-chevron,.div-chevron,.cat-chevron').forEach(function(el){ el.classList.remove('mdi-chevron-down'); el.classList.add('mdi-chevron-up'); });
    
    // Auto-expandir articulos
    setTimeout(function() {
        document.querySelectorAll('.fila-articulo').forEach(function(el){ 
            if(!el.classList.contains('shown-dropdown')) { el.click(); }
        });
    }, 500);

    // Auto-expandir lotes cuando se carguen
    $(document).ajaxComplete(function(event, xhr, settings) {
        if(settings.url.indexOf('inventarioglobal_lotes_articulo.php') !== -1) {
            setTimeout(function() {
                document.querySelectorAll('.fila-lote').forEach(function(el){ 
                    if(!el.classList.contains('shown-folios')) { el.click(); }
                });
            }, 300);
        }
    });

    <?php $hayArticulos = is_array($articulosAlmacen) && count($articulosAlmacen) > 0; ?>
    if (!<?= $hayArticulos ? 'true' : 'false' ?>) {
        var c = document.getElementById('bloque-equipo-capital');
        var d = document.createElement('div'); d.className='alert alert-warning mt-3';
        d.innerHTML='<i class="mdi mdi-alert-circle-outline mr-1"></i> No se encontraron artículos para <strong><?= htmlspecialchars(trim($busqueda)) ?></strong>.';
        if (c) c.parentNode.insertBefore(d, c);
    }
<?php endif; ?>
})();
</script>
</body>

</html>