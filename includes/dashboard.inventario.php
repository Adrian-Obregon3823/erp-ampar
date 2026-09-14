<?php
include_once("../includes/helpers/dashboard.inventario.helper.php");

$periodoDias = isset($_GET['periodo']) ? (int)$_GET['periodo'] : 90;
$almacenId = $_GET['almacen_id'] ?? 'global';

$almCls = new almacenes();

$esAdmin = !empty($GLOBALS['isAdmin']);
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
    if (empty($almacenes_ids)) $almacenes_ids = [-1];
}

$dbList = new FirebirdConnection(true);
$sqlList = "SELECT ALMACEN_ID ID, ALMACEN_NOMBRE NOMBRE FROM AMPAR_HIS_ALMACEN WHERE ALMACEN_STATUS = 17 AND ALMACEN_TIPOALMACEN IN (1,2)";
if (!$esAdmin) {
    $sqlList .= " AND ALMACEN_ID IN (" . implode(",", $almacenes_ids) . ")";
}
$sqlList .= " ORDER BY ALMACEN_NOMBRE";
$listaResult = $dbList->query($sqlList);
$dbList->close();

$listaAlmacenes = [];
if ($listaResult && is_array($listaResult)) {
    foreach ($listaResult as $al) {
        if (!$esAdmin) {
            $nombre = strtoupper($al['NOMBRE']);
            if (strpos($nombre, 'CADUCADO') !== false || strpos($nombre, 'MALETA') !== false) {
                continue;
            }
        }
        $listaAlmacenes[] = $al;
    }
}

if (!$esAdmin && (!isset($_GET['almacen_id']) || $_GET['almacen_id'] === 'global')) {
    if (!empty($listaAlmacenes)) {
        $almacenId = (string)$listaAlmacenes[0]['ID'];
    }
}

// Render ligero: los datos pesados se cargan por AJAX al abrir el dashboard.
$stockRegistrado = 0;
$stockFisico = 0;
$stockTransito = 0;
$solicitudesPeriodo = 0;
$solicitudesSinStock = 0;
$unidadesObsoletas = 0;
$articulosObsoletos = 0;
$totalArticulos = 0;
$tasaRotacion = 0;
$diasInventario = 0;
$precisionRegistro = 0;
$roturaStock = 0;

$etapa6m = 0;
$etapa3m = 0;
$etapa2m = 0;
$etapa1s = 0;
$caducado = 0;

$articulosPorCaducidad = [
  '6m' => [],
  '3m' => [],
  '2m' => [],
  '1s' => [],
  'caducado' => [],
];

$topCaducidad = [];
$topCaducidadArticulos = 0;
$topCaducidadUnidadesDia = 0;

$flujoEtapas = [
  [
    'etapa' => '6 meses',
    'key' => '6m',
    'conteo' => $etapa6m,
    'accion' => 'Producto entra en proceso',
    'prioridad' => 'Monitoreo',
    'clase' => 'stage-monitor',
    'expandible' => true,
  ],
  [
    'etapa' => '3 meses',
    'key' => '3m',
    'conteo' => $etapa3m,
    'accion' => 'Retorno a almacen y toma de decision: carta canje o venta',
    'prioridad' => 'Atencion',
    'clase' => 'stage-warning',
    'expandible' => true,
  ],
  [
    'etapa' => '2 meses',
    'key' => '2m',
    'conteo' => $etapa2m,
    'accion' => 'Estatus de carta canje y/o mensaje de estatus de articulo',
    'prioridad' => 'Urgente',
    'clase' => 'stage-alert',
    'expandible' => true,
  ],
  [
    'etapa' => '1 semana',
    'key' => '1s',
    'conteo' => $etapa1s,
    'accion' => 'Recolecta de articulo y/o registro de cierre de proceso',
    'prioridad' => 'Critico',
    'clase' => 'stage-critical',
    'expandible' => true,
  ],
  [
    'etapa' => 'Caducado',
    'key' => 'caducado',
    'conteo' => $caducado,
    'accion' => 'Seguimiento inmediato y disposicion final',
    'prioridad' => 'Accion inmediata',
    'clase' => 'stage-expired',
    'expandible' => true,
  ],
];

$flujoTotal = 0;

$dashboardInitialPayload = [
  'periodoDias' => (int)$periodoDias,
  'almacenId' => $almacenId,
  'chartData' => [
    'labels' => [],
    'entradas' => [],
    'salidas' => [],
    'donutSalud' => [
      'stockSano' => 0,
      'obsoleto' => 0,
    ],
  ],
  'kpis' => [
    'tasaRotacion' => 0,
    'diasInventario' => 0,
    'precisionRegistro' => 0,
    'stockFisico' => 0,
    'stockRegistrado' => 0,
    'stockTransito' => 0,
    'unidadesObsoletas' => 0,
    'articulosObsoletos' => 0,
    'roturaStock' => 0,
    'solicitudesSinStock' => 0,
    'solicitudesPeriodo' => 0,
    'totalArticulos' => 0,
  ],
  'flujo' => [
    '6m' => 0,
    '3m' => 0,
    '2m' => 0,
    '1s' => 0,
    'caducado' => 0,
  ],
  'articulosPorCaducidad' => $articulosPorCaducidad,
  'topCaducidad' => [],
  'topCaducidadArticulos' => 0,
  'topCaducidadUnidadesDia' => 0,
];

