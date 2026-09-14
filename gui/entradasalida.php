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
  <?php $entradasalida = new entradasalida(); ?>
  <div class="container-scroller">
    <?php include_once("../includes/header.php"); ?>
    <div class="container-fluid page-body-wrapper">
      <?php include_once("../includes/menu.sidebar.php") ?>
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <?php include_once("../includes/entradasalida.menu.php"); ?>
              </div>
              <div class="tab-content tab-content-basic">
                <!-- Inicia contenido principal -->
                <ul class="nav nav-tabs border-bottom-0 mb-4" id="esTabs" role="tablist">
                  <li class="nav-item" role="presentation">
                    <a class="nav-link active font-weight-bold" style="color: #334155; border-radius: 8px 8px 0 0;" id="entradas-tab" data-toggle="tab" href="#entradas" role="tab" aria-controls="entradas" aria-selected="true">
                      Entradas
                    </a>
                  </li>
                  <li class="nav-item" role="presentation">
                    <a class="nav-link font-weight-bold" style="color: #334155; border-radius: 8px 8px 0 0;" id="salidas-tab" data-toggle="tab" href="#salidas" role="tab" aria-controls="salidas" aria-selected="false">
                      Salidas
                    </a>
                  </li>
                </ul>

                <?php
                $res = $entradasalida->getentradasalida();
                $datosEntradas = [];
                $datosSalidas = [];
                if ($res <> 0) {
                    foreach ($res as $row) {
                        if ($row['ES_TIPO'] == 'E' || $row['ES_TIPO'] == 'R') {
                            $datosEntradas[] = $row;
                        } else {
                            $datosSalidas[] = $row;
                        }
                    }
                }
                ?>

                <div class="tab-content tab-content-basic border-0 p-0" id="esTabContent">
                   <div class="tab-pane fade show active" id="entradas" role="tabpanel" aria-labelledby="entradas-tab">
                     <div class="card">
                       <div class="card-header bg-white py-3 d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between">
                         <h4 class="card-title mb-0 font-weight-bold text-dark">Historial de Entradas</h4>
                         <div class="d-flex align-items-center mt-3 mt-sm-0">
                           <button class="btn btn-primary btn-sm px-4 py-2 font-weight-bold d-flex align-items-center" data-toggle="modal" data-target="#modalglobal" data-title="Nueva Entrada" data-url="../includes/entradasalida.entradas.php">
                              <i class="mdi mdi-plus-circle-outline mr-2" style="font-size: 1.1rem;"></i> Nueva Entrada
                           </button>
                           <a class="btn btn-info btn-sm px-4 py-2 font-weight-bold d-flex align-items-center ml-2" href="../includes/entradasalida.reimpresionetiquetas.php" target="_blank">
                              <i class="mdi mdi-printer mr-2" style="font-size: 1.1rem;"></i> Reimprimir Etiqueta
                           </a>
                         </div>
                       </div>
                       <div class="card-body">
                          <?php $datosTablaES = $datosEntradas; include("../includes/entradasalida.tabla.php"); ?>
                       </div>
                     </div>
                   </div>
                   
                   <div class="tab-pane fade" id="salidas" role="tabpanel" aria-labelledby="salidas-tab">
                     <div class="card">
                       <div class="card-header bg-white py-3 d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between">
                         <h4 class="card-title mb-0 font-weight-bold text-dark">Historial de Salidas</h4>
                         <div class="d-flex align-items-center mt-3 mt-sm-0">
                           <button class="btn btn-primary btn-sm px-4 py-2 font-weight-bold d-flex align-items-center" data-toggle="modal" data-target="#modalglobal" data-title="Nueva Salida" data-url="../includes/entradasalida.salidas.php">
                              <i class="mdi mdi-minus-circle-outline mr-2" style="font-size: 1.1rem;"></i> Nueva Salida
                           </button>
                         </div>
                       </div>
                       <div class="card-body">
                          <?php $datosTablaES = $datosSalidas; include("../includes/entradasalida.tabla.php"); ?>
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
<!-- Modal -->
<div class="modal fade" id="modalConfirmarArticulos" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabel">Confirmar artículos de la solicitud</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="form-confirmar-articulos">
          <input type="hidden" id="esid" name="esid">
          <input type="hidden" id="tipo" name="tipo">
          <input type="hidden" id="almacenid" name="almacenid">
          <div id="seccion-docs-generales-confirmar" class="mb-3"></div>
          <div class="table-responsive d-none d-md-block">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th></th>
                  <th>Folio</th>
                  <th>Clave</th>
                  <th>Artículo</th>
                  <th class="text-center">Cantidad</th>
                  <th>Lote</th>
                  <th>Caducidad</th>
                  <th>Serie</th>
                  <th class="text-center">Carta Canje</th>
                  <th class="text-center">Delivery</th>
                </tr>
              </thead>
              <tbody id="tablaConfirmacionBody">
                <!-- Aquí se insertarán las filas dinámicamente -->
              </tbody>
            </table>
          </div>
          <!-- Mobile Cards -->
          <div id="tablaConfirmacionCards" class="d-block d-md-none mt-2">
            <!-- Aquí se insertarán las tarjetas dinámicamente -->
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" onclick="confirmarEntrada()">Confirmar Seleccionados</button>
        <button type="button" class="btn btn-danger" onclick="abrirModalRechazo()">Rechazar Solicitud</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Rechazar -->
