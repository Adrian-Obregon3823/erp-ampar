<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* INTERFAZ PRINCIPAL DE ALMACENES
*********************************************************************************
*/
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
  <div class="container-scroller">
    <?php include_once("../includes/header.php"); ?>
    <div class="container-fluid page-body-wrapper">
      <?php include_once("../includes/menu.sidebar.php") ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="tab-content tab-content-basic">
                <!-- Inicia contenido principal -->
                <div class="row">
                  <div class="col-12">
                    <div class="card">
                      <div class="card-header">
                        <h4>Almacenes &nbsp;&nbsp;<a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Agregar Almacen" data-url="../includes/almacenes.nuevo.php" aria-selected="false"><img src="../img/agregar2.png" style="width:25px; height:25px" title="Agregar Almacén"></h4>
                      </div>
                      <div class="card-body">
                        <?php
                        $almacenes = new almacenes();
                        $res = $almacenes->getalmacenes();

                        if ($res <> 0) {
                        ?>
                          <!-- Desktop Table -->
                          <div class="table-responsive d-none d-md-block" style="width: 100%;">
                            <table class="table table-striped">
                              <thead>
                                <tr>
                                  <th scope="col" width="60px"></th>
                                  <th scope="col" width="100px">Folio</th>
                                  <th scope="col">Categoría</th>
                                  <th scope="col">Tipo de Almacén</th>
                                  <th scope="col">Nombre</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php foreach ($res as $row) { ?>
                                  <tr>
                                    <td width="60px">
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Detalle de Almacén" data-url="../includes/almacenes.info.php?almacenid=<?= base64_encode($row['ALMACEN_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:20px; height:auto; cursor:pointer;" title="Detalle de Almacén"></a>
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Almacén" data-url="../includes/almacenes.editar.php?almacenid=<?= base64_encode($row['ALMACEN_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:20px; height:auto; cursor:pointer;" title="Editar Almacén"></a>
                                    </td>
                                    <td width="100px">
                                      <strong><?= $row['ALMACEN_FOLIO'] ?></strong>
                                      <?php if ($row['STATUS_ID'] == 18): ?>
                                        <span style="background-color: #<?= $row['STATUS_COLOR'] ?>;
                                                      color: #FFF;
                                                      font-size: 0.5rem;
                                                      padding: 2px 6px;
                                                      border-radius: 8px;
                                                      margin-left: 4px;
                                                      position: relative;
                                                      top: 2px;">
                                          <?= $row['STATUS_NOMBRE'] ?>
                                        </span>
                                      <?php endif; ?>
                                    </td>
                                    <td><?= $row['SUCURSAL_NOMBRE'] ?></td>
                                    <td><?= $row['TIPOALMACEN_NOMBRE'] ?></td>
                                    <td><?= $row['ALMACEN_NOMBRE'] ?></td>
                                  </tr>
                                <?php } ?>
                              </tbody>
                            </table>
                          </div>

                          <!-- Mobile View (Cards) -->
                          <div class="d-block d-md-none mt-2">
                            <?php foreach ($res as $row) { ?>
                              <div class="mobile-card">
                                <div class="mobile-card-header">
                                  <span class="mobile-card-title"><?= $row['ALMACEN_FOLIO'] ?></span>
                                  <?php if ($row['STATUS_ID'] == 18): ?>
                                    <span class="mobile-card-badge" style="background-color: #<?= $row['STATUS_COLOR'] ?>; color: #FFF;">
                                      <?= $row['STATUS_NOMBRE'] ?>
                                    </span>
                                  <?php endif; ?>
                                </div>
                                <div class="mobile-card-body">
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Categoría</span>
                                    <span class="mobile-card-value"><?= $row['SUCURSAL_NOMBRE'] ?></span>
                                  </div>
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Tipo de Almacén</span>
                                    <span class="mobile-card-value"><?= $row['TIPOALMACEN_NOMBRE'] ?></span>
                                  </div>
                                  <div class="mobile-card-row">
                                    <span class="mobile-card-label">Nombre</span>
                                    <span class="mobile-card-value"><?= $row['ALMACEN_NOMBRE'] ?></span>
                                  </div>
                                </div>
                                <div class="mobile-card-actions">
                                  <a data-toggle="modal" data-target="#modalglobal" data-title="Detalle de Almacén" data-url="../includes/almacenes.info.php?almacenid=<?= base64_encode($row['ALMACEN_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:28px; height:auto; cursor:pointer;" title="Detalle de Almacén"></a>
                                  <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Almacén" data-url="../includes/almacenes.editar.php?almacenid=<?= base64_encode($row['ALMACEN_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer;" title="Editar Almacén"></a>
                                </div>
                              </div>
                            <?php } ?>
                          </div>
                        <?php
                        } else {
                          echo '<div class="alert alert-warning text-center">No se encontraron registros</div>';
                        } ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php include_once("../includes/modalglobal.php") ?>
    </div>
    <?php include_once("../includes/footer.php"); ?>
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

  // Delegar clics dentro del modal para recargar el contenido AJAX
  $(document).on('click', '#modalglobal [data-url]', function(e) {
    e.preventDefault();

    const button = $(this);
    const url = button.data('url');
    const title = button.data('title') || 'Detalle';

    const modal = $('#modalglobal');

    modal.find('.modal-title').text(title);
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
</script>