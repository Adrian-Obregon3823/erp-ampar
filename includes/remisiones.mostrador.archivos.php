<?php
require_once("../includes/sesion.php");
require_once("../includes/includes.php");

$remisionid = intval(base64_decode($_GET['remisionid'] ?? ''));
if ($remisionid <= 0) {
    die("ID de remisión inválido");
}

// Obtener remisiones de AMPAR_HIS_REMISIONES
$dbRem = new FirebirdConnection();
$rItem = null;
try {
    try { $dbRem->execute("ALTER TABLE AMPAR_HIS_REMISIONES ADD REMISION_ARCHIVO VARCHAR(500)"); } catch (Throwable $t) {}
    $rr = $dbRem->query("
        SELECT R.REMISION_ID, R.REMISION_FOLIO, R.REMISION_FECHA, R.REMISION_ARCHIVO, R.REMISION_STATUS
        FROM AMPAR_HIS_REMISIONES R
        WHERE R.REMISION_ID = {$remisionid}
    ");
    if ($rr && is_array($rr) && count($rr) > 0) {
        $rItem = $rr[0];
    }
} catch (Throwable $e) {}
$dbRem->close();

if (!$rItem) {
    die("No se encontró la remisión.");
}

$archivoThisRem = trim($rItem['REMISION_ARCHIVO'] ?? '');
$statusGeneral = (int)($rItem['REMISION_STATUS'] ?? 0);
?>

<div class="archivo-recepcion-panel" style="max-width: 900px; margin: 0 auto;">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom" style="padding: 16px 22px;">
            <h5 class="mb-0" style="font-size:1rem;">
                Factura de Remisión #<b><?= htmlspecialchars($rItem['REMISION_FOLIO']) ?></b>
            </h5>
        </div>
        <div class="card-body" style="padding: 22px;">
            <div class="mb-4">
                <div class="card mb-3 border shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                        <span class="font-weight-bold text-dark"><i class="fa fa-file-text-o mr-1 text-danger"></i> Remisión #<?= htmlspecialchars($rItem['REMISION_FOLIO']) ?></span>
                        <span class="text-muted" style="font-size:0.8rem;"><?= htmlspecialchars($rItem['REMISION_FECHA'] ?? '') ?></span>
                    </div>
                    <div class="card-body p-3">
                        <?php if (!empty($archivoThisRem)): ?>
                            <div class="d-flex align-items-center mb-2" style="gap: 12px;">
                                <span class="badge badge-success text-white py-1 px-2"><i class="fa fa-check mr-1"></i> Subido</span>
                                <a href="../<?= htmlspecialchars($archivoThisRem) ?>" target="_blank" class="btn btn-sm btn-outline-danger font-weight-bold">
                                    <i class="fa fa-external-link mr-1"></i> Ver Documento
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="mb-2">
                                <span class="badge badge-warning text-dark py-1 px-2"><i class="fa fa-exclamation-triangle mr-1"></i> Pendiente subir factura para esta remisión</span>
                            </div>
                        <?php endif; ?>

                        <?php if ($statusGeneral !== 3 && $statusGeneral !== 5): ?>
                            <form id="formSubirRemisionMostrador" enctype="multipart/form-data">
                                <input type="hidden" name="remisionid" value="<?= $rItem['REMISION_ID'] ?>">
                                <div class="d-flex align-items-center mt-2" style="gap:10px;">
                                    <input type="file" class="form-control-file" name="archivo_remision" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <button type="submit" class="btn btn-primary btn-sm font-weight-bold" style="white-space:nowrap;">
                                        <i class="fa fa-upload mr-1"></i> <?= !empty($archivoThisRem) ? 'Reemplazar Factura' : 'Subir Factura' ?>
                                    </button>
                                </div>
                            </form>
                        <?php elseif ($statusGeneral === 3): ?>
                            <p class="text-muted mb-0" style="font-size:0.82rem;"><i class="fa fa-lock mr-1"></i> Remisión finalizada.</p>
                        <?php elseif ($statusGeneral === 5): ?>
                            <p class="text-muted mb-0" style="font-size:0.82rem;"><i class="fa fa-lock mr-1"></i> Remisión cancelada.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#formSubirRemisionMostrador').on('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: '../ajax/remisiones.mostrador.archivos.guardar.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() { $("#loading").show(); },
            success: function(response) {
                try {
                    var resJson = typeof response === 'object' ? response : JSON.parse(response);
                    if (resJson.success) {
                        Swal.fire({
                            title: "¡Guardado!",
                            text: resJson.msg || "Factura de remisión guardada correctamente.",
                            icon: "success",
                            customClass: { confirmButton: 'btn btn-success font-weight-bold px-4' }
                        }).then(() => { location.reload(); });
                    } else {
                        Swal.fire({ title: "Atención", html: resJson.error || "No se pudo guardar", icon: "warning", customClass: { confirmButton: 'btn btn-warning' } });
                    }
                } catch(e) {
                    if (response === "" || (typeof response === 'string' && response.trim() === "")) {
                        Swal.fire({ title: "¡Guardado!", text: "Factura guardada correctamente.", icon: "success" }).then(() => { location.reload(); });
                    } else {
                        var msg = typeof response === 'object' ? (response.error || JSON.stringify(response)) : response;
                        Swal.fire({ title: "Atención", html: msg, icon: "warning" });
                    }
                }
            },
            error: function() {
                Swal.fire({ title: "Error", text: "Error de conexión.", icon: "error" });
            },
            complete: function() { $("#loading").hide(); }
        });
    });
});
</script>
