<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$usersesion = $_SESSION['ampar']['usuario'];
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
                        <h4>
                          Eventos - <?= $tabsEventos[$statusFiltro] ?>
                          &nbsp;&nbsp;
                          <a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Agregar Evento" data-url="../includes/eventos.nuevo.php" aria-selected="false">
                            <img src="../img/agregar2.png" style="width:25px; height:25px" title="Agregar Evento">
                          </a>
                        </h4>
                      </div>
                      <div class="card-body">
                        <div class="mb-3">
                          <ul class="nav nav-tabs" role="tablist">
                            <?php foreach ($tabsEventos as $statusId => $label): ?>
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
                        $eventos = new eventos();
                        $res = $eventos->geteventos($usersesion['USUARIO_ID'], "", $statusFiltro);
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

                                <th></th>
                              </tr>
                              <?php foreach ($res as $r): ?>
                                <?php
                                // Consulta si tiene permisos de chofer y especialista
                                $usersesion = $_SESSION['ampar']['usuario'];
                                $permisosespecialista = $eventos->getpermisosespecialista($r['EVENTO_SUCURSALID'], $r['TIPOEVENTOSUBGRUPO_ID'], $usersesion['USUARIO_ID']);
                                $permisoschofer = $eventos->getpermisoschofer($r['EVENTO_SUCURSALID'], $usersesion['USUARIO_ID']);
                                ?>
                                <?php $hasRemisiones = in_array($r['EVENTO_STATUSGENERAL'], [16, 29, 8, 3]); ?>
                                <tr <?= $hasRemisiones ? 'onclick="toggleRemisiones(\'' . base64_encode($r['EVENTO_ID']) . '\', \'' . $r['EVENTO_ID'] . '\', false, event)" style="cursor: pointer;" title="Clic para ver remisiones"' : '' ?>>
                                  <td style="white-space: nowrap; width: 1%; padding-right: 15px;">
                                    <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Evento" data-url="../includes/eventos.info.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false">
                                      <img src="../img/info.png" style="width:20px; height:auto; cursor:pointer;" title="Información">
                                    </a>
                                    <?php if ($r['EVENTO_STATUSGENERAL'] != 14 && $r['EVENTO_STATUSGENERAL'] != 15 && $r['EVENTO_STATUSGENERAL'] != 16): ?>
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Bitácora detalle" data-url="../includes/bitacora.detalle.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false">
                                        <img src="../img/bitacora.jpg" style="width:20px; height:auto; cursor:pointer;" title="Bitácora">
                                      </a>
                                      <a href="../gdocs/eventos.formato.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" target="blank" title="Solicitud de Evento">
                                        <img src="../img/pdf.png" style="width:20px; height:auto;">
                                      </a>
                                    <?php endif; ?>
                                      <?php if ($r['EVENTO_STATUSGENERAL'] == 15 || $r['EVENTO_STATUSGENERAL'] == 16): ?>
                                        <a class="btn btn-outline-primary btn-sm ml-1" style="border-radius: 4px; padding: 4px 10px; font-weight: 600; font-size: 11px; cursor: pointer; display: inline-flex; align-items: center; vertical-align: middle;" data-toggle="modal" data-target="#modalglobal" data-title="Nueva Remisión" data-url="../includes/remisiones.nueva.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false"><i class="mdi mdi-file-document-box-plus mr-1" style="font-size: 14px;"></i>Crear Remisión</a>
                                      <?php endif; ?>
                                      <?php if ($r['EVENTO_STATUSGENERAL'] == 29 || $r['EVENTO_STATUSGENERAL'] == 3): ?>
                                        <a data-toggle="modal" data-target="#modalglobal" data-title="Archivos de Remisión y Evidencia de Recepción" data-url="../includes/eventos.archivos_recepcion.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false">
                                          <img src="../img/upload.jpg" style="width:20px; height:auto; cursor:pointer;" title="Archivos de Remisión y Recepción">
                                        </a>
                                      <?php endif; ?>
                                      <?php
                                      $tieneRemRow = !empty(trim($r['EVENTO_ARCHIVO_REMISION'] ?? ''));
                                      $tieneEvRow  = !empty($r['EVIDENCIA_REC_ID'] ?? '');
                                      ?>
                                    <?php if ($r['EVENTO_STATUSGENERAL'] == 14): ?>
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Evento" data-url="../includes/eventos.editar.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false">
                                        <img src="../img/edit.png" style="width:20px; height:auto; cursor:pointer" title="Edición de Evento">
                                      </a>
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
                                      <?= $r['ESPECIALISTA_NOMBRE'] ?> <span style="color:#<?= $r['STATUSCOLORESPECIALISTA'] ?>">(<?= $r['STATUSESPECIALISTA'] ?>)</span>
                                      <?php if ($r['EVENTO_ESPECIALISTAID'] == $usersesion['USUARIO_ID'] and $r['EVENTO_ESPECIALISTAIDSTATUS'] == 7): ?>
                                        <img src="../img/check.png" style="width:16px; height:auto; cursor:pointer;" title="Confirmar evento como especialista" onclick="confirmarevento('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',2,20)">
                                      <?php endif; ?>
                                    <?php else: ?>
                                      <div class="alert alert-warning text-center p-1 m-1 small">
                                        No asignado
                                      </div>
                                    <?php endif; ?>
                                  </td>

                                  <!-- Chofer -->
                                  <td>
                                    <div class="d-flex justify-content-between align-items-center">
                                      <div>
                                        <?php if ($r['CHOFER_NOMBRE'] <> ""): ?>
                                          <?= $r['CHOFER_NOMBRE'] ?> <span style="color:#<?= $r['STATUSCOLORCHOFER'] ?>">(<?= $r['STATUSCHOFER'] ?>)</span>
                                          <?php if ($r['EVENTO_CHOFERID'] == $usersesion['USUARIO_ID'] and $r['EVENTO_CHOFERIDSTATUS'] == 7): ?>
                                            <img src="../img/check.png" style="width:16px; height:auto; cursor:pointer;" title="Confirmar evento como chofer" onclick="confirmarevento('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',3,20)">
                                          <?php endif; ?>
                                        <?php else: ?>
                                          <div class="alert alert-warning text-center p-1 m-1 small d-inline-block">
                                            No asignado
                                          </div>
                                        <?php endif; ?>
                                      </div>
                                      <?php if ($r['EVENTO_STATUSGENERAL'] == 14): ?>
                                        <img src="../img/eliminar.png" style="width:20px; height:auto; cursor:pointer" onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',5)" title="Cancelar">
                                      <?php endif; ?>
                                    </div>
                                  </td>

                                  <td style="white-space: nowrap; width: 1%; text-align: right; vertical-align: middle;">
                                    <?php if ($r['EVENTO_STATUSGENERAL'] == 16): ?>
                                      <img src="../img/send.png" style="width:24px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',29)" title="Pasar a Recepción">
                                    <?php endif; ?>
                                    <?php if ($r['EVENTO_STATUSGENERAL'] == 29 && $tieneRemRow && $tieneEvRow): ?>
                                      <img src="../img/check.png" style="width:24px; height:auto; cursor:pointer;" onclick="enviarRevisionLista('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')" title="Enviar a Revisión">
                                    <?php elseif ($r['EVENTO_STATUSGENERAL'] == 8): ?>
                                      <img src="../img/check.png" style="width:24px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',3)" title="Finalizar Evento">
                                    <?php endif; ?>
                                  </td>
                                </tr>
                                <tr id="row_remisiones_<?= $r['EVENTO_ID'] ?>" style="display:none;" class="bg-light">
                                  <td colspan="8" id="td_remisiones_<?= $r['EVENTO_ID'] ?>" style="padding:0;">
                                      <div class="text-center p-3"><i class="mdi mdi-spin mdi-loading" style="font-size:24px;"></i> Cargando remisiones...</div>
                                  </td>
                                </tr>
                              <?php endforeach; ?>
                            </table>
                          </div>
                          
                          <!-- Mobile View (Cards) -->
                          <div class="d-block d-md-none mt-2">
                            <?php foreach ($res as $r): ?>
                              <?php
                                // Consulta si tiene permisos de chofer y especialista
                                $usersesion = $_SESSION['ampar']['usuario'];
                                $permisosespecialista = $eventos->getpermisosespecialista($r['EVENTO_SUCURSALID'], $r['TIPOEVENTOSUBGRUPO_ID'], $usersesion['USUARIO_ID']);
                                $permisoschofer = $eventos->getpermisoschofer($r['EVENTO_SUCURSALID'], $usersesion['USUARIO_ID']);
                              ?>
                              <?php $hasRemisiones = in_array($r['EVENTO_STATUSGENERAL'], [16, 29, 8, 3]); ?>
                              <div class="mobile-card" <?= $hasRemisiones ? 'onclick="toggleRemisiones(\'' . base64_encode($r['EVENTO_ID']) . '\', \'' . $r['EVENTO_ID'] . '\', true, event)" style="cursor: pointer;" title="Clic para ver remisiones"' : '' ?>>
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
                                        <?php if ($r['EVENTO_ESPECIALISTAID'] == $usersesion['USUARIO_ID'] and $r['EVENTO_ESPECIALISTAIDSTATUS'] == 7): ?>
                                          <img src="../img/check.png" style="width:20px; height:auto; cursor:pointer;" onclick="confirmarevento('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',2,20)">
                                        <?php endif; ?>
                                      <?php else: ?>
                                        <span class="text-warning">No asignado</span>
                                      <?php endif; ?>
                                    </span>
                                  </div>
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Chofer</span>
                                    <span class="mobile-card-value text-right">
                                      <div class="d-flex align-items-center justify-content-end">
                                        <div class="mr-2">
                                          <?php if ($r['CHOFER_NOMBRE'] <> ""): ?>
                                            <?= $r['CHOFER_NOMBRE'] ?> <br><small style="color:#<?= $r['STATUSCOLORCHOFER'] ?>">(<?= $r['STATUSCHOFER'] ?>)</small>
                                            <?php if ($r['EVENTO_CHOFERID'] == $usersesion['USUARIO_ID'] and $r['EVENTO_CHOFERIDSTATUS'] == 7): ?>
                                              <img src="../img/check.png" style="width:20px; height:auto; cursor:pointer;" onclick="confirmarevento('<?= $r['EVENTO_ID'] ?>','<?= $usersesion['USUARIO_ID'] ?>',3,20)">
                                            <?php endif; ?>
                                          <?php else: ?>
                                            <span class="text-warning">No asignado</span>
                                          <?php endif; ?>
                                        </div>
                                        <?php if ($r['EVENTO_STATUSGENERAL'] == 14): ?>
                                          <img src="../img/eliminar.png" style="width:24px; height:auto; cursor:pointer" onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',5)" title="Cancelar">
                                        <?php endif; ?>
                                      </div>
                                    </span>
                                  </div>
                                </div>
                                <div class="mobile-card-actions">
                                  <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Evento" data-url="../includes/eventos.info.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false">
                                    <img src="../img/info.png" style="width:28px; height:auto; cursor:pointer;" title="Información">
                                  </a>
                                  <?php if ($r['EVENTO_STATUSGENERAL'] != 14 && $r['EVENTO_STATUSGENERAL'] != 15 && $r['EVENTO_STATUSGENERAL'] != 16): ?>
                                    <a data-toggle="modal" data-target="#modalglobal" data-title="Bitácora detalle" data-url="../includes/bitacora.detalle.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false">
                                      <img src="../img/bitacora.jpg" style="width:28px; height:auto; cursor:pointer;" title="Bitácora">
                                    </a>
                                    <a href="../gdocs/eventos.formato.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" target="blank" title="Solicitud de Evento">
                                      <img src="../img/pdf.png" style="width:28px; height:auto;">
                                    </a>
                                  <?php endif; ?>
                                    <?php if ($r['EVENTO_STATUSGENERAL'] == 15 || $r['EVENTO_STATUSGENERAL'] == 16): ?>
                                      <a class="btn btn-outline-primary btn-sm ml-1" style="border-radius: 4px; padding: 4px 10px; font-weight: 600; font-size: 11px; cursor: pointer; display: inline-flex; align-items: center; vertical-align: middle;" data-toggle="modal" data-target="#modalglobal" data-title="Nueva Remisión" data-url="../includes/remisiones.nueva.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false"><i class="mdi mdi-file-document-box-plus mr-1" style="font-size: 14px;"></i>Crear Remisión</a>
                                    <?php endif; ?>
                                    <?php if ($r['EVENTO_STATUSGENERAL'] == 29 || $r['EVENTO_STATUSGENERAL'] == 3): ?>
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Archivos de Remisión y Evidencia de Recepción" data-url="../includes/eventos.archivos_recepcion.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false">
                                        <img src="../img/upload.jpg" style="width:28px; height:auto; cursor:pointer;" title="Archivos de Remisión y Recepción">
                                      </a>
                                    <?php endif; ?>
                                    
                                    <div class="d-inline-flex align-items-center ml-2">
                                      <?php if ($r['EVENTO_STATUSGENERAL'] == 16): ?>
                                        <img src="../img/send.png" style="width:28px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',29)" title="Pasar a Recepción">
                                      <?php endif; ?>
                                      <?php
                                      $tieneRemRow = !empty(trim($r['EVENTO_ARCHIVO_REMISION'] ?? ''));
                                      $tieneEvRow  = !empty($r['EVIDENCIA_REC_ID'] ?? '');
                                      ?>
                                      <?php if ($r['EVENTO_STATUSGENERAL'] == 29 && $tieneRemRow && $tieneEvRow): ?>
                                        <img src="../img/check.png" style="width:28px; height:auto; cursor:pointer;" onclick="enviarRevisionLista('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>')" title="Enviar a Revisión">
                                      <?php elseif ($r['EVENTO_STATUSGENERAL'] == 8): ?>
                                        <img src="../img/check.png" style="width:28px; height:auto; cursor:pointer;" onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',3)" title="Finalizar Evento">
                                      <?php endif; ?>
                                    </div>
                                  <?php if ($r['EVENTO_STATUSGENERAL'] == 14): ?>
                                    <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Evento" data-url="../includes/eventos.editar.php?eventoid=<?= base64_encode($r['EVENTO_ID']) ?>" aria-selected="false">
                                      <img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer" title="Edición de Evento">
                                    </a>
                                    <?php if ($r['EVENTO_ESPECIALISTAIDSTATUS'] == 19 and $r['EVENTO_CHOFERIDSTATUS'] == 19) { ?>
                                      <img src="../img/start.png" style="width:28px; height:auto; cursor:pointer" onclick="changestatus('<?= $r['EVENTO_ID'] ?>','<?= $r['EVENTO_FOLIO'] ?>',15)" title="Iniciar">
                                    <?php } ?>
                                  <?php endif; ?>
                                </div>
                                <div id="mobile_remisiones_<?= $r['EVENTO_ID'] ?>" style="display:none;" class="w-100 mt-2 bg-light p-2 rounded border">
                                    <div class="text-center p-3"><i class="mdi mdi-spin mdi-loading" style="font-size:24px;"></i> Cargando remisiones...</div>
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

