<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");
include_once("../class/oc.php");

$oc = new oc();
$ocs = $oc->getoc('', true); // Trae todas las OCs, ignorando restricción de usuario

// El almacén destino será el ID 1 por defecto
$almacen_fijo_id = 1;
?>

<style>
    #form-recepcion .form-control {
        background-color: #f8f9fa;
        border: 1px solid #ced4da;
        border-radius: 6px;
    }
    #form-recepcion .form-control:focus {
        background-color: #fff;
        border-color: #a0bde3;
        box-shadow: 0 0 0 0.2rem rgba(28, 58, 107, 0.15);
    }
    #form-recepcion label {
        font-size: 0.85rem;
        color: #495057;
        font-weight: 600;
        margin-bottom: 0.3rem;
    }
</style>
<form id="form-recepcion" enctype="multipart/form-data" class="p-2">
    <input type="hidden" name="almacenid" id="almacenid" value="1">
    
    <div class="row mb-3">
        <div class="col-md-12">
            <label for="ocid">Seleccione Orden de Compra <span class="text-danger">*</span></label>
            <select name="ocid" id="ocid" class="form-control" required>
                <option value="">Seleccione OC...</option>
                <?php 
                if ($ocs <> 0) {
                    foreach ($ocs as $o) { 
                        if ($o['OC_STATUS'] != 19 && $o['OC_STATUS'] != 27) continue; // Solo mostrar OCs Confirmadas (ID 19) y Parciales (ID 27)
                ?>
                    <option value="<?= $o['OC_ID'] ?>">Folio OC: <?= $o['OC_FOLIO'] ?> (Destino: <?=$o['NOMBRE']?><?= !empty($o['ALMACEN_NOMBRE']) ? ' - ' . $o['ALMACEN_NOMBRE'] : '' ?>)</option>
                <?php 
                    } 
                }
                ?>
            </select>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <label>Observaciones Generales</label>
            <textarea name="observaciones" class="form-control" rows="2" placeholder="Opcional..."></textarea>
        </div>
    </div>

    <div class="row mb-3" id="seccion-documentos-generales" style="display:none;">
        <div class="col-md-4 mb-2">
            <label>Factura</label>
            <input type="file" class="form-control form-control-sm" name="archivo_factura_general" id="archivo_factura_general" accept=".pdf,application/pdf,image/*,.jpg,.jpeg,.png,.gif,.webp" title="Subir Factura">
        </div>
        <div class="col-md-4 mb-2">
            <label>Evidencia <span class="text-danger">*</span></label>
            <input type="file" class="form-control form-control-sm" name="archivo_evidencia_general" id="archivo_evidencia_general" accept="image/*" title="Subir Evidencia">
        </div>
        <div class="col-md-4 mb-2">
            <label>Lista de Embarque <span class="text-danger">*</span></label>
            <input type="file" class="form-control form-control-sm" name="archivo_lista_embarque_general" id="archivo_lista_embarque_general" accept=".pdf,application/pdf,image/*,.jpg,.jpeg,.png,.gif,.webp" title="Subir Lista de Embarque">
        </div>
    </div>

    <div class="row mb-4" id="seccion-documentos-cc" style="display:none;">
        <div class="col-12">
            <div class="p-3 rounded" style="background-color: #fdfaf3; border: 1px solid #f2e3be;">
                <div class="mb-3">
                    <h6 class="font-weight-bold" style="color: #d18700; font-size: 0.85rem;"><i class="mdi mdi-alert"></i> Documentos Requeridos por Caducidad (< 12 Meses)</h6>
                    <small class="text-muted">Se detectó al menos un artículo con caducidad próxima. Por favor proporcione la Carta Canje y el Número de Delivery.</small>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label>Carta Canje <span class="text-danger">*</span></label>
                        <input type="file" class="form-control form-control-sm" name="archivo_carta_canje_general" id="archivo_carta_canje_general" accept=".pdf,application/pdf,image/*,.jpg,.jpeg,.png,.gif,.webp" title="Subir Carta Canje">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label>Núm. de Delivery <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="num_delivery_general" id="num_delivery_general" placeholder="Ingrese el Número de Delivery">
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>


    <!-- Contenedor dinámico de la tabla de artículos -->
    <div id="contenedor-articulos" class="mt-4">
        <div class="alert alert-secondary text-center">
            Seleccione una Orden de Compra para ver los artículos pendientes.
        </div>
    </div>

    <div class="d-flex flex-column flex-sm-row justify-content-end align-items-stretch align-items-sm-center mt-4 pt-3 border-top">
        <button type="button" class="btn btn-light border mb-2 mb-sm-0 mr-0 mr-sm-2 order-2 order-sm-1" data-dismiss="modal">Cerrar</button>
        <button type="submit" class="btn btn-primary order-1 order-sm-2 mb-2 mb-sm-0" id="btn-guardar-recepcion" style="display:none; background-color: #1d3a8a; border-color: #1d3a8a; padding: 8px 20px;"><i class="mdi mdi-check mr-1"></i> Guardar Recepción</button>
    </div>
