<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<!DOCTYPE html>
<html lang="es">

<head>
  <?php include_once("../includes/head.php"); ?>
</head>

<body>
  <div class="container-scroller">
    <?php include_once("../includes/header.php"); ?>
    <div class="container-fluid page-body-wrapper">
      <?php include_once("../includes/menu.sidebar.php") ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="tab-content tab-content-basic">
                <div class="row">
                  <div class="col-12">
                    <div class="card">
                      <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Requerimientos de Material</h4>
                        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalglobal" data-title="Nuevo Requerimiento de Material" data-url="../includes/requerimientosmaterial.nuevo.php">
                          + Nuevo requerimiento
                        </button>
                      </div>
                      <div class="card-body">
                        <?php
                        $rm = new requerimientosmaterial();
                        $filtroStatus = isset($_GET['status']) ? $_GET['status'] : '1';
                        $res = $rm->getrequerimientos('', $filtroStatus);
                        ?>

                        <!-- TABS DE FILTRO -->
                        <ul class="nav nav-tabs mb-4" style="border-bottom: 2px solid #dee2e6;">
                          <li class="nav-item">
                            <a class="nav-link <?= ($filtroStatus === '1') ? 'active font-weight-bold' : 'text-muted' ?>" href="requerimientosmaterial.php?status=1">Guardados</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link <?= ($filtroStatus === '8') ? 'active font-weight-bold' : 'text-muted' ?>" href="requerimientosmaterial.php?status=8">En Revisión</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link <?= ($filtroStatus === '2') ? 'active font-weight-bold' : 'text-muted' ?>" href="requerimientosmaterial.php?status=2">En Proceso</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link <?= ($filtroStatus === '6') ? 'active font-weight-bold' : 'text-muted' ?>" href="requerimientosmaterial.php?status=6">Rechazados</a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link <?= ($filtroStatus === '3') ? 'active font-weight-bold' : 'text-muted' ?>" href="requerimientosmaterial.php?status=3">Finalizados</a>
                          </li>
                        </ul>
                        <div class="table-responsive">
                          <table class="table table-striped table-hover">
                            <thead>
                              <tr>
                                <th>Folio</th>
                                <th>Almacén</th>
                                <th>Status</th>
                                <th>Fecha</th>
                                <th>Artículos</th>
                                <th></th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php if ($res && $res != 0) {
                                foreach ($res as $row) { ?>
                                  <tr>
                                    <td><strong><?= htmlspecialchars($row['FOLIO'] ?? '') ?></strong></td>
                                    <td><?= htmlspecialchars($row['ALMACEN_NOMBRE'] ?? '') ?></td>
                                    <td style="color:#<?= htmlspecialchars($row['STATUS_COLOR'] ?? '000') ?>"><?= htmlspecialchars($row['STATUS_NOMBRE'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['FECHA'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['ARTICULOS'] ?? 0) ?></td>
                                    <td>
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Detalle del Requerimiento" data-url="../includes/requerimientosmaterial.info.php?reqid=<?= base64_encode($row['ID']) ?>" aria-selected="false">
                                        <img src="../img/info.png" style="width:20px; height:auto; cursor:pointer;" title="Detalle">
                                      </a>
                                      <?php if ($row['STATUS_ID'] == 1 || $row['STATUS_ID'] == 6): ?>
                                        <a data-toggle="modal" data-target="#modalglobal" data-title="Editar Requerimiento" data-url="../includes/requerimientosmaterial.editar.php?reqid=<?= base64_encode($row['ID']) ?>">
                                          <img src="../img/edit.png" style="width:20px; height:auto; cursor:pointer; margin-left: 5px;" title="Editar Requerimiento">
                                        </a>
                                        <a class="btn-enviar-requerimiento" data-id="<?= $row['ID'] ?>">
                                          <img src="../img/send.png" style="width:20px; height:auto; cursor:pointer; margin-left: 5px;" title="Enviar Requerimiento a Revisión">
                                        </a>
                                      <?php endif; ?>
                                      <?php if ($row['STATUS_ID'] == 8): ?>
                                        <?php if ($GLOBALS['isAdmin'] ?? false): ?>
                                          <a data-toggle="modal" data-target="#modalglobal" data-title="Autorizar Requerimiento" data-url="../includes/requerimientosmaterial.autorizar.php?reqid=<?= base64_encode($row['ID']) ?>" aria-selected="false">
                                            <img src="../img/check.png" style="width:20px; height:auto; cursor:pointer; margin-left: 5px;" title="Autorizar Requerimiento">
                                          </a>
                                        <?php endif; ?>
                                      <?php endif; ?>
                                    </td>
                                  </tr>
                                <?php }
                              } else { ?>
                                <tr>
                                  <td colspan="6" class="text-center text-muted">No se encontraron requerimientos</td>
                                </tr>
                              <?php } ?>
                            </tbody>
                          </table>
                        </div>
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
  <?php include_once("../includes/modalglobal.php") ?>
  <?php include_once("../includes/foot.php"); ?>
  <script>
    $('#modalglobal').on('show.bs.modal', function(event) {
      var button = $(event.relatedTarget);
      var url = button.data('url');
      var title = button.data('title');

      var modal = $(this);
      modal.find('.modal-title').text(title);
      modal.find('.modal-dialog').addClass('modal-xl');

      $('#loading').show();

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
          $('#loading').hide();
        }
      });
    });

    $(document).ready(function() {
      $('.btn-enviar-requerimiento').off('click').on('click', function() {
        const reqId = $(this).data('id');

        Swal.fire({
          title: '¿Enviar Requerimiento?',
          text: 'El requerimiento cambiará de estatus y quedará listo para autorización.',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Enviar',
          cancelButtonText: 'Cancelar',
          customClass: {
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-light'
          }
        }).then((result) => {
          if (result.isConfirmed) {
            $.post('../ajax/requerimientosmaterial.confirmar.php', {
              id: reqId
            }, function(r) {
              if (r.ok) {
                Swal.fire({
                  title: '¡Enviado!',
                  text: 'Requerimiento enviado correctamente.',
                  icon: 'success'
                }).then(() => {
                  location.reload();
                });
              } else {
                Swal.fire('Error', r.msg || 'No se pudo enviar', 'error');
              }
            }, 'json').fail(function() {
              Swal.fire('Error', 'Error de conexión al servidor.', 'error');
            });
          }
        });
      });
    });
  </script>
</body>

</html>