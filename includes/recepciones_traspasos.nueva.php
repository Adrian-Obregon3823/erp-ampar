<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");

$usuarioid = $_SESSION['ampar']['usuario']['USUARIO_ID'] ?? 0;
$sucursales = $_SESSION['ampar']['sucursales'] ?? [];
$sucursalesIds = [];
if (!empty($sucursales)) {
    foreach ($sucursales as $suc) {
        $sucursalesIds[] = $suc['SUCURSAL_ID'];
    }
}
$sucursalIdsStr = empty($sucursalesIds) ? "0" : implode(',', $sucursalesIds);

$db = new FirebirdConnection();

// Obtener los traspasos enviados (status 9) al almacén(es) del usuario
$condicionSucursal = "";
if (!$GLOBALS['isAdmin']) {
    $condicionSucursal = "AND AD.ALMACEN_SUCURSAL_MS IN ($sucursalIdsStr)";
}

$sqlTraspasos = "
    SELECT T.TRASPASO_ID, T.TRASPASO_FOLIO, A.ALMACEN_NOMBRE 
    FROM AMPAR_HIS_TRASPASO T
    LEFT JOIN AMPAR_HIS_ALMACEN A ON A.ALMACEN_ID = T.TRASPASO_DEALMACENID
    LEFT JOIN AMPAR_HIS_ALMACEN AD ON AD.ALMACEN_ID = T.TRASPASO_AALMACENID
    WHERE T.TRASPASO_STATUS = 9 
    $condicionSucursal
    AND NOT EXISTS (SELECT 1 FROM AMPAR_DISPUTAS D WHERE D.DISPUTA_TRASPASOID = T.TRASPASO_ID AND D.DISPUTA_STATUS = 1)
";
$traspasos = $db->query($sqlTraspasos);
$db->close();
?>

<form id="form-recepcion-traspaso" enctype="multipart/form-data">
    <input type="hidden" name="almacenid" id="almacenid" value="<?= $almacen_id ?>">
    <div class="row mb-3">
        <div class="col-md-12">
            <label for="traspasoid"><strong>Seleccione Traspaso Pendiente:</strong></label>
            <select name="traspasoid" id="traspasoid" class="form-control" required>
                <option value="">Seleccione Traspaso...</option>
                <?php 
                if ($traspasos <> 0) {
                    foreach ($traspasos as $t) { 
                ?>
                    <option value="<?= $t['TRASPASO_ID'] ?>">Folio Traspaso: <?= $t['TRASPASO_FOLIO'] ?> (Origen: <?= $t['ALMACEN_NOMBRE'] ?>)</option>
                <?php 
                    } 
                }
                ?>
            </select>
        </div>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-12">
            <label><strong>Observaciones Generales:</strong></label>
            <textarea name="observaciones" class="form-control" rows="2"></textarea>
        </div>
    </div>

    <div class="row mb-3" id="seccion-documentos-generales" style="display:none;">
        <div class="col-md-6 mb-2">
            <label><strong>Evidencia <span class="text-danger">*</span>:</strong></label>
            <input type="file" class="form-control form-control-sm" name="archivo_evidencia_general" id="archivo_evidencia_general" accept="image/*" title="Subir Evidencia">
        </div>
        <div class="col-md-6 mb-2">
            <label><strong>Lista de Empaque Firmada <span class="text-danger">*</span>:</strong></label>
            <div id="lista_empaque_container">
                <!-- Inputs dinámicos -->
            </div>
            <button type="button" class="btn btn-sm btn-primary text-white mt-2" onclick="agregarCampoListaEmpaque()"><i class="mdi mdi-camera-plus"></i> Añadir otra foto / archivo</button>
            <small class="form-text text-muted d-block mt-1" id="empaque_pages_info"></small>
        </div>
    </div>


    <!-- Contenedor dinámico de la tabla de artículos -->
    <div id="contenedor-articulos" class="mt-4">
        <div class="alert alert-secondary text-center">
            Seleccione un Traspaso para ver los artículos pendientes.
        </div>
    </div>

    <div class="d-flex flex-column flex-sm-row justify-content-end align-items-stretch align-items-sm-center mt-4 pt-2 border-top">
        <button type="button" class="btn btn-secondary mb-2 mb-sm-0 mr-0 mr-sm-2 order-2 order-sm-1" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-warning mb-2 mb-sm-0 mr-0 mr-sm-2 order-1 order-sm-2" id="btn-iniciar-disputa" style="display:none;" onclick="abrirModalDisputa()"><i class="mdi mdi-alert mr-1"></i> Iniciar Disputa</button>
        <button type="submit" class="btn btn-primary order-1 order-sm-3 mb-2 mb-sm-0" id="btn-guardar-recepcion" style="display:none;"><i class="mdi mdi-check mr-1"></i> Confirmar Recepción</button>
    </div>
</form>