$invCssVersion = @filemtime(__DIR__ . "/../css/dashboard.inventario.css");
$invJsVersion = @filemtime(__DIR__ . "/../js/dashboard.inventario.js");
?>

<link rel="stylesheet" href="../css/dashboard.inventario.css?v=<?= (int)$invCssVersion ?>">

<div class="row">
  <div class="col-sm-12">
    <div class="home-tab inv-shell">
      <?php include_once("../includes/dashboard.menu.php"); ?>
      <div class="tab-content tab-content-basic">
        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
          <div class="inv-header-wrap d-flex flex-wrap align-items-center justify-content-between">
            <div>
              <h4 class="inv-header-title mb-1">Dashboard de Inventario</h4>
              <div class="inv-header-sub">Tablero operativo para rotacion, riesgo de stock y control de caducidad.</div>
            </div>
            <form id="invFilterForm" method="get" action="dashboard.php" class="d-flex align-items-md-center flex-column flex-md-row inv-filter-box mt-3 mt-md-0">
              <div class="d-flex align-items-center mb-2 mb-md-0 mr-md-2 w-100 w-md-auto justify-content-between justify-content-md-start">
                <label for="almacen_id" class="mb-0 mr-2">Almacen:</label>
                <select id="almacen_id" name="almacen_id" class="form-control form-control-sm text-dark" style="min-width: 140px; flex: 1;" <?= (count($listaAlmacenes) === 1 && !$esAdmin) ? 'disabled' : '' ?>>
                  <?php if ($esAdmin): ?>
                    <option value="global" <?= ((string)$almacenId === 'global') ? 'selected' : '' ?>>Global</option>
                  <?php endif; ?>
                  <?php if (!empty($listaAlmacenes)): ?>
                    <?php foreach ($listaAlmacenes as $al): ?>
                      <?php $almacenOptionId = (string)((int)$al['ID']); ?>
                      <option value="<?= (int)$al['ID'] ?>" <?= ((string)$almacenId === $almacenOptionId) ? 'selected' : '' ?>><?= htmlspecialchars($al['NOMBRE']) ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
              <div class="d-flex align-items-center w-100 w-md-auto justify-content-between justify-content-md-start">
                <label for="periodo" class="mb-0 mr-2">Periodo:</label>
                <select id="periodo" name="periodo" class="form-control form-control-sm text-dark" style="min-width: 110px; flex: 1;">
                  <option value="30" <?= $periodoDias === 30 ? 'selected' : '' ?>>30 dias</option>
                  <option value="60" <?= $periodoDias === 60 ? 'selected' : '' ?>>60 dias</option>
                  <option value="90" <?= $periodoDias === 90 ? 'selected' : '' ?>>90 dias</option>
                  <option value="180" <?= $periodoDias === 180 ? 'selected' : '' ?>>180 dias</option>
                  <option value="365" <?= $periodoDias === 365 ? 'selected' : '' ?>>365 dias</option>
                </select>
              </div>
            </form>
          </div>
          <div id="invAjaxStatus" class="inv-mini-note d-none" role="status" aria-live="polite"></div>

          <?php include("../includes/dashboard/partials/inventario/kpis.php"); ?>
          <?php include("../includes/dashboard/partials/inventario/analytics.php"); ?>

          <div id="invFlowPrintArea" class="inv-flow-print-area row mt-3">
            <?php include("../includes/dashboard/partials/inventario/flujo.php"); ?>
            <?php include("../includes/dashboard/partials/inventario/top-caducidad.php"); ?>
          </div>
        </div>

        <div class="tab-pane fade" id="ventas" role="tabpanel" aria-labelledby="ventas">
          <?php include_once("../includes/dashboard.ventas.php"); ?>
        </div>
        <div class="tab-pane fade" id="demographics" role="tabpanel" aria-labelledby="demographics">
          <div class="alert alert-light border mt-3">Modulo de Productos en construccion.</div>
        </div>
        <div class="tab-pane fade" id="more" role="tabpanel" aria-labelledby="more">
          <div class="alert alert-light border mt-3">Mas indicadores en construccion.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
  window.invDashboardConfig = {
    initialPayload: <?= json_encode($dashboardInitialPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || {},
    deferInitialLoad: true,
    dataEndpointCandidates: [
      '../ajax/dashboard.inventario.data.php',
      'ajax/dashboard.inventario.data.php',
      '/ajax/dashboard.inventario.data.php'
    ]
  };
</script>
<script src="../js/dashboard.inventario.js?v=<?= (int)$invJsVersion ?>"></script>
