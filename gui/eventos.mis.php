<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php $usersesion = $_SESSION['ampar']['usuario']; ?>
<?php
$statusFiltro = isset($_GET['status']) ? (string)$_GET['status'] : '14';

$tabsEventos = [
  '14' => 'Creadas',
  '15' => 'Iniciadas',
  '16' => 'En Remisión',
  '29' => 'En Recepción',
  '8'  => 'En Revisión',
  '3'  => 'Finalizadas',
  '5'  => 'Canceladas',
];

if (!array_key_exists($statusFiltro, $tabsEventos)) {
  $statusFiltro = '14';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include_once("../includes/head.php"); ?>
</head>

<body>
  <div id="loading" style="display:none">
    <img id="loading-image" src="../img/logo.png" />
  </div>
  <?php $eventos = new eventos(); ?>
  <div class="container-scroller">
    <?php include_once("../includes/header.php"); ?>
    <div class="container-fluid page-body-wrapper">
      <?php include_once("../includes/menu.sidebar.php") ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <?php include_once("../includes/eventos.menu.php"); ?>
              </div>
              <?php //include_once ("../includes/eventos.dashboard.php");
              ?>
              <div class="tab-content tab-content-basic">
                <!-- Inicia contenido principal -->
                <div class="row">
                  <div class="col-12">
                    <div class="card">
                      <div class="card-header">
                        <h4>Mis Eventos - <?= $tabsEventos[$statusFiltro] ?></h4>
                      </div>
                      <div class="card-body">
                        <div class="mb-3">
                          <ul class="nav nav-tabs" role="tablist">
                            <?php foreach ($tabsEventos as $statusId => $label): ?>
                              <li class="nav-item">
                                <a class="nav-link <?= ($statusFiltro === $statusId ? 'active' : '') ?>" href="?status=<?= $statusId ?>" role="tab">
                                  <?= $label ?>
                                </a>
                              </li>
                            <?php endforeach; ?>
                          </ul>
                        </div>

                        <?php

                        $eventos = new eventos();
                        $res = $eventos->geteventos($usersesion['USUARIO_ID'], $usersesion['USUARIO_ID'], $statusFiltro);
                        if ($res <> 0) {
                        ?>
                          <!-- Desktop Table -->
                          <div class="table-responsive d-none d-md-block" style="width: 100%;">
                            <table class="table table-striped">
                              <tr>
                                <th></th>
                                <th>Folio</th>
                                <th>Status</th>
                                <th>Fecha</th>
                                <th>Tipo Evento</th>
                                <th>Especialista</th>
                                <th>Chofer</th>
                                <th>Flujo</th>
                              </tr>
                              <?php foreach ($res as $r): ?>
                                <?php
                                // Helpers defensivos
                                $evStatus   = (int)$r['EVENTO_STATUSGENERAL'];
                                $remId      = isset($r['REMISION_ID']) ? (int)$r['REMISION_ID'] : 0;
                                $remStatus  = isset($r['REMISION_STATUS']) ? (int)$r['REMISION_STATUS'] : 0;
                                $clienteId  = isset($r['EVENTO_CLIENTEID']) ? (int)$r['EVENTO_CLIENTEID'] : null;

                                // Semáforo REVFLOW
                                $ff = $eventos->getRevflow($r['EVENTO_ID']) ?? [];
                                $oc_s   = (int)($ff['OC_STATUS']   ?? 0);
                                $pago_s = (int)($ff['PAGO_STATUS'] ?? 0);
                                $rev_s  = (int)($ff['REV_STATUS']  ?? 0);
                                $oc_req = (int)($ff['OC_REQUERIDA'] ?? 1);

                                // Helpers de texto/color
                                $ocTxt  = [0 => 'OC:–', 10 => 'OC:Exención', 12 => 'OC:Exención✔', 20 => 'OC:Cargada', 21 => 'OC:Rechazada', 22 => 'OC:Aprobada'][$oc_s] ?? 'OC:?';
                                $ocCls  = [0 => 'secondary', 10 => 'warning', 12 => 'success', 20 => 'info', 21 => 'danger', 22 => 'success'][$oc_s] ?? 'secondary';

                                $pgTxt  = [0 => 'Pago:–', 40 => 'Pago:Abonos', 41 => 'Pago:Cubierto', 42 => 'Pago:Validado'][$pago_s] ?? 'Pago:?';
                                $pgCls  = [0 => 'secondary', 40 => 'info', 41 => 'warning', 42 => 'success'][$pago_s] ?? 'secondary';

                                $rvTxt  = [0 => 'Adm:–', 70 => 'Adm:Revisión', 71 => 'Adm:Observado', 72 => 'Adm:VoBo'][$rev_s] ?? 'Adm:?';
                                $rvCls  = [0 => 'secondary', 70 => 'info', 71 => 'danger', 72 => 'success'][$rev_s] ?? 'secondary';

                                // Flags de visibilidad
                                $eventoFinalizado = ($evStatus === 3);
                                $ocOk   = ($oc_s === 22) || ($oc_s === 12);
                                $pagoOk = in_array($pago_s, [41, 42], true);
                                $esAdmin = true;
                                ?>
                                <tr>
                                  <td>
                                    <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Evento" data-url="../includes/eventos.info.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false">
                                      <img src="../img/info.png" style="width:20px; height:auto; cursor:pointer;" title="Información">
                                    </a>
                                    <a href="../gdocs/eventos.formato.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" target="blank">
                                      <img src="../img/pdf2.png" style="width:20px; height:auto;">
                                    </a>
                                    <?php if ($r['EVENTO_STATUSGENERAL'] == 3): ?>
                                      <a href="../gdocs/remisionES.formato.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" target="blank">
                                        <img src="../img/pdf.png" style="width:20px; height:auto;">
                                      </a>
                                    <?php endif; ?>
                                    <?php if ($r['EVENTO_STATUSGENERAL'] == 14): ?>
                                      <?php if ($r['EVENTO_ESPECIALISTAIDSTATUS'] == 19 and $r['EVENTO_CHOFERIDSTATUS'] == 19) { ?>
                                        <img src="../img/start.png" style="width:20px; height:auto; cursor:pointer" onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',15)" title="Iniciar">
                                      <?php } ?>
                                    <?php endif; ?>
                                    <!-- 1) Finalizar sin remisión -->
                                    <?php if ($evStatus === 15 && (empty($remId) || $remStatus === 5)): ?>
                                      <img src="../img/check.png" style="width:20px; height:auto; cursor:pointer"
                                        title="Finalizar sin remisión"
                                        onclick="finalizarSinRemision('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')">
                                    <?php endif; ?>
                                    <?php if ($evStatus === 16 || ($evStatus === 15 && !empty($remId) && $remStatus !== 5)): ?>
                                      <img src="../img/send.png" style="width:20px; height:auto; cursor:pointer"
                                        title="Pasar a Recepción"
                                        onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',29)">
                                    <?php endif; ?>
                                    <?php if ($evStatus === 29 || $evStatus === 3): ?>
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Archivos de Remisión y Evidencia de Recepción" data-url="../includes/eventos.archivos_recepcion.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false">
                                        <img src="../img/upload.jpg" style="width:20px; height:auto; cursor:pointer;" title="Archivos de Remisión y Recepción">
                                      </a>
                                    <?php endif; ?>
                                    <?php if ($evStatus === 29 && $tieneRem && $tieneEv): ?>
                                      <img src="../img/check.png" style="width:20px; height:auto; cursor:pointer"
                                        title="Enviar a Revisión"
                                        onclick="enviarRevisionLista('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')">
                                    <?php elseif ($evStatus === 8): ?>
                                      <img src="../img/check.png" style="width:20px; height:auto; cursor:pointer"
                                        title="Finalizar Evento"
                                        onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',3)">
                                    <?php endif; ?>
                                    <!-- (A) SUBIR OC -->
                                    <?php if ($eventoFinalizado && $oc_req === 1 && in_array($oc_s, [0, 21], true)): ?>
                                      <img src="../img/upload.jpg" style="width:20px; height:auto;cursor:pointer"
                                        title="Cargar OC de cliente (PDF/imagen)"
                                        onclick="abrirModalOCCliente('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')">
                                    <?php endif; ?>
                                    <!-- (B) SOLICITAR EXENCIÓN DE OC -->
                                    <?php if ($eventoFinalizado && $oc_req === 1 && in_array($oc_s, [0, 21], true)): ?>
                                      <img src="../img/warn.png" style="width:20px; height:auto;cursor:pointer"
                                        title="Solicitar exención de OC"
                                        onclick="abrirModalExencionOC('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')">
                                    <?php endif; ?>
                                    <?php if ($oc_s === 22): ?>
                                      <img src="../img/obs.png" style="width:20px; height:auto;cursor:pointer"
                                        title="Ver OC y comentarios"
                                        data-toggle="modal" data-target="#modalglobal"
                                        data-title="OC aprobada — Consulta"
                                        data-url="../includes/oc.info.php?eventoid=<?= $r['EVENTO_ID'] ?>">
                                    <?php endif; ?>
                                    <!-- (C) REVISAR OC (ADMIN) -->
                                    <?php if ($esAdmin && $eventoFinalizado && in_array($oc_s, [10, 20], true)): ?>
                                      <img src="../img/review.png" style="width:20px;cursor:pointer"
                                        title="Revisar OC / Exención"
                                        onclick="abrirModalRevisarOC('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>', <?= $oc_s ?>)">
                                    <?php endif; ?>
                                    <!-- (D) CARGOS / ABONOS -->
                                    <?php if ($r['EVENTO_PRESUPUESTOPARTICULAR'] <> '' && in_array($evStatus, [14, 15, 16, 3], true)): ?>
                                      <img src="../img/money.png" style="width:20px;cursor:pointer"
                                        title="Agregar cargo/anticipo (con comprobante)"
                                        onclick="abrirModalCargo('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')">
                                      <img src="../img/paid.png" style="width:20px;cursor:pointer"
                                        title="Marcar como Pagado (23)"
                                        onclick="marcarPagado('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')">
                                    <?php endif; ?>
                                    <!-- (E) VALIDAR PAGOS (ADMIN) -->
                                    <?php if ($esAdmin && $pago_s === 41): ?>
                                      <img src="../img/check.png" style="width:20px;height:auto;cursor:pointer"
                                        title="Validar pagos (42)"
                                        onclick="validarPagos('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')">
                                    <?php endif; ?>
                                    <?php if ($r['EVENTO_PRESUPUESTOPARTICULAR'] <> ''): ?>
                                      <a href="../gdocs/eventos.cotizacion.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" target="_blank">
                                        <img src="../img/PDF.png" style="width:20px; height:auto; cursor:pointer" title="Cotización">
                                      </a>
                                    <?php endif; ?>
                                  </td>

                                  <td><?= $r['EVENTO_FOLIO'] ?></td>
                                  <td style="font-weight:bolder; color:#<?= $r['STATUS_COLOR'] ?>"><?= $r['STATUS_NOMBRE'] ?></td>
                                  <td><?= $r['EVENTO_FECHACREACION'] ?></td>
                                  <td><?= $r['TIPOEVENTO_NOMBRE'] ?><br><span class="text-muted small"><?= $r['TIPOEVENTOGRUPO_NOMBRE'] ?> - <?= $r['TIPOEVENTOSUBGRUPO_NOMBRE'] ?><span></td>

                                  <!-- Especialista -->
                                  <td>
                                    <?php if ($r['ESPECIALISTA_NOMBRE'] <> ""): ?>
                                      <?= $r['ESPECIALISTA_NOMBRE'] ?> <span style="color:#<?= $r['STATUSCOLORESPECIALISTA'] ?>">(<?= $r['STATUSESPECIALISTA'] ?>)</span>
                                      <?php if ($r['EVENTO_ESPECIALISTAID'] == $usersesion['USUARIO_ID'] and $r['EVENTO_ESPECIALISTAIDSTATUS'] == 20): ?>
                                        <img src="../img/likemanita.png" style="width:16px; height:auto; cursor:pointer;" title="Confirmar evento como especialista" onclick="confirmarevento('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',2,19)">
                                      <?php endif; ?>
                                    <?php else: ?>
                                      <div class="alert alert-warning text-center p-1 m-1 small">
                                        No asignado
                                      </div>
                                    <?php endif; ?>
                                  </td>

                                  <!-- Chofer -->
                                  <td>
                                    <?php if ($r['CHOFER_NOMBRE'] <> ""): ?>
                                      <?= $r['CHOFER_NOMBRE'] ?> <span style="color:#<?= $r['STATUSCOLORCHOFER'] ?>">(<?= $r['STATUSCHOFER'] ?>)</span>
                                      <?php if ($r['EVENTO_CHOFERID'] == $usersesion['USUARIO_ID'] and $r['EVENTO_CHOFERIDSTATUS'] == 20): ?>
                                        <img src="../img/likemanita.png" style="width:16px; height:auto; cursor:pointer;" title="Confirmar evento como chofer" onclick="confirmarevento('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',3,19)">
                                      <?php endif; ?>
                                    <?php else: ?>
                                      <div class="alert alert-warning text-center p-1 m-1 small">
                                        No asignado
                                      </div>
                                    <?php endif; ?>
                                  </td>

                                  <td class="text-nowrap">
                                    <span class="badge badge-<?= $ocCls ?>"><?= $ocTxt ?></span>
                                    <span class="badge badge-<?= $pgCls ?>"><?= $pgTxt ?></span>
                                  </td>
                                </tr>
                              <?php endforeach; ?>
                            </table>
                          </div>
                          
                          <!-- Mobile View (Cards) -->
                          <div class="d-block d-md-none mt-2">
                            <?php foreach ($res as $r): ?>
                              <?php
                                // Helpers defensivos (Re-calculados para móvil)
                                $evStatus   = (int)$r['EVENTO_STATUSGENERAL'];
                                $remId      = isset($r['REMISION_ID']) ? (int)$r['REMISION_ID'] : 0;
                                $remStatus  = isset($r['REMISION_STATUS']) ? (int)$r['REMISION_STATUS'] : 0;
                                $clienteId  = isset($r['EVENTO_CLIENTEID']) ? (int)$r['EVENTO_CLIENTEID'] : null;

                                // Semáforo REVFLOW
                                $ff = $eventos->getRevflow($r['EVENTO_ID']) ?? [];
                                $oc_s   = (int)($ff['OC_STATUS']   ?? 0);
                                $pago_s = (int)($ff['PAGO_STATUS'] ?? 0);
                                $rev_s  = (int)($ff['REV_STATUS']  ?? 0);
                                $oc_req = (int)($ff['OC_REQUERIDA'] ?? 1);

                                // Helpers de texto/color
                                $ocTxt  = [0 => 'OC:–', 10 => 'OC:Exención', 12 => 'OC:Exención✔', 20 => 'OC:Cargada', 21 => 'OC:Rechazada', 22 => 'OC:Aprobada'][$oc_s] ?? 'OC:?';
                                $ocCls  = [0 => 'secondary', 10 => 'warning', 12 => 'success', 20 => 'info', 21 => 'danger', 22 => 'success'][$oc_s] ?? 'secondary';

                                $pgTxt  = [0 => 'Pago:–', 40 => 'Pago:Abonos', 41 => 'Pago:Cubierto', 42 => 'Pago:Validado'][$pago_s] ?? 'Pago:?';
                                $pgCls  = [0 => 'secondary', 40 => 'info', 41 => 'warning', 42 => 'success'][$pago_s] ?? 'secondary';

                                $eventoFinalizado = ($evStatus === 3);
                                $ocOk   = ($oc_s === 22) || ($oc_s === 12);
                                $pagoOk = in_array($pago_s, [41, 42], true);
                                $esAdmin = true;
                              ?>
                              <div class="mobile-card">
                                <div class="mobile-card-header">
                                  <span class="mobile-card-title"><?= $r['EVENTO_FOLIO'] ?></span>
                                  <span class="mobile-card-badge" style="color:#<?= $r['STATUS_COLOR'] ?>; border: 1px solid #<?= $r['STATUS_COLOR'] ?>40; background-color: #<?= $r['STATUS_COLOR'] ?>15;">
                                    <?= $r['STATUS_NOMBRE'] ?>
                                  </span>
                                </div>
                                <div class="mobile-card-body">
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Fecha</span>
                                    <span class="mobile-card-value"><?= $r['EVENTO_FECHACREACION'] ?></span>
                                  </div>
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Tipo</span>
                                    <span class="mobile-card-value"><?= $r['TIPOEVENTO_NOMBRE'] ?> <br><small class="text-muted"><?= $r['TIPOEVENTOGRUPO_NOMBRE'] ?></small></span>
                                  </div>
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Especialista</span>
                                    <span class="mobile-card-value text-right">
                                      <?php if ($r['ESPECIALISTA_NOMBRE'] <> ""): ?>
                                        <?= $r['ESPECIALISTA_NOMBRE'] ?> <br><small style="color:#<?= $r['STATUSCOLORESPECIALISTA'] ?>">(<?= $r['STATUSESPECIALISTA'] ?>)</small>
                                        <?php if ($r['EVENTO_ESPECIALISTAID'] == $usersesion['USUARIO_ID'] and $r['EVENTO_ESPECIALISTAIDSTATUS'] == 20): ?>
                                          <img src="../img/likemanita.png" style="width:20px; height:auto; cursor:pointer;" onclick="confirmarevento('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',2,19)">
                                        <?php endif; ?>
                                      <?php else: ?>
                                        <span class="text-warning">No asignado</span>
                                      <?php endif; ?>
                                    </span>
                                  </div>
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Chofer</span>
                                    <span class="mobile-card-value text-right">
                                      <?php if ($r['CHOFER_NOMBRE'] <> ""): ?>
                                        <?= $r['CHOFER_NOMBRE'] ?> <br><small style="color:#<?= $r['STATUSCOLORCHOFER'] ?>">(<?= $r['STATUSCHOFER'] ?>)</small>
                                        <?php if ($r['EVENTO_CHOFERID'] == $usersesion['USUARIO_ID'] and $r['EVENTO_CHOFERIDSTATUS'] == 20): ?>
                                          <img src="../img/likemanita.png" style="width:20px; height:auto; cursor:pointer;" onclick="confirmarevento('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',3,19)">
                                        <?php endif; ?>
                                      <?php else: ?>
                                        <span class="text-warning">No asignado</span>
                                      <?php endif; ?>
                                    </span>
                                  </div>
                                  <div class="mobile-card-row mt-2">
                                    <span class="mobile-card-label">Flujo</span>
                                    <span class="mobile-card-value">
                                      <span class="badge badge-<?= $ocCls ?>"><?= $ocTxt ?></span>
                                      <span class="badge badge-<?= $pgCls ?>"><?= $pgTxt ?></span>
                                    </span>
                                  </div>
                                </div>
                                <div class="mobile-card-actions">
                                  <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Evento" data-url="../includes/eventos.info.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false">
                                    <img src="../img/info.png" style="width:28px; height:auto; cursor:pointer;" title="Información">
                                  </a>
                                  <a href="../gdocs/eventos.formato.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" target="blank">
                                    <img src="../img/pdf2.png" style="width:28px; height:auto;">
                                  </a>
                                  <?php if ($r['EVENTO_STATUSGENERAL'] == 3): ?>
                                    <a href="../gdocs/remisionES.formato.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" target="blank">
                                      <img src="../img/pdf.png" style="width:28px; height:auto;">
                                    </a>
                                  <?php endif; ?>
                                  <?php if ($r['EVENTO_STATUSGENERAL'] == 14): ?>
                                    <?php if ($r['EVENTO_ESPECIALISTAIDSTATUS'] == 19 and $r['EVENTO_CHOFERIDSTATUS'] == 19) { ?>
                                      <img src="../img/start.png" style="width:28px; height:auto; cursor:pointer" onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',15)" title="Iniciar">
                                    <?php } ?>
                                  <?php endif; ?>
                                  <!-- 1) Finalizar sin remisión -->
                                  <?php if ($evStatus === 15 && (empty($remId) || $remStatus === 5)): ?>
                                    <img src="../img/check.png" style="width:28px; height:auto; cursor:pointer"
                                      title="Finalizar sin remisión"
                                      onclick="finalizarSinRemision('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')">
                                  <?php endif; ?>
                                  <?php if ($evStatus === 16 || ($evStatus === 15 && !empty($remId) && $remStatus !== 5)): ?>
                                    <img src="../img/send.png" style="width:28px; height:auto; cursor:pointer"
                                      title="Pasar a Recepción"
                                      onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',29)">
                                  <?php endif; ?>
                                  <?php if ($evStatus === 29 || $evStatus === 3): ?>
                                    <a data-toggle="modal" data-target="#modalglobal" data-title="Archivos de Remisión y Evidencia de Recepción" data-url="../includes/eventos.archivos_recepcion.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false">
                                      <img src="../img/upload.jpg" style="width:28px; height:auto; cursor:pointer;" title="Archivos de Remisión y Recepción">
                                    </a>
                                  <?php endif; ?>
                                  <?php if ($evStatus === 29 && $tieneRem && $tieneEv): ?>
                                    <img src="../img/check.png" style="width:28px; height:auto; cursor:pointer"
                                      title="Enviar a Revisión"
                                      onclick="enviarRevisionLista('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')">
                                  <?php elseif ($evStatus === 8): ?>
                                    <img src="../img/check.png" style="width:28px; height:auto; cursor:pointer"
                                      title="Finalizar Evento"
                                      onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',3)">
                                  <?php endif; ?>
                                  <!-- (A) SUBIR OC -->
                                  <?php if ($eventoFinalizado && $oc_req === 1 && in_array($oc_s, [0, 21], true)): ?>
                                    <img src="../img/upload.jpg" style="width:28px; height:auto;cursor:pointer"
                                      title="Cargar OC de cliente (PDF/imagen)"
                                      onclick="abrirModalOCCliente('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')">
                                  <?php endif; ?>
                                  <!-- (B) SOLICITAR EXENCIÓN DE OC -->
                                  <?php if ($eventoFinalizado && $oc_req === 1 && in_array($oc_s, [0, 21], true)): ?>
                                    <img src="../img/warn.png" style="width:28px; height:auto;cursor:pointer"
                                      title="Solicitar exención de OC"
                                      onclick="abrirModalExencionOC('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')">
                                  <?php endif; ?>
                                  <?php if ($oc_s === 22): ?>
                                    <img src="../img/obs.png" style="width:28px; height:auto;cursor:pointer"
                                      title="Ver OC y comentarios"
                                      data-toggle="modal" data-target="#modalglobal"
                                      data-title="OC aprobada — Consulta"
                                      data-url="../includes/oc.info.php?eventoid=<?= $r['EVENTO_ID'] ?>">
                                  <?php endif; ?>
                                  <!-- (C) REVISAR OC (ADMIN) -->
                                  <?php if ($esAdmin && $eventoFinalizado && in_array($oc_s, [10, 20], true)): ?>
                                    <img src="../img/review.png" style="width:28px;cursor:pointer"
                                      title="Revisar OC / Exención"
                                      onclick="abrirModalRevisarOC('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>', <?= $oc_s ?>)">
                                  <?php endif; ?>
                                  <!-- (D) CARGOS / ABONOS -->
                                  <?php if ($r['EVENTO_PRESUPUESTOPARTICULAR'] <> '' && in_array($evStatus, [14, 15, 16, 3], true)): ?>
                                    <img src="../img/money.png" style="width:28px;cursor:pointer"
                                      title="Agregar cargo/anticipo (con comprobante)"
                                      onclick="abrirModalCargo('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')">
                                    <img src="../img/paid.png" style="width:28px;cursor:pointer"
                                      title="Marcar como Pagado (23)"
                                      onclick="marcarPagado('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')">
                                  <?php endif; ?>
                                  <!-- (E) VALIDAR PAGOS (ADMIN) -->
                                  <?php if ($esAdmin && $pago_s === 41): ?>
                                    <img src="../img/check.png" style="width:28px;height:auto;cursor:pointer"
                                      title="Validar pagos (42)"
                                      onclick="validarPagos('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')">
                                  <?php endif; ?>
                                  <?php if ($r['EVENTO_PRESUPUESTOPARTICULAR'] <> ''): ?>
                                    <a href="../gdocs/eventos.cotizacion.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" target="_blank">
                                      <img src="../img/PDF.png" style="width:28px; height:auto; cursor:pointer" title="Cotización">
                                    </a>
                                  <?php endif; ?>
                                </div>
                              </div>
                            <?php endforeach; ?>
                          </div>
                        <?php
                        } else {
                          echo '<div class="alert alert-warning text-center">No se encontraron registros</div>';
                        }
                        ?>
                      </div>
                    </div>
                  </div>
                </div>
                <br>
                <div class="row">
                  <div class="col-12">
                    <div class="card">
                      <div class="card-header">
                        <h4>Invitación a Eventos</h4>
                      </div>
                      <div class="card-body">
                        <?php

                        $eventos = new eventos();
                        $res = $eventos->geteventosparainvitacion($usersesion['USUARIO_ID']);
                        if ($res <> 0) {
                        ?>
                          <!-- Desktop Table -->
                          <div class="table-responsive d-none d-md-block" style="width: 100%;">
                            <table class="table table-striped">
                              <tr>
                                <th></th>
                                <th>Folio</th>
                                <th>Status</th>
                                <th>Fecha</th>
                                <th>Tipo Evento</th>
                                <th>Especialista</th>
                                <th>Chofer</th>
                              </tr>
                              <?php foreach ($res as $r): ?>
                                <tr>
                                  <td>
                                    <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Evento" data-url="../includes/eventos.info.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false">
                                      <img src="../img/info.png" style="width:20px; height:auto; cursor:pointer;" title="Información">
                                    </a>
                                    <a href="../gdocs/eventos.formato.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" target="blank">
                                      <img src="../img/pdf2.png" style="width:20px; height:auto;">
                                    </a>
                                    <?php if ($r['EVENTO_STATUSGENERAL'] == 3): ?>
                                      <a href="../gdocs/remisionES.formato.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" target="blank">
                                        <img src="../img/pdf.png" style="width:20px; height:auto;">
                                      </a>
                                    <?php endif; ?>
                                    <?php if ($r['EVENTO_STATUSGENERAL'] == 14): ?>
                                      <?php if ($r['EVENTO_ESPECIALISTAIDSTATUS'] == 19 and $r['EVENTO_CHOFERIDSTATUS'] == 19) { ?>
                                        <img src="../img/start.png" style="width:20px; height:auto; cursor:pointer" onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',15)" title="Iniciar">
                                      <?php } ?>
                                    <?php endif; ?>
                                  </td>

                                  <td><?= $r['EVENTO_FOLIO'] ?></td>
                                  <td style="font-weight:bolder; color:#<?= $r['STATUS_COLOR'] ?>"><?= $r['STATUS_NOMBRE'] ?></td>
                                  <td><?= $r['EVENTO_FECHACREACION'] ?></td>
                                  <td><?= $r['TIPOEVENTO_NOMBRE'] ?><br><span class="text-muted small"><?= $r['TIPOEVENTOGRUPO_NOMBRE'] ?> - <?= $r['TIPOEVENTOSUBGRUPO_NOMBRE'] ?><span></td>

                                  <!-- Especialista -->
                                  <td>
                                    <?php if ($r['ESPECIALISTA_NOMBRE'] <> ""): ?>
                                      <?= $r['ESPECIALISTA_NOMBRE'] ?>
                                      <?php if ($r['EVENTO_ESPECIALISTAID'] == $usersesion['USUARIO_ID'] and $r['EVENTO_ESPECIALISTAIDSTATUS'] == 20): ?>
                                        <img src="../img/likemanita.png" style="width:16px; height:auto; cursor:pointer;" title="Confirmar evento como especialista" onclick="confirmarevento('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',2,19)">
                                      <?php endif; ?>
                                    <?php else: ?>
                                      <div class="alert alert-warning text-center p-1 m-1 small">
                                        No asignado
                                        <?php if ($r['PUEDE_SOLICITAR_ESPECIALISTA'] == 1): ?>
                                          <img src="../img/like.webp" style="width:16px; height:auto; cursor:pointer;" title="Asignarme evento como especialista" onclick="asignarespchoupdate('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',2,7)">
                                        <?php endif; ?>
                                      </div>
                                    <?php endif; ?>
                                  </td>

                                  <!-- Chofer -->
                                  <td>
                                    <?php if ($r['CHOFER_NOMBRE'] <> ""): ?>
                                      <?= $r['CHOFER_NOMBRE'] ?>
                                      <?php if ($r['EVENTO_CHOFERID'] == $usersesion['USUARIO_ID'] and $r['EVENTO_CHOFERIDSTATUS'] == 20): ?>
                                        <img src="../img/likemanita.png" style="width:16px; height:auto; cursor:pointer;" title="Confirmar evento como chofer" onclick="confirmarevento('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',3,19)">
                                      <?php endif; ?>
                                    <?php else: ?>
                                      <div class="alert alert-warning text-center p-1 m-1 small">
                                        No asignado
                                        <?php if ($r['PUEDE_SOLICITAR_CHOFER'] == 1): ?>
                                          <img src="../img/like.webp" style="width:16px; height:auto; cursor:pointer;" title="Asignarme evento como chofer" onclick="asignarespchoupdate('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',3,7)">
                                        <?php endif; ?>
                                      </div>
                                    <?php endif; ?>
                                  </td>
                                </tr>
                              <?php endforeach; ?>
                            </table>
                          </div>
                          
                          <!-- Mobile View (Cards) -->
                          <div class="d-block d-md-none mt-2">
                            <?php foreach ($res as $r): ?>
                              <div class="mobile-card">
                                <div class="mobile-card-header">
                                  <span class="mobile-card-title"><?= $r['EVENTO_FOLIO'] ?></span>
                                  <span class="mobile-card-badge" style="color:#<?= $r['STATUS_COLOR'] ?>; border: 1px solid #<?= $r['STATUS_COLOR'] ?>40; background-color: #<?= $r['STATUS_COLOR'] ?>15;">
                                    <?= $r['STATUS_NOMBRE'] ?>
                                  </span>
                                </div>
                                <div class="mobile-card-body">
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Fecha</span>
                                    <span class="mobile-card-value"><?= $r['EVENTO_FECHACREACION'] ?></span>
                                  </div>
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Tipo</span>
                                    <span class="mobile-card-value"><?= $r['TIPOEVENTO_NOMBRE'] ?> <br><small class="text-muted"><?= $r['TIPOEVENTOGRUPO_NOMBRE'] ?></small></span>
                                  </div>
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Especialista</span>
                                    <span class="mobile-card-value text-right">
                                      <?php if ($r['ESPECIALISTA_NOMBRE'] <> ""): ?>
                                        <?= $r['ESPECIALISTA_NOMBRE'] ?>
                                        <?php if ($r['EVENTO_ESPECIALISTAID'] == $usersesion['USUARIO_ID'] and $r['EVENTO_ESPECIALISTAIDSTATUS'] == 20): ?>
                                          <img src="../img/likemanita.png" style="width:20px; height:auto; cursor:pointer;" onclick="confirmarevento('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',2,19)">
                                        <?php endif; ?>
                                      <?php else: ?>
                                        <span class="text-warning">No asignado</span>
                                        <?php if ($r['PUEDE_SOLICITAR_ESPECIALISTA'] == 1): ?>
                                          <img src="../img/like.webp" style="width:20px; height:auto; cursor:pointer;" onclick="asignarespchoupdate('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',2,7)">
                                        <?php endif; ?>
                                      <?php endif; ?>
                                    </span>
                                  </div>
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Chofer</span>
                                    <span class="mobile-card-value text-right">
                                      <?php if ($r['CHOFER_NOMBRE'] <> ""): ?>
                                        <?= $r['CHOFER_NOMBRE'] ?>
                                        <?php if ($r['EVENTO_CHOFERID'] == $usersesion['USUARIO_ID'] and $r['EVENTO_CHOFERIDSTATUS'] == 20): ?>
                                          <img src="../img/likemanita.png" style="width:20px; height:auto; cursor:pointer;" onclick="confirmarevento('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',3,19)">
                                        <?php endif; ?>
                                      <?php else: ?>
                                        <span class="text-warning">No asignado</span>
                                        <?php if ($r['PUEDE_SOLICITAR_CHOFER'] == 1): ?>
                                          <img src="../img/like.webp" style="width:20px; height:auto; cursor:pointer;" onclick="asignarespchoupdate('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',3,7)">
                                        <?php endif; ?>
                                      <?php endif; ?>
                                    </span>
                                  </div>
                                </div>
                                <div class="mobile-card-actions">
                                  <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Evento" data-url="../includes/eventos.info.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false">
                                    <img src="../img/info.png" style="width:28px; height:auto; cursor:pointer;" title="Información">
                                  </a>
                                  <a href="../gdocs/eventos.formato.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" target="blank">
                                    <img src="../img/pdf2.png" style="width:28px; height:auto;">
                                  </a>
                                  <?php if ($r['EVENTO_STATUSGENERAL'] == 3): ?>
                                    <a href="../gdocs/remisionES.formato.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" target="blank">
                                      <img src="../img/pdf.png" style="width:28px; height:auto;">
                                    </a>
                                  <?php endif; ?>
                                  <?php if ($r['EVENTO_STATUSGENERAL'] == 14): ?>
                                    <?php if ($r['EVENTO_ESPECIALISTAIDSTATUS'] == 19 and $r['EVENTO_CHOFERIDSTATUS'] == 19) { ?>
                                      <img src="../img/start.png" style="width:28px; height:auto; cursor:pointer" onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',15)" title="Iniciar">
                                    <?php } ?>
                                  <?php endif; ?>
                                </div>
                              </div>
                            <?php endforeach; ?>
                          </div>
                        <?php

                        } else {
                          echo '<div class="alert alert-warning text-center">No se encontraron registros</div>';
                        }
                        ?>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Finaliza contenido princial -->
              </div>
            </div>
          </div>
        </div>
        <?php include_once("../includes/modalglobal.php") ?>
        <?php include_once("../includes/eventos.modal.confirmar.finalizacion.php") ?>
        <?php include_once("../includes/footer.php"); ?>
      </div>
    </div>
  </div>
  <?php include_once("../includes/foot.php"); ?>
