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
    <?php $entradasalida = new entradasalida();?>
    <div class="container-scroller"> 
      <?php include_once("../includes/header.php"); ?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once ("../includes/menu.sidebar.php")?>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-sm-12">
                <div class="home-tab">
                  <?php include_once("../includes/entradasalida.menu.php");?>
                </div>
                  <div class="tab-content tab-content-basic">
                    <!-- Inicia contenido principal -->
                    <div class="row">
                      <div class="col-12">
                        <div class="card">
                          <div class="card-header">
                            <h4>Garantía de Proveedor</h4>
                          </div>
                          <div class="card-body">
                            <?php
                            $res = $entradasalida->getgarantiaproveedor();
                            if ($res <> 0) {
                              ?>
                              <div style="overflow-x:auto; width: 100%;">
                                <table class="table table-striped">
                                  <thead>
                                    <tr>
                                      <th scope="col" width="140px"></th>
                                      <th scope="col" width="200px">Folio</th>
                                      <th scope="col" width="200px">Status</th>
                                      <th scope="col">Categoria</th>
                                      <th scope="col">Almacen</th>
                                      <th scope="col">Proveedor</th>
                                    </tr>
                                  </thead>
                                  <tbody>
                                    <?php foreach ($res as $row){?>
                                    <tr>
                                      <td width="140px" style="white-space: nowrap;">
                                        <div style="display: flex; align-items: center; gap: 5px;">
                                          <a data-toggle="modal" data-target="#modalglobal" 
                                            data-title="Información de Garantía de Proveedor" 
                                            data-url="../includes/caducidadproveedor.info.php?cpid=<?=base64_encode($row['CADUCIDADPROV_ID'])?>" 
                                            aria-selected="false">
                                            <img src="../img/info.png" style="width:20px; height:auto; cursor:pointer;" title="Información">
                                          </a>
                                          <a href="../gdocs/caducidadproveedor.formato.php?cpid=<?=base64_encode($row['CADUCIDADPROV_ID'])?>" target="_blank">
                                            <img src="../img/pdf.png" style="width:20px; height:auto; cursor:pointer;" title="Garantía de Proveedor">
                                          </a>
                                          <?php
                                            if ($row['STATUS_ID'] == 9){
                                              ?>
                                              <a data-toggle="modal" data-target="#modalglobal" 
                                                data-title="Reposición de Artículos" 
                                                data-url="../includes/caducidadproveedor.reposicion.php?cpid=<?=base64_encode($row['CADUCIDADPROV_ID'])?>" 
                                                aria-selected="false">
                                                <img src="../img/replace.png" style="width:20px; height:auto; cursor:pointer;" title="Reposición">
                                              </a>
                                              <?php
                                            }
                                          ?>
                                          <a data-toggle="modal" data-target="#modalglobal" 
                                            data-title="Información de Garantía de Proveedor" 
                                            data-url="../includes/caducidadproveedor.documentossoporte.php?cpid=<?=base64_encode($row['CADUCIDADPROV_ID'])?>" 
                                            aria-selected="false">
                                            <img src="../img/upload.jpg" style="width:20px; height:auto; cursor:pointer;" title="Documentos de Soporte">
                                          </a>
                                        </div>
                                      </td>
                                      <td width="200px">
                                        <?=$row['CADUCIDADPROV_FOLIO']?>
                                      </td>
                                      <td width="200px">
                                        <span style="font-size:10px; color:#<?=$row['STATUS_COLOR']?>"><?=$row['STATUS_NOMBRE']?></span>
                                      </td>
                                      <td><?=$row['SUCURSAL_NOMBRE']?></td>
                                      <td><?=$row['ALMACEN_NOMBRE']?></td>
                                      <td><?=$row['NOMBREPROVEEDOR']?></td>
                                    </tr>
                                    <?php } ?>
                                  </tbody>
                                </table>
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
        <h5 class="modal-title" id="modalLabel">Confirmar artículos de la Garantía de Proveedor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="form-confirmar-articulos">
          <input type="hidden" id="cpid" name="cpid">
          <input type="hidden" id="almacenid" name="almacenid">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th></th>
                <th>Folio</th>
                <th>Clave</th>
                <th>Artículo</th>
                <th>Lote</th>
                <th>Caducidad</th>
              </tr>
            </thead>
            <tbody id="tablaConfirmacionBody">
              <!-- Aquí se insertarán las filas dinámicamente -->
            </tbody>
          </table>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" onclick="confirmarEntrada()">Confirmar Seleccionados</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<script>

  $('#modalglobal').on('show.bs.modal', function (event) {
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

  /*
  function changestatus(id,folio,status,almacenid){
    status = parseInt(status);
    var accion = "";
    var accion1 = "";
    var done = "";
    switch (status) {
      case 9: 
        accion = "cambiar el status a en Revisión a Enviado";
        accion1 = "cambiar";
        done = "status modificado a enviado";
        break;
      case 8: 
        accion = "cambiar el status a en Revisión a en Revisión";
        accion1 = "cambiar";
        done = "status modificado a en Revisión";
        break;
      case 3: 
        accion = "Finalizar el folio de garantía de Proveedor";
        accion1 = "finalizar";
        done = "Garantía de Proveedor Finalizada correctamente";
        break;
      case 5: 
        accion = "cambiar el status a Cancelado";
        accion1 = "cambiar";
        done = "status modificado a Cancelado";
        break;
      default:
    }
    Swal.fire({
          text: '¿Seguro que deseas '+accion+' el folio de garantía de proveedor '+folio+'?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Sí, '+accion1,
          cancelButtonText: 'Cancelar',
          customClass: {
              confirmButton: 'btn btn-success',
              cancelButton: 'btn btn-secondary'
          },
          buttonsStyling: false // necesario si usas clases Bootstrap
      }).then((result) => {
          if (result.isConfirmed) {
            if (status == 3){
              mostrarConfirmacionArticulos(id,almacenid);
            }else{
              $.ajax({
                  url: '../ajax/caducidadproveedor.update.status.php',
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
                              html: "Solicitud de " + etiqueta + " " + done + " con éxito",
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
            }
          }else{
            return false;
          }
      })
  }
  */

  function mostrarConfirmacionArticulos(cpid,almacenid) {
    $.ajax({
      url: '../ajax/caducidadproveedor.get.detalle.solicitud.php', // Tu archivo PHP para el query
      method: 'POST',
      data: { cpid: cpid },
      dataType: 'json',
      success: function(data) {
        $("#cpid").val(cpid);
        $("#almacenid").val(almacenid);
        let html = '';

        data.forEach(function(item, index) {
          html += `
            <tr>
              <td>
                <input type="checkbox" name="confirmados[]" value="${item.CADUCIDADPROVDET_ID}" checked>
                <input type="hidden" name="detalles[${index}][id]" value="${item.CADUCIDADPROVDET_ID}">
              </td>
              <td>${item.STOCK_FOLIO}</td>
              <td>${item.CLAVE_ARTICULO}</td>
              <td>${item.ARTICULO_NOMBRE}</td>
              <td>${item.STOCK_LOTE || ''}</td>
              <td>${item.STOCK_CADUCIDAD || ''}</td>
            </tr>`;
        });

        $("#tablaConfirmacionBody").html(html);
        $("#modalConfirmarArticulos").modal('show');
      },
      error: function() {
        Swal.fire({
            html: "No se pudieron obtener los artículos.",
            icon: "warning",
            customClass: {
                confirmButton: 'btn btn-success' // usa clases de Bootstrap
            }
        });
      }
    });
  }

  function confirmarEntrada() {
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
      url: "../ajax/caducidadproveedor.finalizar.php",
      method: "POST",
      data: { 
        detalles: seleccionados,
        cpid : $("#cpid").val(),
        almacenid : $("#almacenid").val()
      },
      success: function(response) {
        if (response.trim() === ""){
            Swal.fire({
                html: "Confirmación registrada",
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
      }
    });
  }

</script>