<?php if (isset($_GET['open_remision']) && !empty($_GET['open_remision'])): ?>
<script>
$(document).ready(function() {
    setTimeout(function() {
        var eventoid = "<?= htmlspecialchars($_GET['open_remision']) ?>";
        var btn = $('<a data-toggle="modal" data-target="#modalglobal" data-title="Nueva Remisión" data-url="../includes/remisiones.nueva.php?eventoid=' + eventoid + '" aria-selected="false"></a>');
        $('body').append(btn);
        btn.click();
    }, 500);
});
</script>
<?php endif; ?>
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
                if (statusid === 15) {
                    Swal.fire({
                        title: '¿Quieres hacer una remisión ahora?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, Nueva Remisión',
                        cancelButtonText: 'No, más tarde',
                        customClass: {
                            confirmButton: 'btn btn-primary',
                            cancelButton: 'btn btn-secondary'
                        },
                        buttonsStyling: false
                    }).then((remisionResult) => {
                        if (remisionResult.isConfirmed) {
                            window.location.href = "eventos.php?status=15&open_remision=" + btoa(id);
                        } else {
                            window.location.href = "eventos.php?status=15";
                        }
                    });
                } else {
                    location.reload();
                }
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

  function asignarespchoupdate(eventoid, usuarioid, tipoid) {
    Swal.fire({
      text: '¿Seguro que deseas asignarte?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, asignar',
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
            usuarioid: usuarioid
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
  function toggleRemisiones(encodedId, rawId, isMobile, event) {
    if (event) {
        var target = $(event.target);
        // Ignore clicks on buttons, links, images, or anything inside an anchor tag
        if (target.is('a') || target.is('button') || target.is('img') || target.parents('a').length || target.parents('button').length) {
            return;
        }
    }
    
    var container = isMobile ? $("#mobile_remisiones_" + rawId) : $("#row_remisiones_" + rawId);
    var targetDiv = isMobile ? container : $("#td_remisiones_" + rawId);
    
    if (container.is(":visible")) {
      container.hide();
    } else {
      container.show();
      if (targetDiv.html().indexOf('Cargando remisiones') > -1 || $.trim(targetDiv.html()) === '') {
        targetDiv.html('<div class="text-center p-3"><i class="mdi mdi-spin mdi-loading" style="font-size:24px;"></i> Cargando remisiones...</div>');
        $.ajax({
          url: '../includes/eventos.remisiones.lista.php?eventoid=' + encodedId + '&format=row',
          type: 'GET',
          success: function(response) {
            targetDiv.html(response);
          },
          error: function() {
            targetDiv.html('<div class="alert alert-danger m-2">Error al cargar las remisiones.</div>');
          }
        });
      }
    }
  }
</script>

<?php if (isset($_GET['open_remision'])): ?>
<script>
  $(document).ready(function() {
    setTimeout(function() {
      var btn = $("a[data-url*='eventoid=<?= base64_encode($_GET['open_remision']) ?>']").filter(function() {
        return $(this).text().indexOf('Crear Remisión') > -1;
      }).first();
      if (btn.length > 0) {
        btn.click();
      }
    }, 500);
  });
</script>
<?php endif; ?>

<?php if (isset($_GET['open_datos_extra'])): ?>
<script>
  $(document).ready(function() {
    setTimeout(function() {
      $('#modalglobal .modal-content').html('<div class="modal-body text-center"><i class="mdi mdi-spin mdi-loading" style="font-size:24px;"></i> Cargando...</div>');
      $('#modalglobal').modal('show');
      $('#modalglobal .modal-content').load('../includes/remisiones.datos_extra.php?remisionid=<?= $_GET['open_datos_extra'] ?>&from_new=1', function() {
          $('#modalglobal .modal-title').html('Datos Complementarios');
          
          $('#modalglobal').one('hidden.bs.modal', function () {
              setTimeout(function() {
                  <?php 
                  $next_id = null;
                  if (isset($_GET['all_ids'])) {
                      $all_ids_str = base64_decode($_GET['all_ids']);
                      $ids_arr = explode(',', $all_ids_str);
                      $current_id = base64_decode($_GET['open_datos_extra']);
                      $current_index = array_search($current_id, $ids_arr);
                      if ($current_index !== false && isset($ids_arr[$current_index + 1])) {
                          $next_id = $ids_arr[$current_index + 1];
                      }
                  }
                  if ($next_id): ?>
                      Swal.fire({
                          title: "Siguiente Remisión",
                          text: "Ahora por favor llena los datos complementarios de la siguiente remisión creada automáticamente.",
                          icon: "info",
                          confirmButtonText: "Continuar",
                          allowOutsideClick: false,
                          allowEscapeKey: false
                      }).then(() => {
                          window.location.href = 'eventos.php?status=16&open_datos_extra=<?= base64_encode($next_id) ?>&event_id=<?= $_GET["event_id"] ?>&all_ids=<?= $_GET["all_ids"] ?>';
                      });
                  <?php else: ?>
                      Swal.fire({
                          title: "¿Deseas hacer OTRA remisión?",
                          text: "Si ya terminaste, el evento pasará a Recepción.",
                          icon: "question",
                          showCancelButton: true,
                          confirmButtonText: "Sí, hacer otra",
                          cancelButtonText: "No, ya terminé",
                          allowOutsideClick: false,
                          allowEscapeKey: false,
                          customClass: {
                              confirmButton: 'btn btn-success mr-2',
                              cancelButton: 'btn btn-secondary'
                          },
                          buttonsStyling: false
                      }).then((result) => {
                          if (result.isConfirmed) {
                              window.location.href = 'eventos.php?status=16&open_remision=<?= $_GET["event_id"] ?>';
                          } else {
                              Swal.fire({
                                  title: "¿Estás seguro?",
                                  text: "Ya no habrá más remisiones para este evento. Pasará a Recepción.",
                                  icon: "warning",
                                  showCancelButton: true,
                                  confirmButtonText: "Sí, pasar a Recepción",
                                  cancelButtonText: "Cancelar",
                                  allowOutsideClick: false,
                                  allowEscapeKey: false,
                                  customClass: {
                                      confirmButton: 'btn btn-primary mr-2',
                                      cancelButton: 'btn btn-secondary'
                                  },
                                  buttonsStyling: false
                              }).then((res2) => {
                                  if (res2.isConfirmed) {
                                      $.ajax({
                                          url: '../ajax/eventos.update.statusgeneral.php',
                                          type: 'POST',
                                          data: { id: atob('<?= $_GET["event_id"] ?>'), status: 29 },
                                          beforeSend: function() { $("#loading").show(); },
                                          success: function() {
                                              window.location.href = 'eventos.php?status=29';
                                          }
                                      });
                                  } else {
                                      window.location.href = 'eventos.php?status=16';
                                  }
                              });
                          }
                      });
                  <?php endif; ?>
              }, 800); // end setTimeout
          });
      });
    }, 500);
  });
</script>
<?php endif; ?>