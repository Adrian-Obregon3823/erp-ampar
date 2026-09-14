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
  <?php $login = new login(); ?>
  <div class="container-scroller">
    <?php include_once("../includes/header.php"); ?>
    <div class="container-fluid page-body-wrapper">
      <?php include_once("../includes/menu.sidebar.php") ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <?php include_once("../includes/usuarios.menu.php"); ?>
              </div>
              <div class="tab-content tab-content-basic">
                <!-- Inicia contenido principal -->
                <div class="row">
                  <div class="col-12">
                    <div class="card">
                      <div class="card-header">
                        <h4>Usuarios &nbsp;&nbsp;<a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Agregar Usuario" data-url="../includes/usuarios.nuevo.php" aria-selected="false"><img src="../img/agregar2.png" style="width:25px; height:25px"></a></h4>
                      </div>
                      <div class="card-body">
                        <?php
                        $res = $login->getusuarios();
                        if ($res <> 0) {
                        ?>
                              <!-- Desktop Table -->
                              <div class="table-responsive d-none d-md-block" style="width: 100%;">
                                <table class="table table-striped">
                                  <thead>
                                    <tr>
                                      <th scope="col">Tipo</th>
                                      <th scope="col">Usuario</th>
                                      <th scope="col">Alias</th>
                                      <th scope="col">Nombre</th>
                                      <th scope="col" width="100px"></th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <?php foreach ($res as $row) { ?>
                                      <tr>
                                        <td><?= !empty($row['USUARIOTIPO_NOMBRE']) ? $row['USUARIOTIPO_NOMBRE'] : 'No especificado' ?></td>
                                        <td><?= $row['USUARIO_CORREO'] ?></td>
                                        <td><?= $row['USUARIO_ALIAS'] ?></td>
                                        <td><?= $row['USUARIO_NOMBRE'] ?></td>
                                        <td>
                                          <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Usuario" data-url="../includes/usuarios.info.php?usuarioid=<?= base64_encode($row['USUARIO_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:25px; height:auto; cursor:pointer;" title="Información"></a>
                                          <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Usuario" data-url="../includes/usuarios.editar.php?usuarioid=<?= base64_encode($row['USUARIO_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:23px; height:auto; cursor:pointer;" title="Editar"></a>
                                          <?php if ($row['USUARIO_ACTIVO'] == 1) { ?>
                                            <a onclick="changeactivo('<?= $row['USUARIO_ID'] ?>',0)"><img src="../img/activo.png" style="width:40px; height:auto; cursor:pointer;" title="Desactivar"></a>
                                          <?php } else { ?>
                                            <a onclick="changeactivo('<?= $row['USUARIO_ID'] ?>',1);"><img src="../img/inactivo.png" style="width:40px; height:auto; cursor:pointer;" title="Activar"></a>
                                          <?php } ?>
                                        </td>
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
                                      <span class="mobile-card-title"><?= $row['USUARIO_NOMBRE'] ?></span>
                                      <?php if ($row['USUARIO_ACTIVO'] == 1): ?>
                                        <span class="mobile-card-badge" style="color:#28a745; border: 1px solid #28a74540; background-color: #28a74515;">Activo</span>
                                      <?php else: ?>
                                        <span class="mobile-card-badge" style="color:#dc3545; border: 1px solid #dc354540; background-color: #dc354515;">Inactivo</span>
                                      <?php endif; ?>
                                    </div>
                                    <div class="mobile-card-body">
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Tipo</span>
                                        <span class="mobile-card-value"><?= !empty($row['USUARIOTIPO_NOMBRE']) ? $row['USUARIOTIPO_NOMBRE'] : 'No especificado' ?></span>
                                      </div>
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Usuario (Correo)</span>
                                        <span class="mobile-card-value font-weight-bold"><?= $row['USUARIO_CORREO'] ?></span>
                                      </div>
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Alias</span>
                                        <span class="mobile-card-value"><?= $row['USUARIO_ALIAS'] ?></span>
                                      </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Usuario" data-url="../includes/usuarios.info.php?usuarioid=<?= base64_encode($row['USUARIO_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:28px; height:auto; cursor:pointer;" title="Información"></a>
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Usuario" data-url="../includes/usuarios.editar.php?usuarioid=<?= base64_encode($row['USUARIO_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer;" title="Editar"></a>
                                      <?php if ($row['USUARIO_ACTIVO'] == 1) { ?>
                                        <a onclick="changeactivo('<?= $row['USUARIO_ID'] ?>',0)"><img src="../img/activo.png" style="width:40px; height:auto; cursor:pointer;" title="Desactivar"></a>
                                      <?php } else { ?>
                                        <a onclick="changeactivo('<?= $row['USUARIO_ID'] ?>',1);"><img src="../img/inactivo.png" style="width:40px; height:auto; cursor:pointer;" title="Activar"></a>
                                      <?php } ?>
                                    </div>
                                  </div>
                                <?php } ?>
                              </div>
                        <?php
                        } else {
                          echo "No se encontraron registros<br>";
                        } ?>
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
  $("#utipo").select2({
    placeholder: "Seleccione uno o más tipos",
    width: '100%'
  });

  $('#modalglobal').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var url = button.data('url');
    var title = button.data('title');

    var modal = $(this);
    modal.find('.modal-title').text(title);
    //modal.find('.modal-dialog').addClass('modal-xl')

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

  function changeactivo(id, status) {
    var accion = "";
    var done = "";
    switch (status) {
      case 0:
        accion = "desactivar";
        done = "desactivado";
        break;
      case 1:
        accion = "activar";
        done = "activado";
        break;
      default:
    }

    if (confirm('¿Seguro que deseas ' + accion + ' el usuario?')) {
      $.ajax({
        url: '../ajax/usuarios.activacion.php',
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
            alert("Usuario " + done + " con éxito");
            location.reload();
          } else {
            alert(response);
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
  }
</script>