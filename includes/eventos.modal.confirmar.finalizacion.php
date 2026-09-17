<?php
// includes/eventos.modal.confirmar.finalizacion.php
// Modal de confirmación con checklist y revisión de documentos al finalizar un evento (Replicando diseño idéntico de entradasalida)
?>
<!-- Modal Confirmar Finalización de Evento -->
<div class="modal fade" id="modalConfirmarFinalizacionEvento" tabindex="-1" role="dialog" aria-labelledby="modalConfirmarEvLabel" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title font-weight-bold" id="modalConfirmarEvLabel">Confirmar artículos del evento</h5>
        <button type="button" class="btn-close close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="form-confirmar-finalizacion-evento">
          <input type="hidden" id="confirm_eventoid" name="confirm_eventoid">
          <input type="hidden" id="confirm_eventofolio" name="confirm_eventofolio">

          <div id="seccion-docs-generales-ev-confirmar" class="mb-3">
            <!-- Se llena dinámicamente -->
          </div>

          <div class="table-responsive d-none d-md-block">
            <table class="table table-bordered table-hover">
              <thead class="bg-light">
                <tr>
                  <th class="text-center" style="width: 45px;">
                    <input type="checkbox" id="chk_master_ev_items" checked onchange="$('.chk-item-evento').prop('checked', this.checked)" style="width: 18px; height: 18px; cursor: pointer; accent-color: #20c997;">
                  </th>
                  <th>Tipo</th>
                  <th>Folio / Clave</th>
                  <th>Descripción / Artículo</th>
                  <th>Lote</th>
                  <th>Caducidad</th>
                  <th>Serie</th>
                </tr>
              </thead>
              <tbody id="tablaConfirmacionEventoBody">
                <!-- Se llena dinámicamente -->
              </tbody>
            </table>
          </div>

          <!-- Mobile Cards -->
          <div id="tablaConfirmacionEventoCards" class="d-block d-md-none mt-2">
            <!-- Se llena dinámicamente -->
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success font-weight-bold" id="btnConfirmarYFinalizarEv" onclick="confirmarYFinalizarEvento()">Confirmar Seleccionados</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Galería de Evidencias Confirmación -->
<div class="modal fade" id="modalGaleriaEvidenciasConfirm" tabindex="-1" role="dialog" aria-labelledby="modalGaleriaEvLabel" aria-hidden="true" style="z-index: 1070;">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-light border-bottom py-3 px-4">
        <h5 class="modal-title font-weight-bold text-dark" id="modalGaleriaEvLabel">
          <i class="mdi mdi-image-multiple text-info mr-2"></i> Evidencias de Recepción del Evento
        </h5>
        <button type="button" class="btn-close close" onclick="$('#modalGaleriaEvidenciasConfirm').modal('hide')" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-4 bg-white" id="galeriaEvidenciasConfirmBody" style="max-height: 75vh; overflow-y: auto;">
        <!-- Se llena dinámicamente -->
      </div>
      <div class="modal-footer bg-light px-4 py-3 border-top d-flex justify-content-between align-items-center">
        <span class="text-muted" style="font-size: 0.88rem;"><i class="mdi mdi-information-outline mr-1"></i> Puedes hacer clic sobre cualquier imagen para verla en tamaño completo.</span>
        <div>
          <button type="button" class="btn btn-secondary mr-2" onclick="$('#modalGaleriaEvidenciasConfirm').modal('hide')">Cerrar</button>
          <button type="button" class="btn btn-success font-weight-bold px-4" onclick="validarEvidenciasYCerrarGaleria()"><i class="mdi mdi-check-circle mr-1"></i> Validar Evidencias y Continuar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.ev-confirm-item-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 12px;
}
.ev-confirm-item-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 8px;
    margin-bottom: 8px;
}
</style>

<script>
window._currentEvidenciasConfirm = [];

function avisoCasillaBloqueada(nombreDoc) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'warning',
        title: `Primero debes abrir y ver ${nombreDoc} para poder habilitar y marcar esta casilla.`,
        showConfirmButton: false,
        timer: 3500
    });
}