<div class="modal fade" id="modalRechazarEntrada" aria-labelledby="modalRechazarLabel" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="modalRechazarLabel"><i class="mdi mdi-alert-circle"></i> Rechazar Solicitud</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>Por favor seleccione los documentos generales que son motivo de rechazo y justifique detalladamente el motivo de cada uno:</p>
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

  function changestatus(id, status, folio, tipo) {
    var etiqueta = "";
    switch (tipo) {
      case 'E':
        etiqueta = "Entrada";
        break;
      case 'S':
        etiqueta = "Salida";
        break;
      default:
    }
    var accion = "";
    var accion1 = "";
    var done = "";
    switch (status) {
      case 2:
        accion = "autorizar";
        accion1 = "autorizar";
        done = "autorizada";
        break;
      case 5:
        accion = "cancelar";
        accion1 = "cancelar";
        done = "cancelada";
        break;
      case 8:
        accion = "cambiar el status a en Revisión a";
        accion1 = "cambiar";
        done = "actualizada";
        break;
      case 9:
        accion = "enviar";
        accion1 = "enviar";
        done = "enviada";
        break;
      default:
    }

    let swalConfig = {
      text: '¿Seguro que deseas ' + accion + ' la Solicitud de ' + etiqueta + '?' + (status === 5 ? '\nPor favor, ingresa el motivo del rechazo/cancelación:' : ''),
      icon: (status === 5 ? 'warning' : 'question'),
      showCancelButton: true,
      confirmButtonText: 'Sí, ' + accion1,
      cancelButtonText: 'Cancelar',
      customClass: {
        confirmButton: (status === 5 ? 'btn btn-danger' : 'btn btn-success'),
        cancelButton: 'btn btn-secondary'
      },
      buttonsStyling: false
    };
    if (status === 5) {
      swalConfig.input = 'textarea';
      swalConfig.inputPlaceholder = 'Escribe detalladamente el motivo del rechazo aquí...';
      swalConfig.inputAttributes = {
        'style': 'width: 90% !important; height: 130px !important; font-size: 15px !important; color: #000000 !important; background-color: #ffffff !important; border: 2px solid #6c757d !important; border-radius: 8px !important; padding: 12px !important; margin: 15px auto !important; display: block !important; box-shadow: 0 2px 5px rgba(0,0,0,0.1) !important; line-height: 1.5 !important; opacity: 1 !important;'
      };
      swalConfig.didOpen = () => {
        $('.modal').removeAttr('tabindex');
        const textarea = Swal.getInput();
        if (textarea) {
          textarea.focus();
        }
      };
      swalConfig.willClose = () => {
        $('#modalConfirmarArticulos').attr('tabindex', '-1');
      };
      swalConfig.inputValidator = (value) => {
        if (!value || !value.trim()) {
          return '¡Debes ingresar un motivo de cancelación/rechazo!';
        }
      };
    }
    Swal.fire(swalConfig).then((result) => {
      if (result.isConfirmed) {
        let ajaxData = {
          id: id,
          status: status
        };
        if (status === 5) {
          ajaxData.motivo_rechazo = result.value.trim();
        }
        $.ajax({
          url: '../ajax/entradasalida.update.status.php',
          type: 'POST',
          data: ajaxData,
          dataType: 'html',
          beforeSend: function() {
            $("#loading").show();
          },
          success: function(response) {
            if (response.trim() === "") {
              Swal.fire({
                html: "Solicitud de " + etiqueta + " " + done + " con éxito",
                icon: "success",
                customClass: {
                  confirmButton: 'btn btn-success' // usa clases de Bootstrap
                }
              }).then(() => {
                location.reload();
              });
            } else {
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
      } else {
        return false;
      }
    })
  }

  function mostrarConfirmacionArticulos(esid, tipo, almacenid) {
    $.ajax({
      url: '../ajax/entradasalida.get.detalle.solicitud.php',
      method: 'POST',
      data: {
        esid: esid
      },
      dataType: 'json',
      success: function(data) {
        $("#esid").val(esid);
        $("#tipo").val(tipo);
        $("#almacenid").val(almacenid);

        // Verificar si hay algún ESDET_ID válido
        const tieneDetalles = data.some(item => item.ESDET_ID && item.ESDET_ID !== "");

        if (!tieneDetalles) {
          // No hay detalles válidos
          Swal.fire({
            html: "Esta solicitud no tiene artículos para confirmar.",
            icon: "info",
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });

          // Deshabilita botón de confirmar
          $("#btnConfirmarSeleccionados").prop("disabled", true);

          // Limpiar tabla por si había algo
          $("#tablaConfirmacionBody").html("");

          return;
        }

        // Si hay detalles válidos, habilita el botón (por si estaba deshabilitado antes)
        $("#btnConfirmarSeleccionados").prop("disabled", false);

        let ocFolioStr = '';
        if (data.length > 0) {
          if (data[0].OC_FOLIO_REAL && data[0].OC_FOLIO_REAL !== '') {
            ocFolioStr = 'OC-' + data[0].OC_FOLIO_REAL;
          } else if (data[0].ES_MOTIVO && data[0].ES_MOTIVO.indexOf('Recepcion de OC Folio:') !== -1) {
            let numOc = data[0].ES_MOTIVO.replace('Recepcion de OC Folio:', '').trim();
            ocFolioStr = 'OC-' + numOc;
          }
        }

        let modalTitle = "Confirmar artículos de la solicitud";
        if (data.length > 0 && data[0].ES_FOLIO) {
          modalTitle += ` <b>${data[0].ES_FOLIO}</b>`;
        }
        if (ocFolioStr) {
          modalTitle += ` <span class="badge badge-info ml-2" style="font-size: 1rem; background-color: #17a2b8; color: #fff;">${ocFolioStr}</span>`;
        }
        $("#modalLabel").html(modalTitle);

        let docsGenHtml = '';
        if (ocFolioStr) {
          docsGenHtml += `<div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center justify-content-between shadow-sm" style="font-size: 1.05rem; border-left: 5px solid #17a2b8; background-color: #e8f4f8; color: #0c5460;">
            <span><strong>Solicitud:</strong> ${data[0].ES_FOLIO || ''}</span>
            <span class="badge badge-info px-3 py-2" style="font-size: 1rem; background-color: #17a2b8; color: #fff;"><i class="mdi mdi-cart mr-1"></i> Folio OC: ${ocFolioStr}</span>
          </div>`;
        }
        if (data.length > 0 && (data[0].RECEPCION_FACTURA || data[0].RECEPCION_EVIDENCIA || data[0].RECEPCION_LISTA_EMBARQUE || data[0].RECEPCION_CARTA_CANJE || data[0].RECEPCION_DELIVERY_NUM)) {
          docsGenHtml += `<div class="p-3 rounded border bg-light d-flex flex-wrap align-items-center" style="gap: 15px;">
            <strong class="text-dark">Documentos Generales de la Recepción:</strong>`;
          if (data[0].RECEPCION_FACTURA) {
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <input type="checkbox" id="chk_doc_factura_general" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el botón Factura para habilitar la casilla">
               <a href="../${data[0].RECEPCION_FACTURA}" target="_blank" onclick="$('#chk_doc_factura_general').prop('disabled', false).css('cursor', 'pointer'); return true;" class="btn btn-sm btn-danger font-weight-bold py-1 px-2 my-0"><i class="mdi mdi-file-document mr-1"></i> Factura</a>
            </div>`;
          }
          if (data[0].RECEPCION_EVIDENCIA) {
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <input type="checkbox" id="chk_doc_evidencia_general" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el botón Evidencia para habilitar la casilla">
               <a href="../${data[0].RECEPCION_EVIDENCIA}" target="_blank" onclick="$('#chk_doc_evidencia_general').prop('disabled', false).css('cursor', 'pointer'); return true;" class="btn btn-sm btn-info text-white font-weight-bold py-1 px-2 my-0"><i class="mdi mdi-image mr-1"></i> Evidencia</a>
            </div>`;
          }
          if (data[0].RECEPCION_LISTA_EMBARQUE) {
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <input type="checkbox" id="chk_doc_lista_embarque_general" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el botón Lista de Embarque para habilitar la casilla">
               <a href="../${data[0].RECEPCION_LISTA_EMBARQUE}" target="_blank" onclick="$('#chk_doc_lista_embarque_general').prop('disabled', false).css('cursor', 'pointer'); return true;" class="btn btn-sm btn-success text-white font-weight-bold py-1 px-2 my-0"><i class="mdi mdi-file-document mr-1"></i> Lista de Embarque</a>
            </div>`;
          }
          if (data[0].RECEPCION_CARTA_CANJE) {
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <input type="checkbox" id="chk_doc_carta_canje_general" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el botón Carta Canje para habilitar la casilla">
               <a href="../${data[0].RECEPCION_CARTA_CANJE}" target="_blank" onclick="$('#chk_doc_carta_canje_general').prop('disabled', false).css('cursor', 'pointer'); return true;" class="btn btn-sm btn-primary font-weight-bold py-1 px-2 my-0"><i class="mdi mdi-file-document mr-1"></i> Carta Canje</a>
            </div>`;
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <input type="checkbox" id="chk_doc_delivery_general" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el botón Delivery para habilitar la casilla">
               <a href="../${data[0].RECEPCION_CARTA_CANJE}" target="_blank" onclick="$('#chk_doc_delivery_general').prop('disabled', false).css('cursor', 'pointer'); return true;" class="btn btn-sm btn-dark font-weight-bold py-1 px-2 my-0"><i class="mdi mdi-file-document mr-1"></i> Delivery</a>
            </div>`;
          }
          if (data[0].RECEPCION_DELIVERY_NUM) {
            docsGenHtml += `<div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
               <span class="badge badge-dark text-white py-1 px-2" style="font-size: 0.85rem;"><i class="mdi mdi-truck-delivery mr-1"></i> Num Delivery: ${data[0].RECEPCION_DELIVERY_NUM}</span>
            </div>`;
          }
          docsGenHtml += `</div>`;
        }
        $("#seccion-docs-generales-confirmar").html(docsGenHtml);

        let html = '';
        let htmlMobile = '';

        // Agrupar items
        let grupos = {};
        data.forEach(function(item, index) {
            let key = item.CLAVE_ARTICULO + '|' + (item.ESDET_LOTE || '') + '|' + (item.ESDET_CADUCIDAD || '');
            if (!grupos[key]) {
                grupos[key] = {
                    key: key,
                    CLAVE_ARTICULO: item.CLAVE_ARTICULO,
                    ARTICULO_NOMBRE: item.ARTICULO_NOMBRE,
                    ESDET_LOTE: item.ESDET_LOTE,
                    ESDET_CADUCIDAD: item.ESDET_CADUCIDAD,
                    CARTACANJE_RUTA_IMG: item.CARTACANJE_RUTA_IMG,
                    CARTACANJE_NUM_DELIVERY: item.CARTACANJE_NUM_DELIVERY,
                    items: []
                };
            }
            grupos[key].items.push({ item: item, originalIndex: index });
        });

        let groupIndex = 0;
        Object.values(grupos).forEach(function(grupo) {
          let loteWarning = '';
          let caducidadWarning = '';
          let rowClass = '';

          if (!grupo.ESDET_CADUCIDAD) {
            caducidadWarning = 'text-danger fw-bold';
            rowClass = 'table-warning';
          }

          let hiddenInputs = '';
          let seriesArray = [];
          let foliosArray = [];
          grupo.items.forEach(function(gItem) {
              let idx = gItem.originalIndex;
              let item = gItem.item;
              if(item.ESDET_SERIE) seriesArray.push(item.ESDET_SERIE);
              if(item.STOCK_FOLIO) foliosArray.push(item.STOCK_FOLIO);
              
              hiddenInputs += `
                <input type="checkbox" name="confirmados[]" data-idx="${idx}" value="${item.ESDET_ID}" class="chk-hidden-group-${groupIndex}" checked style="display:none;">
                <input type="hidden" name="detalles[${idx}][id]" value="${item.ESDET_ID}">
                <input type="hidden" name="seguimiento[${idx}]" value="${item.SEGUIMIENTO}">
                <input type="hidden" name="nombre[${idx}]" value="${item.ARTICULO_NOMBRE}">
                <input type="hidden" name="lote[${idx}]" value="${item.ESDET_LOTE || ''}">
                <input type="hidden" name="caducidad[${idx}]" value="${item.ESDET_CADUCIDAD || ''}">
                <input type="hidden" name="serie[${idx}]" value="${item.ESDET_SERIE || ''}">
              `;
          });
          
          let displaySerie = seriesArray.length > 1 ? "Varios" : (seriesArray[0] || '<em>Sin Serie</em>');
          let displayFolio = foliosArray.length > 1 ? "Varios" : (foliosArray[0] || '');

          let checkboxHtml = `
            <input type="checkbox" onchange="$('.chk-hidden-group-${groupIndex}').prop('checked', this.checked)" checked title="Confirmar grupo" style="width:18px; height:18px;">
            ${hiddenInputs}
          `;

          let cartaCanjeHtml = '';
          if (grupo.CARTACANJE_RUTA_IMG) {
            cartaCanjeHtml = `<a href="../${grupo.CARTACANJE_RUTA_IMG}" target="_blank" class="btn btn-sm btn-primary text-white font-weight-bold py-1 px-2 rounded" title="Ver Carta Canje"><i class="mdi mdi-file-document mr-1"></i> Ver</a>`;
          } else {
            cartaCanjeHtml = `<span class="badge badge-light border text-muted px-2 py-1" style="font-size: 0.75rem;">Sin Documento</span>`;
          }

          let deliveryHtml = '';
          if (grupo.CARTACANJE_NUM_DELIVERY) {
            let isFileDel = String(grupo.CARTACANJE_NUM_DELIVERY).indexOf('uploads/') !== -1;
            if (isFileDel) {
              deliveryHtml = `<a href="../${grupo.CARTACANJE_NUM_DELIVERY}" target="_blank" class="btn btn-sm btn-dark text-white font-weight-bold py-1 px-2 rounded" title="Ver Documento Delivery"><i class="mdi mdi-file-document mr-1"></i> Delivery</a>`;
            } else {
              deliveryHtml = `<span class="badge badge-dark text-white py-1 px-2" style="font-size: 0.75rem;">Delivery: ${grupo.CARTACANJE_NUM_DELIVERY}</span>`;
            }
          } else {
            deliveryHtml = `<span class="badge badge-light border text-muted px-2 py-1" style="font-size: 0.75rem;">Sin Documento</span>`;
          }

          html += `
            <tr class="${rowClass}">
              <td class="text-center align-middle">${checkboxHtml}</td>
              <td class="align-middle">${displayFolio}</td>
              <td class="align-middle">${grupo.CLAVE_ARTICULO}</td>
              <td class="align-middle">${grupo.ARTICULO_NOMBRE}</td>
              <td class="text-center align-middle font-weight-bold">${grupo.items.length}</td>
              <td class="align-middle ${loteWarning}">${grupo.ESDET_LOTE || '<em>Sin Lote</em>'}</td>
              <td class="align-middle ${caducidadWarning}">${grupo.ESDET_CADUCIDAD || '<em>Sin Caducidad</em>'}</td>
              <td class="align-middle">${displaySerie}</td>
              <td class="text-center align-middle">${cartaCanjeHtml}</td>
              <td class="text-center align-middle">${deliveryHtml}</td>
            </tr>`;

          let cardWarning = (!grupo.ESDET_CADUCIDAD) ? 'border border-danger' : '';
          htmlMobile += `
            <div class="mobile-card ${cardWarning}">
              <div class="mobile-card-header">
                <span class="mobile-card-title">${grupo.CLAVE_ARTICULO}</span>
                <span class="mobile-card-badge">${displayFolio}</span>
              </div>
              <div class="mobile-card-body">
                <div class="mobile-card-row">
                  <span class="mobile-card-label" style="font-weight:bold; color:#000;">Confirmar Grupo</span>
                  <span class="mobile-card-value">${checkboxHtml}</span>
                </div>
                <div class="mobile-card-row">
                  <span class="mobile-card-label">Artículo</span>
                  <span class="mobile-card-value">${grupo.ARTICULO_NOMBRE}</span>
                </div>
                <div class="mobile-card-row bg-light font-weight-bold">
                  <span class="mobile-card-label">Cantidad</span>
                  <span class="mobile-card-value">${grupo.items.length}</span>
                </div>
                <div class="mobile-card-row">
                  <span class="mobile-card-label">Lote</span>
                  <span class="mobile-card-value ${loteWarning}">${grupo.ESDET_LOTE || '<em>Sin Lote</em>'}</span>
                </div>
                <div class="mobile-card-row">
                  <span class="mobile-card-label">Caducidad</span>
                  <span class="mobile-card-value ${caducidadWarning}">${grupo.ESDET_CADUCIDAD || '<em>Sin Caducidad</em>'}</span>
                </div>
                <div class="mobile-card-row">
                  <span class="mobile-card-label">Serie</span>
                  <span class="mobile-card-value">${displaySerie}</span>
                </div>
                <div class="mobile-card-row">
                  <span class="mobile-card-label">Carta Canje</span>
                  <span class="mobile-card-value">${cartaCanjeHtml}</span>
                </div>
                <div class="mobile-card-row">
                  <span class="mobile-card-label">Delivery</span>
                  <span class="mobile-card-value">${deliveryHtml}</span>
                </div>
              </div>
            </div>`;
            
          groupIndex++;
        });

        $("#tablaConfirmacionBody").html(html);
        $("#tablaConfirmacionCards").html(htmlMobile);
        $("#modalConfirmarArticulos").modal('show');
      },
      error: function() {
        Swal.fire({
          html: "No se pudieron obtener los artículos.",
          icon: "warning",
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      }
    });
  }

  function confirmarEntrada() {
    const errores = [];
    const seleccionados = [];

    const chkFacturaGen = $("#chk_doc_factura_general");
    if (chkFacturaGen.length > 0 && !chkFacturaGen.is(":checked")) {
      errores.push("Es obligatorio abrir/ver el documento de la Factura general para validar y activar su casilla.");
    }
    const chkEvidenciaGen = $("#chk_doc_evidencia_general");
    if (chkEvidenciaGen.length > 0 && !chkEvidenciaGen.is(":checked")) {
      errores.push("Es obligatorio abrir/ver la Evidencia general para validar y activar su casilla.");
    }
    const chkListaGen = $("#chk_doc_lista_embarque_general");
    if (chkListaGen.length > 0 && !chkListaGen.is(":checked")) {
      errores.push("Es obligatorio abrir/ver la Lista de Embarque general para validar y activar su casilla.");
    }

    $("input[name='confirmados[]']:checked").each(function() {
      const row = $(this).closest('tr');
      const id = $(this).val();
      const index = $(this).data('idx');

      const seguimiento = $(`input[name='seguimiento[${index}]']`).val();
      const nombre = $(`input[name='nombre[${index}]']`).val();
      const lote = $(`input[name='lote[${index}]']`).val();
      const caducidad = $(`input[name='caducidad[${index}]']`).val();
      const serie = $(`input[name='serie[${index}]']`).val();

      let tieneError = false;

      if (!caducidad) {
        tieneError = true;
        errores.push(`"${nombre}" requiere Caducidad`);
        row.addClass('table-danger');
      }


      const chkDelivery = $(`input[name='chk_doc_delivery_${index}']`);
      if (chkDelivery.length > 0 && chkDelivery.filter(':checked').length === 0) {
        tieneError = true;
        errores.push(`"${nombre}": es obligatorio abrir/ver el documento de Delivery para validar y activar su casilla`);
        row.addClass('table-danger');
      }

      if (!tieneError) {
        row.removeClass('table-danger');
        seleccionados.push(id);
      }
    });

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

    if (seleccionados.length === 0) {
      Swal.fire({
        html: "Selecciona al menos un artículo para confirmar.",
        icon: "warning",
        customClass: {
          confirmButton: 'btn btn-success'
        }
      });
      return;
    }

    $.ajax({
      url: "../ajax/entradasalida.autorizar.php",
      method: "POST",
      data: {
        detalles: seleccionados,
        esid: $("#esid").val(),
        tipo: $("#tipo").val(),
        almacenid: $("#almacenid").val()
      },
      beforeSend: function() {
        $("#loading").show();
      },
      success: function(response) {
        if (response.trim() === "") {
          Swal.fire({
            html: "Confirmación registrada",
            icon: "success",
            customClass: {
              confirmButton: 'btn btn-success'
            }
          }).then(() => {
            $("#modalConfirmarArticulos").modal('hide');
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
      complete: function() {
        $("#loading").hide();
      },
      error: function() {
        Swal.fire({
          html: "Ocurrió un error al guardar la confirmación.",
          icon: "warning",
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
      }
    });
  }

  function abrirModalRechazo() {
    // Esconder momentáneamente el modal principal y mostrar el de rechazo
    $('#modalConfirmarArticulos').modal('hide');
    $('#modalRechazarEntrada').modal('show');

    // Al cerrar el modal de rechazo, si no se guardó, volver a mostrar el principal
    $('#modalRechazarEntrada').on('hidden.bs.modal', function () {
        // Verificar si el modal principal sigue existiendo en el DOM y necesita mostrarse
        if ($('#esid').val() !== '') {
           $('#modalConfirmarArticulos').modal('show');
        }
    });

    // Construir la lista de documentos disponibles en esta entrada
    let htmlDocs = '';
    const docsInfo = {
      'factura': {id: 'chk_doc_factura_general', label: 'Factura', color: 'danger'},
      'evidencia': {id: 'chk_doc_evidencia_general', label: 'Evidencia', color: 'info'},
      'lista_embarque': {id: 'chk_doc_lista_embarque_general', label: 'Lista de Embarque', color: 'success'},
      'carta_canje': {id: 'chk_doc_carta_canje_general', label: 'Carta Canje', color: 'primary'},
      'delivery': {id: 'chk_doc_delivery_general', label: 'Delivery', color: 'dark'}
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
      htmlDocs = '<div class="alert alert-warning">No se encontraron documentos generales asociados a esta entrada. Aún puedes rechazar indicando un motivo general.</div><textarea class="form-control txt-rechazo-doc" id="motivo_rechazo_general" rows="3" placeholder="Escribe el motivo del rechazo..."></textarea>';
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

    // Recopilar motivos
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

    let motivoConsolidado = "Documentos Rechazados:\n" + motivosRechazoArr.join("\n");
    const esid = $("#esid").val();

    // Resetear esid para que no vuelva a abrir el modal principal al ocultar el de rechazo en éxito
    $('#esid').val('');

    $.ajax({
      url: '../ajax/entradasalida.update.status.php',
      type: 'POST',
      data: {
        id: esid,
        status: 6, // ESTATUS RECHAZADO
        motivo_rechazo: motivoConsolidado
      },
      dataType: 'html',
      beforeSend: function() {
        $("#loading").show();
      },
      success: function(response) {
        $("#loading").hide();
        if (response.trim() === "") {
          $("#modalRechazarEntrada").modal('hide');
          Swal.fire({
            html: "La solicitud fue rechazada con éxito. Se ha notificado al creador.",
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
  // ======== AUTO-OPEN desde módulo Actividades ========
  $(document).ready(function() {
    const params = new URLSearchParams(window.location.search);
    const autoEsid     = params.get('autoopen');
    const autoTipo     = params.get('tipo');
    const autoAlmacen  = params.get('almacenid');
    if (autoEsid && autoTipo && autoAlmacen) {
      // Pequeño delay para asegurar que el DOM esté listo
      setTimeout(function() {
        mostrarConfirmacionArticulos(autoEsid, autoTipo, autoAlmacen);
      }, 400);
    }
  });
</script>