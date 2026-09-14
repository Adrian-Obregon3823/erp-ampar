<?php include_once("../includes/includes.php");?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once("../includes/head.php");?>
  </head>
  <body>
    <div class="container-scroller"> 
      <?php include_once("../includes/header.php"); ?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once ("../includes/menu.eventosfiltrados.sidebar.php")?>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-sm-12">
                <div class="home-tab">
                  <?php include_once("../includes/eventos.menu.php");?>
                </div>
                  <div class="tab-content tab-content-basic">
                    <!-- Inicia contenido principal -->
                    <div class="row">
                      <div class="col-12">
                        <div class="card">
                          <div class="card-header">
                            <h4>Entradas / Salidas</h4>
                          </div>
                          <div class="card-body">
                              <?php include_once("../includes/eventos.calendario.php");?>
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

  $('#modalglobal').on('show.bs.modal', function (event) {
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

</script>