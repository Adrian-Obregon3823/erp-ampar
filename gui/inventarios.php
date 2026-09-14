<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <?php include_once("../includes/head.php"); ?>
</head>

<body>
  <?php $almacenes = new almacenes(); ?>
  <div class="container-scroller">
    <?php include_once("../includes/header.php"); ?>
    <div class="container-fluid page-body-wrapper">
      <?php include_once("../includes/menu.sidebar.php") ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <?php include_once("../includes/inventarios.menu.php"); ?>
              </div>
              <div class="tab-content tab-content-basic">
                <!-- Inicia contenido principal -->
                <div class="row mt-4">
                  <div class="col-12 text-center">
                    <br><br>
                    <img src="../img/logo.png" style="opacity: 0.2; max-width: 200px;" alt="Ampar">
                    <h3 class="text-muted mt-4">Seleccione una opción del menú superior</h3>
                    <p class="text-muted">Utilice las pestañas para gestionar Maletas, Entradas, Salidas, Traspasos, o ver el <a href="inventarioglobal.php">Dashboard Global</a>.</p>
                  </div>
                </div>
                <!-- Finaliza contenido principal -->
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
    var url = button.data('url'); // Obtén la URL del atributo data-url
    var title = button.data('title'); // Obtén el título del atributo data-title

    // Cambia el título del encabezado del modal
    var modal = $(this);
    modal.find('.modal-title').text(title); // Actualiza el título en el header del modal

    // Asegurar que el modal tenga el tamaño xl
    modal.find('.modal-dialog').addClass('modal-xl')

    // Realiza la solicitud AJAX para cargar el contenido de la URL en el modal
    $.ajax({
      url: url,
      type: 'GET',
      success: function(response) {
        modal.find('.modal-body').html(response); // Inserta el contenido en el modal
      },
      error: function() {
        modal.find('.modal-body').html('<p>Error al cargar la página.</p>');
      }
    });
  });

  function cancelar(id) {
    if (confirm('¿Seguro que deseas cancelar la solicitud de inventario?')) {
      $.ajax({
        url: '../ajax/inventarios.update.status.php',
        type: 'POST',
        data: {
          id: id,
          status: 3
        },
        dataType: 'html',
        beforeSend: function() {
          $("#loading").show();
        },
        success: function(response) {
          if (response.trim() === "") {
            alert("Almacén modificado con éxito");
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

  function autorizar(id) {
    if (confirm('¿Seguro que deseas autorizar la solicitud de inventario?')) {
      $.ajax({
        url: '../ajax/inventarios.update.status.php',
        type: 'POST',
        data: {
          id: id,
          status: 2
        },
        dataType: 'html',
        beforeSend: function() {
          $("#loading").show();
        },
        success: function(response) {
          if (response.trim() === "") {
            alert("Almacén modificado con éxito");
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