</form>

<script>
$(document).ready(function() {
    
    // Cargar artículos cuando se selecciona una OC
    $('#ocid').on('change', function() {
        var ocid = $(this).val();
        if (ocid) {
            $('#contenedor-articulos').html('<div class="text-center"><i class="mdi mdi-spin mdi-loading" style="font-size:24px;"></i> Cargando artículos...</div>');
            $.ajax({
                url: '../ajax/recepcionesmercancia.getoc.php',
                type: 'GET',
                data: { ocid: ocid },
                success: function(res) {
                    $('#contenedor-articulos').html(res);
                    $('#btn-guardar-recepcion').show();
                    $('#seccion-documentos-generales').show();
                },
                error: function() {
                    $('#contenedor-articulos').html('<div class="alert alert-danger">Error al cargar artículos.</div>');
                    $('#btn-guardar-recepcion').hide();
                    $('#seccion-documentos-generales').hide();
                }
            });
        } else {
            $('#contenedor-articulos').html('<div class="alert alert-secondary text-center">Seleccione una Orden de Compra para ver los artículos pendientes.</div>');
            $('#btn-guardar-recepcion').hide();
            $('#seccion-documentos-generales').hide();
        }
    });

    $(document).on('input', 'input[name*="[lote]"], input[name*="[serie]"]', function() {
        if ($(this).val().trim() !== '') {
            $(this).removeClass('is-invalid');
            $(this).closest('tr').find('input[name*="[lote]"], input[name*="[serie]"]').removeClass('is-invalid');
        }
    });

    $(document).on('change', 'input[name^="archivo_evidencia_"], input[name^="archivo_factura_"], #archivo_evidencia_general, #archivo_factura_general, #archivo_lista_embarque_general', function() {
        if ($(this)[0].files.length > 0) {
            $(this).removeClass('is-invalid');
        }
    });

    $('#form-recepcion').on('submit', function(e) {
        e.preventDefault();

        // Validaciones básicas
        var almacen = $('#almacenid').val();
        if (!almacen) {
            alert('Debe seleccionar el almacén destino.');
            return false;
        }

        // Verificar que haya al menos un artículo con lote/serie/caducidad asignado
        var articulosConLote = $('input[name^="detalles["][name$="[recibida]"]').length;
        if (articulosConLote === 0) {
            alert('Debe asignar lote/serie/caducidad a al menos un artículo antes de confirmar la recepción.');
            return false;
        }

        var inputEviGen = $('#archivo_evidencia_general')[0];
        if (inputEviGen.files.length === 0) {
            alert('Debe adjuntar el archivo general de Evidencia (imagen) de la recepción.');
            $('#archivo_evidencia_general').addClass('is-invalid');
            return false;
        } else {
            var eviExt = inputEviGen.files[0].name.split('.').pop().toLowerCase();
            if ($.inArray(eviExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']) === -1) {
                alert('El archivo de Evidencia debe ser una imagen (JPG, PNG, JPEG).');
                $('#archivo_evidencia_general').addClass('is-invalid');
                return false;
            }
            $('#archivo_evidencia_general').removeClass('is-invalid');
        }

        var inputFacGen = $('#archivo_factura_general')[0];
        if (inputFacGen.files.length === 0) {
            alert('Debe adjuntar la Factura de la recepción.');
            $('#archivo_factura_general').addClass('is-invalid');
            return false;
        } else {
            var facExt = inputFacGen.files[0].name.split('.').pop().toLowerCase();
            if ($.inArray(facExt, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp']) === -1) {
                alert('La Factura debe ser PDF o imagen.');
                $('#archivo_factura_general').addClass('is-invalid');
                return false;
            }
            $('#archivo_factura_general').removeClass('is-invalid');
        }

        var inputEviGen = $('#archivo_evidencia_general')[0];
        if (inputEviGen.files.length === 0) {
            alert('Debe adjuntar la Evidencia de la recepción.');
            $('#archivo_evidencia_general').addClass('is-invalid');
            return false;
        } else {
            var eviExt = inputEviGen.files[0].name.split('.').pop().toLowerCase();
            if ($.inArray(eviExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']) === -1) {
                alert('La Evidencia debe ser una imagen.');
                $('#archivo_evidencia_general').addClass('is-invalid');
                return false;
            }
            $('#archivo_evidencia_general').removeClass('is-invalid');
        }

        var inputListaGen = $('#archivo_lista_embarque_general')[0];
        if (inputListaGen.files.length === 0) {
            alert('Debe adjuntar la Lista de Embarque de la recepción.');
            $('#archivo_lista_embarque_general').addClass('is-invalid');
            return false;
        } else {
            var listaExt = inputListaGen.files[0].name.split('.').pop().toLowerCase();
            if ($.inArray(listaExt, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp']) === -1) {
                alert('La Lista de Embarque debe ser PDF o imagen.');
                $('#archivo_lista_embarque_general').addClass('is-invalid');
                return false;
            }
            $('#archivo_lista_embarque_general').removeClass('is-invalid');
        }

        // Validar Carta Canje si la sección está visible
        if ($('#seccion-documentos-cc').is(':visible')) {
            var inputCC = $('#archivo_carta_canje_general')[0];
            if (inputCC.files.length === 0) {
                alert('Debe adjuntar la Carta Canje (aplica para caducidades < 12 meses).');
                $('#archivo_carta_canje_general').addClass('is-invalid');
                return false;
            }
            var numDel = $('#num_delivery_general').val().trim();
            if (numDel === '') {
                alert('Debe capturar el Número de Delivery.');
                $('#num_delivery_general').addClass('is-invalid');
                return false;
            }
        }



        if (confirm('¿Está seguro de registrar la recepción de los artículos seleccionados en el almacén?')) {
            var formData = new FormData(this);
            $.ajax({
                url: '../ajax/recepcionesmercancia.guardar.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('#btn-guardar-recepcion').prop('disabled', true).text('Guardando...');
                    $('#loading').show();
                },
                success: function(response) {
                    if (response.trim() === 'OK') {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                html: "Recepción registrada exitosamente.<br><br><b>Siguiente paso:</b> Los artículos y/o equipos han ingresado formalmente a tu almacén y ya están disponibles.",
                                icon: "success",
                                customClass: { confirmButton: 'btn btn-success' }
                            }).then(() => {
                                $('#modalglobal').modal('hide');
                                location.reload();
                            });
                        } else {
                            alert("Recepción registrada exitosamente.\n\nSiguiente paso: Los artículos y/o equipos han ingresado formalmente a tu almacén y ya están disponibles.");
                            $('#modalglobal').modal('hide');
                            location.reload();
                        }
                    } else {
                        alert(response);
                        $('#btn-guardar-recepcion').prop('disabled', false).text('Confirmar Recepción');
                    }
                },
                error: function() {
                    alert('Error en la comunicación con el servidor.');
                    $('#btn-guardar-recepcion').prop('disabled', false).text('Confirmar Recepción');
                },
                complete: function() {
                    $('#loading').hide();
                }
            });
        }
    });
});
</script>
