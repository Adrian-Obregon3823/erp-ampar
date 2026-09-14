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
    <?php $traspasos = new traspasos();?>
    <div class="container-scroller"> 
      <?php include_once("../includes/header.php"); ?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once ("../includes/menu.sidebar.php")?>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-sm-12">
                <div class="home-tab">
                  <?php //include_once("../includes/traspasos.menu.php");?>
                </div>
                  <div class="tab-content tab-content-basic">
                    <!-- Inicia contenido principal -->
                    <div class="row">
                      <div class="col-12">
                        <div class="card">
                          <div class="card-header">
                            <h4>Traspasos  &nbsp;&nbsp;<a style="cursor:pointer;" data-toggle="modal" data-target="#modalglobal" data-title="Traspaso de Almacén" data-url="../includes/traspasos.nuevo.php" aria-selected="false"><img src="../img/agregar2.png" style="width:25px; height:25px" title="Traspaso de Almacén"></a></h4>
                          </div>
                          <div class="card-body">
                            <?php
                            $filtroStatus = isset($_GET['status']) ? $_GET['status'] : '1';
                            $res = $traspasos->gettraspasos($filtroStatus);
                            ?>
                            
                            <!-- TABS DE FILTRO -->
                            <ul class="nav nav-tabs mb-4" style="border-bottom: 2px solid #dee2e6;">
                              <li class="nav-item">
                                <a class="nav-link <?= ($filtroStatus === '1') ? 'active font-weight-bold' : 'text-muted' ?>" href="traspasos.php?status=1">Guardados</a>
                              </li>
                              <li class="nav-item">
                                <a class="nav-link <?= ($filtroStatus === '8') ? 'active font-weight-bold' : 'text-muted' ?>" href="traspasos.php?status=8">En Revisión</a>
                              </li>
                              <li class="nav-item">
                                <a class="nav-link <?= ($filtroStatus === '9') ? 'active font-weight-bold' : 'text-muted' ?>" href="traspasos.php?status=9">Enviados</a>
                              </li>
                              <li class="nav-item">
                                <a class="nav-link <?= ($filtroStatus === '3') ? 'active font-weight-bold' : 'text-muted' ?>" href="traspasos.php?status=3">Finalizados</a>
                              </li>
                            </ul>

                            <?php
                            if ($res <> 0) {
                              ?>
                              <!-- Desktop Table -->
                              <div class="table-responsive d-none d-md-block" style="width: 100%;">
                                <table class="table table-striped">
                                  <thead>
                                    <tr>
                                      <th scope="col" width="100px"></th>
                                      <th scope="col">Folio</th>
                                      <th scope="col">De Almacén</th>
                                      <th scope="col">A Almacén</th>
                                      <th scope="col">Artículos</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                      <?php foreach ($res as $row){
                                        $isMaletaDe = ((int)$row['TIPO_DE'] === 3);
                                        $isMaletaA = ((int)$row['TIPO_A'] === 3);
                                        $isSpecial = false;
                                        if (($isMaletaDe && !$isMaletaA) || (!$isMaletaDe && $isMaletaA)) {
                                            $isSpecial = true;
                                        }
                                        if (!$isMaletaDe && !$isMaletaA && (int)$row['TRASPASO_AALMACENID'] === 10 && (int)$row['SUCURSAL_IDDE'] === 1) {
                                            $isSpecial = true;
                                        }
                                      ?>
                                    <tr>
                                      <td>
                                        <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Traspaso" data-url="../includes/traspasos.info.php?traspasoid=<?=base64_encode($row['TRASPASO_ID'])?>" aria-selected="false"><img src="../img/info.png" style="width:20px; height:auto; cursor:pointer;" title="Información"></a>
                                        <?php if ($row['TRASPASO_STATUS'] == 1 || $row['TRASPASO_STATUS'] == 6){ ?>
                                          <a data-toggle="modal" data-target="#modalglobal" data-title="Edición Traspaso" data-url="../includes/traspasos.editar.php?traspasoid=<?=base64_encode($row['TRASPASO_ID'])?>" aria-selected="false"><img src="../img/edit.png" style="width:20px; height:auto; cursor:pointer;" title="Editar"></a>
                                          <a onclick="changestatus('<?=$row['TRASPASO_ID']?>',8,'<?=$row['TRASPASO_FOLIO']?>')"><img src="../img/REVISAR.png" style="width:20px; height:auto; cursor:pointer;" title="Enviar a Revisión"></a>
                                          <a onclick="changestatus('<?=$row['TRASPASO_ID']?>',5,'<?=$row['TRASPASO_FOLIO']?>');"><img src="../img/eliminar.png" style="width:20px; height:auto; cursor:pointer;" title="Cancelar"></a>
                                        <?php } ?> 
                                        <a href="../gdocs/traspaso.formato.php?traspasoid=<?=base64_encode($row['TRASPASO_ID'])?>" target="blank"><img src="../img/pdf.png" style="width:20px; height:auto; cursor:pointer;" title="Formato"></a>
                                        <?php if (!$isSpecial): ?>
                                          <a href="../gdocs/traspaso.etiqueta.php?traspasoid=<?=base64_encode($row['TRASPASO_ID'])?>" target="blank"><img src="../img/barcode.webp" style="width:20px; height:auto; cursor:pointer;" title="Etiqueta"></a>
                                        <?php endif; ?>
                                        <?php if ($row['TRASPASO_STATUS'] == 8){ ?>
                                          <?php if ($GLOBALS['isAdmin'] ?? false): ?>
                                            <a onclick="mostrarConfirmacionArticulos('<?=$row['TRASPASO_ID']?>')"><img src="../img/check.png" style="width:20px; height:auto; cursor:pointer;" title="Enviar / Autorizar"></a>
                                          <?php endif; ?>
                                          <a onclick="changestatus('<?=$row['TRASPASO_ID']?>',5,'<?=$row['TRASPASO_FOLIO']?>');"><img src="../img/eliminar.png" style="width:20px; height:auto; cursor:pointer;" title="Cancelar"></a>
                                        <?php } ?>
                                        <?php if ($row['TRASPASO_STATUS'] == 9){ ?>
                                          <!-- En Enviado no se hace nada, se espera la recepción para pasar a finalizado -->
                                        <?php } ?>
                                      </td>
                                      <td><?=$row['TRASPASO_FOLIO']?> <span style="font-size:10px; color:#<?=$row['STATUS_COLOR']?>">(<?=$row['STATUS_NOMBRE']?>)</span></td>
                                      <td><?=$row['ALMACEN_NOMBREDE']?> <b>(<?=$row['SUCURSAL_NOMBREDE']?>)</b></td>
                                      <td><?=$row['ALMACEN_NOMBREA']?> <b>(<?=$row['SUCURSAL_NOMBREA']?>)</b></td>
                                      <td><?=$row['CANTIDAD']?></td>
                                    </tr>
                                    <?php } ?>
                                  </tbody>
                                </table>
                              </div>
                              
                              <!-- Mobile View (Cards) -->
                              <div class="d-block d-md-none mt-2">
                                <?php foreach ($res as $row){
                                        $isMaletaDe = ((int)$row['TIPO_DE'] === 3);
                                        $isMaletaA = ((int)$row['TIPO_A'] === 3);
                                        $isSpecial = false;
                                        if (($isMaletaDe && !$isMaletaA) || (!$isMaletaDe && $isMaletaA)) {
                                            $isSpecial = true;
                                        }
                                        if (!$isMaletaDe && !$isMaletaA && (int)$row['TRASPASO_AALMACENID'] === 10 && (int)$row['SUCURSAL_IDDE'] === 1) {
                                            $isSpecial = true;
                                        }
                                ?>
                                  <div class="mobile-card">
                                    <div class="mobile-card-header">
                                      <span class="mobile-card-title"><?=$row['TRASPASO_FOLIO']?></span>
                                      <span class="mobile-card-badge" style="color:#<?=$row['STATUS_COLOR']?>; border: 1px solid #<?=$row['STATUS_COLOR']?>40; background-color: #<?=$row['STATUS_COLOR']?>15;">
                                        <?=$row['STATUS_NOMBRE']?>
                                      </span>
                                    </div>
                                    <div class="mobile-card-body">
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">De Almacén</span>
                                        <span class="mobile-card-value text-right"><?=$row['ALMACEN_NOMBREDE']?><br><small class="text-muted"><?=$row['SUCURSAL_NOMBREDE']?></small></span>
                                      </div>
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">A Almacén</span>
                                        <span class="mobile-card-value text-right"><?=$row['ALMACEN_NOMBREA']?><br><small class="text-muted"><?=$row['SUCURSAL_NOMBREA']?></small></span>
                                      </div>
                                      <div class="mobile-card-row">
                                        <span class="mobile-card-label">Artículos</span>
                                        <span class="mobile-card-value font-weight-bold"><?=$row['CANTIDAD']?></span>
                                      </div>
                                    </div>
                                    <div class="mobile-card-actions">
                                      <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Traspaso" data-url="../includes/traspasos.info.php?traspasoid=<?=base64_encode($row['TRASPASO_ID'])?>" aria-selected="false"><img src="../img/info.png" style="width:28px; height:auto; cursor:pointer;" title="Información"></a>
                                      <?php if ($row['TRASPASO_STATUS'] == 1 || $row['TRASPASO_STATUS'] == 6){ ?>
                                        <a data-toggle="modal" data-target="#modalglobal" data-title="Edición Traspaso" data-url="../includes/traspasos.editar.php?traspasoid=<?=base64_encode($row['TRASPASO_ID'])?>" aria-selected="false"><img src="../img/edit.png" style="width:28px; height:auto; cursor:pointer;" title="Editar"></a>
                                        <a onclick="changestatus('<?=$row['TRASPASO_ID']?>',8,'<?=$row['TRASPASO_FOLIO']?>')"><img src="../img/REVISAR.png" style="width:28px; height:auto; cursor:pointer;" title="Enviar a Revisión"></a>
                                        <a onclick="changestatus('<?=$row['TRASPASO_ID']?>',5,'<?=$row['TRASPASO_FOLIO']?>');"><img src="../img/eliminar.png" style="width:28px; height:auto; cursor:pointer;" title="Cancelar"></a>
                                      <?php } ?> 
                                      <a href="../gdocs/traspaso.formato.php?traspasoid=<?=base64_encode($row['TRASPASO_ID'])?>" target="blank"><img src="../img/pdf.png" style="width:28px; height:auto; cursor:pointer;" title="Formato"></a>
                                      <?php if (!$isSpecial): ?>
                                        <a href="../gdocs/traspaso.etiqueta.php?traspasoid=<?=base64_encode($row['TRASPASO_ID'])?>" target="blank"><img src="../img/barcode.webp" style="width:28px; height:auto; cursor:pointer;" title="Etiqueta"></a>
                                      <?php endif; ?>
                                      <?php if ($row['TRASPASO_STATUS'] == 8){ ?>
                                        <?php if ($GLOBALS['isAdmin'] ?? false): ?>
                                          <a onclick="mostrarConfirmacionArticulos('<?=$row['TRASPASO_ID']?>')"><img src="../img/check.png" style="width:28px; height:auto; cursor:pointer;" title="Enviar / Autorizar"></a>
                                        <?php endif; ?>
                                        <a onclick="changestatus('<?=$row['TRASPASO_ID']?>',5,'<?=$row['TRASPASO_FOLIO']?>');"><img src="../img/eliminar.png" style="width:28px; height:auto; cursor:pointer;" title="Cancelar"></a>
                                      <?php } ?>
                                      <?php if ($row['TRASPASO_STATUS'] == 9){ ?>
                                        <!-- En Enviado no se hace nada, se espera la recepción para pasar a finalizado -->
                                      <?php } ?>
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
<!-- Modal -->
<div class="modal fade" id="modalConfirmarArticulos" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabel">Confirmar artículos de la solicitud</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="form-confirmar-articulos">
          <input type="hidden" id="traspasoid" name="traspasoid">
          <div id="seccion-docs-generales-confirmar" class="mb-3"></div>
          <div style="overflow-x:auto; width: 100%;">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>Confirmar</th>
                  <th>Id</th>
                  <th>Clave</th>
                  <th>Artículo</th>
                  <th>Lote</th>
                  <th>Caducidad</th>
                  <th>Serie</th>
                </tr>
              </thead>
              <tbody id="tablaConfirmacionBody">
                <!-- Aquí se insertarán las filas dinámicamente -->
              </tbody>
            </table>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" onclick="confirmarEntrada()">Confirmar Seleccionados</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Regresar</button>
        <button type="button" class="btn btn-danger" onclick="abrirModalRechazo()">Rechazar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Rechazar -->
<div class="modal fade" id="modalRechazarTraspaso" aria-labelledby="modalRechazarLabel" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalRechazarLabel"><i class="mdi mdi-alert-circle"></i> Rechazar Solicitud</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>Por favor seleccione los documentos que son motivo de rechazo y justifique detalladamente el motivo de cada uno:</p>
        <div id="contenedor-docs-rechazo">
          <!-- Se llenará dinámicamente -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Regresar</button>
        <button type="button" class="btn btn-danger" onclick="guardarRechazo()">Sí, Rechazar Solicitud</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Enviar a Revisión -->
<div class="modal fade" id="modalEnviarRevision" tabindex="-1" aria-labelledby="modalRevisionLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalRevisionLabel">Enviar a Revisión - Traspaso <span id="folio_revision_text"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="form-enviar-revision" enctype="multipart/form-data">
          <input type="hidden" id="traspasoid_revision" name="traspasoid_revision">
          
          <div class="alert alert-info req-traspaso-all">
              Por favor, adjunta la siguiente información requerida para enviar a revisión.
          </div>

          <div class="row req-traspaso-all">
              <div class="col-12 col-md-6 mb-3 req-traspaso-field">
                  <label><strong>Tomar foto de la caja por fuera:</strong> <span class="text-danger">*</span></label>
                  <input type="file" class="form-control form-control-sm mb-1" name="archivo_evidencia1_traspaso" id="archivo_evidencia1_traspaso" accept="image/*" capture="environment">
                  <small class="text-success d-none ev-badge" id="badge_ev1"><i class="fa fa-check-circle"></i> Archivo guardado previamente</small>
              </div>
              <div class="col-12 col-md-6 mb-3 req-traspaso-field">
                  <label><strong>Tomar foto de la caja abierta:</strong> <span class="text-danger">*</span></label>
                  <input type="file" class="form-control form-control-sm mb-1" name="archivo_evidencia2_traspaso" id="archivo_evidencia2_traspaso" accept="image/*" capture="environment">
                  <small class="text-success d-none ev-badge" id="badge_ev2"><i class="fa fa-check-circle"></i> Archivo guardado previamente</small>
              </div>
          </div>
          <div class="row req-traspaso-all">
              <div class="col-12 col-md-6 mb-3">
                  <label><strong>Tomar foto de los artículos:</strong> <span class="text-danger">*</span></label>
                  <input type="file" class="form-control form-control-sm mb-1" name="archivo_evidencia3_traspaso" id="archivo_evidencia3_traspaso" accept="image/*" capture="environment">
                  <small class="text-success d-none ev-badge" id="badge_ev3"><i class="fa fa-check-circle"></i> Archivo guardado previamente</small>
              </div>
              <div class="col-12 col-md-6 mb-3 req-traspaso-field">
                  <label><strong>Tomar foto de la guía pegada:</strong> <span class="text-danger">*</span></label>
                  <input type="file" class="form-control form-control-sm mb-1" name="archivo_evidencia4_traspaso" id="archivo_evidencia4_traspaso" accept="image/*" capture="environment">
                  <small class="text-success d-none ev-badge" id="badge_ev4"><i class="fa fa-check-circle"></i> Archivo guardado previamente</small>
              </div>
          </div>

          <div class="row mb-3 req-traspaso-all req-traspaso-field">
              <div class="col-12 mb-2">
                  <label><strong>Lista de Empaque:</strong> <span class="text-danger">*</span></label>
                  <div id="lista_eminputs_container">
                      <!-- dynamic inputs -->
                  </div>
                  <small class="text-success d-none ev-badge" id="badge_lista"><i class="fa fa-check-circle"></i> Archivo guardado previamente</small>
                  <small class="form-text text-muted d-block mt-1" id="empaque_pages_info"></small>
              </div>
              <div class="col-12 mb-2">
                  <button type="button" class="btn btn-sm btn-primary text-white" onclick="agregarCampoListaEmpaque()"><i class="mdi mdi-camera-plus"></i> Añadir otra foto / archivo</button>
              </div>
          </div>

          <div class="row mb-3 req-traspaso-all req-traspaso-field">
              <div class="col-12 col-md-4 mb-2">
                  <label><strong>Paquetería:</strong> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-sm" name="paqueteria_revision" id="paqueteria_revision" placeholder="Ej. DHL, FedEx...">
              </div>
              <div class="col-12 col-md-4 mb-2">
                  <label><strong>Link de Rastreo:</strong> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-sm" name="link_rastreo_revision" id="link_rastreo_revision" placeholder="Ej. https://dhl.com/...">
              </div>
              <div class="col-12 col-md-4 mb-2">
                  <label><strong>Número de Guía:</strong> <span class="text-danger">*</span></label>
                  <input type="text" class="form-control form-control-sm" name="numero_guia_revision" id="numero_guia_revision" placeholder="Número de guía">
              </div>
          </div>

        </form>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-info" onclick="guardarAvance()">Guardar Avance</button>
        <div>
            <button type="button" class="btn btn-success" onclick="enviarRevision()">Enviar a Revisión</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </div>
    </div>
  </div>
</div>

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

  function changestatus(id,status,folio){
    var accion = "";
    var done = "";
    switch (status) {
      case 2: 
        accion = "autorizar";
        done = "autorizado";
        break;
      case 5: 
        accion = "cancelar";
        done = "cancelado";
        break;
      case 8: 
        if(status == 8){
          $("#traspasoid_revision").val(id);
          $("#folio_revision_text").text(folio);
          // Limpiar inputs
          $("#archivo_evidencia1_traspaso").val("");
          $("#archivo_evidencia2_traspaso").val("");
          $("#archivo_evidencia3_traspaso").val("");
          $("#archivo_evidencia4_traspaso").val("");
          $("#archivo_lista_empaque").val("");
          $("#paqueteria_revision").val("");
          $("#link_rastreo_revision").val("");
          $("#numero_guia_revision").val("");
          
          $.ajax({
            url: '../ajax/traspasos.get.requisitos.php',
            type: 'POST',
            data: { traspasoid: id },
            dataType: 'json',
            success: function(res) {
              if (res.ok) {
                // Mostrar todos por defecto
                $(".req-traspaso-field").show();
                $(".req-traspaso-all").show();
                $(".ev-badge").addClass('d-none');
                
                window.ev1_subida = res.data.evidencia1_subida;
                window.ev2_subida = res.data.evidencia2_subida;
                window.ev3_subida = res.data.evidencia3_subida;
                window.ev4_subida = res.data.evidencia4_subida;
                window.lista_subida = res.data.listaempaque_subida;
                
                if (window.ev1_subida) $("#badge_ev1").removeClass('d-none');
                if (window.ev2_subida) $("#badge_ev2").removeClass('d-none');
                if (window.ev3_subida) $("#badge_ev3").removeClass('d-none');
                if (window.ev4_subida) $("#badge_ev4").removeClass('d-none');
                if (window.lista_subida) $("#badge_lista").removeClass('d-none');
                
                let expectedPages = res.data.pdfPages || 1;
                window.expectedEmpaquePages = expectedPages;
                $('#empaque_pages_info').text('Se requiere subir al menos ' + expectedPages + ' foto(s), una por cada hoja del formato.');
                $('#lista_eminputs_container').empty();
                for (let i = 0; i < expectedPages; i++) {
                    agregarCampoListaEmpaque(i === 0);
                }
                
                if (res.data.eximeTodo) {
                  // Ocultar todo, ya que no se pide nada
                  $(".req-traspaso-all").hide();
                  Swal.fire({
                    title: 'Aviso',
                    text: 'Este traspaso entre maleta y almacén no requiere adjuntar evidencia ni paquetería.',
                    icon: 'info'
                  });
                } else if (res.data.requiereFotosSolamente) {
                  // Ocultar paqueteria, guía, lista de empaque
                  $(".req-traspaso-field").hide();
                  Swal.fire({
                    title: 'Aviso',
                    text: 'Este traspaso solo requiere fotos de evidencia, no requiere datos de paquetería.',
                    icon: 'info'
                  });
                }
                $("#modalEnviarRevision").modal('show');
              } else {
                Swal.fire('Error', res.error, 'error');
              }
            },
            error: function() {
              Swal.fire('Error', 'No se pudieron obtener los requisitos del traspaso', 'error');
            }
          });
          return;
        }
        accion = "cambiar el status a en Revisión a ";
        done = "actualizado";
        break;
        done = "enviado";
        break;
      default:
    }

    Swal.fire({
          text: '¿Seguro que deseas '+accion+' el Traspaso con Folio: '+folio+'?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Sí, '+accion,
          cancelButtonText: 'Cancelar',
          customClass: {
              confirmButton: 'btn btn-success',
              cancelButton: 'btn btn-secondary'
          },
          buttonsStyling: false // necesario si usas clases Bootstrap
    }).then((result) => {
          if (result.isConfirmed) {
            $.ajax({
                url: '../ajax/traspasos.update.status.php',
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
                    if (response.trim() === ""){
                        Swal.fire({
                            html: "Solicitud de Traspaso " + done + " con éxito",
                            icon: "success",
                            customClass: {
                                confirmButton: 'btn btn-success' // usa clases de Bootstrap
                            }
                        }).then(() => {
                            location.reload();
                        });
                    }else{
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
          }else{
            return false;
          }
    });
  }
  function guardarAvance() {
      var formData = new FormData(document.getElementById("form-enviar-revision"));
      formData.append("is_avance", "1");
      
      $.ajax({
          url: '../ajax/traspasos.enviar_revision.php',
          type: 'POST',
          data: formData,
          dataType: 'html',
          cache: false,
          contentType: false,
          processData: false,
          beforeSend: function() {
              $("#loading").show();
          },
          success: function(response) {
              if (response.trim() === "") {
                  Swal.fire({
                      html: "Avance guardado con éxito",
                      icon: "success",
                      customClass: { confirmButton: 'btn btn-success' }
                  }).then(() => {
                      $("#modalEnviarRevision").modal('hide');
                      // location.reload(); // opcional, por ahora solo cerramos el modal
                  });
              } else {
                  Swal.fire({
                      html: response,
                      icon: "warning",
                      customClass: { confirmButton: 'btn btn-success' }
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
  }

  function enviarRevision() {
      // Validaciones
      if (!window.ev1_subida && $("#archivo_evidencia1_traspaso").is(':visible') && $("#archivo_evidencia1_traspaso").val() == "") {
          Swal.fire({ html: "La foto de la caja por fuera es obligatoria", icon: "warning", customClass: { confirmButton: 'btn btn-success' } });
          return;
      }
      if (!window.ev2_subida && $("#archivo_evidencia2_traspaso").is(':visible') && $("#archivo_evidencia2_traspaso").val() == "") {
          Swal.fire({ html: "La foto de la caja abierta es obligatoria", icon: "warning", customClass: { confirmButton: 'btn btn-success' } });
          return;
      }
      if (!window.ev3_subida && $("#archivo_evidencia3_traspaso").is(':visible') && $("#archivo_evidencia3_traspaso").val() == "") {
          Swal.fire({ html: "La foto de los artículos es obligatoria", icon: "warning", customClass: { confirmButton: 'btn btn-success' } });
          return;
      }
      if (!window.ev4_subida && $("#archivo_evidencia4_traspaso").is(':visible') && $("#archivo_evidencia4_traspaso").val() == "") {
          Swal.fire({ html: "La foto de la guía pegada es obligatoria", icon: "warning", customClass: { confirmButton: 'btn btn-success' } });
          return;
      }
      
      let inputsListaEmpaque = $('.input-lista-empaque-dinamico');
      let filesCountLista = 0;
      let hasInvalidFileLista = false;
      let isListaVisible = $('#lista_eminputs_container').is(':visible');
      
      if (!window.lista_subida && isListaVisible) {
          inputsListaEmpaque.each(function() {
              if (this.files.length > 0) {
                  filesCountLista++;
                  let empExt = this.files[0].name.split('.').pop().toLowerCase();
                  if ($.inArray(empExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf']) === -1) {
                      hasInvalidFileLista = true;
                  }
              }
          });
          
          let expected = window.expectedEmpaquePages || 1;
          if (filesCountLista < expected) {
              Swal.fire({ html: "Debe subir al menos " + expected + " archivo(s) para la Lista de Empaque. Ha seleccionado " + filesCountLista + ".", icon: "warning", customClass: { confirmButton: 'btn btn-success' } });
              return;
          }
          if (hasInvalidFileLista) {
              Swal.fire({ html: "Todos los archivos de la Lista de Empaque deben ser válidos (imágenes o PDF).", icon: "warning", customClass: { confirmButton: 'btn btn-success' } });
              return;
          }
      }

      if ($("#paqueteria_revision").is(':visible') && $("#paqueteria_revision").val() == "") {
          Swal.fire({ html: "La Paquetería es obligatoria", icon: "warning", customClass: { confirmButton: 'btn btn-success' } });
          return;
      }
      if ($("#link_rastreo_revision").is(':visible') && $("#link_rastreo_revision").val() == "") {
          Swal.fire({ html: "El Link de Rastreo es obligatorio", icon: "warning", customClass: { confirmButton: 'btn btn-success' } });
          return;
      }
      if ($("#numero_guia_revision").is(':visible') && $("#numero_guia_revision").val() == "") {
          Swal.fire({ html: "El Número de Guía es obligatorio", icon: "warning", customClass: { confirmButton: 'btn btn-success' } });
          return;
      }

      var formData = new FormData(document.getElementById("form-enviar-revision"));
      
      $.ajax({
          url: '../ajax/traspasos.enviar_revision.php',
          type: 'POST',
          data: formData,
          dataType: 'html',
          cache: false,
          contentType: false,
          processData: false,
          beforeSend: function() {
              $("#loading").show();
          },
          success: function(response) {
              if (response.trim() === "") {
                  Swal.fire({
                      html: "Traspaso enviado a revisión con éxito",
                      icon: "success",
                      customClass: {
                          confirmButton: 'btn btn-success'
                      }
                  }).then(() => {
                      location.reload();
                  });
              } else {
                  Swal.fire({
                      html: response,
                      icon: "warning",
                      customClass: {
                          confirmButton: 'btn btn-success'
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
  }

  function mostrarConfirmacionArticulos(traspasoid) {
    $.ajax({
      url: '../ajax/traspasos.get.requisitos.php',
      method: 'POST',
      data: { traspasoid: traspasoid },
      dataType: 'json',
      success: function(reqData) {
        if (reqData.ok && reqData.data.eximeTodo) {
          $.ajax({
            url: '../ajax/traspasos.verificar_escaneo.php',
            method: 'POST',
            data: { traspasoid: traspasoid },
            dataType: 'json',
            success: function(escRes) {
              if (escRes.ok) {
                $.ajax({
                  url: '../ajax/traspasos.get.detalle.solicitud.php',
                  method: 'POST',
                  data: { traspasoid: traspasoid },
                  dataType: 'json',
                  success: function(detData) {
                    let seleccionados = detData.map(item => item.TRASPASODET_ID);
                    $.ajax({
                      url: "../ajax/traspasos.autorizar.php",
                      method: "POST",
                      data: { detalles: seleccionados, traspasoid : traspasoid },
                      beforeSend: function() { $("#loading").show(); },
                      success: function(authRes) {
                        if (authRes == ""){
                          let successMsg = escRes.bypassed ? 'Traspaso autorizado automáticamente.' : 'Escaneo físico verificado y traspaso autorizado automáticamente.';
                          Swal.fire('Éxito', successMsg, 'success')
                            .then(() => location.reload());
                        } else {
                          Swal.fire('Error', authRes, 'warning');
                        }
                      },
                      complete: function() { $("#loading").hide(); }
                    });
                  }
                });
              } else {
                Swal.fire('Autorización Bloqueada', escRes.error, 'error');
              }
            }
          });
        } else {
          cargarModalConfirmacionNormal(traspasoid);
        }
      },
      error: function() {
        cargarModalConfirmacionNormal(traspasoid);
      }
    });
  }

  function cargarModalConfirmacionNormal(traspasoid) {
    $.ajax({
      url: '../ajax/traspasos.get.detalle.solicitud.php', // Tu archivo PHP para el query
      method: 'POST',
      data: { traspasoid: traspasoid },
      dataType: 'json',
      success: function(data) {
        $("#traspasoid").val(traspasoid);
        
        let docsGenHtml = '';
        if (data.length > 0 && (data[0].TRASPASO_EVIDENCIA || data[0].TRASPASO_GUIA || data[0].TRASPASO_PAQUETERIA || data[0].TRASPASO_NUMGUIA || data[0].TRASPASO_LINK || data[0].TRASPASO_EVIDENCIA1 || data[0].TRASPASO_EVIDENCIA2 || data[0].TRASPASO_EVIDENCIA3 || data[0].TRASPASO_EVIDENCIA4 || data[0].TRASPASO_LISTAEMPAQUE)) {
          docsGenHtml += `<div class="p-3 mb-3 rounded border bg-light d-flex flex-wrap align-items-center" style="gap: 15px;">
            <strong class="text-dark">Datos del Traspaso:</strong>`;
          if (data[0].TRASPASO_LINK && data[0].TRASPASO_PAQUETERIA) {
            let rastreoLink = data[0].TRASPASO_LINK;
            if(!rastreoLink.startsWith('http')) {
                rastreoLink = 'https://' + rastreoLink;
            }
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <input type="checkbox" id="chk_doc_paqueteria_traspaso" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el enlace para habilitar la casilla">
               <a href="${rastreoLink}" target="_blank" onclick="$('#chk_doc_paqueteria_traspaso').prop('disabled', false).css('cursor', 'pointer'); return true;" class="btn btn-sm btn-primary text-white font-weight-bold py-1 px-2 my-0"><i class="mdi mdi-link mr-1"></i> ${data[0].TRASPASO_PAQUETERIA}</a>
            </div>`;
          } else if (data[0].TRASPASO_PAQUETERIA) {
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <input type="checkbox" id="chk_doc_paqueteria_traspaso" style="width: 18px; height: 18px; cursor: pointer; accent-color: #20c997;" title="Marcar paquetería confirmada">
               <span class="btn btn-sm text-dark font-weight-bold py-1 px-2 my-0" style="background:#e2e8f0; cursor:default; border:1px solid #cbd5e1;"><i class="mdi mdi-package-variant mr-1"></i> ${data[0].TRASPASO_PAQUETERIA}</span>
            </div>`;
          }
          if (data[0].TRASPASO_NUMGUIA) {
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <input type="checkbox" id="chk_doc_numguia_traspaso" style="width: 18px; height: 18px; cursor: pointer; accent-color: #20c997;" title="Marcar número de guía confirmado">
               <span class="btn btn-sm text-dark font-weight-bold py-1 px-2 my-0" style="background:#e2e8f0; cursor:default; border:1px solid #cbd5e1;"><i class="mdi mdi-barcode mr-1"></i> Guía: ${data[0].TRASPASO_NUMGUIA}</span>
            </div>`;
          }
          if (data[0].TRASPASO_EVIDENCIA) {
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <input type="checkbox" id="chk_doc_evidencia_traspaso" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el botón Evidencia para habilitar la casilla">
               <a href="../${data[0].TRASPASO_EVIDENCIA}" target="_blank" onclick="$('#chk_doc_evidencia_traspaso').prop('disabled', false).css('cursor', 'pointer'); return true;" class="btn btn-sm btn-info text-white font-weight-bold py-1 px-2 my-0"><i class="mdi mdi-image mr-1"></i> Evidencia</a>
            </div>`;
          }
          if (data[0].TRASPASO_GUIA) {
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <input type="checkbox" id="chk_doc_guia_traspaso" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el botón Guía para habilitar la casilla">
               <a href="../${data[0].TRASPASO_GUIA}" target="_blank" onclick="$('#chk_doc_guia_traspaso').prop('disabled', false).css('cursor', 'pointer'); return true;" class="btn btn-sm btn-success text-white font-weight-bold py-1 px-2 my-0"><i class="mdi mdi-truck-delivery mr-1"></i> Guía de Embarque</a>
            </div>`;
          }
          if (data[0].TRASPASO_EVIDENCIA1) {
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <input type="checkbox" id="chk_doc_evidencia1_traspaso" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el botón para habilitar la casilla">
               <a href="../${data[0].TRASPASO_EVIDENCIA1}" target="_blank" onclick="$('#chk_doc_evidencia1_traspaso').prop('disabled', false).css('cursor', 'pointer'); return true;" class="btn btn-sm btn-info text-white font-weight-bold py-1 px-2 my-0"><i class="mdi mdi-image mr-1"></i> Caja por fuera</a>
            </div>`;
          }
          if (data[0].TRASPASO_EVIDENCIA2) {
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <input type="checkbox" id="chk_doc_evidencia2_traspaso" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el botón para habilitar la casilla">
               <a href="../${data[0].TRASPASO_EVIDENCIA2}" target="_blank" onclick="$('#chk_doc_evidencia2_traspaso').prop('disabled', false).css('cursor', 'pointer'); return true;" class="btn btn-sm btn-info text-white font-weight-bold py-1 px-2 my-0"><i class="mdi mdi-image mr-1"></i> Caja abierta</a>
            </div>`;
          }
          if (data[0].TRASPASO_EVIDENCIA3) {
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <input type="checkbox" id="chk_doc_evidencia3_traspaso" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el botón para habilitar la casilla">
               <a href="../${data[0].TRASPASO_EVIDENCIA3}" target="_blank" onclick="$('#chk_doc_evidencia3_traspaso').prop('disabled', false).css('cursor', 'pointer'); return true;" class="btn btn-sm btn-info text-white font-weight-bold py-1 px-2 my-0"><i class="mdi mdi-image mr-1"></i> Artículos</a>
            </div>`;
          }
          if (data[0].TRASPASO_EVIDENCIA4) {
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <input type="checkbox" id="chk_doc_evidencia4_traspaso" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el botón para habilitar la casilla">
               <a href="../${data[0].TRASPASO_EVIDENCIA4}" target="_blank" onclick="$('#chk_doc_evidencia4_traspaso').prop('disabled', false).css('cursor', 'pointer'); return true;" class="btn btn-sm btn-success text-white font-weight-bold py-1 px-2 my-0"><i class="mdi mdi-truck-delivery mr-1"></i> Guía pegada</a>
            </div>`;
          }
          if (data[0].TRASPASO_LISTAEMPAQUE) {
            let listas = data[0].TRASPASO_LISTAEMPAQUE.split('|');
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px; flex-wrap:wrap;">
               <input type="checkbox" id="chk_doc_listaempaque_traspaso" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en un enlace para habilitar la casilla">`;
            listas.forEach((lista, idx) => {
                let suffix = listas.length > 1 ? ` ${idx+1}` : '';
                docsGenHtml += `<a href="../${lista}" target="_blank" onclick="$('#chk_doc_listaempaque_traspaso').prop('disabled', false).css('cursor', 'pointer'); return true;" class="btn btn-sm btn-primary text-white font-weight-bold py-1 px-2 my-0"><i class="mdi mdi-format-list-bulleted mr-1"></i> Lista de Empaque${suffix}</a>`;
            });
            docsGenHtml += `</div>`;
          }
          docsGenHtml += `</div>`;
        }
        $("#seccion-docs-generales-confirmar").html(docsGenHtml);

        let html = '';

        data.forEach(function(item, index) {
          html += `
            <tr>
              <td>
                <input type="checkbox" name="confirmados[]" value="${item.TRASPASODET_ID}" checked>
                <input type="hidden" name="detalles[${index}][id]" value="${item.TRASPASODET_ID}">
              </td>
              <td>${item.STOCK_FOLIO}</td>
              <td>${item.CLAVE_ARTICULO}</td>
              <td>${item.ARTICULO_NOMBRE}</td>
              <td>${item.ESDET_LOTE || ''}</td>
              <td>${item.ESDET_CADUCIDAD || ''}</td>
              <td>${item.ESDET_SERIE || ''}</td>
            </tr>`;
        });

        $("#tablaConfirmacionBody").html(html);
        $("#modalConfirmarArticulos").modal('show');
      },
      error: function() {
        Swal.fire({
            html: "No se pudieron obtener los artículos",
            icon: "warning",
            customClass: {
                confirmButton: 'btn btn-success' // usa clases de Bootstrap
            }
        });
      }
    });
  }

  function confirmarEntrada() {
    let errores = [];

    const chkEvidencia = $("#chk_doc_evidencia_traspaso");
    if (chkEvidencia.length > 0 && !chkEvidencia.is(":checked")) {
      errores.push("Es obligatorio abrir/ver la Evidencia para validar y activar su casilla.");
    }
    const chkGuia = $("#chk_doc_guia_traspaso");
    if (chkGuia.length > 0 && !chkGuia.is(":checked")) {
      errores.push("Es obligatorio abrir/ver la Guía de Embarque para validar y activar su casilla.");
    }
    const chkPaqueteria = $("#chk_doc_paqueteria_traspaso");
    if (chkPaqueteria.length > 0 && !chkPaqueteria.is(":checked")) {
      errores.push("Es obligatorio abrir/ver el Link de Rastreo para validar y activar su casilla.");
    }
    const chkNumGuia = $("#chk_doc_numguia_traspaso");
    if (chkNumGuia.length > 0 && !chkNumGuia.is(":checked")) {
      errores.push("Es obligatorio confirmar el Número de Guía marcando su casilla.");
    }
    const chkEvidencia1 = $("#chk_doc_evidencia1_traspaso");
    if (chkEvidencia1.length > 0 && !chkEvidencia1.is(":checked")) {
      errores.push("Es obligatorio abrir/ver la foto de Caja por fuera para validar y activar su casilla.");
    }
    const chkEvidencia2 = $("#chk_doc_evidencia2_traspaso");
    if (chkEvidencia2.length > 0 && !chkEvidencia2.is(":checked")) {
      errores.push("Es obligatorio abrir/ver la foto de Caja abierta para validar y activar su casilla.");
    }
    const chkEvidencia3 = $("#chk_doc_evidencia3_traspaso");
    if (chkEvidencia3.length > 0 && !chkEvidencia3.is(":checked")) {
      errores.push("Es obligatorio abrir/ver la foto de Artículos para validar y activar su casilla.");
    }
    const chkEvidencia4 = $("#chk_doc_evidencia4_traspaso");
    if (chkEvidencia4.length > 0 && !chkEvidencia4.is(":checked")) {
      errores.push("Es obligatorio abrir/ver la foto de Guía pegada para validar y activar su casilla.");
    }
    const chkListaEmpaque = $("#chk_doc_listaempaque_traspaso");
    if (chkListaEmpaque.length > 0 && !chkListaEmpaque.is(":checked")) {
      errores.push("Es obligatorio abrir/ver la Lista de Empaque para validar y activar su casilla.");
    }

    if (errores.length > 0) {
      Swal.fire({
        html: `<b>Corrige los siguientes errores antes de confirmar:</b><br><ul style="text-align:left">${errores.map(e => `<li>${e}</li>`).join('')}</ul>`,
        icon: "warning",
        customClass: {
          confirmButton: 'btn btn-success'
        }
      });
      return;
    }

    const seleccionados = [];
    $("input[name='confirmados[]']:checked").each(function() {
      seleccionados.push($(this).val());
    });

    if (seleccionados.length === 0) {
      Swal.fire({
          html: "Selecciona al menos un artículo para confirmar.",
          icon: "warning",
          customClass: {
              confirmButton: 'btn btn-success' // usa clases de Bootstrap
          }
      });
      return;
    }

    $.ajax({
      url: "../ajax/traspasos.autorizar.php",
      method: "POST",
      data: { 
        detalles: seleccionados,
        traspasoid : $("#traspasoid").val()
      },
      beforeSend: function() {
          $("#loading").show();
      },
      success: function(response) {
        if (response.trim() === ""){
            Swal.fire({
                html: "Confirmación registrada.",
                icon: "success",
                customClass: {
                    confirmButton: 'btn btn-success' // usa clases de Bootstrap
                }
            }).then(() => {
              $("#modalConfirmarArticulos").modal('hide');
              location.reload();
            });
        }else{
            Swal.fire({
                html: response,
                icon: "warning",
                customClass: {
                    confirmButton: 'btn btn-success' // usa clases de Bootstrap
                }
            });
        }
      },
      error: function() {
        Swal.fire({
            html: "Ocurrió un error al guardar la confirmación.",
            icon: "warning",
            customClass: {
                confirmButton: 'btn btn-success' // usa clases de Bootstrap
            }
        });
      },
      complete: function(data) {
          $("#loading").hide();
      }
    });
  }

  function abrirModalRechazo() {
    $('#modalConfirmarArticulos').modal('hide');
    $('#modalRechazarTraspaso').modal('show');

    $('#modalRechazarTraspaso').on('hidden.bs.modal', function () {
        if ($('#traspasoid').val() !== '') {
           $('#modalConfirmarArticulos').modal('show');
        }
    });

    let htmlDocs = '';
    const docsInfo = {
      'paqueteria': {id: 'chk_doc_paqueteria_traspaso', label: 'Paquetería/Rastreo', color: 'primary'},
      'numguia': {id: 'chk_doc_numguia_traspaso', label: 'Número de Guía', color: 'dark'},
      'evidencia1': {id: 'chk_doc_evidencia1_traspaso', label: 'Caja por fuera', color: 'info'},
      'evidencia2': {id: 'chk_doc_evidencia2_traspaso', label: 'Caja abierta', color: 'info'},
      'evidencia3': {id: 'chk_doc_evidencia3_traspaso', label: 'Artículos', color: 'info'},
      'evidencia4': {id: 'chk_doc_evidencia4_traspaso', label: 'Guía pegada', color: 'success'},
      'listaempaque': {id: 'chk_doc_listaempaque_traspaso', label: 'Lista de Empaque', color: 'primary'}
    };

    let hayDocs = false;
    for(const key in docsInfo) {
      if ($('#' + docsInfo[key].id).length > 0) {
        hayDocs = true;
        htmlDocs += `
          <div class="mb-3 p-3 border rounded bg-light">
            <div class="custom-control custom-checkbox mb-2">
              <input type="checkbox" class="custom-control-input chk-rechazo-doc" id="rechazo_${key}" value="${docsInfo[key].label}" onchange="toggleRechazoTextarea('${key}')">
              <label class="custom-control-label font-weight-bold text-${docsInfo[key].color}" for="rechazo_${key}">Rechazar ${docsInfo[key].label}</label>
            </div>
            <textarea class="form-control d-none txt-rechazo-doc" id="motivo_rechazo_${key}" rows="2" placeholder="Escribe el motivo del rechazo para la ${docsInfo[key].label}..."></textarea>
          </div>
        `;
      }
    }

    if (!hayDocs) {
      htmlDocs = '<div class="alert alert-warning">No se encontraron documentos asociados a este traspaso. Aún puedes rechazar indicando un motivo general.</div><textarea class="form-control txt-rechazo-doc" id="motivo_rechazo_general" rows="3" placeholder="Escribe el motivo del rechazo..."></textarea>';
    }

    $('#contenedor-docs-rechazo').html(htmlDocs);
  }

  function toggleRechazoTextarea(key) {
    if ($('#rechazo_' + key).is(':checked')) {
      $('#motivo_rechazo_' + key).removeClass('d-none').focus();
    } else {
      $('#motivo_rechazo_' + key).addClass('d-none').val('');
    }
  }

  function guardarRechazo() {
    let motivosRechazoArr = [];
    let isValid = true;

    if ($('#motivo_rechazo_general').length > 0) {
      let val = $('#motivo_rechazo_general').val().trim();
      if (val === '') {
        isValid = false;
        $('#motivo_rechazo_general').addClass('is-invalid');
      } else {
        $('#motivo_rechazo_general').removeClass('is-invalid');
        motivosRechazoArr.push("Motivo General: " + val);
      }
    } else {
      let seleccionoAlgo = false;
      $('.chk-rechazo-doc').each(function() {
        if ($(this).is(':checked')) {
          seleccionoAlgo = true;
          let label = $(this).val();
          let key = $(this).attr('id').replace('rechazo_', '');
          let motivo = $('#motivo_rechazo_' + key).val().trim();
          
          if (motivo === '') {
            isValid = false;
            $('#motivo_rechazo_' + key).addClass('is-invalid');
          } else {
            $('#motivo_rechazo_' + key).removeClass('is-invalid');
            motivosRechazoArr.push(`- ${label}: ${motivo}`);
          }
        }
      });

      if (!seleccionoAlgo) {
        Swal.fire('Atención', 'Debes seleccionar al menos un documento para rechazar e indicar el motivo.', 'warning');
        return;
      }
    }

    if (!isValid) {
      Swal.fire('Atención', 'Por favor, escribe el motivo detallado de cada documento seleccionado.', 'warning');
      return;
    }

    let motivoConsolidado = "Documentos Rechazados:\\n" + motivosRechazoArr.join("\\n");
    const traspasoid = $("#traspasoid").val();

    $('#traspasoid').val('');

    $.ajax({
      url: '../ajax/traspasos.update.status.php',
      type: 'POST',
      data: {
        id: traspasoid,
        status: 6,
        motivo_rechazo: motivoConsolidado
      },
      dataType: 'html',
      beforeSend: function() {
        $("#loading").show();
      },
      success: function(response) {
        $("#loading").hide();
        if (response.trim() === "") {
          $("#modalRechazarTraspaso").modal('hide');
          Swal.fire({
            html: "La solicitud fue rechazada con éxito.",
            icon: "success",
            customClass: { confirmButton: 'btn btn-success' }
          }).then(() => {
            location.reload();
          });
        } else {
          Swal.fire({
            html: response,
            icon: "error",
            customClass: { confirmButton: 'btn btn-danger' }
          });
        }
      },
      error: function() {
        $("#loading").hide();
        Swal.fire({ html: "Ocurrió un error al intentar rechazar la solicitud.", icon: "error" });
      }
    });
  }

  $(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const reqid = urlParams.get('reqid');
    const originId = urlParams.get('origin_id');
    const destId = urlParams.get('dest_id');
    const autoTraspasoid = urlParams.get('autoopen');
    
    if (reqid) {
      // Abrir el modal automáticamente
      const title = "Traspaso de Almacén";
      const url = "../includes/traspasos.nuevo.php?reqid=" + reqid + "&origin_id=" + (originId || '') + "&dest_id=" + (destId || '');
      
      const modal = $('#modalglobal');
      modal.find('.modal-title').text(title);
      modal.find('.modal-dialog').addClass('modal-xl');
      $('#loading').show();
      
      $.ajax({
        url: url,
        type: 'GET',
        success: function (response) {
          modal.find('.modal-body').html(response);
          modal.modal('show');
        },
        error: function () {
          modal.find('.modal-body').html('<p>Error al cargar la página.</p>');
        },
        complete: function () {
          $('#loading').hide();
        }
      });
    }

    // ======== AUTO-OPEN desde módulo Actividades ========
    if (autoTraspasoid) {
      setTimeout(function() {
        mostrarConfirmacionArticulos(autoTraspasoid);
      }, 400);
    }
  });

function agregarCampoListaEmpaque(isFirst) {
    let newId = "lista_empaque_" + Date.now() + Math.floor(Math.random() * 1000);
    let btnDelete = '';
    if (!isFirst) {
        btnDelete = `<button type="button" class="btn btn-sm btn-danger ms-2 ml-2" onclick="$('#div_${newId}').remove()"><i class="mdi mdi-delete"></i></button>`;
    }
    let html = `
    <div class="d-flex mb-2 mt-2 align-items-center" id="div_${newId}">
        <input type="file" class="form-control form-control-sm input-lista-empaque-dinamico" name="archivo_lista_empaque[]" accept=".pdf,application/pdf,image/*,.jpg,.jpeg,.png,.gif,.webp">
        ${btnDelete}
    </div>`;
    $("#lista_eminputs_container").append(html);
}
</script>