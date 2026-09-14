<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/recepcionesmercancia.php");

$recepciones = new recepcionesmercancia();
$id = base64_decode($_GET['id'] ?? '');

if (!$id) {
    echo "ID de recepción inválido.";
    exit;
}

// Obtener info de la recepcion
$recepInfo = $recepciones->getRecepcionById($id);

// Obtener el ES_MOTIVO_RECHAZO
$db = new FirebirdConnection();
$sqlEs = "SELECT FIRST 1 ES_ID, ES_MOTIVO_RECHAZO FROM AMPAR_HIS_ES WHERE ES_MOTIVO = 'Recepcion de OC Folio: ' || ? ORDER BY ES_ID DESC";
$esResult = $db->query($sqlEs, [$recepInfo['RECEPCION_OCID']]);
$esId = $esResult[0]['ES_ID'] ?? null;
$motivoRechazo = $esResult[0]['ES_MOTIVO_RECHAZO'] ?? 'No especificado';
$db->close();
?>

<div class="row">
    <div class="col-12">
        <h5 class="mb-3">Corregir Documentos de Recepción #REC-<?=$id?></h5>
        
        <div class="alert alert-danger">
            <h6 class="font-weight-bold mb-1"><i class="mdi mdi-alert-circle"></i> Motivos de Rechazo:</h6>
            <p class="mb-0" style="white-space: pre-wrap; font-size: 0.95rem;"><?=$motivoRechazo?></p>
        </div>

        <form id="form-edit-recepcion" enctype="multipart/form-data">
            <input type="hidden" name="recepcion_id" value="<?=$id?>">
            <input type="hidden" name="es_id" value="<?=$esId?>">

            <div class="mb-3 p-3 bg-light rounded border">
                <p class="text-muted small mb-3"><i class="mdi mdi-information-outline"></i> Sube únicamente los documentos que fueron marcados como incorrectos en el motivo de rechazo. Los documentos no subidos conservarán su versión anterior.</p>
                
                <div class="form-group mb-4">
                    <label class="font-weight-bold">Factura <span class="text-muted font-weight-normal">(Opcional si no fue rechazada)</span></label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input form-control-sm" id="archivo_factura_general" name="archivo_factura_general" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp">
                        <label class="custom-file-label" for="archivo_factura_general" data-browse="Seleccionar">Elegir archivo...</label>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="font-weight-bold">Evidencia <span class="text-muted font-weight-normal">(Opcional si no fue rechazada)</span></label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input form-control-sm" id="archivo_evidencia_general" name="archivo_evidencia_general" accept=".jpg,.jpeg,.png,.gif,.webp">
                        <label class="custom-file-label" for="archivo_evidencia_general" data-browse="Seleccionar">Elegir archivo...</label>
                    </div>
                </div>

                <div class="form-group mb-2">
                    <label class="font-weight-bold">Lista de Embarque <span class="text-muted font-weight-normal">(Opcional si no fue rechazada)</span></label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input form-control-sm" id="archivo_lista_embarque_general" name="archivo_lista_embarque_general" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp">
                        <label class="custom-file-label" for="archivo_lista_embarque_general" data-browse="Seleccionar">Elegir archivo...</label>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="font-weight-bold">Carta Canje <span class="text-muted font-weight-normal">(Opcional si no fue rechazada)</span></label>
                    <div class="custom-file">
                        <input type="file" class="custom-file-input form-control-sm" id="archivo_carta_canje_general" name="archivo_carta_canje_general" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp">
                        <label class="custom-file-label" for="archivo_carta_canje_general" data-browse="Seleccionar">Elegir archivo...</label>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="font-weight-bold">Núm. de Delivery <span class="text-muted font-weight-normal">(Opcional si no fue rechazado)</span></label>
                    <input type="text" class="form-control form-control-sm" name="num_delivery_general" id="num_delivery_general" placeholder="Ingrese el Número de Delivery si necesita actualizarlo">
                </div>
            </div>

            <div class="mt-4 text-right">
                <button type="button" class="btn btn-secondary text-white" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary text-white" id="btn-guardar-edicion">
                    <i class="mdi mdi-content-save mr-1"></i> Guardar y Reenviar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Para que el input file muestre el nombre del archivo seleccionado
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if(fileName) {
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        } else {
            $(this).next('.custom-file-label').removeClass("selected").html("Elegir archivo...");
        }
    });

    $('#form-edit-recepcion').on('submit', function(e) {
        e.preventDefault();
        
        var form = this;
        var btn = $('#btn-guardar-edicion');
        var originalText = btn.html();

        var formData = new FormData(form);

        $.ajax({
            url: '../ajax/recepcionesmercancia.update.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                btn.html('<i class="mdi mdi-loading mdi-spin mr-1"></i> Guardando...');
                btn.prop('disabled', true);
            },
            success: function(response) {
                if(response.trim() === "") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Documentos Actualizados',
                        text: 'La recepción ha sido corregida y enviada a revisión exitosamente.',
                        showConfirmButton: true,
                        confirmButtonClass: 'btn btn-success'
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response,
                        showConfirmButton: true,
                        confirmButtonClass: 'btn btn-danger'
                    });
                    btn.html(originalText);
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Conexión',
                    text: 'Ocurrió un error al enviar los datos. Intenta nuevamente.',
                    showConfirmButton: true,
                    confirmButtonClass: 'btn btn-danger'
                });
                btn.html(originalText);
                btn.prop('disabled', false);
            }
        });
    });
});
</script>