function mostrarConfirmacionEvento(eventoid, folio) {
    $.ajax({
        url: '../ajax/eventos.get.detalle.revision.php',
        method: 'POST',
        data: { eventoid: eventoid },
        dataType: 'json',
        beforeSend: function() {
            $("#loading").show();
        },
        success: function(data) {
            if (data.error) {
                Swal.fire({ title: 'Error', text: data.error, icon: 'error' });
                return;
            }

            $("#confirm_eventoid").val(eventoid);
            $("#confirm_eventofolio").val(folio || (data.cabecera && data.cabecera.EVENTO_FOLIO ? data.cabecera.EVENTO_FOLIO : ''));

            let folioStr = folio || (data.cabecera ? data.cabecera.EVENTO_FOLIO : '');
            let sucursalStr = data.cabecera && data.cabecera.SUCURSAL_NOMBRE ? data.cabecera.SUCURSAL_NOMBRE : '';
            let clienteStr = data.cabecera && data.cabecera.CLIENTE_NOMBRE ? data.cabecera.CLIENTE_NOMBRE : (data.cabecera && data.cabecera.HOSPITAL_NOMBRE ? data.cabecera.HOSPITAL_NOMBRE : '');

            $("#modalConfirmarEvLabel").html(`Confirmar artículos y documentos del evento <b>${folioStr}</b>`);

            // Documentos generales del evento (estilo idéntico a entradasalida.php)
            let docsHtml = '';
            docsHtml += `<div class="alert alert-info py-2 px-3 mb-3 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between shadow-sm" style="font-size: 1.05rem; border-left: 5px solid #17a2b8; background-color: #e8f4f8; color: #0c5460; gap: 10px;">
                <span><strong>Evento:</strong> ${folioStr}</span>
                <span class="badge badge-info text-white px-3 py-2 text-wrap text-left" style="font-size: 0.95rem; background-color: #17a2b8; line-height: 1.4;"><i class="mdi mdi-hospital-building mr-1"></i> ${clienteStr || sucursalStr}</span>
            </div>
            <div class="p-3 rounded border bg-light d-flex flex-wrap align-items-center" style="gap: 15px;">
                <strong class="text-dark">Documentos y Evidencias de la Recepción:</strong>`;

            // Archivo de Remisión (Múltiples remisiones o general)
            if (data.remisiones_lista && data.remisiones_lista.length > 0) {
                data.remisiones_lista.forEach(function(rem, rIdx) {
                    let folioRem = rem.REMISION_FOLIO || rem.REMISION_ID;
                    if (rem.REMISION_ARCHIVO && rem.REMISION_ARCHIVO.trim() !== '') {
                        docsHtml += `
                        <div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
                            <span onclick="if($('#chk_doc_remision_item_${rIdx}').is(':disabled')) { avisoCasillaBloqueada('la Remisión #${folioRem}'); }">
                              <input type="checkbox" id="chk_doc_remision_item_${rIdx}" class="chk-doc-remision-item" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el botón de la Remisión para habilitar esta casilla">
                            </span>
                            <a href="../${rem.REMISION_ARCHIVO}" target="_blank" onclick="$('#chk_doc_remision_item_${rIdx}').prop('disabled', false).css('cursor', 'pointer'); Swal.fire({toast:true, position:'top-end', icon:'info', title:'Ya puedes marcar la casilla de la Remisión #${folioRem}', showConfirmButton:false, timer:2500}); return true;" class="btn btn-sm btn-danger text-white font-weight-bold py-1 px-3 my-0"><i class="mdi mdi-file-document mr-1"></i> Remisión ${folioRem}</a>
                        </div>`;
                    } else {
                        docsHtml += `
                        <div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
                            <span class="badge badge-warning text-dark py-1 px-2"><i class="mdi mdi-alert mr-1"></i> Sin archivo en Remisión ${folioRem}</span>
                        </div>`;
                    }
                });
            } else if (data.cabecera && data.cabecera.EVENTO_ARCHIVO_REMISION && data.cabecera.EVENTO_ARCHIVO_REMISION.trim() !== '') {
                docsHtml += `
                <div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
                    <span onclick="if($('#chk_doc_remision_ev_confirm').is(':disabled')) { avisoCasillaBloqueada('el Archivo de Remisión'); }">
                      <input type="checkbox" id="chk_doc_remision_ev_confirm" class="chk-doc-remision-item" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el botón Archivo de Remisión para habilitar esta casilla">
                    </span>
                    <a href="../${data.cabecera.EVENTO_ARCHIVO_REMISION}" target="_blank" onclick="$('#chk_doc_remision_ev_confirm').prop('disabled', false).css('cursor', 'pointer'); Swal.fire({toast:true, position:'top-end', icon:'info', title:'Ya puedes marcar la casilla del Archivo de Remisión', showConfirmButton:false, timer:2500}); return true;" class="btn btn-sm btn-danger text-white font-weight-bold py-1 px-3 my-0"><i class="mdi mdi-file-document mr-1"></i> Archivo de Remisión</a>
                </div>`;
            } else {
                docsHtml += `
                <div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
                    <span class="badge badge-warning text-dark py-1 px-2"><i class="mdi mdi-alert mr-1"></i> Sin Archivo de Remisión</span>
                </div>`;
            }

            // Evidencias de App Móvil (siempre permitir ver galería para ver sello y foto completa)
            window._currentEvidenciasConfirm = data.evidencias || [];
            if (window._currentEvidenciasConfirm.length > 0) {
                docsHtml += `
                <div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
                    <span onclick="if($('#chk_doc_evidencias_ev_confirm').is(':disabled')) { avisoCasillaBloqueada('las Evidencias de Recepción'); }">
                      <input type="checkbox" id="chk_doc_evidencias_ev_confirm" disabled style="width: 18px; height: 18px; cursor: not-allowed; accent-color: #20c997;" title="Haz clic en el botón Ver Evidencias para habilitar esta casilla">
                    </span>
                    <button type="button" onclick="verEvidenciasGaleriaConfirmModal();" class="btn btn-sm btn-info text-white font-weight-bold py-1 px-3 my-0"><i class="mdi mdi-image-multiple mr-1"></i> Ver Evidencias (${window._currentEvidenciasConfirm.length})</button>
                </div>`;
            } else {
                docsHtml += `
                <div class="d-inline-flex align-items-center p-1 bg-white border rounded shadow-sm" style="gap: 8px;">
                    <span class="badge badge-secondary text-white py-1 px-2"><i class="mdi mdi-image-off mr-1"></i> Sin Evidencias</span>
                </div>`;
            }

            docsHtml += `</div>`;
            $("#seccion-docs-generales-ev-confirmar").html(docsHtml);

            // Llenar tabla y tarjetas
            let items = [];
            // Ya no incluimos data.maletas porque el usuario solo quiere lo que se usó en remisión
            if (data.articulos && data.articulos.length > 0) {
                items = items.concat(data.articulos);
            }

            let tbodyHtml = '';
            let cardsHtml = '';

            if (items.length === 0) {
                tbodyHtml = `<tr><td colspan="7" class="text-center text-muted py-4"><i class="mdi mdi-information-outline mr-1"></i> No se encontraron equipos o artículos registrados para este evento.</td></tr>`;
                cardsHtml = `<div class="alert alert-light border text-center text-muted py-3">No hay artículos para mostrar.</div>`;
            } else {
                items.forEach(function(item, idx) {
                    let esMaleta = item.TIPO_ITEM === 'MALETA_EQUIPO' || item.ES_EQUIPO_CAPITAL == 1 || item.ES_EQUIPO_CAPITAL === '1';
                    let badgeTipo = esMaleta 
                        ? `<span class="badge badge-dark text-white py-1 px-2" style="font-size:0.8rem; background-color: #343a40;"><i class="mdi mdi-briefcase mr-1"></i> Equipo Capital</span>`
                        : `<span class="badge badge-success text-white py-1 px-2" style="font-size:0.8rem; background-color: #28a745;"><i class="mdi mdi-cube-outline mr-1"></i> Artículo</span>`;

                    let folioVal = item.FOLIO || '-';
                    let nombreVal = item.NOMBRE || '-';
                    let loteVal = item.LOTE || '-';
                    let cadVal = item.CADUCIDAD || '-';
                    let serieVal = item.SERIE || '-';

                    tbodyHtml += `
                    <tr>
                      <td class="text-center align-middle">
                        <input type="checkbox" class="chk-item-evento" name="items_confirmados[]" value="${idx}" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: #20c997;">
                      </td>
                      <td class="align-middle">${badgeTipo}</td>
                      <td class="align-middle font-weight-bold text-dark">${folioVal}</td>
                      <td class="align-middle text-dark">${nombreVal}</td>
                      <td class="align-middle">${loteVal}</td>
                      <td class="align-middle">${cadVal}</td>
                      <td class="align-middle">${serieVal}</td>
                    </tr>`;

                    cardsHtml += `
                    <div class="ev-confirm-item-card">
                      <div class="ev-confirm-item-card-header">
                        <div>
                          ${badgeTipo}
                          <span class="font-weight-bold ml-2 text-dark">${folioVal}</span>
                        </div>
                        <div>
                          <label class="mb-0 font-weight-bold mr-1" style="font-size:0.85rem; cursor:pointer;">Confirmar:</label>
                          <input type="checkbox" class="chk-item-evento" name="items_confirmados_card[]" value="${idx}" checked onchange="$('input[name=\\'items_confirmados[]\\'][value=\\'${idx}\\']').prop('checked', this.checked)" style="width: 18px; height: 18px; vertical-align: middle; accent-color: #20c997;">
                        </div>
                      </div>
                      <div class="mb-1 text-dark"><strong>Descripción:</strong> ${nombreVal}</div>
                      <div class="d-flex flex-wrap text-muted" style="gap: 12px; font-size: 0.85rem;">
                        <span><strong>Lote:</strong> ${loteVal}</span>
                        <span><strong>Caducidad:</strong> ${cadVal}</span>
                        <span><strong>Serie:</strong> ${serieVal}</span>
                      </div>
                    </div>`;
                });
            }

            $("#tablaConfirmacionEventoBody").html(tbodyHtml);
            $("#tablaConfirmacionEventoCards").html(cardsHtml);

            $("#modalConfirmarFinalizacionEvento").modal('show');
        },
        error: function() {
            Swal.fire({ title: 'Error', text: 'No se pudieron obtener los detalles del evento para revisión.', icon: 'error' });
        },
        complete: function() {
            $("#loading").hide();
        }
    });
}