</body>

</html>

<!-- Modal: Cargar OC Cliente -->
<div class="modal fade" id="modalOCCliente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formOCCliente" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Cargar OC de cliente</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="eventoid" id="oc_eventoid">
        <div class="form-group">
          <label>Archivo (PDF/JPG/PNG)</label>
          <input type="file" class="form-control" name="archivo" id="oc_archivo" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
        <div class="form-group">
          <label>Notas</label>
          <textarea class="form-control" name="notas" id="oc_notas" rows="2" placeholder="Opcional"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Subir OC</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Agregar cargo interno -->
<div class="modal fade" id="modalCargo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formCargo" class="modal-content" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title">Agregar cargo/anticipo al evento</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="eventoid" id="cargo_eventoid">
        <div class="form-group">
          <label>Monto</label>
          <input type="number" step="0.01" min="0" class="form-control" name="monto" id="cargo_monto" required>
        </div>

        <div class="form-group">
          <label>Concepto/Notas</label>
          <textarea class="form-control" name="concepto" id="cargo_concepto" rows="2" placeholder="Opcional"></textarea>
        </div>

        <div class="form-group">
          <label>Comprobante (PDF/JPG/PNG) — opcional</label>
          <input type="file" class="form-control" name="archivo" id="cargo_archivo" accept=".pdf,.jpg,.jpeg,.png">
          <small class="text-muted">Máx. 10 MB</small>
        </div>

        <small class="text-muted" id="cargo_hint">El backend validará que no supere el monto de la remisión.</small>
      </div>

      <div class="modal-footer">
        <button type="submit" id="btnGuardarCargo" class="btn btn-success">Guardar cargo</button>
      </div>

      <div class="modal-body">
        <div class="mt-3">
          <h6 class="mb-2">Cargos capturados</h6>
          <div class="table-responsive">
            <table class="table table-sm table-striped mb-1">
              <thead>
                <tr>
                  <th style="width: 34%;">Fecha</th>
                  <th style="width: 26%;">Monto</th>
                  <th>Concepto</th>
                  <th style="width: 40px;"></th>
                </tr>
              </thead>
              <tbody id="cargo_list"></tbody>
              <tfoot>
                <tr>
                  <th colspan="1" class="text-end">Total</th>
                  <th id="cargo_total">$0.00</th>
                  <th colspan="2"></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

    </form>
  </div>
