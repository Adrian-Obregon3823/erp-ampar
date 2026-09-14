<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/**
 * Módulo de Actividades
 * Panel de control dinámico basado en el rol del usuario.
 */

// --- Detectar Perfil del usuario ---
$usuarioId       = (int)($usersesion['USUARIO_ID'] ?? 0);
$esAdmin         = false;
$esAlmacenista   = false;

if ($usuarioId > 0) {
  $db = new FirebirdConnection();
  $sqlPerfiles = "
        SELECT P.PERFIL_NOMBRE 
        FROM AMPAR_CAT_USUARIOSPERFILES UP
        JOIN AMPAR_CAT_PERFILES P ON P.PERFIL_ID = UP.USUARIOP_PERFILID
        WHERE UP.USUARIOP_USUARIOID = $usuarioId
    ";
  $resPerfiles = $db->query($sqlPerfiles);
  $db->close();

  foreach ($resPerfiles as $p) {
    $pUpper = strtoupper(trim($p['PERFIL_NOMBRE']));
    if (strpos($pUpper, 'ADMIN') !== false || strpos($pUpper, 'ADMINISTRADOR') !== false) {
      $esAdmin = true;
    }
    if (strpos($pUpper, 'ALMACEN') !== false) {
      $esAlmacenista = true;
    }
  }
}

$actividades = new actividades();
$actividades->initConfigTables();
$asignadas = $actividades->getActividadesAsignadas($usuarioId);
$customActs = $actividades->getActividadesPersonalizadas();

// -- Datos pre-calculados --
$escaneoStatus      = null;
$escaneoAlmacenesStatus = null;
$recepcionesPend    = 0;
$entradasPendAlmacenista  = 0;
$salidasPendAlmacenista   = 0;
$traspasosPendAlmacenista = 0;
$nombreAlmacenAlmacenista = '';

// Calcular solo si le corresponden
if (in_array('ESCANEO_MALETAS', $asignadas)) {
  $escaneoStatus = $actividades->getEscaneoStatus($usuarioId);
  $nombreAlmacenAlmacenista = $actividades->getNombreAlmacenUsuario($usuarioId);
}
if (in_array('ESCANEO_ALMACENES', $asignadas)) {
  $escaneoAlmacenesStatus = $actividades->getEscaneoAlmacenesStatus($usuarioId);
  if (!$nombreAlmacenAlmacenista) $nombreAlmacenAlmacenista = $actividades->getNombreAlmacenUsuario($usuarioId);
}
if (in_array('RECEPCIONES', $asignadas)) {
  $recepcionesMercanciaPend = $actividades->getRecepcionesPendientes();
  $recepcionesTraspasosPend = $actividades->getRecepcionesTraspasosPendientes($usuarioId);
  $recepcionesPend = $recepcionesMercanciaPend + $recepcionesTraspasosPend;
}
if (in_array('ENTRADAS', $asignadas)) {
  $entradasPendAlmacenista = $actividades->getEntradasPendientesAlmacenista();
}
if (in_array('SALIDAS', $asignadas)) {
  $salidasPendAlmacenista  = $actividades->getSalidasPendientesAlmacenista();
}
if (in_array('TRASPASOS', $asignadas)) {
  $traspasosPendAlmacenista = $actividades->getTraspasosPendientesAlmacenista($usuarioId);
}


// -- Datos para Administrador --
$entradasPend  = 0;
$traspasosPend = 0;
$disputasPend = 0;
if (in_array('ENTRADAS_ADMIN', $asignadas)) {
  $entradasPend  = $actividades->getEntradasPorAutorizar();
}
if (in_array('TRASPASOS_ADMIN', $asignadas)) {
  $traspasosPend = $actividades->getTraspasosPorAutorizar();
}
if (in_array('DISPUTAS_ADMIN', $asignadas)) {
  $disputasPend  = $actividades->getDisputasPorRevisar();
}

