<?php include_once("../includes/includes.php");?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once("../includes/head.php");?>
  </head>
  <body>
    <?php $eventos = new eventos();?>
    <div class="container-scroller"> 
      <?php include_once("../includes/header.php"); ?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once ("../includes/menu.eventosfiltrados.sidebar.php")?>
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
                            <h4>Entradas / Salidas</h4>
                          </div>
                          <div class="card-body">
                            <?php
                            $eventos = new eventos();
                            $res = $eventos->geteventos(isset($_GET['responsableid'])?$_GET['responsableid']:"");
                            if ($res <> 0){
                              ?>
                              <!-- Desktop Table -->
                              <div class="table-responsive d-none d-md-block" style="width: 100%;">
                                <table class="table table-striped">
                                  <tr>
                                    <th>Evento Id</th>
                                    <th>Fecha</th>
                                    <th>Tipo Evento</th>
                                    <th>Especialista</th>
                                    <th>Chofer</th>
                                    <th></th>
                                  </tr>
                                  <?php
                                  foreach ($res as $r){
                                    ?>
                                    <tr>
                                      <td style="font_weight:bolder; color:#<?=$r['EVENTOSSTATUS_COLOR']?>"><?=$r['EVENTO_ID']?></td>
                                      <td><?=$r['EVENTO_FECHACREACION']?></td>
                                      <td><?=$r['TIPOEVENTO_NOMBRE']?></td>
                                      <td><?=$r['ESPECIALISTA_NOMBRE']?> <span style="color:#<?=$r['STATUSCOLORESPECIALISTA']?>">(<?=$r['STATUSESPECIALISTA']?>)</span></td>
                                      <td><?=$r['CHOFER_NOMBRE']?> <span style="color:#<?=$r['STATUSCOLORCHOFER']?>">(<?=$r['STATUSCHOFER']?>)</span></td>
                                      <td>
                                        <a href="../gdocs/eventos.formato.php?eventoid=<?=base64_encode($r['EVENTO_ID'])?>" target="blank"><img src="../img/pdf.png" style="width:25px; height:auto;"></a>
                                        <?php if ($r['EVENTO_STATUSGENERAL'] == 2){ ?>
                                          <?php if ($r['EVENTO_ESPECIALISTAIDSTATUS'] == 1){ ?>
                                            <img src="../img/check.png"  style="width:25px; height:auto; cursor:pointer;" onclick="changestatusespecialista('<?=$r['EVENTO_ID']?>','2')">
                                            <img src="../img/rechazar.png"  style="width:25px; height:auto; cursor:pointer;" onclick="changestatusespecialista('<?=$r['EVENTO_ID']?>','3')">
                                          <?php } ?>
                                        <?php } ?>
                                      </td>
                                    </tr>
                                    <?php
                                  }
                                  ?>
                                </table>
                              </div>
                              
                              <!-- Mobile View (Cards) -->
                              <div class="d-block d-md-none mt-2">
                                <?php foreach ($res as $r){ ?>
                                  <div class="mobile-card">
                                    <div class="mobile-card-header">
                                      <span class="mobile-card-title" style="color:#<?=$r['EVENTOSSTATUS_COLOR']?>">Evento #<?=$r['EVENTO_ID']?></span>
                                    </div>
                                    <div class="mobile-card-body">
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Fecha</span>
                                        <span class="mobile-card-value"><?=$r['EVENTO_FECHACREACION']?></span>
                                      </div>
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Tipo Evento</span>
                                        <span class="mobile-card-value"><?=$r['TIPOEVENTO_NOMBRE']?></span>
                                      </div>
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Especialista</span>
                                        <span class="mobile-card-value text-right"><?=$r['ESPECIALISTA_NOMBRE']?> <br><small style="color:#<?=$r['STATUSCOLORESPECIALISTA']?>">(<?=$r['STATUSESPECIALISTA']?>)</small></span>
                                      </div>
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Chofer</span>
                                        <span class="mobile-card-value text-right"><?=$r['CHOFER_NOMBRE']?> <br><small style="color:#<?=$r['STATUSCOLORCHOFER']?>">(<?=$r['STATUSCHOFER']?>)</small></span>
                                      </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                      <a href="../gdocs/eventos.formato.php?eventoid=<?=base64_encode($r['EVENTO_ID'])?>" target="_blank"><img src="../img/pdf.png" style="width:28px; height:auto;"></a>
                                      <?php if ($r['EVENTO_STATUSGENERAL'] == 2){ ?>
                                        <?php if ($r['EVENTO_ESPECIALISTAIDSTATUS'] == 1){ ?>
                                          <img src="../img/check.png"  style="width:28px; height:auto; cursor:pointer;" onclick="changestatusespecialista('<?=$r['EVENTO_ID']?>','2')">
                                          <img src="../img/rechazar.png"  style="width:28px; height:auto; cursor:pointer;" onclick="changestatusespecialista('<?=$r['EVENTO_ID']?>','3')">
                                        <?php } ?>
                                      <?php } ?>
                                    </div>
                                  </div>
                                <?php } ?>
                              </div>
                              <?php

                            }else{
                              echo '<div class="alert alert-warning text-center">No se encontró información</div>';
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

  function changestatusespecialista(eventoid,statusid){
    var varstatus = "";
    if (statusid == 2){
      varstatus = "Confirmar";
    }else{
      varstatus = "rechazar";
    }
    if (confirm('¿Seguro que deseas '+varstatus+' el evento?')){
      $.ajax({
          url: '../ajax/eventosfiltrados.update.status.php',
          type: 'POST',
          data: {
            eventoid: eventoid,
            status: statusid
          },
          dataType: 'html',
          beforeSend: function() {
              $("#loading").show();
          },
          success: function(response) {
              if (response.trim() === ""){
                  alert ("Evento modificado con éxito");
              }else{
                  alert (response);
              }
          },
          error: function(xhr, status, error) {
              console.error('Error en la solicitud:', error);
          },
          complete: function(data) {
              $("#loading").hide();
          }
      });
    }else{
      return false;
    }
  }

</script>