</div>

<div class="modal fade" id="modalExencionOC" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formExencionOC" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Solicitar exención de OC</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="ex_eventoid" name="eventoid">
        <div class="form-group">
          <label>Motivo</label>
          <textarea class="form-control" id="ex_motivo" name="motivo" rows="3" required
            placeholder="Explica por qué el cliente no emite OC"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-warning" type="submit">Solicitar exención</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalRevisarOC" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formRevisarOC" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Revisión de OC / Exención</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="rev_eventoid" name="eventoid">
        <input type="hidden" id="rev_ocstatus" name="ocstatus">
        <div class="form-group">
          <label>Resultado</label>
          <select class="form-control" id="rev_aprobar" name="aprobar">
            <option value="1">Aprobar</option>
            <option value="0">Rechazar</option>
          </select>
        </div>
        <div class="form-group">
          <label>Notas / Motivo</label>
          <textarea class="form-control" id="rev_motivo" name="motivo" rows="3" placeholder="Opcional"></textarea>
          <small class="text-muted">Si es exención (10) → “Aprobar” la deja en 12. Si es OC cargada (20) → “Aprobar” la deja en 22.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" type="submit">Guardar revisión</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalResolverAdmin" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formResolverAdmin" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Resolución de administración</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="adm_eventoid" name="eventoid">
        <input type="hidden" id="adm_aprobar" name="aprobar">
        <div class="form-group">
          <label>Notas</label>
          <textarea class="form-control" id="adm_notas" name="notas" rows="3" placeholder="Opcional"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-success" type="submit">Guardar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Revisión/Consulta OC (UNIFICADO) -->