function verEvidenciasGaleriaConfirmModal() {
    let evs = window._currentEvidenciasConfirm || [];
    if (evs.length === 0) {
        Swal.fire({ title: 'Info', text: 'No hay evidencias que mostrar.', icon: 'info' });
        return;
    }

    let html = `<div class="row">`;
    evs.forEach(function(ev) {
        let rutaFoto = ev.RUTA_FOTO || '';
        let proxyUrl = '../ajax/eventos.imagen.proxy.php?ruta=' + encodeURIComponent(rutaFoto);
        let tipoOp = ev.TIPO_OPERACION || 'Evidencia';
        let fecha = ev.FECHA_REGISTRO || '';
        let sello = ev.SELLO_NUMERO || '';

        html += `
        <div class="col-md-6 col-lg-6 mb-4">
          <div class="card border shadow-sm h-100 rounded bg-white">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
              <span class="font-weight-bold text-dark text-truncate" style="font-size: 0.95rem;" title="${tipoOp}"><i class="mdi mdi-camera mr-1 text-info"></i> ${tipoOp}</span>
              ${sello ? `<span class="badge badge-info text-white py-1 px-2" style="font-size: 0.82rem;">Sello: ${sello}</span>` : ''}
            </div>
            <div style="height: 380px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: zoom-in; padding: 12px;" onclick="window.open('${proxyUrl}', '_blank')" title="Clic para ampliar imagen en nueva pestaña">
              <img src="${proxyUrl}" alt="Evidencia de Recepción" style="max-width: 100%; max-height: 100%; object-fit: contain; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border-radius: 4px; background: #fff;" onerror="this.onerror=null; this.src=''; this.alt='Imagen no disponible';">
            </div>
            <div class="card-footer bg-white text-muted py-2 px-3 d-flex justify-content-between align-items-center" style="font-size: 0.85rem;">
              <span><i class="mdi mdi-calendar-clock mr-1"></i> Registro: ${fecha || 'N/A'}</span>
              <a href="${proxyUrl}" target="_blank" class="text-info font-weight-bold" style="text-decoration: underline;">Ampliar <i class="mdi mdi-open-in-new"></i></a>
            </div>
          </div>
        </div>`;
    });
    html += `</div>`;
    $("#galeriaEvidenciasConfirmBody").html(html);
    $("#modalGaleriaEvidenciasConfirm").modal('show');
}

