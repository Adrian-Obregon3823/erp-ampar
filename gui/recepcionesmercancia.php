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
  <?php
  include_once("../class/recepcionesmercancia.php");
  $rec = new recepcionesmercancia();
  ?>
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
                      <div class="card-header bg-white py-3 d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between">
                        <h4 class="card-title mb-0 font-weight-bold text-dark">Historial de Recepciones</h4>
                      </div>
                      <div class="card-body">
                        
                        <ul class="nav nav-tabs border-bottom-0 mb-4" id="recepcionesTabs" role="tablist">
                          <li class="nav-item" role="presentation">
                            <a class="nav-link active font-weight-bold" style="color: #334155; border-radius: 8px 8px 0 0;" id="mercancia-tab" data-toggle="tab" href="#mercancia" role="tab" aria-controls="mercancia" aria-selected="true">
                              <i class="mdi mdi-cart-outline mr-1"></i> Recepciones de Mercancía
                            </a>
                          </li>
                          <li class="nav-item" role="presentation">
                            <a class="nav-link font-weight-bold" style="color: #334155; border-radius: 8px 8px 0 0;" id="traspaso-tab" data-toggle="tab" href="#traspaso" role="tab" aria-controls="traspaso" aria-selected="false">
                              <i class="mdi mdi-truck-delivery mr-1"></i> Recepciones de Traspasos
                            </a>
                          </li>
                        </ul>

                        <?php
                        $res = $rec->getTodasLasRecepciones();
                        $ocsGruposMercancia = [];
                        $ocsGruposTraspaso = [];

                        if ($res <> 0) {
                            foreach ($res as $r) {
                                if (!empty($r['RECEPCION_OCID'])) {
                                    $grupoKey = 'OC_' . $r['RECEPCION_OCID'];
                                    $folioStr = 'Orden de Compra: ' . $r['OC_FOLIO'];
                                    if (!isset($ocsGruposMercancia[$grupoKey])) {
                                        $ocsGruposMercancia[$grupoKey] = ['ID' => $grupoKey, 'FOLIO' => $folioStr, 'RECEPCIONES' => []];
                                    }
                                    $ocsGruposMercancia[$grupoKey]['RECEPCIONES'][] = $r;
                                } elseif (!empty($r['RECEPCION_TRASPASOID'])) {
                                    $grupoKey = 'TR_' . $r['RECEPCION_TRASPASOID'];
                                    $folioStr = 'Traspaso: ' . $r['TRASPASO_FOLIO'];
                                    if (!isset($ocsGruposTraspaso[$grupoKey])) {
                                        $ocsGruposTraspaso[$grupoKey] = ['ID' => $grupoKey, 'FOLIO' => $folioStr, 'RECEPCIONES' => []];
                                    }
                                    $ocsGruposTraspaso[$grupoKey]['RECEPCIONES'][] = $r;
                                } else {
                                    $grupoKey = 'OTRO_' . $r['RECEPCION_ID'];
                                    $folioStr = 'Sin Folio / Desconocido';
                                    if (!isset($ocsGruposMercancia[$grupoKey])) {
                                        $ocsGruposMercancia[$grupoKey] = ['ID' => $grupoKey, 'FOLIO' => $folioStr, 'RECEPCIONES' => []];
                                    }
                                    $ocsGruposMercancia[$grupoKey]['RECEPCIONES'][] = $r;
                                }
                            }
                        }
                        ?>
                          <style>
                            .recepcion-accordion-card {
                              border: 1px solid #e2e8f0;
                              border-radius: 8px;
                              margin-bottom: 12px;
                              box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                              transition: box-shadow 0.2s ease, border-color 0.2s ease;
                              background-color: #fff;
                            }
                            .recepcion-accordion-card:hover {
                              box-shadow: 0 4px 8px rgba(0,0,0,0.05);
                              border-color: #cbd5e1;
                            }
                            .recepcion-accordion-header {
                              padding: 16px 20px;
                              background-color: transparent;
                              cursor: pointer;
                              transition: background-color 0.2s ease;
                              display: flex;
                              justify-content: space-between;
                              align-items: center;
                              border-bottom: 1px solid transparent;
                            }
                            .recepcion-accordion-header:hover {
                              background-color: #f8fafc;
                              border-radius: 8px;
                            }
                            .recepcion-accordion-header[aria-expanded="true"] {
                              border-bottom-color: #e2e8f0;
                              border-bottom-left-radius: 0;
                              border-bottom-right-radius: 0;
                              background-color: #f8fafc;
                            }
                            .recepcion-title {
                              font-size: 1rem;
                              color: black;
                              margin: 0;
                              font-weight: 400;
                            }
                            .recepcion-badge {
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
                            .recepcion-accordion-header[aria-expanded="true"] .chevron-icon {
                              transform: rotate(180deg);
                            }
                            
                            @media (max-width: 768px) {
                              .recepcion-accordion-header {
                                flex-direction: column;
                                align-items: flex-start;
                                gap: 10px;
                                padding: 12px 16px;
                              }
                              .recepcion-title {
                                font-size: 0.95rem;
                                line-height: 1.4;
                              }
                              .recepcion-accordion-header > div {
                                width: 100%;
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                              }
                              .recepcion-collapse-body .card-body {
                                padding: 12px !important;
                              }
                            }
                            
                            .mobile-recepcion-card {
                              border: 1px solid #e2e8f0;
                              border-radius: 10px;
                              padding: 16px;
                              margin-bottom: 16px;
                              background-color: #ffffff;
                              box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                            }
                            .mobile-recepcion-header {
                              display: flex;
                              justify-content: space-between;
                              align-items: center;
                              margin-bottom: 12px;
                              border-bottom: 1px solid #f1f5f9;
                              padding-bottom: 10px;
                            }
                            .mobile-recepcion-folio {
                              font-weight: 700;
                              color: #1e293b;
                              font-size: 1.1rem;
                            }
                            .mobile-recepcion-body {
                              display: flex;
                              flex-direction: column;
                              gap: 10px;
                              margin-bottom: 14px;
                            }
                            .mobile-recepcion-row {
                              display: flex;
                              justify-content: space-between;
                              align-items: center;
                            }
                            .mobile-recepcion-label {
                              color: #64748b;
                              font-size: 0.85rem;
                              font-weight: 500;
                            }
                            .mobile-recepcion-value {
                              color: #334155;
                              font-weight: 600;
                              font-size: 0.95rem;
                              text-align: right;
                            }
                            .mobile-recepcion-actions {
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
                            .mobile-recepcion-actions a,
                            .mobile-recepcion-actions img {
                              cursor: pointer;
                              transition: transform 0.2s;
                            }
                            .mobile-recepcion-actions img:active {
                              transform: scale(0.9);
                            }
                            .nav-tabs .nav-link.active {
                              border-color: #e2e8f0 #e2e8f0 #fff;
                              background-color: #fff;
                              color: #0ea5e9 !important;
                            }
                            .nav-tabs .nav-link {
                              background-color: #f8fafc;
                              border: 1px solid transparent;
                            }
                          </style>

                          <div class="tab-content" id="recepcionesTabsContent">
                            <!-- TAB MERCANCIA -->
                            <div class="tab-pane fade show active" id="mercancia" role="tabpanel" aria-labelledby="mercancia-tab">
                              
                              <div class="d-flex justify-content-end mb-3">
                                <button class="btn btn-primary text-white shadow-sm mb-0" data-toggle="modal" data-target="#modalglobal" data-url="../includes/recepcionesmercancia.nueva.php" data-title="Nueva Recepción de Mercancía">
                                  <i class="mdi mdi-plus mr-1"></i> Nueva Recepción de Mercancía
                                </button>
                              </div>

                              <?php if (!empty($ocsGruposMercancia)) { ?>
                                <div class="accordion" id="accordionRecepcionesMercancia">
                                  <?php foreach ($ocsGruposMercancia as $grupoKey => $ocGrupo) { 
                                      $targetId = "collapse_" . $grupoKey;
                                      $headingId = "heading_" . $grupoKey;
                                  ?>
                                    <div class="recepcion-accordion-card">
                                      <div class="recepcion-accordion-header" id="<?= $headingId ?>" data-custom-toggle="collapse" data-target="#<?= $targetId ?>" aria-expanded="false" aria-controls="<?= $targetId ?>">
                                        <h5 class="recepcion-title">
                                          <b><?= $ocGrupo['FOLIO'] ?></b>
                                        </h5>
                                        <div>
                                          <span class="recepcion-badge"><?= count($ocGrupo['RECEPCIONES']) ?> Recepción(es)</span>
                                          <i class="fa fa-chevron-down chevron-icon"></i>
                                        </div>
                                      </div>
                                      <div id="<?= $targetId ?>" class="recepcion-collapse-body" style="display: none;" aria-labelledby="<?= $headingId ?>">
                                        <div class="card-body" style="padding: 20px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                                          
                                          <!-- Desktop Table -->
                                          <div class="table-responsive d-none d-md-block" style="background-color: #fff; border-radius: 8px; border: 1px solid #e2e8f0; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                            <table class="table table-hover m-0" style="border-collapse: separate; border-spacing: 0;">
                                              <thead style="background-color: #f1f5f9; color: #475569; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                                <tr>
                                                  <th style="border: none; padding: 14px 16px; font-weight: 600;"># Recepción</th>
                                                  <th style="border: none; padding: 14px 16px; font-weight: 600;">Fecha</th>
                                                  <th style="border: none; padding: 14px 16px; font-weight: 600;">Artículos Recibidos</th>
                                                  <th style="border: none; padding: 14px 16px; font-weight: 600;">Recibió</th>
                                                  <th style="border: none; padding: 14px 16px; font-weight: 600;">Acciones</th>
                                                </tr>
                                              </thead>
                                              <tbody>
                                                <?php foreach ($ocGrupo['RECEPCIONES'] as $r) { ?>
                                                  <tr>
                                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;">
                                                      REC-<?= $r['RECEPCION_ID'] ?>
                                                      <?php if (isset($r['ES_STATUS']) && $r['ES_STATUS'] == 6) { ?>
                                                        <span class="badge ml-2" style="background-color: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; font-size: 0.75rem; padding: 4px 8px; font-weight: bold; border-radius: 12px;">Rechazada</span>
                                                      <?php } ?>
                                                    </td>
                                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><?= $r['RECEPCION_FECHA'] ?></td>
                                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><?= floatval($r['TOTAL_ARTICULOS'] ?? 0) ?></td>
                                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><?= $r['USUARIO_NOMBRE'] ?></td>
                                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;">
                                                      <img src="../img/info.png" style="width:25px; height:auto; cursor:pointer" title="Ver Detalles de la Recepción" data-toggle="modal" data-target="#modalglobal" data-url="../includes/recepcionesmercancia.info.php?id=<?= base64_encode($r['RECEPCION_ID']) ?>" data-title="Detalle Recepción REC-<?= $r['RECEPCION_ID'] ?>">
                                                      <?php if (isset($r['ES_STATUS']) && $r['ES_STATUS'] == 6) { ?>
                                                        <img src="../img/edit.png" style="width:25px; height:auto; cursor:pointer; margin-left: 5px;" title="Editar Documentos (Rechazada)" data-toggle="modal" data-target="#modalglobal" data-url="../includes/recepcionesmercancia.editar.php?id=<?= base64_encode($r['RECEPCION_ID']) ?>" data-title="Corregir Documentos de Recepción REC-<?= $r['RECEPCION_ID'] ?>">
                                                      <?php } ?>
                                                    </td>
                                                  </tr>
                                                <?php } ?>
                                              </tbody>
                                            </table>
                                          </div>
                                          
                                          <!-- Mobile View (Cards) -->
                                          <div class="d-block d-md-none mt-2">
                                            <?php foreach ($ocGrupo['RECEPCIONES'] as $r) { ?>
                                              <div class="mobile-recepcion-card">
                                                <div class="mobile-recepcion-header">
                                                  <span class="mobile-recepcion-folio">
                                                    REC-<?= $r['RECEPCION_ID'] ?>
                                                    <?php if (isset($r['ES_STATUS']) && $r['ES_STATUS'] == 6) { ?>
                                                      <span class="badge ml-2" style="background-color: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; font-size: 0.75rem; padding: 4px 8px; font-weight: bold; border-radius: 12px;">Rechazada</span>
                                                    <?php } ?>
                                                  </span>
                                                </div>
                                                <div class="mobile-recepcion-body">
                                                  <div class="mobile-recepcion-row">
                                                    <span class="mobile-recepcion-label">Fecha</span>
                                                    <span class="mobile-recepcion-value"><?= $r['RECEPCION_FECHA'] ?></span>
                                                  </div>
                                                  <div class="mobile-recepcion-row">
                                                    <span class="mobile-recepcion-label">Artículos</span>
                                                    <span class="mobile-recepcion-value"><?= floatval($r['TOTAL_ARTICULOS'] ?? 0) ?></span>
                                                  </div>
                                                </div>
                                                <div class="mobile-recepcion-actions">
                                                  <img src="../img/info.png" style="width:28px; height:auto; cursor:pointer" title="Ver Detalles" data-toggle="modal" data-target="#modalglobal" data-url="../includes/recepcionesmercancia.info.php?id=<?= base64_encode($r['RECEPCION_ID']) ?>" data-title="Detalle Recepción REC-<?= $r['RECEPCION_ID'] ?>">
                                                  <?php if (isset($r['ES_STATUS']) && $r['ES_STATUS'] == 6) { ?>
                                                    <img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer" title="Editar Documentos (Rechazada)" data-toggle="modal" data-target="#modalglobal" data-url="../includes/recepcionesmercancia.editar.php?id=<?= base64_encode($r['RECEPCION_ID']) ?>" data-title="Corregir Documentos de Recepción REC-<?= $r['RECEPCION_ID'] ?>">
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
                              <?php } else {
                                echo '<div class="alert alert-info text-center">No hay recepciones de mercancía registradas.</div>';
                              } ?>
                            </div>

                            <!-- TAB TRASPASO -->
                            <div class="tab-pane fade" id="traspaso" role="tabpanel" aria-labelledby="traspaso-tab">
                              
                              <div class="d-flex justify-content-end mb-3">
                                <button class="btn btn-success text-white shadow-sm mb-0" data-toggle="modal" data-target="#modalglobal" data-url="../includes/recepciones_traspasos.nueva.php" data-title="Nueva Recepción de Traspaso">
                                  <i class="mdi mdi-plus mr-1"></i> Nueva Recepción de Traspaso
                                </button>
                              </div>

                              <?php if (!empty($ocsGruposTraspaso)) { ?>
                                <div class="accordion" id="accordionRecepcionesTraspaso">
                                  <?php foreach ($ocsGruposTraspaso as $grupoKey => $ocGrupo) { 
                                      $targetId = "collapse_" . $grupoKey;
                                      $headingId = "heading_" . $grupoKey;
                                  ?>
                                    <div class="recepcion-accordion-card">
                                      <div class="recepcion-accordion-header" id="<?= $headingId ?>" data-custom-toggle="collapse" data-target="#<?= $targetId ?>" aria-expanded="false" aria-controls="<?= $targetId ?>">
                                        <h5 class="recepcion-title">
                                          <b><?= $ocGrupo['FOLIO'] ?></b>
                                        </h5>
                                        <div>
                                          <span class="recepcion-badge"><?= count($ocGrupo['RECEPCIONES']) ?> Recepción(es)</span>
                                          <i class="fa fa-chevron-down chevron-icon"></i>
                                        </div>
                                      </div>
                                      <div id="<?= $targetId ?>" class="recepcion-collapse-body" style="display: none;" aria-labelledby="<?= $headingId ?>">
                                        <div class="card-body" style="padding: 20px; background-color: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;">
                                          
                                          <!-- Desktop Table -->
                                          <div class="table-responsive d-none d-md-block" style="background-color: #fff; border-radius: 8px; border: 1px solid #e2e8f0; overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                            <table class="table table-hover m-0" style="border-collapse: separate; border-spacing: 0;">
                                              <thead style="background-color: #f1f5f9; color: #475569; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">
                                                <tr>
                                                  <th style="border: none; padding: 14px 16px; font-weight: 600;"># Recepción</th>
                                                  <th style="border: none; padding: 14px 16px; font-weight: 600;">Fecha</th>
                                                  <th style="border: none; padding: 14px 16px; font-weight: 600;">Artículos Recibidos</th>
                                                  <th style="border: none; padding: 14px 16px; font-weight: 600;">Recibió</th>
                                                  <th style="border: none; padding: 14px 16px; font-weight: 600;">Acciones</th>
                                                </tr>
                                              </thead>
                                              <tbody>
                                                <?php foreach ($ocGrupo['RECEPCIONES'] as $r) { ?>
                                                  <tr>
                                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;">
                                                      REC-<?= $r['RECEPCION_ID'] ?>
                                                      <?php if (isset($r['ES_STATUS']) && $r['ES_STATUS'] == 6) { ?>
                                                        <span class="badge ml-2" style="background-color: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; font-size: 0.75rem; padding: 4px 8px; font-weight: bold; border-radius: 12px;">Rechazada</span>
                                                      <?php } ?>
                                                    </td>
                                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><?= $r['RECEPCION_FECHA'] ?></td>
                                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><?= floatval($r['TOTAL_ARTICULOS'] ?? 0) ?></td>
                                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><?= $r['USUARIO_NOMBRE'] ?></td>
                                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;">
                                                      <img src="../img/info.png" style="width:25px; height:auto; cursor:pointer" title="Ver Detalles de la Recepción" data-toggle="modal" data-target="#modalglobal" data-url="../includes/recepcionesmercancia.info.php?id=<?= base64_encode($r['RECEPCION_ID']) ?>" data-title="Detalle Recepción REC-<?= $r['RECEPCION_ID'] ?>">
                                                      <?php if (isset($r['ES_STATUS']) && $r['ES_STATUS'] == 6) { ?>
                                                        <img src="../img/edit.png" style="width:25px; height:auto; cursor:pointer; margin-left: 5px;" title="Editar Documentos (Rechazada)" data-toggle="modal" data-target="#modalglobal" data-url="../includes/recepcionesmercancia.editar.php?id=<?= base64_encode($r['RECEPCION_ID']) ?>" data-title="Corregir Documentos de Recepción REC-<?= $r['RECEPCION_ID'] ?>">
                                                      <?php } ?>
                                                    </td>
                                                  </tr>
                                                <?php } ?>
                                              </tbody>
                                            </table>
                                          </div>
                                          
                                          <!-- Mobile View (Cards) -->
                                          <div class="d-block d-md-none mt-2">
                                            <?php foreach ($ocGrupo['RECEPCIONES'] as $r) { ?>
                                              <div class="mobile-recepcion-card">
                                                <div class="mobile-recepcion-header">
                                                  <span class="mobile-recepcion-folio">
                                                    REC-<?= $r['RECEPCION_ID'] ?>
                                                    <?php if (isset($r['ES_STATUS']) && $r['ES_STATUS'] == 6) { ?>
                                                      <span class="badge ml-2" style="background-color: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; font-size: 0.75rem; padding: 4px 8px; font-weight: bold; border-radius: 12px;">Rechazada</span>
                                                    <?php } ?>
                                                  </span>
                                                </div>
                                                <div class="mobile-recepcion-body">
                                                  <div class="mobile-recepcion-row">
                                                    <span class="mobile-recepcion-label">Fecha</span>
                                                    <span class="mobile-recepcion-value"><?= $r['RECEPCION_FECHA'] ?></span>
                                                  </div>
                                                  <div class="mobile-recepcion-row">
                                                    <span class="mobile-recepcion-label">Artículos</span>
                                                    <span class="mobile-recepcion-value"><?= floatval($r['TOTAL_ARTICULOS'] ?? 0) ?></span>
                                                  </div>
                                                </div>
                                                <div class="mobile-recepcion-actions">
                                                  <img src="../img/info.png" style="width:28px; height:auto; cursor:pointer" title="Ver Detalles" data-toggle="modal" data-target="#modalglobal" data-url="../includes/recepcionesmercancia.info.php?id=<?= base64_encode($r['RECEPCION_ID']) ?>" data-title="Detalle Recepción REC-<?= $r['RECEPCION_ID'] ?>">
                                                  <?php if (isset($r['ES_STATUS']) && $r['ES_STATUS'] == 6) { ?>
                                                    <img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer" title="Editar Documentos (Rechazada)" data-toggle="modal" data-target="#modalglobal" data-url="../includes/recepcionesmercancia.editar.php?id=<?= base64_encode($r['RECEPCION_ID']) ?>" data-title="Corregir Documentos de Recepción REC-<?= $r['RECEPCION_ID'] ?>">
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
                              <?php } else {
                                echo '<div class="alert alert-info text-center">No hay recepciones de traspasos registradas.</div>';
                              } ?>
                            </div>
                          </div>

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
  // Manejar apertura del modal global — apertura via data-toggle="modal" (estático o dinámico)
  $(document).on('click', '[data-target="#modalglobal"][data-url]', function(e) {
    e.preventDefault();
    var url   = $(this).data('url');
    var title = $(this).data('title') || '';

    if (!url) return;

    var modal = $('#modalglobal');
    modal.find('.modal-title').text(title);
    modal.find('.modal-dialog').addClass('modal-xl');
    modal.find('.modal-body').html('<div class="text-center py-5"><i class="mdi mdi-spin mdi-loading" style="font-size:2rem;"></i></div>');
    modal.modal('show');

    $('#loading').show();
    $.ajax({
      url: url,
      type: 'GET',
      success: function(response) {
        modal.find('.modal-body').html(response);
      },
      error: function() {
        modal.find('.modal-body').html('<p class="text-danger p-3">Error al cargar el contenido.</p>');
      },
      complete: function() {
        $('#loading').hide();
      }
    });
  });

  // Fallback por si el modal se abre con show.bs.modal sin data-url (no hacer nada extra)
  $('#modalglobal').on('show.bs.modal', function(event) {
    var button = event.relatedTarget ? $(event.relatedTarget) : null;
    if (!button || !button.data('url')) return; // evitar cargar si ya se manejó arriba o no hay URL
  });

  // Limpiar backdrop y clases al cerrar el modal para evitar que la página se quede bloqueada
  $('#modalglobal').on('hidden.bs.modal', function() {
    $(this).find('.modal-body').html('');
    $(this).find('.modal-dialog').removeClass('modal-xl');
    $('body').removeClass('modal-open').css('padding-right', '');
    $('.modal-backdrop').remove();
  });

  $(document).ready(function() {
    // Custom toggle to avoid Bootstrap conflicts
    $('.recepcion-accordion-header').on('click', function(e) {
      e.preventDefault();
      var target = $(this).attr('data-target');
      var isExpanded = $(this).attr('aria-expanded') === 'true';
      
      $(target).slideToggle(250);
      $(this).attr('aria-expanded', !isExpanded);
    });

    <?php if (isset($_GET['autoedit']) && !empty($_GET['autoedit'])) { ?>
      var autoeditRId = "<?= htmlspecialchars($_GET['autoedit'], ENT_QUOTES, 'UTF-8') ?>";
      var targetUrl = '../includes/recepcionesmercancia.editar.php?id=' + autoeditRId;
      var btn = $('<a data-toggle="modal" data-target="#modalglobal" data-title="Corregir Documentos de Recepción" data-url="' + targetUrl + '" style="display:none;"></a>');
      $('body').append(btn);
      setTimeout(function() { btn.trigger('click'); }, 500);
      
      // Clean up the URL
      if (window.history.replaceState) {
          var url = window.location.href.split('?')[0];
          window.history.replaceState(null, null, url);
      }
    <?php } ?>
  });
</script>