<div class="modal fade" id="modalOCReview" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="formOCReview" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Revisión de OC</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>

      <!-- Contenido HTML (archivo + comentarios) viene por AJAX de oc.info.php -->
      <div class="modal-body" id="ocreview_body">
        <div class="text-center text-muted">Cargando...</div>
      </div>

      <!-- Footer con acciones: SOLO visible si OC_STATUS = 20 (cargada, pendiente) -->
      <div class="modal-footer d-none" id="ocreview_footer">
        <input type="hidden" id="ocreview_eventoid">
        <div class="form-inline mr-auto">
          <label class="mr-2">Resultado</label>
          <select class="form-control mr-2" id="ocreview_aprobar" name="aprobar">
            <option value="1">Aprobar</option>
            <option value="0">Rechazar</option>
          </select>
          <input type="text" class="form-control" id="ocreview_motivo" name="motivo" placeholder="Comentario (opcional)" style="min-width:260px">
        </div>
        <button class="btn btn-primary" type="submit">Guardar</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalPagosReview" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="formPagosReview" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Revisión de pagos</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body" id="pagosreview_body">
        <div class="text-center text-muted">Cargando...</div>
      </div>
      <div class="modal-footer d-none" id="pagosreview_footer">
        <input type="hidden" id="pagosreview_eventoid">
        <div class="form-inline mr-auto">
          <label class="mr-2">Resultado</label>
          <select class="form-control mr-2" id="pagosreview_aprobar" name="aprobar">
            <option value="1">VoBo (Validar)</option>
            <option value="0">Observar</option>
          </select>
          <input type="text" class="form-control" id="pagosreview_motivo" name="motivo" placeholder="Comentario (opcional)" style="min-width:260px">
        </div>
        <button class="btn btn-primary" type="submit">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
  $('#modalglobal').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var url = button.data('url');
    var title = button.data('title');

    var modal = $(this);
    modal.find('.modal-title').text(title);
    modal.find('.modal-dialog').addClass('modal-xl')

    // Mostrar loading
    $('#loading').show();

    // AJAX
    $.ajax({
      url: url,
      type: 'GET',
      success: function(response) {
        modal.find('.modal-body').html(response);
      },
      error: function() {
        modal.find('.modal-body').html('<p>Error al cargar la página.</p>');
      },
      complete: function() {
        // Ocultar loading al finalizar (éxito o error)
        $('#loading').hide();
      }
    });
  });

  function enviarRevisionLista(id, folio) {
    Swal.fire({
      text: '¿Seguro que deseas enviar a revisión el evento ' + folio + '?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, enviar a revisión',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-primary',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '../ajax/eventos.enviar.revision.php',
          type: 'POST',
          data: { eventoid: id },
          beforeSend: function() { $("#loading").show(); },
          success: function(response) {
            if (response === "") {
              Swal.fire({
                html: "Evento " + folio + " enviado a revisión con éxito",
                icon: "success",
                customClass: { confirmButton: 'btn btn-success' }
              }).then(() => { location.reload(); });
            } else {
              Swal.fire({
                title: "Atención",
                html: response,
                icon: "warning",
                customClass: { confirmButton: 'btn btn-warning' }
              });
            }
          },
          error: function() {
            Swal.fire({ title: "Error", text: "Error al procesar la solicitud.", icon: "error" });
          },
          complete: function() { $("#loading").hide(); }
        });
      }
    });
  }

  function changestatus(id, folio, statusid) {
    if (statusid === 3 || statusid === '3') {
      if (typeof mostrarConfirmacionEvento === 'function') {
        mostrarConfirmacionEvento(id, folio);
        return;
      }
    }
    var etiqueta = "";
    var etiquetares = "";
    switch (statusid) {
      case 29:
        etiqueta = "pasar a recepción";
        etiquetares = "pasado a recepción";
        break;
      case 15:
        etiqueta = "iniciar";
        etiquetares = "iniciado";
        break;
      case 3:
        etiqueta = "finalizar";
        etiquetares = "finalizado";
        break;
      case 5:
        etiqueta = "cancelar";
        etiquetares = "cancelado";
        break;
      default:
    }
    Swal.fire({
      text: '¿Seguro que deseas ' + etiqueta + ' el evento ' + folio + '?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, ' + etiqueta,
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false // necesario si usas clases Bootstrap
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '../ajax/eventos.update.statusgeneral.php',
          type: 'POST',
          data: {
            id: id,
            status: statusid
          },
          dataType: 'html',
          beforeSend: function() {
            $("#loading").show();
          },
          success: function(response) {
            if (response.trim() === "") {
              Swal.fire({
                html: "Evento " + folio + " " + etiquetares + " con éxito",
                icon: "success",
                customClass: {
                  confirmButton: 'btn btn-success' // usa clases de Bootstrap
                }
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                html: response,
                icon: "warning",
                customClass: {
                  confirmButton: 'btn btn-success' // usa clases de Bootstrap
                }
              });
            }

          },
          error: function(xhr, status, error) {
            console.error('Error en la solicitud:', error);
          },
          complete: function(data) {
            $("#loading").hide();
          }
        });
      } else {
        return false;
      }
    });
  }

  function confirmarevento(eventoid, usuarioid, tipoid, status) {
    Swal.fire({
      text: '¿Seguro que deseas confirmar?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, confirmar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false // necesario si usas clases Bootstrap
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '../ajax/eventos.confirmarevento.espcho.php',
          type: 'POST',
          data: {
            eventoid: eventoid,
            tipoid: tipoid,
            usuarioid: usuarioid,
            status: status
          },
          dataType: 'html',
          beforeSend: function() {
            $("#loading").show();
          },
          success: function(response) {
            if (response.trim() === "") {
              Swal.fire({
                html: "Evento confirmado con éxito",
                icon: "success",
                customClass: {
                  confirmButton: 'btn btn-success' // usa clases de Bootstrap
                }
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                html: response,
                icon: "warning",
                customClass: {
                  confirmButton: 'btn btn-success' // usa clases de Bootstrap
                }
              });
            }

          },
          error: function(xhr, status, error) {
            console.error('Error en la solicitud:', error);
          },
          complete: function(data) {
            $("#loading").hide();
          }
        });
      } else {
        return false;
      }
    });
  }

  function asignarespchoupdate(eventoid, usuarioid, tipoid, status) {
    Swal.fire({
      text: '¿Deseas solicitar atender este evento?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, solicitar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false // necesario si usas clases Bootstrap
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '../ajax/eventos.update.espcho.php',
          type: 'POST',
          data: {
            eventoid: eventoid,
            tipoid: tipoid,
            usuarioid: usuarioid,
            status: status
          },
          dataType: 'html',
          beforeSend: function() {
            $("#loading").show();
          },
          success: function(response) {
            if (response.trim() === "") {
              Swal.fire({
                html: "Evento asignado con éxito",
                icon: "success",
                customClass: {
                  confirmButton: 'btn btn-success' // usa clases de Bootstrap
                }
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                html: response,
                icon: "warning",
                customClass: {
                  confirmButton: 'btn btn-success' // usa clases de Bootstrap
                }
              });
            }

          },
          error: function(xhr, status, error) {
            console.error('Error en la solicitud:', error);
          },
          complete: function(data) {
            $("#loading").hide();
          }
        });
      } else {
        return false;
      }
    });
  }