<script>
$(document).ready(function() {
    
    // Cargar artículos cuando se selecciona un Traspaso
    $('#traspasoid').on('change', function() {
        var traspasoid = $(this).val();
        if (traspasoid) {
            $('#contenedor-articulos').html('<div class="text-center"><i class="mdi mdi-spin mdi-loading" style="font-size:24px;"></i> Cargando artículos...</div>');
            $.ajax({
                url: '../ajax/recepciones_traspasos.get.php',
                type: 'GET',
                data: { traspasoid: traspasoid },
                success: function(res) {
                    $('#contenedor-articulos').html(res);
                    $('#btn-guardar-recepcion').show();
                    $('#btn-iniciar-disputa').show();
                    $('#seccion-documentos-generales').show();
                    
                    var expected = parseInt($('#pdf_pages_count').val()) || 1;
                    $('#empaque_pages_info').text('Se requiere subir al menos ' + expected + ' foto(s), una por cada hoja del formato.');
                    
                    $('#lista_empaque_container').empty();
                    for (var i = 0; i < expected; i++) {
                        agregarCampoListaEmpaque(i === 0); // First one can't be deleted easily or just allow it
                    }
                },
                error: function() {
                    $('#contenedor-articulos').html('<div class="alert alert-danger">Error al cargar artículos.</div>');
                    $('#btn-guardar-recepcion').hide();
                    $('#btn-iniciar-disputa').hide();
                    $('#seccion-documentos-generales').hide();
                }
            });
        } else {
            $('#contenedor-articulos').html('<div class="alert alert-secondary text-center">Seleccione un Traspaso para ver los artículos pendientes.</div>');
            $('#btn-guardar-recepcion').hide();
            $('#seccion-documentos-generales').hide();
        }
    });

    $('#form-recepcion-traspaso').on('submit', function(e) {
        e.preventDefault();

        // Validaciones generales
        var countChecked = $('.chk-recibir:checked').length;
        if (countChecked === 0) {
            alert('Debe seleccionar al menos un artículo para recibir.');
            return false;
        }

        var inputEviGen = $('#archivo_evidencia_general')[0];
        if (inputEviGen.files.length === 0) {
            alert('Es obligatorio adjuntar la Evidencia.');
            $('#archivo_evidencia_general').addClass('is-invalid');
            return false;
        } else {
            var eviType = inputEviGen.files[0].type;
            var eviExt = inputEviGen.files[0].name.split('.').pop().toLowerCase();
            if (!eviType.startsWith('image/') && $.inArray(eviExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic']) === -1) {
                alert('El archivo de Evidencia debe ser una imagen válida.');
                $('#archivo_evidencia_general').addClass('is-invalid');
                return false;
            }
            $('#archivo_evidencia_general').removeClass('is-invalid');
        }

        var expectedPages = parseInt($('#pdf_pages_count').val()) || 1;
        var inputsEmpaque = $('.input-empaque-dinamico');
        var filesCount = 0;
        var hasInvalidFile = false;
        
        inputsEmpaque.each(function() {
            if (this.files.length > 0) {
                filesCount++;
                var empType = this.files[0].type;
                var empExt = this.files[0].name.split('.').pop().toLowerCase();
                if (!empType.startsWith('image/') && $.inArray(empExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic']) === -1) {
                    hasInvalidFile = true;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            } else {
                $(this).addClass('is-invalid');
            }
        });

        if (filesCount < expectedPages) {
            alert('Debe subir al menos ' + expectedPages + ' archivo(s) para la Lista de Empaque Firmada (uno por cada hoja del formato). Ha seleccionado ' + filesCount + '.');
            return false;
        }
        
        if (hasInvalidFile) {
            alert('Todos los archivos de la Lista de Empaque deben ser imágenes válidas.');
            return false;
        }

        if (confirm('¿Está seguro de registrar la recepción de este traspaso en el almacén?')) {
            var formData = new FormData(this);
            $.ajax({
                url: '../ajax/recepciones_traspasos.guardar.php',
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

        window.abrirModalDisputa = function() {
            var traspasoid = $('#traspasoid').val();
            if (!traspasoid) {
                alert("Seleccione un traspaso primero.");
                return;
            }
            $('#modalglobal .modal-content').html('<div class="modal-body text-center"><i class="mdi mdi-spin mdi-loading" style="font-size: 2rem;"></i> Cargando...</div>');
            $('#modalglobal .modal-content').load('../includes/recepciones_traspasos.disputa.php?traspasoid=' + traspasoid);
        };
    
    window.agregarCampoListaEmpaque = function(isFirst) {
        let newId = "lista_empaque_" + Date.now() + Math.floor(Math.random() * 1000);
        let btnDelete = '';
        if (!isFirst) {
            btnDelete = `<button type="button" class="btn btn-sm btn-danger ms-2 ml-2" onclick="$('#div_${newId}').remove()"><i class="mdi mdi-delete"></i></button>`;
        }
        let html = `
        <div class="d-flex mb-2 mt-2 align-items-center" id="div_${newId}">
            <input type="file" class="form-control form-control-sm input-empaque-dinamico" name="archivo_empaque[]" accept="image/*">
            ${btnDelete}
        </div>`;
        $("#lista_empaque_container").append(html);
    };

});
</script>
