<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$usersesion = $_SESSION['ampar']['usuario'];
$statusFiltro = isset($_GET['status']) ? (string)$_GET['status'] : '14';

$tabsProyectos = [
  '14' => 'Creados',
  '15' => 'En marcha',
  '3'  => 'Finalizados',
  '5'  => 'Cancelados',
];

if (!array_key_exists($statusFiltro, $tabsProyectos)) {
  $statusFiltro = '14';
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <?php include_once("../includes/head.php"); ?>
  <style>
    .compliance-warning {
      color: #dc3545;
      font-weight: bold;
    }
    .compliance-ok {
      color: #198754;
      font-weight: bold;
    }
    .progress {
      height: 10px;
      margin-top: 5px;
      border-radius: 5px;
    }
    .alert-banner {
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 15px;
      font-weight: 500;
    }
  </style>
</head>

<body>
  <div id="loading" style="display:none">
    <img id="loading-image" src="../img/logo.png" />
  </div>
  <?php $proyectos = new proyectos(); ?>
  <div class="container-scroller">
    <?php include_once("../includes/header.php"); ?>
    <div class="container-fluid page-body-wrapper">
      <?php include_once("../includes/menu.sidebar.php") ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="tab-content tab-content-basic">
                <!-- Contenido principal -->
                <div class="row">
                  <div class="col-12">
                    <div class="card">
                      <div class="card-header">
                        <h4>
                          Proyectos - <?= $tabsProyectos[$statusFiltro] ?>
                          &nbsp;&nbsp;
                          <a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Agregar Proyecto" data-url="../includes/proyectos.nuevo.php" aria-selected="false">
                            <img src="../img/agregar2.png" style="width:25px; height:25px" title="Agregar Proyecto">
                          </a>
                        </h4>
                      </div>
                      <div class="card-body">
                        <div class="mb-3">
                          <ul class="nav nav-tabs" role="tablist">
                            <?php foreach ($tabsProyectos as $statusId => $label): ?>
                              <li class="nav-item">
                                <a
                                  class="nav-link <?= ($statusFiltro === $statusId ? 'active' : '') ?>"
                                  href="?status=<?= $statusId ?>"
                                  role="tab">
                                  <?= $label ?>
                                </a>
                              </li>
                            <?php endforeach; ?>
                          </ul>
                        </div>
                        <?php
                        $res = $proyectos->getproyectos($usersesion['USUARIO_ID'], $statusFiltro);
                        if ($res <> 0 && is_array($res)) {
                        ?>
                          <!-- Desktop Table -->
                          <div class="table-responsive d-none d-md-block" style="width: 100%;">
                            <table class="table table-striped">
                              <thead>
                                <tr>
                                  <th></th>
                                  <th>Folio</th>
                                  <th>Status</th>
                                  <th>Tipo</th>
                                  <th>Razón Social / Paciente</th>
                                  <th>Responsable</th>
                                  <th>Vigencia</th>
                                  <th>Cumplimiento</th>
                                </tr>
                              </thead>
                              <tbody>                                <?php foreach ($res as $r): 
                                  $cump = $proyectos->getcumplimiento($r['PROYECTO_ID']);
                                  $porcentaje = $cump['meta'] > 0 ? min(100, round(($cump['remisionados'] / $cump['meta']) * 100)) : 100;
                                  $plazosMap = [
                                      'SEMANA' => 'Semanal',
                                      'MES' => 'Mensual',
                                      'BIMENSUAL' => 'Bimensual',
                                      'TRIMESTRAL' => 'Trimestral',
                                      'SEMESTRAL' => 'Semestral',
                                      'TODO' => 'Total',
                                  ];
                                  $plazoText = isset($plazosMap[$cump['plazo']]) ? $plazosMap[$cump['plazo']] : 'Total';
                                ?>
                                  <tr>
                                    <td width="120px">
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Proyecto" data-url="../includes/proyectos.info.php?proyectoid=<?= base64_encode($r['PROYECTO_ID']) ?>" aria-selected="false">
                                        <img src="../img/info.png" style="width:20px; height:auto; cursor:pointer;" title="Información">
                                      </a>
                                      
                                      <?php if ($r['PROYECTO_STATUSGENERAL'] == 14): ?>
                                        <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Proyecto" data-url="../includes/proyectos.editar.php?proyectoid=<?= base64_encode($r['PROYECTO_ID']) ?>" aria-selected="false">
                                          <img src="../img/edit.png" style="width:20px; height:auto; cursor:pointer" title="Edición de Proyecto">
                                        </a>
                                        <img src="../img/start.png" style="width:20px; height:auto; cursor:pointer" onclick="changestatus('<?= $r['PROYECTO_ID'] ?>','<?= $r['PROYECTO_FOLIO'] ?>',15)" title="Iniciar Proyecto">
                                        <img src="../img/eliminar.png" style="width:20px; height:auto; cursor:pointer" onclick="changestatus('<?= $r['PROYECTO_ID'] ?>','<?= $r['PROYECTO_FOLIO'] ?>',5)" title="Cancelar Proyecto">
                                      <?php endif; ?>
 
                                      <?php if ($r['PROYECTO_STATUSGENERAL'] == 15): ?>
                                        <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Proyecto" data-url="../includes/proyectos.editar.php?proyectoid=<?= base64_encode($r['PROYECTO_ID']) ?>" aria-selected="false">
                                          <img src="../img/edit.png" style="width:20px; height:auto; cursor:pointer" title="Edición de Proyecto/Cumplimiento">
                                        </a>
                                        <img src="../img/check.png" style="width:20px; height:auto; cursor:pointer" onclick="changestatus('<?= $r['PROYECTO_ID'] ?>','<?= $r['PROYECTO_FOLIO'] ?>',3)" title="Finalizar/Cerrar Proyecto">
                                        <img src="../img/eliminar.png" style="width:20px; height:auto; cursor:pointer" onclick="changestatus('<?= $r['PROYECTO_ID'] ?>','<?= $r['PROYECTO_FOLIO'] ?>',5)" title="Cancelar Proyecto">
                                        <a data-toggle="modal" data-target="#modalglobal" data-title="Traspasar Artículos al Proyecto" data-url="../includes/proyectos.entrada.php?proyectoid=<?= base64_encode($r['PROYECTO_ID']) ?>" aria-selected="false" title="Traspasar Artículos al Almacén del Proyecto">
                                          <img src="../img/agregar2.png" style="width:20px; height:auto; cursor:pointer">
                                        </a>
                                      <?php endif; ?>
                                    </td>
                                    <td><?= $r['PROYECTO_FOLIO'] ?></td>
                                    <td style="font-weight:bolder; color:#<?= $r['STATUS_COLOR'] ?>"><?= $r['STATUS_NOMBRE'] ?></td>
                                    <td>
                                      <?php
                                      $tText = 'S/A';
                                      if (isset($r['PROYECTO_TIPO'])) {
                                          if ($r['PROYECTO_TIPO'] == 1) $tText = 'Consigna';
                                          elseif ($r['PROYECTO_TIPO'] == 2) $tText = 'Renta';
                                          elseif ($r['PROYECTO_TIPO'] == 3) $tText = 'Comodato';
                                      }
                                      ?>
                                      <span class="badge badge-outline-info font-weight-bold" style="padding: 2px 6px; font-size:0.75rem;"><?= $tText ?></span>
                                    </td>
                                    <td><?= htmlentities($r['CLIENTE_NOMBRE'] ?? 'S/A') ?></td>
                                    <td><?= htmlentities($r['RESPONSABLE_NOMBRE'] ?? 'S/A') ?></td>
                                    <td>
                                      <small>
                                        <b>I:</b> <?= date('Y-m-d H:i', strtotime($r['PROYECTO_FECHAI'])) ?><br>
                                        <b>F:</b> <?= date('Y-m-d H:i', strtotime($r['PROYECTO_FECHAF'])) ?>
                                      </small>
                                    </td>
                                    <td>
                                      <div>
                                        <span class="<?= $cump['cumple'] ? 'compliance-ok' : 'compliance-warning' ?>">
                                          <?= $cump['remisionados'] ?> / <?= $cump['meta'] ?>
                                        </span>
                                        <small class="text-muted" style="font-size: 0.7rem; font-weight: normal;">(<?= $plazoText ?>)</small>
                                        <?php if (!$cump['cumple']): ?>
                                          <span class="text-danger" title="Cumplimiento no alcanzado"><i class="mdi mdi-alert-circle"></i></span>
                                        <?php endif; ?>
                                      </div>
                                      <div class="progress">
                                        <div class="progress-bar <?= $cump['cumple'] ? 'bg-success' : 'bg-danger' ?>" role="progressbar" style="width: <?= $porcentaje ?>%"></div>
                                      </div>
                                    </td>
                                  </tr>
                                <?php endforeach; ?>
                              </tbody>
                            </table>
                          </div>

                          <!-- Mobile View -->
                          <div class="d-block d-md-none mt-2">
                            <?php foreach ($res as $r): 
                              $cump = $proyectos->getcumplimiento($r['PROYECTO_ID']);
                              $porcentaje = $cump['meta'] > 0 ? min(100, round(($cump['remisionados'] / $cump['meta']) * 100)) : 100;
                              $plazosMap = [
                                  'SEMANA' => 'Semanal',
                                  'MES' => 'Mensual',
                                  'BIMENSUAL' => 'Bimensual',
                                  'TRIMESTRAL' => 'Trimestral',
                                  'SEMESTRAL' => 'Semestral',
                                  'TODO' => 'Total',
                              ];
                              $plazoText = isset($plazosMap[$cump['plazo']]) ? $plazosMap[$cump['plazo']] : 'Total';
                            ?>
                              <div class="card mb-3 border">
                                <div class="card-body p-3">
                                  <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="font-weight-bold text-primary"><?= $r['PROYECTO_FOLIO'] ?></span>
                                    <span class="badge" style="background-color: #<?= $r['STATUS_COLOR'] ?>; color: #fff;"><?= $r['STATUS_NOMBRE'] ?></span>
                                  </div>
                                  <?php
                                  $tText = 'S/A';
                                  if (isset($r['PROYECTO_TIPO'])) {
                                      if ($r['PROYECTO_TIPO'] == 1) $tText = 'Consigna';
                                      elseif ($r['PROYECTO_TIPO'] == 2) $tText = 'Renta';
                                      elseif ($r['PROYECTO_TIPO'] == 3) $tText = 'Comodato';
                                  }
                                  ?>
                                  <p class="mb-1"><b>Tipo:</b> <span class="badge badge-outline-info font-weight-bold" style="padding: 2px 6px; font-size:0.7rem;"><?= $tText ?></span></p>
                                  <p class="mb-1"><b>Cliente:</b> <?= htmlentities($r['CLIENTE_NOMBRE'] ?? 'S/A') ?></p>
                                  <p class="mb-1"><b>Responsable:</b> <?= htmlentities($r['RESPONSABLE_NOMBRE'] ?? 'S/A') ?></p>
                                  <p class="mb-1"><b>F. Fin:</b> <?= date('Y-m-d H:i', strtotime($r['PROYECTO_FECHAF'])) ?></p>
                                  <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span><b>Cumplimiento (<?= $plazoText ?>):</b></span>
                                    <span class="<?= $cump['cumple'] ? 'compliance-ok' : 'compliance-warning' ?>"><?= $cump['remisionados'] ?> / <?= $cump['meta'] ?></span>
                                  </div>
                                  <div class="progress mb-3">
                                    <div class="progress-bar <?= $cump['cumple'] ? 'bg-success' : 'bg-danger' ?>" role="progressbar" style="width: <?= $porcentaje ?>%"></div>
                                  </div>
                                  <div class="text-right">
                                    <button class="btn btn-outline-info btn-sm" data-toggle="modal" data-target="#modalglobal" data-title="Información" data-url="../includes/proyectos.info.php?proyectoid=<?= base64_encode($r['PROYECTO_ID']) ?>">Info</button>
                                    <?php if ($r['PROYECTO_STATUSGENERAL'] == 14 || $r['PROYECTO_STATUSGENERAL'] == 15): ?>
                                      <button class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#modalglobal" data-title="Editar" data-url="../includes/proyectos.editar.php?proyectoid=<?= base64_encode($r['PROYECTO_ID']) ?>">Editar</button>
                                      <button class="btn btn-outline-danger btn-sm" onclick="changestatus('<?= $r['PROYECTO_ID'] ?>','<?= $r['PROYECTO_FOLIO'] ?>',5)">Cancelar</button>
                                    <?php endif; ?>
                                    <?php if ($r['PROYECTO_STATUSGENERAL'] == 15): ?>
                                      <button class="btn btn-outline-success btn-sm" data-toggle="modal" data-target="#modalglobal" data-title="Traspasar Artículos al Proyecto" data-url="../includes/proyectos.entrada.php?proyectoid=<?= base64_encode($r['PROYECTO_ID']) ?>">
                                        <i class="mdi mdi-transfer"></i> Traspasar
                                      </button>
                                    <?php endif; ?>
                                  </div>
                                </div>
                              </div>
                            <?php endforeach; ?>
                          </div>
                        <?php } else { ?>
                          <div class="alert alert-info text-center">No se encontraron proyectos en esta categoría.</div>
                        <?php } ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php include_once("../includes/footer.php"); ?>
      </div>
    </div>
  </div>

  <div class="modal fade" id="modalglobal" tabindex="-1" role="dialog" aria-labelledby="modalglobalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <!-- Modal content loaded dynamically -->
      </div>
    </div>
  </div>

  <?php include_once("../includes/foot.php"); ?>

  <script>
    $('#modalglobal').on('show.bs.modal', function(event) {
      var button = $(event.relatedTarget);
      var title = button.data('title');
      var url = button.data('url');
      var modal = $(this);
      modal.find('.modal-dialog').addClass('modal-lg');
      modal.find('.modal-content').load(url);
    });

    function changestatus(id, folio, status) {
      var msg = "¿Seguro que deseas cambiar el estatus del proyecto?";
      if (status == 15) msg = "¿Seguro que deseas iniciar el proyecto " + folio + "?";
      if (status == 3) msg = "¿Seguro que deseas finalizar el proyecto " + folio + "?";
      if (status == 5) msg = "¿Seguro que deseas cancelar el proyecto " + folio + "?";

      Swal.fire({
        text: msg,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        customClass: {
          confirmButton: 'btn btn-success',
          cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: '../ajax/proyectos.update.status.php',
            type: 'POST',
            data: { id: id, status: status },
            success: function(response) {
              if (response.trim() === "") {
                Swal.fire({
                  text: "Estatus actualizado con éxito",
                  icon: "success",
                  customClass: { confirmButton: 'btn btn-success' }
                }).then(() => {
                  location.reload();
                });
              } else {
                Swal.fire({
                  text: response,
                  icon: "warning",
                  customClass: { confirmButton: 'btn btn-success' }
                });
              }
            }
          });
        }
      });
    }
  </script>
</body>

</html>