function validarEvidenciasYCerrarGaleria() {
    $('#chk_doc_evidencias_ev_confirm').prop('disabled', false).css('cursor', 'pointer').prop('checked', true);
    $('#modalGaleriaEvidenciasConfirm').modal('hide');
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Evidencias de recepción validadas',
        showConfirmButton: false,
        timer: 2500
    });
}

function confirmarYFinalizarEvento() {
    const errores = [];

    const chkRemisiones = $(".chk-doc-remision-item");
    if (chkRemisiones.length > 0) {
        let faltaRem = false;
        chkRemisiones.each(function() {
            if (!$(this).is(":checked")) {
                if ($(this).is(":disabled")) {
                    errores.push("Primero debes abrir y ver cada Archivo de Remisión para activar y marcar sus casillas.");
                } else {
                    errores.push("Debes marcar las casillas de todas las Remisiones confirmando que fueron revisadas.");
                }
                faltaRem = true;
                return false;
            }
        });
    }

    const chkEvidencias = $("#chk_doc_evidencias_ev_confirm");
    if (chkEvidencias.length > 0 && !chkEvidencias.is(":checked")) {
        if (chkEvidencias.is(":disabled")) {
            errores.push("Primero debes abrir y ver las Evidencias de Recepción para activar y marcar su casilla.");
        } else {
            errores.push("Debes marcar la casilla de Evidencias de Recepción confirmando que fueron verificadas.");
        }
    }

    const checksItems = $("#tablaConfirmacionEventoBody .chk-item-evento");
    if (checksItems.length > 0 && checksItems.filter(":checked").length === 0) {
        errores.push("Debes confirmar con checklist al menos un equipo o artículo del evento.");
    }

    if (errores.length > 0) {
        Swal.fire({
            title: "Requisitos incompletos",
            html: `<b>Por favor revisa lo siguiente para finalizar:</b><br><ul style="text-align:left; margin-top:12px; margin-bottom:0;">${errores.map(e => `<li style="margin-bottom:6px;">${e}</li>`).join('')}</ul>`,
            icon: "warning",
            customClass: { confirmButton: 'btn btn-success font-weight-bold px-4' }
        });
        return;
    }

    const eventoid = $("#confirm_eventoid").val();
    const folio = $("#confirm_eventofolio").val();

    Swal.fire({
        title: '¿Confirmar Finalización?',
        html: `¿Estás seguro que deseas concluir y marcar como <b>Finalizado</b> el evento <b>${folio}</b>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, Finalizar Evento',
        cancelButtonText: 'Cancelar',
        customClass: {
            confirmButton: 'btn btn-success font-weight-bold px-4',
            cancelButton: 'btn btn-secondary px-4'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../ajax/eventos.update.statusgeneral.php',
                type: 'POST',
                data: {
                    id: eventoid,
                    status: 3
                },
                beforeSend: function() {
                    $("#loading").show();
                },
                success: function(response) {
                    $("#modalConfirmarFinalizacionEvento").modal('hide');
                    Swal.fire({
                        title: "¡Evento Finalizado!",
                        text: `El evento ${folio} ha sido finalizado correctamente.`,
                        icon: "success",
                        customClass: { confirmButton: 'btn btn-success font-weight-bold px-4' }
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function() {
                    Swal.fire({ title: "Error", text: "Hubo un problema al finalizar el evento.", icon: "error" });
                },
                complete: function() {
                    $("#loading").hide();
                }
            });
        }
    });
}

$(document).ready(function() {
    // Forzar que el body mantenga la clase modal-open si el principal sigue abierto
    $('#modalGaleriaEvidenciasConfirm').on('hidden.bs.modal', function () {
        if ($('#modalConfirmarFinalizacionEvento').hasClass('show') || $('#modalConfirmarFinalizacionEvento').is(':visible')) {
            $('body').addClass('modal-open');
        }
    });
});
</script>

<style>
/* CSS BRUTAL: El modal principal NUNCA debe perder su capacidad de scroll, incluso si Bootstrap intenta quitársela */
#modalConfirmarFinalizacionEvento {
    overflow-y: auto !important;
}

/* Y si el modal principal está abierto, el body NUNCA debe hacer scroll */
body.modal-open {
    overflow: hidden !important;
}
</style>
