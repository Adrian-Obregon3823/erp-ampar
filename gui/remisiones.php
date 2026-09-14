<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include_once("../includes/head.php"); ?>
</head>

<body>
  <div id="loading" style="display:none">
    <img id="loading-image" src="../img/logo.png" />
  </div>
  <?php $remisiones = new remisiones(); ?>
  <div class="container-scroller">
    <?php include_once("../includes/header.php"); ?>
    <div class="container-fluid page-body-wrapper">
      <?php include_once("../includes/menu.sidebar.php") ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
              </div>
              <div class="tab-content tab-content-basic">
                <!-- Inicia contenido principal -->
                <div class="row">
                  <div class="col-12">
                    <div class="card">
                      <div class="card-header">
                        <h4>Remisiones &nbsp;&nbsp;<a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Nueva Remisión" data-url="../includes/remisiones.nueva.php" aria-selected="false"><img src="../img/agregar2.png" style="width:25px; height:25px" title="Nueva Remisión"></a></h4>
                      </div>
                      <div class="card-body">
                        <?php
                        $res = $remisiones->getremisiones();
                        $eventosGrupos = [];
                        $proyectosGrupos = [];
                        if ($res <> 0) {
                            foreach ($res as $r) {
                                if ($r['REMISION_EVENTOID']) {
                                    $eid = $r['REMISION_EVENTOID'];
                                    if (!isset($eventosGrupos[$eid])) {
                                        $eventosGrupos[$eid] = [
                                            'ID' => $eid,
                                            'FOLIO' => $r['EVENTO_FOLIO'],
                                            'CONCEPTO' => $r['EVENTO_CONCEPTO'],
                                            'REMISIONES' => []
                                        ];
                                    }
                                    $eventosGrupos[$eid]['REMISIONES'][] = $r;
                                } elseif ($r['REMISION_PROYECTOID']) {
                                    $pid = $r['REMISION_PROYECTOID'];
                                    if (!isset($proyectosGrupos[$pid])) {
                                        $proyectosGrupos[$pid] = [
                                            'ID' => $pid,
                                            'FOLIO' => $r['PROYECTO_FOLIO'],
                                            'CONCEPTO' => $r['PROYECTO_CONCEPTO'],
                                            'REMISIONES' => []
                                        ];
                                    }
                                    $proyectosGrupos[$pid]['REMISIONES'][] = $r;
                                }
                            }
                        }
                        ?>
                          <style>
                            .remision-accordion-card {
                              border: 1px solid #e2e8f0;
                              border-radius: 8px;
                              margin-bottom: 12px;
                              box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                              transition: box-shadow 0.2s ease, border-color 0.2s ease;
                              background-color: #fff;
                            }
                            .remision-accordion-card:hover {
                              box-shadow: 0 4px 8px rgba(0,0,0,0.05);
                              border-color: #cbd5e1;
                            }
                            .remision-accordion-header {
                              padding: 16px 20px;
                              background-color: transparent;
                              cursor: pointer;
                              transition: background-color 0.2s ease;
                              display: flex;
                              justify-content: space-between;
                              align-items: center;
                              border-bottom: 1px solid transparent;
                            }
                            .remision-accordion-header:hover {
                              background-color: #f8fafc;
                              border-radius: 8px;
                            }
                            .remision-accordion-header[aria-expanded="true"] {
                              border-bottom-color: #e2e8f0;
                              border-bottom-left-radius: 0;
                              border-bottom-right-radius: 0;
                              background-color: #f8fafc;
                            }
                            .remision-title {
                              font-size: 1rem;
                              color: black;
                              margin: 0;
                              font-weight: 400;
                            }
                            .remision-badge {
                              background-color: #f1f5f9;
                              color: #475569;
                              font-size: 0.85rem;
                              font-weight: 600;
                              padding: 6px 12px;
                              border-radius: 20px;
                              border: 1px solid #e2e8f0;
                              display: inline-block;
                            }
                            .chevron-icon {
                              transition: transform 0.3s ease;
                              color: #94a3b8;
                              font-size: 0.9rem;
                              margin-left: 10px;
                            }
                            .remision-accordion-header[aria-expanded="true"] .chevron-icon {
                              transform: rotate(180deg);
                            }
                            
                            /* Mejoras responsivas */
                            @media (max-width: 768px) {
                              .remision-accordion-header {
                                flex-direction: column;
                                align-items: flex-start;
                                gap: 10px;
                                padding: 12px 16px;
                              }
                              .remision-title {
                                font-size: 0.95rem;
                                line-height: 1.4;
                              }
                              .remision-accordion-header > div {
                                width: 100%;
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                              }
                              .remision-collapse-body .card-body {
                                padding: 12px !important;
                              }
                            }
                            
                            /* Mobile Cards Styles */
                            .mobile-remision-card {
                              border: 1px solid #e2e8f0;
                              border-radius: 10px;
                              padding: 16px;
                              margin-bottom: 16px;
                              background-color: #ffffff;
                              box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                            }
                            .mobile-remision-header {
                              display: flex;
                              justify-content: space-between;
                              align-items: center;
                              margin-bottom: 12px;
                              border-bottom: 1px solid #f1f5f9;
                              padding-bottom: 10px;
                            }
                            .mobile-remision-folio {
                              font-weight: 700;
                              color: #1e293b;
                              font-size: 1.1rem;
                            }
                            .mobile-remision-status {
                              font-weight: 700;
                              font-size: 0.8rem;
                              padding: 4px 10px;
                              border-radius: 6px;
                              text-transform: uppercase;
                              letter-spacing: 0.5px;
                            }
                            .mobile-remision-body {
                              display: flex;
                              flex-direction: column;
                              gap: 10px;
                              margin-bottom: 14px;
                            }
                            .mobile-remision-row {
                              display: flex;
                              justify-content: space-between;
                              align-items: center;
                            }
                            .mobile-remision-label {
                              color: #64748b;
                              font-size: 0.85rem;
                              font-weight: 500;
                            }
                            .mobile-remision-value {
                              color: #334155;
                              font-weight: 600;
                              font-size: 0.95rem;
                              text-align: right;
                            }
                            .mobile-remision-total {
                              font-size: 1.15rem;
                              font-weight: 800;
                              color: #0f172a;
                            }
                            .mobile-remision-actions {
                              display: flex;
                              flex-wrap: wrap;
                              gap: 16px;
                              justify-content: center;
                              border-top: 1px solid #f1f5f9;
                              padding-top: 14px;
                              background-color: #f8fafc;
                              margin: 0 -16px -16px -16px;
                              padding-bottom: 16px;
                              border-bottom-left-radius: 10px;
                              border-bottom-right-radius: 10px;
                            }
                            .mobile-remision-actions a,
                            .mobile-remision-actions img {
                              cursor: pointer;
                              transition: transform 0.2s;
                            }
                            .mobile-remision-actions img:active {
                              transform: scale(0.9);
                            }
                          </style>
                          <ul class="nav nav-tabs" id="remisionesTabs" role="tablist" style="margin-bottom: 20px;">
                            <li class="nav-item">
                              <a class="nav-link active" id="eventos-tab" data-toggle="tab" href="#eventos" role="tab" aria-controls="eventos" aria-selected="true" style="font-weight: 600;">Eventos</a>
                            </li>
                            <li class="nav-item">
                              <a class="nav-link" id="proyectos-tab" data-toggle="tab" href="#proyectos" role="tab" aria-controls="proyectos" aria-selected="false" style="font-weight: 600;">Proyectos</a>
                            </li>
                          </ul>

                          <div class="tab-content" id="remisionesTabsContent" style="padding: 0; border: none;">
                            <!-- TAB EVENTOS -->
                            <div class="tab-pane fade show active" id="eventos" role="tabpanel" aria-labelledby="eventos-tab">
                              <?php if (count($eventosGrupos) > 0) { ?>
                              <div class="accordion" id="accordionRemisionesEventos">
                                <?php foreach ($eventosGrupos as $eid => $evento) { 
                                    $targetId = "collapseEvento" . $eid;
                                    $headingId = "headingEvento" . $eid;
                                ?>
                              <div class="remision-accordion-card">
                                <div class="remision-accordion-header" id="<?= $headingId ?>" data-custom-toggle="collapse" data-target="#<?= $targetId ?>" aria-expanded="false" aria-controls="<?= $targetId ?>">
                                  <h5 class="remision-title">
                                    Evento: <?= $evento['FOLIO'] ?> - <?= $evento['CONCEPTO'] ?>
                                  </h5>
                                  <div>
                                    <span class="remision-badge"><?= count($evento['REMISIONES']) ?> Remisión(es)</span>
                                    <i class="fa fa-chevron-down chevron-icon"></i>
                                  </div>
                                </div>
                                <div id="<?= $targetId ?>" class="remision-collapse-body" style="display: none;" aria-labelledby="<?= $headingId ?>">
                                  <div class="card-body" style="padding: 20px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                                    <div class="table-responsive d-none d-md-block" style="background-color: #fff; border-radius: 8px; border: 1px solid #e2e8f0; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                      <table class="table table-hover m-0" style="border-collapse: separate; border-spacing: 0;">
                                        <thead style="background-color: #f1f5f9; color: #475569; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                          <tr>
                                            <th style="border: none; padding: 14px 16px; font-weight: 600;">Folio</th>
                                            <th style="border: none; padding: 14px 16px; font-weight: 600;">Status</th>
                                            <th style="border: none; padding: 14px 16px; font-weight: 600;">Fecha</th>
                                            <th style="border: none; padding: 14px 16px; font-weight: 600;">Sucursal</th>
                                            <th style="border: none; padding: 14px 16px; font-weight: 600;">Total</th>
                                            <th style="border: none; padding: 14px 16px;"></th>
                                          </tr>
                                        </thead>
                                        <tbody>
                                          <?php foreach ($evento['REMISIONES'] as $r) { ?>
                                            <tr>
                                              <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><?= $r['REMISION_FOLIO'] ?></td>
                                              <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9; font_weight:bolder; color:#<?= $r['STATUS_COLOR'] ?>"><?= $r['STATUS_NOMBRE'] ?></td>
                                              <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><?= $r['REMISION_FECHA'] ?></td>
                                              <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><?= $r['NOMBRE'] ?></td>
                                              <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><b><?= number_format($r['TOTAL'], 2, ".", ",") ?></b></td>
                                              <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9; text-align: right;">
                                                <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Nota de Remisión" data-url="../includes/remisiones.info.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:25px; height:auto; cursor:pointer;" title="Información"></a>
                                                <?php if ($r['REMISION_STATUS'] != 1) { ?>
                                                  <a href="../gdocs/remisiones.nuevo.formato.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" target="blank"><img src="../img/pdf.png" style="width:25px; height:auto;" title="Nota de Remisión"></a>
                                                <?php } ?>
                                                <a href="../gdocs/eventos.formato.php?eventoid=<?= base64_encode($r['REMISION_EVENTOID']) ?>" target="blank"><img src="../img/pdf2.png" style="width:25px; height:auto;" title="Fromato de Evento"></a>
                                                <?php if ($r['REMISION_STATUS'] == 1) { ?>
                                                  <a data-toggle="modal" data-target="#modalglobal" data-title="Datos Complementarios" data-url="../includes/remisiones.datos_extra.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/editar.webp" style="width:25px; height:auto; cursor:pointer;" title="Datos Extra"></a>
                                                  <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Nota de Remisión" data-url="../includes/remisiones.editar.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:25px; height:auto; cursor:pointer;" title="Editar"></a>
                                                  <img src="../img/send.png" style="width:25px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',29,'<?= $r['REMISION_FOLIO'] ?>')" title="Enviar a Recepción">
                                                  <img src="../img/rechazar.png" style="width:25px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                                <?php } ?>
                                                <?php if ($r['REMISION_STATUS'] == 29) { ?>
                                                  <a data-toggle="modal" data-target="#modalglobal" data-title="Archivo de Remisión y Evidencias" data-url="../includes/eventos.archivos_recepcion.php?eventoid=<?= base64_encode($r['REMISION_EVENTOID']) ?>&remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/upload.jpg" style="width:25px; height:auto; cursor:pointer;" title="Subir Archivo de esta Remisión"></a>
                                                  <img src="../img/check.png" style="width:25px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',3,'<?= $r['REMISION_FOLIO'] ?>')" title="Finalizar Remisión">
                                                  <img src="../img/rechazar.png" style="width:25px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                                <?php } ?>
                                              </td>
                                            </tr>
                                          <?php } ?>
                                        </tbody>
                                      </table>
                                    </div>
                                    
                                    <!-- Mobile View (Cards) -->
                                    <div class="d-block d-md-none mt-2">
                                      <?php foreach ($evento['REMISIONES'] as $r) { ?>
                                        <div class="mobile-remision-card">
                                          <div class="mobile-remision-header">
                                            <span class="mobile-remision-folio">
                                              <?= $r['REMISION_FOLIO'] ?>
                                            </span>
                                            <span class="mobile-remision-status" style="color:#<?= $r['STATUS_COLOR'] ?>; border: 1px solid #<?= $r['STATUS_COLOR'] ?>40; background-color: #<?= $r['STATUS_COLOR'] ?>15;">
                                              <?= $r['STATUS_NOMBRE'] ?>
                                            </span>
                                          </div>
                                          <div class="mobile-remision-body">
                                            <div class="mobile-remision-row">
                                              <span class="mobile-remision-label">Fecha</span>
                                              <span class="mobile-remision-value"><?= $r['REMISION_FECHA'] ?></span>
                                            </div>
                                            <div class="mobile-remision-row">
                                              <span class="mobile-remision-label">Sucursal</span>
                                              <span class="mobile-remision-value"><?= $r['NOMBRE'] ?></span>
                                            </div>
                                            <div class="mobile-remision-row">
                                              <span class="mobile-remision-label">Total</span>
                                              <span class="mobile-remision-value mobile-remision-total">$<?= number_format($r['TOTAL'], 2, ".", ",") ?></span>
                                            </div>
                                          </div>
                                          <div class="mobile-remision-actions">
                                            <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Nota de Remisión" data-url="../includes/remisiones.info.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:28px; height:auto; cursor:pointer;" title="Información"></a>
                                            <?php if ($r['REMISION_STATUS'] != 1) { ?>
                                              <a href="../gdocs/remisiones.nuevo.formato.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" target="blank"><img src="../img/pdf.png" style="width:28px; height:auto;" title="Nota de Remisión"></a>
                                            <?php } ?>
                                            <a href="../gdocs/eventos.formato.php?eventoid=<?= base64_encode($r['REMISION_EVENTOID']) ?>" target="blank"><img src="../img/pdf2.png" style="width:28px; height:auto;" title="Fromato de Evento"></a>
                                            <?php if ($r['REMISION_STATUS'] == 1) { ?>
                                              <a data-toggle="modal" data-target="#modalglobal" data-title="Datos Complementarios" data-url="../includes/remisiones.datos_extra.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/editar.webp" style="width:28px; height:auto; cursor:pointer;" title="Datos Extra"></a>
                                              <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Nota de Remisión" data-url="../includes/remisiones.editar.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer;" title="Editar"></a>
                                              <img src="../img/send.png" style="width:28px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',29,'<?= $r['REMISION_FOLIO'] ?>')" title="Enviar a Recepción">
                                              <img src="../img/rechazar.png" style="width:28px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                            <?php } ?>
                                            <?php if ($r['REMISION_STATUS'] == 29) { ?>
                                              <a data-toggle="modal" data-target="#modalglobal" data-title="Archivo de Remisión y Evidencias" data-url="../includes/eventos.archivos_recepcion.php?eventoid=<?= base64_encode($r['REMISION_EVENTOID']) ?>&remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/upload.jpg" style="width:28px; height:auto; cursor:pointer;" title="Subir Archivo de esta Remisión"></a>
                                              <img src="../img/check.png" style="width:28px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',3,'<?= $r['REMISION_FOLIO'] ?>')" title="Finalizar Remisión">
                                              <img src="../img/rechazar.png" style="width:28px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                            <?php } ?>
                                          </div>
                                        </div>
                                      <?php } ?>
                                    </div>
                                  </div>
                                </div>
                              </div>
                                <?php } ?>
                              </div>
                              <?php } else { ?>
                                <div class="alert alert-warning text-center">No se encontraron remisiones de eventos</div>
                              <?php } ?>
                            </div>

                            <!-- TAB PROYECTOS -->
                            <div class="tab-pane fade" id="proyectos" role="tabpanel" aria-labelledby="proyectos-tab">
                              <?php if (count($proyectosGrupos) > 0) { ?>
                              <div class="accordion" id="accordionRemisionesProyectos">
                                <?php foreach ($proyectosGrupos as $pid => $proyecto) { 
                                    $targetId = "collapseProyecto" . $pid;
                                    $headingId = "headingProyecto" . $pid;
                                ?>
                                  <div class="remision-accordion-card">
                                    <div class="remision-accordion-header" id="<?= $headingId ?>" data-custom-toggle="collapse" data-target="#<?= $targetId ?>" aria-expanded="false" aria-controls="<?= $targetId ?>">
                                      <h5 class="remision-title">
                                        Proyecto: <?= $proyecto['FOLIO'] ?> - <?= $proyecto['CONCEPTO'] ?>
                                      </h5>
                                      <div>
                                        <span class="remision-badge"><?= count($proyecto['REMISIONES']) ?> Remisión(es)</span>
                                        <i class="fa fa-chevron-down chevron-icon"></i>
                                      </div>
                                    </div>
                                    <div id="<?= $targetId ?>" class="remision-collapse-body" style="display: none;" aria-labelledby="<?= $headingId ?>">
                                      <div class="card-body" style="padding: 20px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                                        <div class="table-responsive d-none d-md-block" style="background-color: #fff; border-radius: 8px; border: 1px solid #e2e8f0; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                          <table class="table table-hover m-0" style="border-collapse: separate; border-spacing: 0;">
                                            <thead style="background-color: #f1f5f9; color: #475569; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                              <tr>
                                                <th style="border: none; padding: 14px 16px; font-weight: 600;">Folio</th>
                                                <th style="border: none; padding: 14px 16px; font-weight: 600;">Status</th>
                                                <th style="border: none; padding: 14px 16px; font-weight: 600;">Fecha</th>
                                                <th style="border: none; padding: 14px 16px; font-weight: 600;">Sucursal</th>
                                                <th style="border: none; padding: 14px 16px; font-weight: 600;">Total</th>
                                                <th style="border: none; padding: 14px 16px;"></th>
                                              </tr>
                                            </thead>
                                            <tbody>
                                              <?php foreach ($proyecto['REMISIONES'] as $r) { ?>
                                                <tr>
                                                  <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><?= $r['REMISION_FOLIO'] ?></td>
                                                  <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9; font_weight:bolder; color:#<?= $r['STATUS_COLOR'] ?>"><?= $r['STATUS_NOMBRE'] ?></td>
                                                  <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><?= $r['REMISION_FECHA'] ?></td>
                                                  <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><?= $r['NOMBRE'] ?></td>
                                                  <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><b><?= number_format($r['TOTAL'], 2, ".", ",") ?></b></td>
                                                  <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9; text-align: right;">
                                                    <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Nota de Remisión" data-url="../includes/remisiones.info.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:25px; height:auto; cursor:pointer;" title="Información"></a>
                                                    <?php if ($r['REMISION_STATUS'] != 1) { ?>
                                                      <a href="../gdocs/remisiones.nuevo.formato.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" target="blank"><img src="../img/pdf.png" style="width:25px; height:auto;" title="Nota de Remisión"></a>
                                                    <?php } ?>
                                                    <!-- Para proyectos no hay evento formato por ahora, o tal vez haya un proyecto formato en el futuro -->
                                                    <?php if ($r['REMISION_STATUS'] == 1) { ?>
                                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Datos Complementarios" data-url="../includes/remisiones.datos_extra.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/editar.webp" style="width:25px; height:auto; cursor:pointer;" title="Datos Extra"></a>
                                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Nota de Remisión" data-url="../includes/remisiones.editar.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:25px; height:auto; cursor:pointer;" title="Editar"></a>
                                                      <img src="../img/send.png" style="width:25px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',29,'<?= $r['REMISION_FOLIO'] ?>')" title="Enviar a Recepción">
                                                      <img src="../img/rechazar.png" style="width:25px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                                    <?php } ?>
                                                    <?php if ($r['REMISION_STATUS'] == 29) { ?>
                                                      <!-- No eventoid here -->
                                                      <img src="../img/check.png" style="width:25px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',3,'<?= $r['REMISION_FOLIO'] ?>')" title="Finalizar Remisión">
                                                      <img src="../img/rechazar.png" style="width:25px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                                    <?php } ?>
                                                  </td>
                                                </tr>
                                              <?php } ?>
                                            </tbody>
                                          </table>
                                        </div>
                                        
                                        <!-- Mobile View (Cards) -->
                                        <div class="d-block d-md-none mt-2">
                                          <?php foreach ($proyecto['REMISIONES'] as $r) { ?>
                                            <div class="mobile-remision-card">
                                              <div class="mobile-remision-header">
                                                <span class="mobile-remision-folio">
                                                  <?= $r['REMISION_FOLIO'] ?>
                                                </span>
                                                <span class="mobile-remision-status" style="color:#<?= $r['STATUS_COLOR'] ?>; border: 1px solid #<?= $r['STATUS_COLOR'] ?>40; background-color: #<?= $r['STATUS_COLOR'] ?>15;">
                                                  <?= $r['STATUS_NOMBRE'] ?>
                                                </span>
                                              </div>
                                              <div class="mobile-remision-body">
                                                <div class="mobile-remision-row">
                                                  <span class="mobile-remision-label">Fecha</span>
                                                  <span class="mobile-remision-value"><?= $r['REMISION_FECHA'] ?></span>
                                                </div>
                                                <div class="mobile-remision-row">
                                                  <span class="mobile-remision-label">Sucursal</span>
                                                  <span class="mobile-remision-value"><?= $r['NOMBRE'] ?></span>
                                                </div>
                                                <div class="mobile-remision-row">
                                                  <span class="mobile-remision-label">Total</span>
                                                  <span class="mobile-remision-value mobile-remision-total">$<?= number_format($r['TOTAL'], 2, ".", ",") ?></span>
                                                </div>
                                              </div>
                                              <div class="mobile-remision-actions">
                                                <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Nota de Remisión" data-url="../includes/remisiones.info.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:28px; height:auto; cursor:pointer;" title="Información"></a>
                                                <?php if ($r['REMISION_STATUS'] != 1) { ?>
                                                  <a href="../gdocs/remisiones.nuevo.formato.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" target="blank"><img src="../img/pdf.png" style="width:28px; height:auto;" title="Nota de Remisión"></a>
                                                <?php } ?>
                                                <?php if ($r['REMISION_STATUS'] == 1) { ?>
                                                  <a data-toggle="modal" data-target="#modalglobal" data-title="Datos Complementarios" data-url="../includes/remisiones.datos_extra.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/editar.webp" style="width:28px; height:auto; cursor:pointer;" title="Datos Extra"></a>
                                                  <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Nota de Remisión" data-url="../includes/remisiones.editar.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer;" title="Editar"></a>
                                                  <img src="../img/send.png" style="width:28px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',29,'<?= $r['REMISION_FOLIO'] ?>')" title="Enviar a Recepción">
                                                  <img src="../img/rechazar.png" style="width:28px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                                <?php } ?>
                                                <?php if ($r['REMISION_STATUS'] == 29) { ?>
                                                  <img src="../img/check.png" style="width:28px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',3,'<?= $r['REMISION_FOLIO'] ?>')" title="Finalizar Remisión">
                                                  <img src="../img/rechazar.png" style="width:28px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                                <?php } ?>
                                              </div>
                                            </div>
                                          <?php } ?>
                                        </div>
                                      </div>
                                    </div>
                                  </div>
                                <?php } ?>
                              </div>
                              <?php } else { ?>
                                <div class="alert alert-warning text-center">No se encontraron remisiones de proyectos</div>
                              <?php } ?>
                            </div>
                          </div>
                        <?php
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
        <?php include_once("../includes/footer.php"); ?>
      </div>
    </div>
  </div>
  <?php include_once("../includes/foot.php"); ?>
</body>

</html>
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

  function changestatus(id, status, folio) {
    var accion = "";
    var done = "";

    switch (status) {
      case 29:
        accion = "enviar a recepción";
        done = "enviada a recepción";
        break;
      case 3:
        accion = "finalizar";
        done = "finalizada";
        break;
      case 5:
        accion = "cancelar";
        done = "cancelada";
        break;
      default:
    }

    Swal.fire({
      text: '¿Seguro que deseas ' + accion + ' la nota de Remisión con Folio: ' + folio + '?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, ' + accion,
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false // necesario si usas clases Bootstrap
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '../ajax/remisiones.update.status.php',
          type: 'POST',
          data: {
            id: id,
            status: status
          },
          dataType: 'html',
          beforeSend: function() {
            $("#loading").show();
          },
          success: function(response) {
            if (response.trim() === "") {
              Swal.fire({
                html: "Nota de remisión " + done + " con éxito",
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
    })
  }

  $(document).ready(function() {
    // Custom toggle to avoid Bootstrap conflicts
    $('.remision-accordion-header').on('click', function(e) {
      e.preventDefault();
      var target = $(this).attr('data-target');
      var isExpanded = $(this).attr('aria-expanded') === 'true';
      
      $(target).slideToggle(250);
      $(this).attr('aria-expanded', !isExpanded);
    });
  });
</script>