</script>
<script>
  function finalizarSinRemision(eventoid, folio) {
    Swal.fire({
      text: '¿Finalizar el evento ' + folio + ' sin remisión?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, finalizar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then(res => {
      if (!res.isConfirmed) return;
      $.ajax({
        url: '../ajax/eventos.finalizar.sinremision.php',
        type: 'POST',
        data: {
          eventoid: eventoid
        },
        beforeSend: () => $("#loading").show(),
        success: function(resp) {
          if (resp === '') {
            Swal.fire({
                html: 'Evento finalizado.',
                icon: 'success',
                customClass: {
                  confirmButton: 'btn btn-success'
                }
              })
              .then(() => location.reload());
          } else {
            Swal.fire({
              html: resp,
              icon: 'warning',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          }
        },
        complete: () => $("#loading").hide()
      })
    });
  }

  // -------- OC Cliente ----------
  function abrirModalOCCliente(eventoid, folio) {
    $('#oc_eventoid').val(eventoid);
    $('#oc_archivo').val('');
    $('#oc_notas').val('');
    $('#modalOCCliente').modal('show');
  }

  $('#formOCCliente').on('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    $.ajax({
      url: '../ajax/revflow.oc.upload.php',
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      beforeSend: () => $("#loading").show(),
      success: function(resp) {
        if (resp === '') {
          Swal.fire({
            html: 'OC cargada y evento marcado como (22) Con OC de cliente.',
            icon: 'success',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          }).then(() => location.reload());
        } else {
          Swal.fire({
            html: resp,
            icon: 'warning',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      },
      complete: () => $("#loading").hide()
    });
  });

  // -------- Cargos Internos ----------
  /*
  function abrirModalCargo(eventoid, folio){
    $('#cargo_eventoid').val(eventoid);
    $('#cargo_monto').val('');
    $('#cargo_concepto').val('');
    $('#modalCargo').modal('show');
  }

  $('#formCargo').on('submit', function(e){
    e.preventDefault();
    $.ajax({
      url:'../ajax/eventos.cargos.add.php',
      type:'POST',
      data: $(this).serialize(),
      beforeSend:()=>$("#loading").show(),
      success:function(resp){
        if(resp===''){
          Swal.fire({html:'Cargo registrado.',icon:'success',
                     customClass:{confirmButton:'btn btn-success'}}).then(()=>location.reload());
        }else{
          Swal.fire({html:resp,icon:'warning',customClass:{confirmButton:'btn btn-success'}});
        }
      },
      complete:()=>$("#loading").hide()
    });
  });
  */

  function marcarPagado(eventoid, folio) {
    Swal.fire({
      text: '¿Marcar evento ' + folio + ' como Pagado (23)?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, marcar pagado',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then(res => {
      if (!res.isConfirmed) return;
      $.ajax({
        url: '../ajax/eventos.cargos.marcarpagado.php',
        type: 'POST',
        data: {
          eventoid
        },
        beforeSend: () => $("#loading").show(),
        success: function(resp) {
          if (resp === '') {
            Swal.fire({
              html: 'Evento marcado como Pagado (23).',
              icon: 'success',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            }).then(() => location.reload());
          } else {
            Swal.fire({
              html: resp,
              icon: 'warning',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          }
        },
        complete: () => $("#loading").hide()
      })
    });
  }

  function loadCargos(eventoid) {
    $('#cargo_list').html('<tr><td colspan="4">Cargando...</td></tr>');
    $.getJSON('../ajax/eventos.cargos.list.php', {
      eventoid
    }, function(r) {
      if (!r || !r.ok) {
        $('#cargo_list').html('<tr><td colspan="4">Error al cargar cargos</td></tr>');
        return;
      }
      let html = '';
      let total = 0;
      r.rows.forEach(row => {
        total += Number(row.monto || 0);

        const safeConcepto = row.concepto ? $('<div>').text(row.concepto).html() : '';
        const fileCell = row.file_url ?
          `<a href="${row.file_url}" target="_blank" title="Ver comprobante">${row.file_name ? $('<div>').text(row.file_name).html() : 'Comprobante'}</a>` :
          '';

        html += `
        <tr data-id="${row.id}">
          <td>${row.fecha ?? ''}</td>
          <td>$${Number(row.monto||0).toFixed(2)}</td>
          <td>${safeConcepto} ${fileCell ? '<br>'+fileCell : ''}</td>
          <td class="text-center">
            <img src="../img/delete.png" style="width:18px; height:auto; cursor:pointer" title="Eliminar"
                 onclick="deleteCargo(${row.id})">
          </td>
        </tr>`;
      });
      if (r.rows.length === 0) html = '<tr><td colspan="4" class="text-muted">Sin cargos</td></tr>';
      $('#cargo_list').html(html);
      $('#cargo_total').text('$' + total.toFixed(2));
    });
  }

  function deleteCargo(id) {
    Swal.fire({
      text: '¿Eliminar este cargo?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-danger',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then(res => {
      if (!res.isConfirmed) return;
      $.ajax({
        url: '../ajax/eventos.cargos.delete.php',
        type: 'POST',
        data: {
          id
        },
        beforeSend: () => $("#loading").show(),
        success: function(resp) {
          if (resp === '') {
            // refrescar lista
            const ev = $('#cargo_eventoid').val();
            loadCargos(ev);
          } else {
            Swal.fire({
              html: resp,
              icon: 'warning',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          }
        },
        complete: () => $("#loading").hide()
      });
    });
  }

  // Hook: cuando abres el modal de cargos, carga la lista
  function abrirModalCargo(eventoid, folio) {
    $('#cargo_eventoid').val(eventoid);
    $('#cargo_monto').val('');
    $('#cargo_concepto').val('');
    $('#cargo_archivo').val(''); // limpia archivo
    $('#modalCargo').modal('show');
    loadCargos(eventoid);
  }

  $('#formCargo').off('submit').on('submit', function(e) {
    e.preventDefault();

    const btn = $('#btnGuardarCargo');
    btn.prop('disabled', true);

    const fd = new FormData(this); // incluye archivo si se adjuntó

    $.ajax({
      url: '../ajax/eventos.cargos.add.php',
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      beforeSend: () => $("#loading").show(),
      success: function(resp) {
        if (resp === '') {
          const ev = $('#cargo_eventoid').val();
          loadCargos(ev); // refresca lista
          $('#cargo_monto').val('');
          $('#cargo_concepto').val('');
          $('#cargo_archivo').val('');
        } else {
          Swal.fire({
            html: resp,
            icon: 'warning',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      },
      complete: function() {
        $("#loading").hide();
        btn.prop('disabled', false);
      }
    });
  });
</script>
<script>
  // ===== EXENCIÓN OC =====
  function abrirModalExencionOC(eventoid, folio) {
    $('#ex_eventoid').val(eventoid);
    $('#ex_motivo').val('');
    $('#modalExencionOC').modal('show');
  }
  $('#formExencionOC').on('submit', function(e) {
    e.preventDefault();
    const fd = $(this).serialize();
    $.ajax({
      url: '../ajax/revflow.oc.exencion.solicitar.php',
      type: 'POST',
      data: fd,
      beforeSend: () => $("#loading").show(),
      success: function(r) {
        if (r === '') {
          Swal.fire({
              html: 'Exención solicitada.',
              icon: 'success',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            })
            .then(() => location.reload());
        } else {
          Swal.fire({
            html: r,
            icon: 'warning',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      },
      complete: () => $("#loading").hide()
    });
  });

  // ===== REVISAR OC / EXENCIÓN (ADMIN) =====
  function abrirModalRevisarOC(eventoid, folio, ocstatus) {
    $('#rev_eventoid').val(eventoid);
    $('#rev_ocstatus').val(ocstatus);
    $('#rev_aprobar').val('1');
    $('#rev_motivo').val('');
    $('#modalRevisarOC').modal('show');
  }
  $('#formRevisarOC').on('submit', function(e) {
    e.preventDefault();
    const ev = $('#rev_eventoid').val();
    const st = parseInt($('#rev_ocstatus').val(), 10);
    const aprobar = $('#rev_aprobar').val();
    const motivo = $('#rev_motivo').val();

    // si st==10 → exención; si st==20 → OC cargada
    const url = (st === 10) ? '../ajax/revflow.oc.exencion.autorizar.php' :
      '../ajax/revflow.oc.revisar.php';

    $.ajax({
      url,
      type: 'POST',
      data: {
        eventoid: ev,
        aprobar,
        motivo
      },
      beforeSend: () => $("#loading").show(),
      success: function(r) {
        if (r === '') {
          Swal.fire({
              html: 'Revisión guardada.',
              icon: 'success',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            })
            .then(() => location.reload());
        } else {
          Swal.fire({
            html: r,
            icon: 'warning',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      },
      complete: () => $("#loading").hide()
    });
  });

  // ===== VALIDAR PAGOS (ADMIN) =====
  function validarPagos(eventoid, folio) {
    Swal.fire({
      text: '¿Validar pagos del evento ' + folio + '?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, validar',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then(res => {
      if (!res.isConfirmed) return;
      $.post('../ajax/revflow.pagos.validar.php', {
        eventoid
      }, function(r) {
        if (r === '') {
          Swal.fire({
              html: 'Pagos validados (42).',
              icon: 'success',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            })
            .then(() => location.reload());
        } else {
          Swal.fire({
            html: r,
            icon: 'warning',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      });
    });
  }

  // ===== ENVIAR A ADMINISTRACIÓN =====
  function enviarAAdmin(eventoid, folio) {
    Swal.fire({
      text: '¿Enviar el evento ' + folio + ' a administración?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, enviar',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then(res => {
      if (!res.isConfirmed) return;
      $.post('../ajax/revflow.enviar_admin.php', {
        eventoid
      }, function(r) {
        if (r === '') {
          Swal.fire({
              html: 'Enviado a administración (70).',
              icon: 'success',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            })
            .then(() => location.reload());
        } else {
          Swal.fire({
            html: r,
            icon: 'warning',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      });
    });
  }

  // ===== RESOLVER ADMIN (VoBo u Observación) =====
  function abrirModalResolverAdmin(eventoid, folio, aprobar) {
    $('#adm_eventoid').val(eventoid);
    $('#adm_aprobar').val(aprobar);
    $('#adm_notas').val('');
    $('#modalResolverAdmin').modal('show');
  }
  $('#formResolverAdmin').on('submit', function(e) {
    e.preventDefault();
    const d = $(this).serialize();
    $.ajax({
      url: '../ajax/revflow.admin.resolver.php',
      type: 'POST',
      data: d,
      beforeSend: () => $("#loading").show(),
      success: function(r) {
        if (r === '') {
          Swal.fire({
              html: 'Resolución guardada.',
              icon: 'success',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            })
            .then(() => location.reload());
        } else {
          Swal.fire({
            html: r,
            icon: 'warning',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      },
      complete: () => $("#loading").hide()
    });
  });

  // ===== INTEGRACIÓN con CARGOS (recalcular “pago_status”) =====
  // después de agregar o eliminar un cargo, ya llamas loadCargos(); añade también:
  function recalcPagos(eventoid) {
    $.post('../ajax/revflow.pagos.recalc.php', {
      eventoid
    });
  }
  // En tu éxito de ADD cargo:
  ///   if(resp===''){ const ev=$('#cargo_eventoid').val(); loadCargos(ev); recalcPagos(ev); ... }
  // En tu éxito de DELETE cargo:
  ///   if(resp===''){ const ev=$('#cargo_eventoid').val(); loadCargos(ev); recalcPagos(ev); ... }

  // Abre modal unificado de Revisión/Consulta
  function abrirOCReview(eventoid, folio) {
    $('#ocreview_eventoid').val(eventoid);
    $('#ocreview_body').html('<div class="text-center text-muted">Cargando...</div>');
    $('#ocreview_footer').addClass('d-none'); // oculto por defecto
    $('#modalOCReview').modal('show');

    // Cargamos el HTML de consulta (trae OC_STATUS en un hidden)
    $.ajax({
      url: '../includes/oc.info.php',
      type: 'GET',
      data: {
        eventoid: eventoid
      },
      success: function(html) {
        $('#ocreview_body').html(html);

        // Lee el OC_STATUS que nos manda oc.info.php
        var st = parseInt($('#oc_status_val').val() || '0', 10);
        // Si está CARGADA (20) => mostrar acciones aprobar/rechazar
        if (st === 20) {
          $('#ocreview_footer').removeClass('d-none');
        } else {
          $('#ocreview_footer').addClass('d-none');
        }
      },
      error: function() {
        $('#ocreview_body').html('<div class="alert alert-danger m-2">No se pudo cargar la información de OC.</div>');
      }
    });
  }

  // Guardar revisión (aprueba/rechaza) desde el modal unificado
  $('#formOCReview').on('submit', function(e) {
    e.preventDefault();
    var eventoid = $('#ocreview_eventoid').val();
    var aprobar = $('#ocreview_aprobar').val();
    var motivo = $('#ocreview_motivo').val();

    $.ajax({
      url: '../ajax/revflow.oc.revisar.php',
      type: 'POST',
      data: {
        eventoid: eventoid,
        aprobar: aprobar,
        motivo: motivo
      },
      beforeSend: () => $("#loading").show(),
      success: function(r) {
        if (r === '') {
          Swal.fire({
              html: 'Revisión guardada.',
              icon: 'success',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            })
            .then(() => location.reload());
        } else {
          Swal.fire({
            html: r,
            icon: 'warning',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      },
      complete: () => $("#loading").hide()
    });
  });
</script>
<script>
  function enviarPagosRevision(eventoid, folio) {
    Swal.fire({
      text: '¿Enviar los pagos del evento ' + folio + ' a revisión?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, enviar',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    }).then(res => {
      if (!res.isConfirmed) return;
      $.post('../ajax/revflow.pagos.enviar_revision.php', {
        eventoid
      }, function(r) {
        if (r === '') {
          Swal.fire({
              html: 'Pagos enviados a revisión.',
              icon: 'success',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            })
            .then(() => location.reload());
        } else {
          Swal.fire({
            html: r,
            icon: 'warning',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      });
    });
  }

  function abrirPagosReview(eventoid, folio) {
    $('#pagosreview_eventoid').val(eventoid);
    $('#pagosreview_body').html('<div class="text-center text-muted">Cargando...</div>');
    $('#pagosreview_footer').addClass('d-none');
    $('#modalPagosReview').modal('show');

    $.get('../includes/pagos.info.php', {
      eventoid
    }, function(html) {
      $('#pagosreview_body').html(html);
      var rev = parseInt($('#pagos_rev_val').val() || '0', 10);
      if (rev === 70) {
        $('#pagosreview_footer').removeClass('d-none');
      }
    }).fail(() => {
      $('#pagosreview_body').html('<div class="alert alert-danger m-2">No se pudo cargar pagos.</div>');
    });
  }

  $('#formPagosReview').on('submit', function(e) {
    e.preventDefault();
    var ev = $('#pagosreview_eventoid').val();
    var aprobar = $('#pagosreview_aprobar').val();
    var motivo = $('#pagosreview_motivo').val();

    $.ajax({
      url: '../ajax/revflow.pagos.revisar.php',
      type: 'POST',
      data: {
        eventoid: ev,
        aprobar: aprobar,
        motivo: motivo
      },
      beforeSend: () => $("#loading").show(),
      success: function(r) {
        if (r === '') {
          Swal.fire({
              html: 'Revisión de pagos guardada.',
              icon: 'success',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            })
            .then(() => location.reload());
        } else {
          Swal.fire({
            html: r,
            icon: 'warning',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        }
      },
      complete: () => $("#loading").hide()
    });
  });
</script>