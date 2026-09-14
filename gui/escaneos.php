<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once("../includes/head.php");?>
  </head>
  <body>
    <?php $rfid = new rfid();?>
    <div class="container-scroller"> 
      <?php include_once("../includes/header.php"); ?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once ("../includes/menu.sidebar.php")?>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-sm-12">
                <div class="home-tab">
                  <?php //include_once("../includes/tickets.menu.php");?>
                </div>
                  <div class="tab-content tab-content-basic">
                    <!-- Inicia contenido principal -->
                    <div class="row">
                      <div class="col-12">
                        <div class="card">
                          <div class="card-header">
                            <h4>Escaneos</h4>
                          </div>
                          <div class="card-body">
                            <?php
                            $res = $rfid->getescaneos();
                            if ($res <> 0) {
                              ?>
                              <!-- Desktop Table -->
                              <div class="table-responsive d-none d-md-block" style="width: 100%;">
                                <table class="table table-striped">
                                  <thead>
                                    <tr>
                                      <th scope="col">Folio Maleta</th>
                                      <th scope="col">Fecha</th>
                                      <th scope="col">Responsable</th>
                                      <th scope="col" width="100px"></th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <?php foreach ($res as $row){?>
                                    <tr>
                                      <td><?=$row['RFID_FOLIO']?> <?= !empty($row['MALETA_NOMBRE']) ? ' - ' . htmlentities($row['MALETA_NOMBRE']) : '' ?></td>
                                      <td><?=$row['RFID_FECHA']?></td>
                                      <td><?=$row['RESPONSABLE_NOMBRE']?></td>
                                      <td>
                                        <a data-toggle="modal" data-target="#modalglobal" data-title="Detalle de Ticket" data-url="../includes/escaneos.info.php?escaneoid=<?=base64_encode($row['RFID_ID'])?>" aria-selected="false"><img src="../img/info.png" style="width:25px; height:auto; cursor:pointer;" title="Información"></a>
                                        <a href="../gdocs/escaneos.formato.php?escaneoid=<?=base64_encode($row['RFID_ID'])?>" target="_blank"><img src="../img/pdf.png" style="width:25px; height:auto; cursor:pointer;" title="Formato"></a>
                                      </td>
                                    </tr>
                                    <?php } ?>
                                  </tbody>
                                </table>
                              </div>
                              
                              <!-- Mobile View (Cards) -->
                              <div class="d-block d-md-none mt-2">
                                <?php foreach ($res as $row){?>
                                  <div class="mobile-card">
                                    <div class="mobile-card-header">
                                      <span class="mobile-card-title"><?=$row['RFID_FOLIO']?> <?= !empty($row['MALETA_NOMBRE']) ? ' - ' . htmlentities($row['MALETA_NOMBRE']) : '' ?></span>
                                    </div>
                                    <div class="mobile-card-body">
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Fecha</span>
                                        <span class="mobile-card-value"><?=$row['RFID_FECHA']?></span>
                                      </div>
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Responsable</span>
                                        <span class="mobile-card-value"><?=$row['RESPONSABLE_NOMBRE']?></span>
                                      </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Detalle de Ticket" data-url="../includes/escaneos.info.php?escaneoid=<?=base64_encode($row['RFID_ID'])?>" aria-selected="false"><img src="../img/info.png" style="width:28px; height:auto; cursor:pointer;" title="Información"></a>
                                      <a href="../gdocs/escaneos.formato.php?escaneoid=<?=base64_encode($row['RFID_ID'])?>" target="_blank"><img src="../img/pdf.png" style="width:28px; height:auto; cursor:pointer;" title="Formato"></a>
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

  function cancelar(id) {
    // 1) Pido el comentario
    var comentario = prompt('Por favor, escribe el motivo de la cancelación:');
    if (comentario === null) {
      // El usuario pulsó "Cancelar" en el prompt
      return false;
    }
    comentario = comentario.trim();
    if (comentario === '') {
      alert('Debes escribir un comentario para continuar.');
      return false;
    }

    // 2) Confirmo la operación
    if (!confirm('¿Seguro que deseas cancelar el Ticket?')) {
      return false;
    }

    // 3) Envío vía AJAX incluyendo el comentario
    $.ajax({
      url: '../ajax/ticket.update.status.php',
      type: 'POST',
      data: {
        id:     id,
        status: 3,
        comentario:   comentario   // aquí va tu campo
      },
      dataType: 'html',
      beforeSend: function() {
        $("#loading").show();
      },
      success: function(response) {
        if (response === "") {
          alert("Ticket cancelado con éxito");
          location.reload();
        } else {
          alert(response);
        }
      },
      error: function(xhr, status, error) {
        console.error('Error en la solicitud:', error);
      },
      complete: function() {
        $("#loading").hide();
      }
    });
  }

  function autorizar(id) {
    // 1) Pido el comentario
    var comentario = prompt('Por favor, escribe una nota para la autorización');
    if (comentario === null) {
      // El usuario pulsó "Cancelar" en el prompt
      return false;
    }
    comentario = comentario.trim();
    if (comentario === '') {
      alert('Debes escribir una nota de autorización para continuar.');
      return false;
    }

    // 2) Confirmo la operación
    if (!confirm('¿Seguro que deseas autorizar el Ticket?')) {
      return false;
    }

    // 3) Envío vía AJAX incluyendo el comentario
    $.ajax({
      url: '../ajax/ticket.update.status.php',
      type: 'POST',
      data: {
        id:     id,
        status: 2,
        comentario:   comentario   // aquí va tu campo
      },
      dataType: 'html',
      beforeSend: function() {
        $("#loading").show();
      },
      success: function(response) {
        if (response === "") {
          alert("Ticket autorizado con éxito");
          location.reload();
        } else {
          alert(response);
        }
      },
      error: function(xhr, status, error) {
        console.error('Error en la solicitud:', error);
      },
      complete: function() {
        $("#loading").hide();
      }
    });
  }

</script>