<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once("../includes/head.php");?>
  </head>
  <body>
    <div id="loading" style="display:none">
      <img id="loading-image" src="../img/logo.png"/>
    </div>
    <?php $login = new login();?>
    <div class="container-scroller"> 
      <?php include_once("../includes/header.php"); ?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once ("../includes/menu.sidebar.php")?>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-sm-12">
                  <div class="home-tab">
                    <?php include_once("../includes/usuarios.menu.php");?>
                  </div>
                  <div class="tab-content tab-content-basic">
                    <!-- Inicia contenido principal -->
                    <div class="row">
                      <div class="col-12">
                        <div class="card">
                          <div class="card-header">
                            <h4>Permisos de Sucursales por Usuario</h4>
                          </div>
                          <div class="card-body">
                            <div class="form-group">
                              <label for="usuario-search">Buscar Usuario</label>
                              <input type="text" id="buscadorUsuario" placeholder="Buscar usuario..." class="form-control">
                              <input type="hidden" id="usuario_id">
                            </div>
                            <div class="form-group">
                              <div id="contenedorSucursales"></div>
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
          <?php include_once("../includes/modalglobal.php")?>
          <?php include_once("../includes/footer.php");?>
        </div>
      </div>
    </div>
    <?php include_once("../includes/foot.php");?>
  </body>
</html>
<script>

  $("#buscadorUsuario").autocomplete({
      source: function(request, response) {
          $.ajax({
              url: "../ajax/get.usuarios.php",
              dataType: "json",
              data: { term: request.term },
              success: function(data) {
                  // Mapear los campos para que jQuery UI los entienda
                  const usuarios = $.map(data, function(item) {
                      return {
                          label: item.LABEL, // Lo que se muestra
                          value: item.LABEL, // Lo que se pone en el input
                          id: item.ID        // Tu ID para después
                      };
                  });
                  response(usuarios);
              }
          });
      },
      minLength: 1,
      select: function(event, ui) {
          $("#usuario_id").val(ui.item.id);
          cargarSucursales(ui.item.id);
      }
  });

  function cargarSucursales(usuarioId) {
    $('#loading').show();
    $.getJSON('../ajax/get.sucursales.php', { usuarioid: usuarioId }, function(data) {
      let html = '<h5>Sucursales</h5>';
      html += '<div class="row">';
      data.forEach(function(item) {
        const checked = item.PERMISO == 1 ? 'checked' : '';
        html += `
          <div class="col-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" ${checked} id="suc${item.ID}" 
                      onchange="actualizarPermiso(${usuarioId}, ${item.ID}, this.checked)">
              <label class="form-check-label" for="suc${item.ID}">${item.NOMBRE}</label>
            </div>
          </div>
        `;
      });
      html += '</div>';
      $('#contenedorSucursales').html(html);
      $('#loading').hide();
    });
  }

  function actualizarPermiso(usuarioId, sucursalId, checked) {
    $.post('../ajax/usuarios.actualizar.sucursales.php', {
      usuarioid: usuarioId,
      sucursalid: sucursalId,
      permiso: checked ? 1 : 0
    });
  }

</script>