<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");

$traspasoid = isset($_GET['traspasoid']) ? (int)$_GET['traspasoid'] : 0;

$db = new FirebirdConnection();
$sql = "
    SELECT T.TRASPASO_FOLIO, A1.ALMACEN_NOMBRE as ORIGEN, A2.ALMACEN_NOMBRE as DESTINO
    FROM AMPAR_HIS_TRASPASO T
    LEFT JOIN AMPAR_HIS_ALMACEN A1 ON A1.ALMACEN_ID = T.TRASPASO_DEALMACENID
    LEFT JOIN AMPAR_HIS_ALMACEN A2 ON A2.ALMACEN_ID = T.TRASPASO_AALMACENID
    WHERE T.TRASPASO_ID = ?
";
$res = $db->query($sql, [$traspasoid]);
$db->close();

if (empty($res)) {
    echo "<div class='modal-body'><div class='alert alert-danger'>Traspaso no encontrado.</div></div>";
    exit;
}
$t = $res[0];
?>

<div class="modal-header bg-warning text-white">
    <h5 class="modal-title" id="disputaModalLabel"><i class="mdi mdi-alert-circle-outline"></i> Iniciar Disputa para Traspaso <?= $t['TRASPASO_FOLIO'] ?></h5>
    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body">
    <div class="alert alert-info">
        <strong>Origen:</strong> <?= $t['ORIGEN'] ?> <br>
        <strong>Destino:</strong> <?= $t['DESTINO'] ?>
    </div>

    <form id="form-disputa-traspaso" enctype="multipart/form-data">
        <input type="hidden" name="traspasoid" value="<?= $traspasoid ?>">
        
        <div class="form-group mb-3">
            <label><strong>Motivo de la Disputa: <span class="text-danger">*</span></strong></label>
            <select name="motivo" class="form-control" required>
                <option value="">Seleccione el motivo...</option>
                <option value="Mat. Faltante">Mat. Faltante</option>
                <option value="Mat. Sin Etiqueta">Mat. Sin Etiqueta</option>
                <option value="Mat. Falto todo=Robo">Mat. Falto todo=Robo</option>
                <option value="Mat. Dañado">Mat. Dañado</option>
                <option value="Embalaje Vacio">Embalaje Vacío</option>
                <option value="Mat. incorrecto">Mat. incorrecto</option>
            </select>
        </div>

        <div class="form-group mb-3">
            <label><strong>Detalles adicionales: <span class="text-danger">*</span></strong></label>
            <textarea name="detalle" class="form-control" rows="3" placeholder="Describa brevemente el problema encontrado..." required></textarea>
        </div>

        <div class="form-group mb-3">
            <label><strong>Evidencias Fotográficas Obligatorias: <span class="text-danger">*</span></strong></label>
            <div id="evidencias-alert" class="alert alert-secondary p-2 mt-1 mb-2" style="font-size: 0.85rem;">
                <i class="mdi mdi-information mr-1"></i> Seleccione un motivo de disputa para ver las evidencias requeridas.
            </div>
            
            <div id="evidencias-container" class="row">
                <!-- Se llenará con JS -->
            </div>
        </div>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="volverARecepcion()">Volver</button>
    <button type="button" class="btn btn-warning" id="btn-guardar-disputa"><i class="mdi mdi-alert mr-1"></i> Enviar Disputa</button>
</div>

<script>
    var evidenciasPorMotivo = {
        'Mat. Faltante': ['Foto de la caja (cerrada o empaque)', 'Foto del interior de la caja (mostrando el faltante)'],
        'Mat. Sin Etiqueta': ['Foto del material sin etiqueta', 'Foto de la caja exterior'],
        'Mat. Falto todo=Robo': ['Acta de hechos / Reporte de Robo', 'Foto del empaque violado/roto'],
        'Mat. Dañado': ['Foto de la caja exterior', 'Foto del empaque original', 'Foto del material dañado'],
        'Embalaje Vacio': ['Foto de la caja exterior', 'Foto del interior vacío'],
        'Mat. incorrecto': ['Foto del material recibido', 'Foto del empaque original', 'Foto de la etiqueta de envío']
    };

    $('select[name="motivo"]').change(function() {
        var motivo = $(this).val();
        var container = $('#evidencias-container');
        var alertBox = $('#evidencias-alert');
        
        container.empty();
        
        if (motivo && evidenciasPorMotivo[motivo]) {
            alertBox.removeClass('alert-secondary').addClass('alert-warning').html('<i class="mdi mdi-information mr-1"></i> Para resolver esta disputa, es <strong>obligatorio</strong> adjuntar las siguientes fotografías:');
            
            var requeridas = evidenciasPorMotivo[motivo];
            requeridas.forEach(function(ev, index) {
                var html = '<div class="col-md-12 mb-2">' +
                           '<label style="font-size: 0.85rem;" class="text-muted mb-1">' + (index + 1) + '. ' + ev + ' <span class="text-danger">*</span></label>' +
                           '<input type="file" name="evidencia_' + index + '" class="form-control form-control-sm" required accept="image/*,.pdf">' +
                           '</div>';
                container.append(html);
            });
        } else {
            alertBox.removeClass('alert-warning').addClass('alert-secondary').html('<i class="mdi mdi-information mr-1"></i> Seleccione un motivo de disputa para ver las evidencias requeridas.');
        }
    });

    function volverARecepcion() {
        $('#modalglobal .modal-content').html('<div class="modal-body text-center"><i class="mdi mdi-spin mdi-loading" style="font-size: 2rem;"></i> Cargando...</div>');
        $('#modalglobal .modal-content').load('../includes/recepciones_traspasos.nueva.php');
    }

    $('#btn-guardar-disputa').click(function() {
        var form = $('#form-disputa-traspaso')[0];
        if(!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        var formData = new FormData(form);

        $.ajax({
            url: '../ajax/disputas.guardar.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#btn-guardar-disputa').prop('disabled', true).text('Enviando...');
            },
            success: function(response) {
                if(response.trim() === 'OK') {
                    alert('La disputa ha sido iniciada. El administrador revisará el caso.');
                    $('#modalglobal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response);
                    $('#btn-guardar-disputa').prop('disabled', false).html('<i class="mdi mdi-alert mr-1"></i> Enviar Disputa');
                }
            },
            error: function() {
                alert('Error al conectar con el servidor.');
                $('#btn-guardar-disputa').prop('disabled', false).html('<i class="mdi mdi-alert mr-1"></i> Enviar Disputa');
            }
        });
    });
</script>