$horaActual = (int)date('H');
$escaneoActivo = ($horaActual >= 8);
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <?php include_once("../includes/head.php"); ?>
  <title>Actividades | AMPAR</title>
  <style>
    /* ======================== Actividades Module Styles ======================== */
    .actividades-page {
      background: #f4f6f9;
      min-height: 100vh;
      font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .actividades-hero {
      background: #fff;
      border-left: 5px solid #0d6efd;
      border-radius: 10px;
      padding: 20px 25px;
      margin-bottom: 25px;
      color: #333;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .actividades-hero h1 {
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 4px;
      color: #1e293b;
    }

    .actividades-hero p {
      color: #64748b;
      margin: 0;
      font-size: 0.95rem;
    }

    .hero-icon {
      font-size: 2rem;
      color: #0d6efd;
    }

    /* Activity Cards */
    .activity-card {
      border-radius: 10px;
      border: 1px solid #f1f5f9;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
      background: #fff;
      margin-bottom: 20px;
      padding: 0;
      overflow: hidden;
    }

    .card-entradas {
      border-left: 5px solid #f59e0b;
    }

    .card-traspasos {
      border-left: 5px solid #ef4444;
    }

    .card-escaneo {
      border-left: 5px solid #0ea5e9;
    }

    .card-recepciones {
      border-left: 5px solid #8b5cf6;
    }

    .activity-card .card-header {
      padding: 20px 20px 10px;
      border-bottom: none;
      display: flex;
      align-items: flex-start;
      gap: 12px;
      background: transparent !important;
    }

    .activity-card .card-header .icon-wrap {
      width: 42px;
      height: 42px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
    }

    .card-entradas .icon-wrap {
      background: #fef3c7;
      color: #d97706;
    }

    .card-traspasos .icon-wrap {
      background: #fee2e2;
      color: #b91c1c;
    }

    .card-escaneo .icon-wrap {
      background: #e0f2fe;
      color: #0284c7;
    }

    .card-recepciones .icon-wrap {
      background: #ede9fe;
      color: #6d28d9;
    }

    .activity-card .card-header h5 {
      font-size: 1.1rem;
      font-weight: 700;
      color: #1e293b;
      margin: 0;
    }

    .activity-card .card-header p {
      font-size: 0.85rem;
      color: #64748b;
      margin: 4px 0 0;
    }

    .activity-card .card-body {
      padding: 10px 20px 20px;
    }

    /* Counter */
    .big-counter {
      font-size: 2.2rem;
      font-weight: 800;
      color: #1e293b;
      line-height: 1;
    }

    .counter-desc {
      font-size: 0.9rem;
      color: #64748b;
      margin-top: 8px;
    }

    /* Action button */
    .btn-activity {
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.85rem;
      padding: 8px 16px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      border: none;
      text-decoration: none;
      transition: all 0.2s;
    }

    .btn-activity.amber {
      background: #fef3c7;
      color: #d97706;
    }

    .btn-activity.amber:hover {
      background: #fde68a;
      color: #b45309;
    }

    .btn-activity.rose {
      background: #fee2e2;
      color: #b91c1c;
    }

    .btn-activity.rose:hover {
      background: #fecaca;
      color: #991b1b;
    }

    .btn-activity.teal {
      background: #ccfbf1;
      color: #0f766e;
    }

    .btn-activity.teal:hover {
      background: #99f6e4;
      color: #115e59;
    }

    /* Time reminder */
    .time-note {
      font-size: 0.8rem;
      color: #94a3b8;
      margin-top: 10px;
    }

    .time-note i {
      margin-right: 4px;
    }

    /* Section label */
    .section-label {
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 1.2px;
      text-transform: uppercase;
      color: #94a3b8;
      margin-bottom: 16px;
      margin-top: 10px;
    }

    /* Folios list */
    .folios-list {
      display: none;
      margin-top: 15px;
      max-height: 320px;
      overflow-y: auto;
    }

    .folio-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 12px 15px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      margin-bottom: 8px;
    }

    .folio-badge {
      font-size: 0.85rem;
      font-weight: 700;
      color: #334155;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .folio-meta {
      font-size: 0.75rem;
      color: #64748b;
      margin-top: 3px;
    }

    .btn-revisar {
      flex-shrink: 0;
      border-radius: 20px;
      padding: 5px 14px;
      font-size: 0.8rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      border: none;
      transition: all 0.2s;
    }

    .btn-revisar.amber {
      background: #fef3c7;
      color: #d97706;
    }

    .btn-revisar.amber:hover {
      background: #fde68a;
    }

    .btn-revisar.rose {
      background: #fee2e2;
      color: #b91c1c;
    }

    .btn-revisar.rose:hover {
      background: #fecaca;
    }

    .toggle-list-btn {
      background: none;
      border: none;
      color: #007bff;
      font-size: 0.85rem;
      font-weight: 600;
      cursor: pointer;
      padding: 4px 0;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .toggle-list-btn:hover {
      color: #0056b3;
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <div id="loading" style="display:none">
    <img id="loading-image" src="../img/logo.png" />
  </div>
  <div class="container-scroller">
    <?php include_once("../includes/header.php"); ?>
    <div class="container-fluid page-body-wrapper">
      <?php include_once("../includes/menu.sidebar.php") ?>
      <div class="main-panel">
        <div class="content-wrapper actividades-page">

          <!-- Hero -->
          <div class="actividades-hero">
            <div class="hero-icon"><i class="mdi mdi-calendar-check-outline"></i></div>
            <div>
              <h1>Actividades del Día</h1>
              <p>
                <?php
                $meses = ["", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                $dias = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
                echo $dias[date('w')] . ", " . date('d') . " de " . $meses[(int)date('m')] . " de " . date('Y');
                ?> &mdash;
                Hola, <strong><?php echo htmlspecialchars($usersesion['USUARIO_NOMBRE'] ?? 'Usuario'); ?></strong>
              </p>
            </div>
            <?php if ($esAdmin): ?>
              <div style="margin-left: auto;">
                <button class="btn btn-outline-primary btn-sm" onclick="openConfigModal()">
                  <i class="mdi mdi-cog-outline"></i> Configurar Actividades
                </button>
              </div>
            <?php endif; ?>
          </div>

          <?php /* ========== ACTIVIDADES DEL SISTEMA ========== */ ?>
          <div class="section-label">
            <i class="mdi mdi-account-hard-hat"></i> Actividades Asignadas
          </div>

          <div class="row mb-4">

            <!-- ====== CARD ESCANEO DIARIO ====== -->
            <?php if (in_array('ESCANEO_MALETAS', $asignadas)): ?>
              <div class="col-12 col-md-6 mb-4">
                <div class="activity-card card card-escaneo">
                  <div class="card-header">
                    <div class="icon-wrap"><i class="mdi mdi-radar"></i></div>
                    <div>
                      <h5>Escaneo Diario de Maletas</h5>
                      <p>Requerido todos los días a las 8:00 AM</p>
                    </div>
                  </div>
                  <div class="card-body">

                    <?php
                    $totalMaletas  = $escaneoStatus['total_maletas'];
                    $escaneosHoy   = $escaneoStatus['escaneos_hoy'];
                    $completado    = $escaneoStatus['completado'];
                    $ultimoEscaneo = $escaneoStatus['ultimo_escaneo'];
                    $pct = $totalMaletas > 0 ? min(100, round(($escaneosHoy / $totalMaletas) * 100)) : 0;
                    $circumference = 2 * M_PI * 42; // radio 42
                    $offset = $circumference - ($pct / 100) * $circumference;
                    ?>

                    <div class="progress-ring-container">
                      <!-- SVG Ring -->
                      <svg class="progress-ring" width="100" height="100" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="42" fill="none" stroke="#e2e8f0" stroke-width="10" />
                        <circle cx="50" cy="50" r="42" fill="none"
                          stroke="<?php echo $completado ? '#10b981' : '#14b8a6'; ?>"
                          stroke-width="10"
                          stroke-linecap="round"
                          stroke-dasharray="<?php echo round($circumference, 2); ?>"
                          stroke-dashoffset="<?php echo round($offset, 2); ?>"
                          transform="rotate(-90 50 50)" />
                        <text x="50" y="54" text-anchor="middle" font-size="18" font-weight="800"
                          fill="<?php echo $completado ? '#065f46' : '#0f766e'; ?>"><?php echo $pct; ?>%</text>
                      </svg>

                      <div>
                        <div style="font-size:1.4rem; font-weight:800; color:#1e293b; line-height:1.2;">
                          <?php echo $escaneosHoy; ?> / <?php echo $totalMaletas; ?>
                        </div>
                        <div style="font-size:0.8rem; color:#64748b; margin-top:4px;">Maletas escaneadas hoy</div>

                        <div class="mt-3">
                          <?php if ($completado): ?>
                            <span class="status-badge-done">
                              <i class="mdi mdi-check-circle"></i> Completado
                            </span>
                          <?php elseif (!$escaneoActivo): ?>
                            <span class="status-badge-waiting">
                              <i class="mdi mdi-clock-outline"></i> Esperar 8:00 AM
                            </span>
                          <?php else: ?>
                            <span class="status-badge-pending" style="cursor: pointer; background-color: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; padding: 4px 12px; border-radius: 20px; display: inline-block;" onclick="$(this).closest('.progress-ring-container').nextAll('.escaneo-tabs-container-ml').first().slideToggle()" title="Clic para ver detalles">
                              <i class="mdi mdi-gesture-tap"></i> Pendiente
                            </span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>

                    <div class="escaneo-tabs-container-ml mt-3" style="display:none;">
                      <ul class="nav nav-tabs" style="border-bottom: 2px solid #dee2e6;">
                        <li class="nav-item">
                          <a class="nav-link active font-weight-bold" data-toggle="tab" href="#tab-pend-ml" style="color: #dc3545; padding: 8px 12px; font-size: 0.85rem;">Pendientes (<?php echo count($escaneoStatus['pendientes'] ?? []); ?>)</a>
                        </li>
                        <li class="nav-item">
                          <a class="nav-link font-weight-bold" data-toggle="tab" href="#tab-val-ml" style="color: #10b981; padding: 8px 12px; font-size: 0.85rem;">Escaneadas (<?php echo count($escaneoStatus['validados'] ?? []); ?>)</a>
                        </li>
                      </ul>
                      <div class="tab-content mt-2" style="padding: 0; border: none;">
                        <div id="tab-pend-ml" class="tab-pane active">
                          <div class="folios-list" style="display:block; max-height: 250px; overflow-y: auto; margin-top: 5px;">
                            <?php if (!empty($escaneoStatus['pendientes'])): ?>
                              <?php foreach ($escaneoStatus['pendientes'] as $pend): ?>
                                <div class="folio-item" style="padding:8px 12px; margin-bottom:5px; background: #fff5f5; border-color: #ffe3e3;">
                                  <div>
                                    <div class="folio-badge"><i class="mdi mdi-briefcase"></i> <?php echo htmlspecialchars($pend['FOLIO']); ?></div>
                                    <div class="folio-meta"><?php echo htmlspecialchars($pend['NOMBRE']); ?></div>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <div class="alert alert-success mt-2" style="padding: 8px;">No hay maletas pendientes.</div>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div id="tab-val-ml" class="tab-pane fade">
                          <div class="folios-list" style="display:block; max-height: 250px; overflow-y: auto; margin-top: 5px;">
                            <?php if (!empty($escaneoStatus['validados'])): ?>
                              <?php foreach ($escaneoStatus['validados'] as $val): ?>
                                <div class="folio-item" style="padding:8px 12px; margin-bottom:5px;">
                                  <div>
                                    <div class="folio-badge"><i class="mdi mdi-briefcase"></i> <?php echo htmlspecialchars($val['FOLIO']); ?></div>
                                    <div class="folio-meta"><?php echo htmlspecialchars($val['NOMBRE']); ?></div>
                                  </div>
                                  <div style="font-size:0.75rem; color:#10b981;"><i class="mdi mdi-clock-outline"></i> <?php echo date('H:i', strtotime($val['FECHA'])); ?></div>
                                </div>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <div class="alert alert-warning mt-2" style="padding: 8px;">Aún no se ha escaneado ninguna maleta hoy.</div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>

                    <?php if ($ultimoEscaneo): ?>
                      <div class="time-note"><i class="mdi mdi-clock-check-outline"></i> Último escaneo: <?php echo $ultimoEscaneo; ?></div>
                    <?php endif; ?>

                    <div class="time-note"><i class="mdi mdi-information-outline"></i> Se marca como completado cuando hayas escaneado todas las maletas (<?php echo $totalMaletas; ?>) después de las 8:00 AM.</div>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <!-- ====== CARD ESCANEO DE ALMACENES ====== -->
            <?php if (in_array('ESCANEO_ALMACENES', $asignadas)): ?>
              <div class="col-12 col-md-6 mb-4">
                <div class="activity-card card card-escaneo">
                  <div class="card-header">
                    <div class="icon-wrap"><i class="mdi mdi-domain"></i></div>
                    <div>
                      <h5>Escaneo Diario de Almacenes</h5>
                      <p>Requerido todos los días a las 8:00 AM</p>
                    </div>
                  </div>
                  <div class="card-body">
                    <?php
                    $totalAlmacenes = $escaneoAlmacenesStatus['total_almacenes'];
                    $escaneosAlmHoy = $escaneoAlmacenesStatus['escaneos_hoy'];
                    $completadoAlm  = $escaneoAlmacenesStatus['completado'];
                    $ultimoEscaneoAlm = $escaneoAlmacenesStatus['ultimo_escaneo'];
                    $pctAlm = $totalAlmacenes > 0 ? min(100, round(($escaneosAlmHoy / $totalAlmacenes) * 100)) : 0;
                    $circumferenceAlm = 2 * M_PI * 42; // radio 42
                    $offsetAlm = $circumferenceAlm - ($pctAlm / 100) * $circumferenceAlm;
                    ?>

                    <div class="progress-ring-container">
                      <!-- SVG Ring -->
                      <svg class="progress-ring" width="100" height="100" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="42" fill="none" stroke="#e2e8f0" stroke-width="10" />
                        <circle cx="50" cy="50" r="42" fill="none"
                          stroke="<?php echo $completadoAlm ? '#10b981' : '#14b8a6'; ?>"
                          stroke-width="10"
                          stroke-linecap="round"
                          stroke-dasharray="<?php echo round($circumferenceAlm, 2); ?>"
                          stroke-dashoffset="<?php echo round($offsetAlm, 2); ?>"
                          transform="rotate(-90 50 50)" />
                        <text x="50" y="54" text-anchor="middle" font-size="18" font-weight="800"
                          fill="<?php echo $completadoAlm ? '#065f46' : '#0f766e'; ?>"><?php echo $pctAlm; ?>%</text>
                      </svg>

                      <div>
                        <div style="font-size:1.4rem; font-weight:800; color:#1e293b; line-height:1.2;">
                          <?php echo $escaneosAlmHoy; ?> / <?php echo $totalAlmacenes; ?>
                        </div>
                        <div style="font-size:0.8rem; color:#64748b; margin-top:4px;">Almacenes escaneados hoy</div>

                        <div class="mt-3">
                          <?php if ($completadoAlm): ?>
                            <span class="status-badge-done">
                              <i class="mdi mdi-check-circle"></i> Completado
                            </span>
                          <?php elseif (!$escaneoActivo): ?>
                            <span class="status-badge-waiting">
                              <i class="mdi mdi-clock-outline"></i> Esperar 8:00 AM
                            </span>
                          <?php else: ?>
                            <span class="status-badge-pending" style="cursor: pointer; background-color: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; padding: 4px 12px; border-radius: 20px; display: inline-block;" onclick="$(this).closest('.progress-ring-container').nextAll('.escaneo-tabs-container-al').first().slideToggle()" title="Clic para ver detalles">
                              <i class="mdi mdi-gesture-tap"></i> Pendiente
                            </span>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>

                    <div class="escaneo-tabs-container-al mt-3" style="display:none;">
                      <ul class="nav nav-tabs" style="border-bottom: 2px solid #dee2e6;">
                        <li class="nav-item">
                          <a class="nav-link active font-weight-bold" data-toggle="tab" href="#tab-pend-al" style="color: #dc3545; padding: 8px 12px; font-size: 0.85rem;">Pendientes (<?php echo count($escaneoAlmacenesStatus['pendientes'] ?? []); ?>)</a>
                        </li>
                        <li class="nav-item">
                          <a class="nav-link font-weight-bold" data-toggle="tab" href="#tab-val-al" style="color: #10b981; padding: 8px 12px; font-size: 0.85rem;">Escaneados (<?php echo count($escaneoAlmacenesStatus['validados'] ?? []); ?>)</a>
                        </li>
                      </ul>
                      <div class="tab-content mt-2" style="padding: 0; border: none;">
                        <div id="tab-pend-al" class="tab-pane active">
                          <div class="folios-list" style="display:block; max-height: 250px; overflow-y: auto; margin-top: 5px;">
                            <?php if (!empty($escaneoAlmacenesStatus['pendientes'])): ?>
                              <?php foreach ($escaneoAlmacenesStatus['pendientes'] as $pend): ?>
                                <div class="folio-item" style="padding:8px 12px; margin-bottom:5px; background: #fff5f5; border-color: #ffe3e3;">
                                  <div>
                                    <div class="folio-badge"><i class="mdi mdi-domain"></i> <?php echo htmlspecialchars($pend['FOLIO']); ?></div>
                                    <div class="folio-meta"><?php echo htmlspecialchars($pend['NOMBRE']); ?></div>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <div class="alert alert-success mt-2" style="padding: 8px;">No hay almacenes pendientes.</div>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div id="tab-val-al" class="tab-pane fade">
                          <div class="folios-list" style="display:block; max-height: 250px; overflow-y: auto; margin-top: 5px;">
                            <?php if (!empty($escaneoAlmacenesStatus['validados'])): ?>
                              <?php foreach ($escaneoAlmacenesStatus['validados'] as $val): ?>
                                <div class="folio-item" style="padding:8px 12px; margin-bottom:5px;">
                                  <div>
                                    <div class="folio-badge"><i class="mdi mdi-domain"></i> <?php echo htmlspecialchars($val['FOLIO']); ?></div>
                                    <div class="folio-meta"><?php echo htmlspecialchars($val['NOMBRE']); ?></div>
                                  </div>
                                  <div style="font-size:0.75rem; color:#10b981;"><i class="mdi mdi-clock-outline"></i> <?php echo date('H:i', strtotime($val['FECHA'])); ?></div>
                                </div>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <div class="alert alert-warning mt-2" style="padding: 8px;">Aún no se ha escaneado ningún almacén hoy.</div>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>

                    <?php if ($ultimoEscaneoAlm): ?>
                      <div class="time-note"><i class="mdi mdi-clock-check-outline"></i> Último escaneo: <?php echo $ultimoEscaneoAlm; ?></div>
                    <?php endif; ?>

                    <div class="time-note"><i class="mdi mdi-information-outline"></i> Se marca como completado cuando hayas escaneado todos los almacenes (<?php echo $totalAlmacenes; ?>) después de las 8:00 AM.</div>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <!-- ====== CARD RECEPCIONES PENDIENTES ====== -->
            <?php if (in_array('RECEPCIONES', $asignadas)): ?>
              <div class="col-12 col-md-6 mb-4">
                <div class="activity-card card card-recepciones">
                  <div class="card-header">
                    <div class="icon-wrap"><i class="mdi mdi-package-variant-closed"></i></div>
                    <div>
                      <h5>Recepciones</h5>
                      <p>Recepciones pendientes por dar entrada</p>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="big-counter <?php echo $recepcionesPend > 0 ? 'warning' : 'success'; ?>">
                      <?php echo $recepcionesPend; ?>
                    </div>
                    <div class="counter-desc">
                      <?php if ($recepcionesPend == 0): ?>
                        <span class="status-badge-done mt-2 d-inline-flex"><i class="mdi mdi-check-circle"></i> Sin recepciones pendientes</span>
                      <?php else: ?>
                        recepción<?php echo $recepcionesPend != 1 ? 'es' : ''; ?> esperando ser procesada<?php echo $recepcionesPend != 1 ? 's' : ''; ?>

                        <ul class="mt-2 text-left mb-0" style="padding-left:1.5rem;">
                          <?php if ($recepcionesMercanciaPend > 0): ?>
                            <li><strong><?php echo $recepcionesMercanciaPend; ?></strong> de Mercancía</li>
                          <?php endif; ?>
                          <?php if ($recepcionesTraspasosPend > 0): ?>
                            <li><strong><?php echo $recepcionesTraspasosPend; ?></strong> de Traspasos</li>
                          <?php endif; ?>
                        </ul>
                      <?php endif; ?>
                    </div>

                    <?php if ($recepcionesPend > 0): ?>
                      <div class="alert mt-3 mb-0" style="background:#fef3c7; border:1px solid #fcd34d; border-radius:10px; padding:10px 14px; font-size:0.85rem; color:#92400e;">
                        <i class="mdi mdi-alert-outline"></i> Hay <strong><?php echo $recepcionesPend; ?></strong> recepción<?php echo $recepcionesPend != 1 ? 'es' : ''; ?> esperando que registres la entrada a almacén.
                      </div>
                    <?php endif; ?>

                    <div class="mt-4">
                      <a href="../gui/recepcionesmercancia.php" class="btn-activity purple">
                        <i class="mdi mdi-package-variant-closed-check"></i> Ir a Recepciones
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <!-- ====== CARD ENTRADAS ALMACENISTA ====== -->
            <?php if (in_array('ENTRADAS', $asignadas)): ?>
              <div class="col-12 col-md-6 mb-4">
                <div class="activity-card card card-entradas">
                  <div class="card-header">
                    <div class="icon-wrap" style="background:#fef3c7; color:#d97706;"><i class="mdi mdi-arrow-down-box"></i></div>
                    <div>
                      <h5>Entradas Pendientes</h5>
                      <p>Entradas en estatus Guardado y Enviado</p>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="big-counter <?php echo $entradasPendAlmacenista > 0 ? 'warning' : 'success'; ?>">
                      <?php echo $entradasPendAlmacenista; ?>
                    </div>
                    <div class="counter-desc">
                      <?php if ($entradasPendAlmacenista == 0): ?>
                        <span class="status-badge-done mt-2 d-inline-flex"><i class="mdi mdi-check-circle"></i> Sin entradas pendientes</span>
                      <?php else: ?>
                        entrada<?php echo $entradasPendAlmacenista != 1 ? 's' : ''; ?> pendiente<?php echo $entradasPendAlmacenista != 1 ? 's' : ''; ?>
                      <?php endif; ?>
                    </div>
                    <div class="mt-4 d-flex gap-2 flex-wrap">
                      <a href="../gui/entradasalida.php" class="btn-activity amber" style="font-size:0.82rem; padding:7px 14px;">
                        <i class="mdi mdi-open-in-new"></i> Ir a Entradas
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <!-- ====== CARD SALIDAS ALMACENISTA ====== -->
            <?php if (in_array('SALIDAS', $asignadas)): ?>
              <div class="col-12 col-md-6 mb-4">
                <div class="activity-card card card-escaneo" style="border-left: 5px solid #10b981;">
                  <div class="card-header">
                    <div class="icon-wrap" style="background:#d1fae5; color:#047857;"><i class="mdi mdi-arrow-up-box"></i></div>
                    <div>
                      <h5>Salidas Pendientes</h5>
                      <p>Salidas en estatus Guardado y Enviado</p>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="big-counter <?php echo $salidasPendAlmacenista > 0 ? 'warning' : 'success'; ?>">
                      <?php echo $salidasPendAlmacenista; ?>
                    </div>
                    <div class="counter-desc">
                      <?php if ($salidasPendAlmacenista == 0): ?>
                        <span class="status-badge-done mt-2 d-inline-flex"><i class="mdi mdi-check-circle"></i> Sin salidas pendientes</span>
                      <?php else: ?>
                        salida<?php echo $salidasPendAlmacenista != 1 ? 's' : ''; ?> pendiente<?php echo $salidasPendAlmacenista != 1 ? 's' : ''; ?>
                      <?php endif; ?>
                    </div>
                    <div class="mt-4 d-flex gap-2 flex-wrap">
                      <a href="../gui/entradasalida.php" class="btn-activity teal" style="font-size:0.82rem; padding:7px 14px;">
                        <i class="mdi mdi-open-in-new"></i> Ir a Salidas
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <!-- ====== CARD TRASPASOS ALMACENISTA ====== -->
            <?php if (in_array('TRASPASOS', $asignadas)): ?>
              <div class="col-12 col-md-6 mb-4">
                <div class="activity-card card card-traspasos">
                  <div class="card-header">
                    <div class="icon-wrap" style="background:#fee2e2; color:#b91c1c;"><i class="mdi mdi-swap-horizontal"></i></div>
                    <div>
                      <h5>Traspasos Pendientes</h5>
                      <p>Traspasos en estatus Guardado y Enviado</p>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="big-counter <?php echo $traspasosPendAlmacenista > 0 ? 'warning' : 'success'; ?>">
                      <?php echo $traspasosPendAlmacenista; ?>
                    </div>
                    <div class="counter-desc">
                      <?php if ($traspasosPendAlmacenista == 0): ?>
                        <span class="status-badge-done mt-2 d-inline-flex"><i class="mdi mdi-check-circle"></i> Sin traspasos pendientes</span>
                      <?php else: ?>
                        traspaso<?php echo $traspasosPendAlmacenista != 1 ? 's' : ''; ?> pendiente<?php echo $traspasosPendAlmacenista != 1 ? 's' : ''; ?>
                      <?php endif; ?>
                    </div>
                    <div class="mt-4 d-flex gap-2 flex-wrap">
                      <a href="../gui/traspasos.php" class="btn-activity rose" style="font-size:0.82rem; padding:7px 14px;">
                        <i class="mdi mdi-open-in-new"></i> Ir a Traspasos
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>

          </div>

          <?php /* ========== VISTA ADMINISTRADOR ========== */ ?>

          <div class="section-label">
            <i class="mdi mdi-shield-account"></i> Actividades Administrativas
          </div>

          <div class="row mb-4">

            <!-- ====== CARD ENTRADAS POR AUTORIZAR ====== -->
            <?php if (in_array('ENTRADAS_ADMIN', $asignadas)): ?>
              <div class="col-12 col-md-6 mb-4">
                <div class="activity-card card card-entradas">
                  <div class="card-header">
                    <div class="icon-wrap"><i class="mdi mdi-clipboard-check-outline"></i></div>
                    <div>
                      <h5>Entradas por Autorizar</h5>
                      <p>Solicitudes en estatus <strong>En Revisión</strong> — pendientes de aprobación</p>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="big-counter <?php echo $entradasPend > 0 ? 'warning' : 'success'; ?>">
                      <?php echo $entradasPend; ?>
                    </div>
                    <div class="counter-desc">
                      <?php if ($entradasPend == 0): ?>
                        <span class="status-badge-done mt-2 d-inline-flex"><i class="mdi mdi-check-circle"></i> Sin entradas pendientes</span>
                      <?php else: ?>
                        entrada<?php echo $entradasPend != 1 ? 's' : ''; ?> en revisión, pendiente<?php echo $entradasPend != 1 ? 's' : ''; ?> de autorización
                      <?php endif; ?>
                    </div>

                    <?php if ($entradasPend > 0): ?>
                      <div class="mt-3">
                        <button class="toggle-list-btn" onclick="toggleFoliosList('entradas-list', this)">
                          <i class="mdi mdi-chevron-down"></i> Ver entradas pendientes
                        </button>
                      </div>
                      <div id="entradas-list" class="folios-list">
                        <div style="text-align:center; color:#94a3b8; font-size:0.8rem; padding:10px 0;"><i class="mdi mdi-loading mdi-spin"></i> Cargando...</div>
                      </div>
                    <?php endif; ?>

                    <div class="mt-4 d-flex gap-2 flex-wrap">
                      <a href="../gui/entradasalida.php" class="btn-activity amber" style="font-size:0.82rem; padding:7px 14px;">
                        <i class="mdi mdi-open-in-new"></i> Ver módulo completo
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <!-- ====== CARD TRASPASOS POR AUTORIZAR ====== -->
            <?php if (in_array('TRASPASOS_ADMIN', $asignadas)): ?>
              <div class="col-12 col-md-6 mb-4">
                <div class="activity-card card card-traspasos">
                  <div class="card-header">
                    <div class="icon-wrap"><i class="mdi mdi-swap-horizontal"></i></div>
                    <div>
                      <h5>Traspasos por Autorizar</h5>
                      <p>Solicitudes en estatus <strong>En Revisión</strong> — pendientes de aprobación</p>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="big-counter <?php echo $traspasosPend > 0 ? 'danger' : 'success'; ?>">
                      <?php echo $traspasosPend; ?>
                    </div>
                    <div class="counter-desc">
                      <?php if ($traspasosPend == 0): ?>
                        <span class="status-badge-done mt-2 d-inline-flex"><i class="mdi mdi-check-circle"></i> Sin traspasos pendientes</span>
                      <?php else: ?>
                        traspaso<?php echo $traspasosPend != 1 ? 's' : ''; ?> en espera de ser autorizado<?php echo $traspasosPend != 1 ? 's' : ''; ?>
                      <?php endif; ?>
                    </div>

                    <?php if ($traspasosPend > 0): ?>
                      <div class="mt-3">
                        <button class="toggle-list-btn" onclick="toggleFoliosList('traspasos-list', this)">
                          <i class="mdi mdi-chevron-down"></i> Ver traspasos pendientes
                        </button>
                      </div>
                      <div id="traspasos-list" class="folios-list">
                        <div style="text-align:center; color:#94a3b8; font-size:0.8rem; padding:10px 0;"><i class="mdi mdi-loading mdi-spin"></i> Cargando...</div>
                      </div>
                    <?php endif; ?>

                    <div class="mt-4 d-flex gap-2 flex-wrap">
                      <a href="../gui/traspasos.php" class="btn-activity rose" style="font-size:0.82rem; padding:7px 14px;">
                        <i class="mdi mdi-open-in-new"></i> Ver módulo completo
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <!-- ====== CARD DISPUTAS POR REVISAR (ADMIN) ====== -->
            <?php if (in_array('DISPUTAS_ADMIN', $asignadas)): ?>
              <div class="col-12 col-md-6 mb-4">
                <div class="activity-card card card-traspasos" style="border-left: 5px solid #dc3545;">
                  <div class="card-header">
                    <div class="icon-wrap" style="background:#fee2e2; color:#b91c1c;"><i class="mdi mdi-alert-decagram"></i></div>
                    <div>
                      <h5>Disputas por revisar</h5>
                      <p>Traspasos bloqueados por conflicto</p>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="big-counter <?php echo $disputasPend > 0 ? 'warning' : 'success'; ?>" style="color: <?php echo $disputasPend > 0 ? '#b91c1c' : '#1e293b'; ?>">
                      <?php echo $disputasPend; ?>
                    </div>
                    <div class="counter-desc">
                      <?php if ($disputasPend == 0): ?>
                        <span class="status-badge-done mt-2 d-inline-flex"><i class="mdi mdi-check-circle"></i> Sin disputas pendientes</span>
                      <?php else: ?>
                        disputa<?php echo $disputasPend != 1 ? 's' : ''; ?> pendiente<?php echo $disputasPend != 1 ? 's' : ''; ?> de resolución
                      <?php endif; ?>
                    </div>

                    <?php if ($disputasPend > 0): ?>
                      <div class="alert mt-3 mb-0" style="background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:10px 14px; font-size:0.85rem; color:#991b1b;">
                        <i class="mdi mdi-alert-circle-outline"></i> Existen <strong><?php echo $disputasPend; ?></strong> disputa(s) bloqueando la recepción de traspasos.
                      </div>
                      <div class="mt-3">
                        <button class="toggle-list-btn" onclick="toggleFoliosList('disputas-list', this)" style="color: #b91c1c;">
                          <i class="mdi mdi-chevron-down"></i> Ver disputas pendientes
                        </button>
                      </div>
                      <div id="disputas-list" class="folios-list">
                        <div style="text-align:center; color:#94a3b8; font-size:0.8rem; padding:10px 0;"><i class="mdi mdi-loading mdi-spin"></i> Cargando...</div>
                      </div>
                    <?php endif; ?>

                    <div class="mt-4">
                      <a href="../gui/disputas.php" class="btn-activity rose" style="padding: 7px 14px; font-size: 0.82rem;">
                        <i class="mdi mdi-open-in-new"></i> Ir a Disputas
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endif; ?>

          </div>

          <?php /* ========== ACTIVIDADES PERSONALIZADAS (REMOVIDO) ========== */ ?>


          <?php if (empty($asignadas)): ?>
            <div class="alert alert-info text-center" style="border-radius:14px; padding:30px;">
              <i class="mdi mdi-information-outline" style="font-size:2rem;"></i>
              <p class="mt-2 mb-0">Tu perfil no tiene actividades asignadas en este módulo.</p>
            </div>
          <?php endif; ?>

        </div>
        <?php include_once("../includes/footer.php"); ?>
      </div>
    </div>
  </div>

  <!-- MODAL CONFIGURACIÓN DE ACTIVIDADES -->
  <?php if ($esAdmin): ?>
    <div class="modal fade" id="modalConfigActividades" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
          <div class="modal-header" style="background:#f8fafc; border-bottom:1px solid #e2e8f0; border-radius: 12px 12px 0 0;">
            <h5 class="modal-title" style="color:#0f172a; font-weight:700;"><i class="mdi mdi-cog-outline"></i> Configuración de Actividades</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" style="padding: 25px;">

            <ul class="nav nav-tabs" id="configTabs" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="assign-tab" data-toggle="tab" href="#assign" role="tab"><i class="mdi mdi-account-multiple-plus"></i> Asignaciones</a>
              </li>
            </ul>

            <div class="tab-content mt-4" id="configTabsContent">
              <!-- TAB ASIGNACIONES -->
              <div class="tab-pane fade show active" id="assign" role="tabpanel">
                <div class="row">
                  <div class="col-md-4 border-right">
                    <h6 class="mb-3 font-weight-bold text-muted">1. Selecciona una Actividad</h6>
                    <div class="list-group" id="activityList">
                      <a href="#" class="list-group-item list-group-item-action activity-list-item" data-code="ESCANEO_MALETAS"><i class="mdi mdi-radar"></i> Escaneo de Maletas</a>
                      <a href="#" class="list-group-item list-group-item-action activity-list-item" data-code="ESCANEO_ALMACENES"><i class="mdi mdi-domain"></i> Escaneo de Almacenes</a>
                      <a href="#" class="list-group-item list-group-item-action activity-list-item" data-code="RECEPCIONES"><i class="mdi mdi-package-variant-closed"></i> Recepciones (Almacenista)</a>
                      <a href="#" class="list-group-item list-group-item-action activity-list-item" data-code="ENTRADAS"><i class="mdi mdi-arrow-down-box"></i> Entradas (Almacenista)</a>
                      <a href="#" class="list-group-item list-group-item-action activity-list-item" data-code="SALIDAS"><i class="mdi mdi-arrow-up-box"></i> Salidas (Almacenista)</a>
                      <a href="#" class="list-group-item list-group-item-action activity-list-item" data-code="TRASPASOS"><i class="mdi mdi-swap-horizontal"></i> Traspasos (Almacenista)</a>
                      <a href="#" class="list-group-item list-group-item-action activity-list-item" data-code="ENTRADAS_ADMIN"><i class="mdi mdi-clipboard-check-outline"></i> Entradas (Admin)</a>
                      <a href="#" class="list-group-item list-group-item-action activity-list-item" data-code="TRASPASOS_ADMIN"><i class="mdi mdi-swap-horizontal"></i> Traspasos (Admin)</a>
                      <a href="#" class="list-group-item list-group-item-action activity-list-item" data-code="DISPUTAS_ADMIN"><i class="mdi mdi-alert-decagram"></i> Disputas (Admin)</a>
                    </div>
                  </div>
                  <div class="col-md-8">
                    <h6 class="mb-3 font-weight-bold text-muted">2. Asigna a Usuarios</h6>
                    <div class="alert alert-info" id="assignMsg" style="display:none;"></div>
                    <div id="usersListContainer" style="display:none;">
                      <div style="max-height: 400px; overflow-y: auto;" class="border rounded p-3 bg-light">
                        <table class="table table-sm table-hover" id="usersTable">
                          <thead>
                            <tr>
                              <th style="width: 50px;">
                                <div class="form-check form-check-flat form-check-primary m-0">
                                  <label class="form-check-label"><input type="checkbox" class="form-check-input" id="checkAllUsers"><i class="input-helper"></i></label>
                                </div>
                              </th>
                              <th>Usuario</th>
                              <th>Perfil</th>
                            </tr>
                          </thead>
                          <tbody id="usersTbody">
                            <!-- AJAX -->
                          </tbody>
                        </table>
                      </div>
                      <div class="mt-3 text-right">
                        <button class="btn btn-primary" onclick="saveAssignments()" id="btnSaveAssignments">Guardar Asignaciones</button>
                      </div>
                    </div>
                    <div id="noActivitySelected" class="text-center p-5 text-muted">
                      <i class="mdi mdi-arrow-left" style="font-size:2rem;"></i><br>Selecciona una actividad de la lista
                    </div>
                  </div>
                </div>
              </div>

            </div>

          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php include_once("../includes/foot.php"); ?>
</body>

</html>

<script>
  let currentActivityCode = '';
  let usersData = [];

  function openConfigModal() {
    $('#modalConfigActividades').modal('show');
    loadUsers();
  }

  function loadUsers() {
    $.post('../ajax/actividades.config.php', {
      action: 'get_users'
    }, function(res) {
      if (res.success) {
        usersData = res.data;
        renderUsersList([]);
      }
    }, 'json');
  }

  $('.activity-list-item').click(function(e) {
    e.preventDefault();
    $('.activity-list-item').removeClass('active');
    $(this).addClass('active');
    currentActivityCode = $(this).data('code');
    $('#noActivitySelected').hide();
    $('#usersListContainer').show();

    // Load assignments for this activity
    $.post('../ajax/actividades.config.php', {
      action: 'get_assignments',
      codigo: currentActivityCode
    }, function(res) {
      if (res.success) {
        renderUsersList(res.data);
      }
    }, 'json');
  });

  function renderUsersList(assignedIds) {
    let html = '';

    let filteredUsers = usersData.filter(u => {
      if (!currentActivityCode) return false; // Don't show any if none selected

      let pName = u.PERFIL_NOMBRE.toUpperCase();
      let isAlmacenista = pName.includes('ALMACENISTA');
      let isAdmin = pName.includes('ADMIN');

      if (currentActivityCode.includes('ADMIN')) {
        return isAdmin;
      } else if (currentActivityCode.includes('ALMACENISTA') || currentActivityCode.includes('ALMACENES') || currentActivityCode.includes('MALETAS')) {
        return isAlmacenista;
      }
      return true; // Fallback
    });

    if (filteredUsers.length === 0) {
      html = `<tr><td colspan="3" class="text-center text-muted">No hay usuarios con el perfil adecuado para esta actividad.</td></tr>`;
    } else {
      filteredUsers.forEach(u => {
        let isChecked = assignedIds.includes(parseInt(u.USUARIO_ID)) ? 'checked' : '';
        html += `<tr>
          <td>
            <div class="form-check form-check-flat form-check-primary m-0">
              <label class="form-check-label"><input type="checkbox" class="form-check-input user-checkbox" value="${u.USUARIO_ID}" ${isChecked}><i class="input-helper"></i></label>
            </div>
          </td>
          <td>${u.USUARIO_NOMBRE} <small class="text-muted">(${u.USUARIO_USERNAME})</small></td>
          <td><span class="badge badge-outline-secondary">${u.PERFIL_NOMBRE}</span></td>
        </tr>`;
      });
    }
    $('#usersTbody').html(html);
  }

  $('#checkAllUsers').change(function() {
    $('.user-checkbox').prop('checked', $(this).is(':checked'));
  });

  function saveAssignments() {
    if (!currentActivityCode) return;
    let selectedUsers = [];
    $('.user-checkbox:checked').each(function() {
      selectedUsers.push($(this).val());
    });

    $('#btnSaveAssignments').prop('disabled', true).text('Guardando...');

    $.post('../ajax/actividades.config.php', {
      action: 'save_assignments',
      codigo: currentActivityCode,
      users: selectedUsers
    }, function(res) {
      $('#btnSaveAssignments').prop('disabled', false).text('Guardar Asignaciones');
      if (res.success) {
        $('#assignMsg').removeClass('alert-danger').addClass('alert-success').text('Asignaciones guardadas correctamente.').show();
        setTimeout(() => $('#assignMsg').fadeOut(), 3000);
      } else {
        $('#assignMsg').removeClass('alert-success').addClass('alert-danger').text(res.message).show();
      }
    }, 'json');
  }

  // Custom activities JS functions removed

  // ========== Toggle lista de folios ==========
  function toggleFoliosList(listId, btn) {
    const list = document.getElementById(listId);
    const isOpen = list.style.display === 'block';
    const labelMap = {
      'entradas-list': 'Ver entradas pendientes',
      'traspasos-list': 'Ver traspasos pendientes',
      'disputas-list': 'Ver disputas pendientes'
    };
    if (isOpen) {
      list.style.display = 'none';
      btn.innerHTML = '<i class="mdi mdi-chevron-down"></i> ' + (labelMap[listId] || 'Ver pendientes');
    } else {
      list.style.display = 'block';
      btn.innerHTML = '<i class="mdi mdi-chevron-up"></i> Ocultar lista';
      if (listId === 'entradas-list') cargarEntradasPendientes();
      if (listId === 'traspasos-list') cargarTraspasosPendientes();
      if (listId === 'disputas-list') cargarDisputasPendientes();
    }
  }

  // ========== Cargar lista de Entradas ==========
  function cargarEntradasPendientes() {
    $.ajax({
      url: '../ajax/actividades.entradas.pendientes.php',
      method: 'GET',
      dataType: 'json',
      success: function(data) {
        let html = '';
        if (!data || data.length === 0) {
          html = '<div style="text-align:center; color:#94a3b8; font-size:0.8rem; padding:10px 0;">Sin folios pendientes</div>';
        } else {
          data.forEach(function(item) {
            html += `<div class="folio-item">
            <div>
              <div class="folio-badge"><i class="mdi mdi-clipboard-text-outline"></i> ${item.ES_FOLIO}</div>
              <div class="folio-meta">${item.ALMACEN_NOMBRE || ''} ${item.SUCURSAL_NOMBRE ? '· ' + item.SUCURSAL_NOMBRE : ''} · ${item.CANTIDAD || 0} artículo(s)</div>
            </div>
            <button class="btn-revisar amber" onclick="revisarEntrada('${item.ES_ID}','${item.ES_TIPO}','${item.ES_ALMACENID}')">
              <i class="mdi mdi-check-circle-outline"></i> Revisar
            </button>
          </div>`;
          });
        }
        $('#entradas-list').html(html);
      },
      error: function() {
        $('#entradas-list').html('<div style="color:#be123c; font-size:0.8rem; padding:10px 0;">Error al cargar los datos.</div>');
      }
    });
  }

  // ========== Cargar lista de Traspasos ==========
  function cargarTraspasosPendientes() {
    $.ajax({
      url: '../ajax/actividades.traspasos.pendientes.php',
      method: 'GET',
      dataType: 'json',
      success: function(data) {
        let html = '';
        if (!data || data.length === 0) {
          html = '<div style="text-align:center; color:#94a3b8; font-size:0.8rem; padding:10px 0;">Sin traspasos pendientes</div>';
        } else {
          data.forEach(function(item) {
            html += `<div class="folio-item">
            <div>
              <div class="folio-badge"><i class="mdi mdi-swap-horizontal"></i> ${item.TRASPASO_FOLIO}</div>
              <div class="folio-meta">${item.ALMACEN_DE || ''} → ${item.ALMACEN_A || ''} · ${item.CANTIDAD || 0} artículo(s)</div>
            </div>
            <button class="btn-revisar rose" onclick="revisarTraspaso('${item.TRASPASO_ID}','${item.TRASPASO_FOLIO}')">
              <i class="mdi mdi-check-circle-outline"></i> Revisar
            </button>
          </div>`;
          });
        }
        $('#traspasos-list').html(html);
      },
      error: function() {
        $('#traspasos-list').html('<div style="color:#be123c; font-size:0.8rem; padding:10px 0;">Error al cargar los datos.</div>');
      }
    });
  }

  // ========== Cargar lista de Disputas ==========
  function cargarDisputasPendientes() {
    $.ajax({
      url: '../ajax/actividades.disputas.pendientes.php',
      method: 'GET',
      dataType: 'json',
      success: function(data) {
        let html = '';
        if (!data || data.length === 0) {
          html = '<div style="text-align:center; color:#94a3b8; font-size:0.8rem; padding:10px 0;">Sin disputas pendientes</div>';
        } else {
          data.forEach(function(item) {
            html += `<div class="folio-item">
            <div>
              <div class="folio-badge" style="background:#fee2e2; color:#b91c1c;"><i class="mdi mdi-alert-decagram"></i> ${item.TRASPASO_FOLIO}</div>
              <div class="folio-meta">Disputa #${item.DISPUTA_ID} · ${item.DISPUTA_MOTIVO || ''}</div>
            </div>
            <button class="btn-revisar rose" style="background-color: #e11d48; color: white; border-color: #e11d48;" onclick="revisarDisputa('${item.DISPUTA_ID}')">
              <i class="mdi mdi-gavel"></i> Resolver
            </button>
          </div>`;
          });
        }
        $('#disputas-list').html(html);
      },
      error: function() {
        $('#disputas-list').html('<div style="color:#be123c; font-size:0.8rem; padding:10px 0;">Error al cargar los datos.</div>');
      }
    });
  }

  // ========== Redirigir a Entradas con Auto-Open ==========
  function revisarEntrada(esid, tipo, almacenid) {
    window.location.href = '../gui/entradasalida.php?autoopen=' + encodeURIComponent(esid) + '&tipo=' + encodeURIComponent(tipo) + '&almacenid=' + encodeURIComponent(almacenid);
  }

  // ========== Redirigir a Traspasos con Auto-Open ==========
  function revisarTraspaso(traspasoid, folio) {
    window.location.href = '../gui/traspasos.php?autoopen=' + encodeURIComponent(traspasoid);
  }

  // ========== Redirigir a Disputas con Auto-Open ==========
  function revisarDisputa(disputaid) {
    window.location.href = '../gui/disputas.php?autoopen=' + encodeURIComponent(disputaid);
  }
</script>