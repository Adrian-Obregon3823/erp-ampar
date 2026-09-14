<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

$eventoid = intval(base64_decode($_GET['eventoid'] ?? ''));
$remisionid = intval(base64_decode($_GET['remisionid'] ?? ''));
if ($eventoid <= 0) {
    die("ID de evento inválido");
}

$evObj = new eventos();
$res = $evObj->geteventobyid($eventoid);
if (!$res || count($res) === 0) {
    die("No se encontró el evento.");
}

$ev = $res[0];
$archivoRem    = $ev['EVENTO_ARCHIVO_REMISION'] ?? '';
$statusGeneral = (int)($ev['EVENTO_STATUSGENERAL'] ?? 0);

// Obtener evidencias de recepción desde AMPAR_ENTREGAEVENTO (API)
$dbEv = new FirebirdConnection();
$evidencias = [];
$maletasCount = 0;
try {
    $evRows = $dbEv->query("
        SELECT EVIDENCIA_ID, EVENTO_ID, MALETA_ID, USUARIO_ID, 
               SELLO_NUMERO, RUTA_FOTO, TIPO_OPERACION, FECHA_REGISTRO
        FROM AMPAR_ENTREGAEVENTO
        WHERE EVENTO_ID = {$eventoid}
        ORDER BY FECHA_REGISTRO DESC
    ");
    if ($evRows && is_array($evRows)) {
        $evidencias = $evRows;
    }
    
    $rm = $dbEv->query("SELECT COUNT(*) AS T FROM AMPAR_HIS_EVENTOSMALETAS WHERE EVENTOMALETA_EVENTOID = {$eventoid} AND EVENTOMALETA_MALETAID > 0");
    if ($rm && isset($rm[0]['T'])) {
        $maletasCount = (int)$rm[0]['T'];
    }
} catch (Throwable $e) {
    // Tabla puede no existir aún
    $evidencias = [];
}
$dbEv->close();

// Obtener remisiones de AMPAR_HIS_REMISIONES para este evento
$remRows = [];
$dbRem = new FirebirdConnection();
try {
    try { $dbRem->execute("ALTER TABLE AMPAR_HIS_REMISIONES ADD REMISION_ARCHIVO VARCHAR(500)"); } catch (Throwable $t) {}
    $rr = $dbRem->query("
        SELECT R.REMISION_ID, R.REMISION_FOLIO, R.REMISION_FECHA, R.REMISION_ARCHIVO
        FROM AMPAR_HIS_REMISIONES R
        WHERE R.REMISION_EVENTOID = {$eventoid}
          AND R.REMISION_STATUS IN (1, 3, 29)
        ORDER BY R.REMISION_ID ASC
    ");
    if ($rr && is_array($rr)) {
        $remRows = $rr;
    }
} catch (Throwable $e) {}
$dbRem->close();

$tieneRemision = false;
$remisionesCompletas = false;
$subidasCount = 0;
if (count($remRows) > 0) {
    foreach ($remRows as $rItem) {
        if (!empty(trim($rItem['REMISION_ARCHIVO'] ?? ''))) {
            $subidasCount++;
        }
    }
    if ($subidasCount > 0) $tieneRemision = true;
    if ($subidasCount >= count($remRows)) $remisionesCompletas = true;
} else {
    // Si no hay remisiones, no se puede enviar a revisión
    $tieneRemision = false;
    $remisionesCompletas = false;
    $subidasCount = 0;
}

$maletasEscaneadas = [];
foreach ($evidencias as $evItem) {
    if (!empty($evItem['MALETA_ID'])) {
        $maletasEscaneadas[$evItem['MALETA_ID']] = true;
    }
}
$tieneEvidencias = count($maletasEscaneadas) >= $maletasCount && $maletasCount > 0;
$requiereEvidencias = ($maletasCount > 0);
$evidenciasCumplidas = $requiereEvidencias ? $tieneEvidencias : true;

$puedeEnviarRevision = $tieneRemision && $evidenciasCumplidas && $statusGeneral === 29;
$enRevision     = ($statusGeneral === 8);
$puedeFinalizarDirecto = ($statusGeneral === 29 && $enRevision === false); // legacy

$soloEstaRemision = ($remisionid > 0);
$folioRemisionActual = "";
if ($soloEstaRemision) {
    foreach ($remRows as $rTemp) {
        if ((int)$rTemp['REMISION_ID'] === $remisionid) {
            $folioRemisionActual = $rTemp['REMISION_FOLIO'] ?? $remisionid;
            break;
        }
    }
    if (empty($folioRemisionActual)) $folioRemisionActual = $remisionid;
}
?>

<style>
.archivo-recepcion-panel { max-width: 900px; margin: 0 auto; }
.ev-section-title {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #64748b;
    margin-bottom: 10px;
    padding-bottom: 4px;
    border-bottom: 2px solid #e2e8f0;
}
.ev-badge-check {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}
.ev-badge-ok   { background: #d1fae5; color: #065f46; }
.ev-badge-warn { background: #fef3c7; color: #92400e; }
.ev-badge-miss { background: #fee2e2; color: #991b1b; }
.evidencia-thumb {
    width: 90px;
    height: 90px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
    cursor: pointer;
    transition: transform 0.15s, border-color 0.15s;
}
.evidencia-thumb:hover {
    transform: scale(1.05);
    border-color: #8b5cf6;
}
.evidencia-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 10px;
}
.evidencia-item {
    text-align: center;
    font-size: 0.72rem;
    color: #475569;
    max-width: 100px;
}
.evidencia-item span {
    display: block;
    margin-top: 4px;
    word-break: break-all;
}
.btn-enviar-revision {
    background: linear-gradient(135deg, #7c3aed, #5b21b6);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 9px 22px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: opacity 0.2s, transform 0.15s;
}
.btn-enviar-revision:hover { opacity: 0.9; transform: translateY(-1px); color: #fff; }
.btn-enviar-revision:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
.btn-finalizar-ev {
    background: linear-gradient(135deg, #059669, #047857);
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 9px 22px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: opacity 0.2s, transform 0.15s;
}
.btn-finalizar-ev:hover { opacity: 0.9; transform: translateY(-1px); color: #fff; }
.revision-banner {
    background: linear-gradient(135deg, #ede9fe, #ddd6fe);
    border: 1px solid #c4b5fd;
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.revision-banner i { font-size: 1.4rem; color: #7c3aed; }
.revision-banner-text { font-size: 0.92rem; color: #4c1d95; font-weight: 500; }
</style>

<div class="archivo-recepcion-panel">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom" style="padding: 16px 22px;">
            <h5 class="mb-0" style="font-size:1rem;">
                <?php if ($soloEstaRemision): ?>
                    Archivo de Remisión #<b><?= htmlspecialchars($folioRemisionActual) ?></b> <span class="text-muted" style="font-size:0.85rem;">(Evento: <?= htmlspecialchars($ev['EVENTO_FOLIO']) ?>)</span>
                <?php else: ?>
                    Archivos de Remisión y Evidencias de Recepción — <b><?= htmlspecialchars($ev['EVENTO_FOLIO']) ?></b>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body" style="padding: 22px;">

            <?php if ($enRevision && !$soloEstaRemision): ?>
            <!-- BANNER: En Revisión -->
            <div class="revision-banner">
                <i class="fa fa-eye"></i>
                <div class="revision-banner-text">
                    Este evento está <b>En Revisión</b>. Verifica todos los archivos y evidencias antes de finalizar.
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$soloEstaRemision): ?>
            <!-- ======= CHECKLIST DE REQUISITOS ======= -->
            <div class="mb-4">
                <div class="ev-section-title">Estado de Requisitos</div>
                <div class="d-flex flex-wrap gap-2" style="gap:10px;">
                    <span class="ev-badge-check <?= $remisionesCompletas ? 'ev-badge-ok' : 'ev-badge-miss' ?>">
                        <i class="fa <?= $remisionesCompletas ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                        Archivos de Remisión (<?= count($remRows) > 0 ? $subidasCount . '/' . count($remRows) : 'Sin Remisiones (Requerido)' ?>)
                    </span>
                    <span class="ev-badge-check <?= $evidenciasCumplidas ? 'ev-badge-ok' : 'ev-badge-warn' ?>">
                        <i class="fa <?= $evidenciasCumplidas ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
                        Evidencias de Recepción <?= $requiereEvidencias ? '('.count($maletasEscaneadas).'/'.$maletasCount.')' : '(No requeridas)' ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <!-- ======= ARCHIVOS DE REMISIÓN ======= -->
            <div class="mb-4">
                <?php if (count($remRows) > 0 || $soloEstaRemision): ?>
                    <?php if (!$soloEstaRemision): ?>
                        <div class="ev-section-title">Archivos de Remisión por Folio (<?= count($remRows) ?> remisiones en el evento)</div>
                    <?php endif; ?>
                    <?php 
                    $filasAMostrar = $remRows;
                    if ($soloEstaRemision && count($filasAMostrar) === 0) {
                        $filasAMostrar = [['REMISION_ID' => $remisionid, 'REMISION_FOLIO' => $folioRemisionActual, 'REMISION_FECHA' => date('Y-m-d')]];
                    }
                    foreach ($filasAMostrar as $rItem): 
                        if ($soloEstaRemision && (int)$rItem['REMISION_ID'] !== $remisionid) {
                            continue;
                        }
                        $archivoThisRem = trim($rItem['REMISION_ARCHIVO'] ?? '');
                    ?>
                    <div class="card mb-3 border shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                            <span class="font-weight-bold text-dark"><i class="fa fa-file-text-o mr-1 text-danger"></i> Remisión #<?= htmlspecialchars($rItem['REMISION_FOLIO'] ?? $rItem['REMISION_ID']) ?></span>
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
                                    <span class="badge badge-warning text-dark py-1 px-2"><i class="fa fa-exclamation-triangle mr-1"></i> Pendiente subir archivo para esta remisión</span>
                                </div>
                            <?php endif; ?>

                            <?php if ($statusGeneral !== 30 && $statusGeneral !== 3 && $statusGeneral !== 8): ?>
                                <form class="formSubirRemisionIndividual" enctype="multipart/form-data">
                                    <input type="hidden" name="eventoid" value="<?= $eventoid ?>">
                                    <input type="hidden" name="remisionid" value="<?= $rItem['REMISION_ID'] ?>">
                                    <div class="d-flex align-items-center mt-2" style="gap:10px;">
                                        <input type="file" class="form-control-file" name="archivo_remision" accept="image/*,application/pdf" required>
                                        <button type="submit" class="btn btn-primary btn-sm font-weight-bold" style="white-space:nowrap;">
                                            <i class="fa fa-upload mr-1"></i> <?= !empty($archivoThisRem) ? 'Reemplazar' : 'Subir Archivo' ?>
                                        </button>
                                    </div>
                                </form>
                            <?php elseif ($statusGeneral === 8): ?>
                                <p class="text-muted mb-0" style="font-size:0.82rem;"><i class="fa fa-lock mr-1"></i> Bloqueado en revisión.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php if (!$soloEstaRemision): ?>
                    <div class="ev-section-title">Archivo de Remisión</div>
                    
                    <?php if ($tieneRemision && !empty(trim($archivoRem))): ?>
                    <div class="d-flex align-items-center mb-3" style="gap: 12px;">
                        <?php
                        $extRem = strtolower(pathinfo($archivoRem, PATHINFO_EXTENSION));
                        ?>
                        <?php if (in_array($extRem, ['jpg','jpeg','png'])): ?>
                            <img src="../<?= htmlspecialchars($archivoRem) ?>" 
                                 alt="Archivo Remisión" 
                                 style="height:70px; border-radius:6px; border:1px solid #e2e8f0; cursor:pointer;"
                                 onclick="window.open('../<?= htmlspecialchars($archivoRem) ?>', '_blank')">
                        <?php endif; ?>
                        <a href="../<?= htmlspecialchars($archivoRem) ?>" target="_blank" class="btn btn-sm btn-outline-primary font-weight-bold">
                            <i class="fa fa-external-link mr-1"></i> Ver / Descargar Archivo de Remisión
                        </a>
                    </div>
                    <?php endif; ?>

                    <?php if (count($remRows) === 0 && empty(trim($archivoRem))): ?>
                    <div class="alert alert-danger py-2 px-3 mb-0" style="font-size:0.85rem;">
                        <i class="fa fa-exclamation-triangle mr-1"></i>
                        Este evento no tiene ninguna remisión generada. Debes crear al menos una remisión antes de poder enviar el evento a revisión.
                    </div>
                    <?php endif; ?>

                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if (!$soloEstaRemision): ?>
            <!-- ======= EVIDENCIAS DE RECEPCIÓN (API) ======= -->
            <div class="mb-4">
                <div class="ev-section-title">Evidencias de Recepción (desde App Móvil)</div>

                <?php if (count($evidencias) === 0): ?>
                    <?php if (!$requiereEvidencias): ?>
                        <div class="alert alert-info py-2 px-3 mb-0" style="font-size:0.85rem;">
                            <i class="fa fa-info-circle mr-1"></i>
                            Este evento no tiene maletas asignadas, por lo tanto no se requiere evidencia de recepción desde la app móvil.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning py-2 px-3 mb-0" style="font-size:0.85rem;">
                            <i class="fa fa-exclamation-triangle mr-1"></i>
                            No hay evidencias de recepción registradas aún. El chofer o especialista debe subir las fotos de las maletas desde la aplicación móvil.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                <div class="evidencia-grid">
                    <?php foreach ($evidencias as $ev_item): ?>
                    <?php
                        $rutaFoto = $ev_item['RUTA_FOTO'] ?? '';
                        $proxyUrl = '../ajax/eventos.imagen.proxy.php?ruta=' . urlencode($rutaFoto);
                        $extEv    = strtolower(pathinfo($rutaFoto, PATHINFO_EXTENSION));
                        $esImagen = in_array($extEv, ['jpg','jpeg','png','gif','webp']);
                        $tipoOp   = htmlspecialchars($ev_item['TIPO_OPERACION'] ?? '');
                        $fechaReg = htmlspecialchars($ev_item['FECHA_REGISTRO'] ?? '');
                        $sello    = htmlspecialchars($ev_item['SELLO_NUMERO'] ?? '');
                    ?>
                    <div class="evidencia-item">
                        <?php if ($esImagen): ?>
                            <img src="<?= $proxyUrl ?>" 
                                 alt="Evidencia" 
                                 class="evidencia-thumb"
                                 onclick="verEvidenciaFull('<?= $proxyUrl ?>', '<?= $tipoOp ?>', '<?= $fechaReg ?>')"
                                 onerror="this.onerror=null; this.src=''; this.alt='Imagen no disponible';">
                        <?php else: ?>
                            <div style="width:90px;height:90px;background:#f1f5f9;border-radius:8px;border:2px solid #e2e8f0;display:flex;align-items:center;justify-content:center;">
                                <i class="fa fa-file" style="font-size:2rem;color:#94a3b8;"></i>
                            </div>
                        <?php endif; ?>
                        <span title="<?= $tipoOp ?>"><?= $tipoOp ?></span>
                        <?php if ($sello): ?><span>Sello: <?= $sello ?></span><?php endif; ?>
                        <span style="color:#94a3b8; font-size:0.68rem;"><?= $fechaReg ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size:0.78rem;">
                    <i class="fa fa-info-circle mr-1"></i>
                    <?= count($evidencias) ?> evidencia(s) registrada(s). Haz clic en cada imagen para verla en tamaño completo.
                </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!$soloEstaRemision): ?>
            <!-- ======= BOTONES DE ACCIÓN ======= -->
            <div class="d-flex justify-content-between align-items-center pt-3" style="border-top: 1px solid #e2e8f0;">
                <div></div>
                <div class="d-flex" style="gap: 10px;">

                    <?php if ($statusGeneral === 29 && $puedeEnviarRevision): ?>
                    <!-- BOTÓN: Enviar a Revisión -->
                        <button type="button" class="btn-enviar-revision" id="btnEnviarRevision"
                                onclick="enviarRevision(<?= $eventoid ?>, '<?= htmlspecialchars($ev['EVENTO_FOLIO']) ?>')">
                            <i class="fa fa-paper-plane mr-1"></i> Enviar a Revisión
                        </button>
                    <?php endif; ?>

                    <?php if ($statusGeneral === 8): ?>
                    <!-- BOTÓN: Finalizar Evento (solo en revisión) -->
                    <button type="button" class="btn-finalizar-ev"
                            onclick="finalizarEvento(<?= $eventoid ?>, '<?= htmlspecialchars($ev['EVENTO_FOLIO']) ?>')">
                        <i class="fa fa-check-circle mr-1"></i> Finalizar Evento
                    </button>
                    <?php endif; ?>

                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Modal de vista previa de evidencia -->
<div id="modalEvidenciaFull" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
     background:rgba(0,0,0,0.85); z-index:99999; align-items:center; justify-content:center; flex-direction:column;">
    <div style="position:relative; max-width:90vw; max-height:85vh;">
        <img id="imgEvidenciaFull" src="" alt="Evidencia" 
             style="max-width:90vw; max-height:80vh; border-radius:8px; object-fit:contain; display:block;">
        <div id="lblEvidenciaInfo" style="text-align:center; color:#fff; margin-top:10px; font-size:0.85rem;"></div>
    </div>
    <button onclick="cerrarEvidencia()" 
            style="margin-top:16px; background:#fff; border:none; border-radius:6px; padding:8px 20px; font-weight:600; cursor:pointer;">
        <i class="fa fa-times mr-1"></i> Cerrar
    </button>
</div>

<script>
// ======= GUARDAR ARCHIVO DE REMISIÓN =======
$(document).ready(function() {
    $('#formArchivosRecepcion').on('submit', function(e) {
        e.preventDefault();

        var fileInput = document.getElementById('archivo_remision');
        if (!fileInput || !fileInput.files.length) {
            Swal.fire({ title: 'Atención', text: 'Selecciona un archivo antes de guardar.', icon: 'warning',
                customClass: { confirmButton: 'btn btn-warning' } });
            return;
        }

        var formData = new FormData(this);
        $.ajax({
            url: '../ajax/eventos.archivos_recepcion.guardar.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    var resJson = typeof response === 'object' ? response : JSON.parse(response);
                    if (resJson.success) {
                        if (resJson.auto_revision) {
                            Swal.fire({
                                title: "¡Paso Automático a Revisión!",
                                html: resJson.msg,
                                icon: "success",
                                customClass: { confirmButton: 'btn btn-success font-weight-bold px-4' }
                            }).then(() => { location.reload(); });
                        } else {
                            Swal.fire({
                                title: "¡Guardado!",
                                text: resJson.msg || "Archivo de remisión guardado correctamente.",
                                icon: "success",
                                customClass: { confirmButton: 'btn btn-success font-weight-bold px-4' }
                            }).then(() => { location.reload(); });
                        }
                    } else {
                        Swal.fire({ title: "Atención", html: resJson.error || "No se pudo guardar", icon: "warning", customClass: { confirmButton: 'btn btn-warning' } });
                    }
                } catch(e) {
                    if (response === "" || (typeof response === 'string' && response.trim() === "")) {
                        Swal.fire({
                            title: "¡Guardado!",
                            text: "Archivo guardado correctamente.",
                            icon: "success",
                            customClass: { confirmButton: 'btn btn-success' }
                        }).then(() => { location.reload(); });
                    } else {
                        var msg = typeof response === 'object' ? (response.error || JSON.stringify(response)) : response;
                        Swal.fire({ title: "Atención", html: msg, icon: "warning", customClass: { confirmButton: 'btn btn-warning' } });
                    }
                }
            },
            error: function() {
                Swal.fire({ title: "Error", text: "Error al procesar la solicitud.", icon: "error" });
            },
            complete: function() { $("#loading").hide(); }
        });
    });

    $(document).on('submit', '.formSubirRemisionIndividual', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            url: '../ajax/eventos.archivos_recepcion.guardar.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() { $("#loading").show(); },
            success: function(response) {
                try {
                    var resJson = typeof response === 'object' ? response : JSON.parse(response);
                    if (resJson.success) {
                        if (resJson.auto_revision) {
                            Swal.fire({
                                title: "¡Paso Automático a Revisión!",
                                html: resJson.msg,
                                icon: "success",
                                customClass: { confirmButton: 'btn btn-success font-weight-bold px-4' }
                            }).then(() => { location.reload(); });
                        } else {
                            Swal.fire({
                                title: "¡Guardado!",
                                text: resJson.msg || "Archivo de remisión guardado correctamente.",
                                icon: "success",
                                customClass: { confirmButton: 'btn btn-success font-weight-bold px-4' }
                            }).then(() => { location.reload(); });
                        }
                    } else {
                        Swal.fire({ title: "Atención", html: resJson.error || "No se pudo guardar", icon: "warning", customClass: { confirmButton: 'btn btn-warning' } });
                    }
                } catch(e) {
                    if (response === "" || (typeof response === 'string' && response.trim() === "")) {
                        Swal.fire({ title: "¡Guardado!", text: "Archivo guardado correctamente.", icon: "success" }).then(() => { location.reload(); });
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

// ======= ENVIAR A REVISIÓN =======
function enviarRevision(eventoid, folio) {
    Swal.fire({
        title: '¿Enviar a revisión?',
        html: 'Se enviará el evento <b>' + folio + '</b> a revisión.<br>Ya no podrás modificar el archivo de remisión.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, enviar a revisión',
        cancelButtonText: 'Cancelar',
        customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-secondary' },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../ajax/eventos.enviar.revision.php',
                type: 'POST',
                data: { eventoid: eventoid },
                beforeSend: function() { $("#loading").show(); },
                success: function(response) {
                    if (response === "") {
                        Swal.fire({
                            title: "¡Enviado!",
                            text: "El evento fue enviado a revisión correctamente.",
                            icon: "success",
                            customClass: { confirmButton: 'btn btn-success' }
                        }).then(() => { location.reload(); });
                    } else {
                        Swal.fire({ title: "Error", html: response, icon: "warning",
                            customClass: { confirmButton: 'btn btn-warning' } });
                    }
                },
                error: function() {
                    Swal.fire({ title: "Error", text: "Error al procesar la solicitud.", icon: "error" });
                },
                complete: function() { $("#loading").hide(); }
            });
        }
    });
}

// ======= FINALIZAR EVENTO =======
function finalizarEvento(eventoid, folio) {
    if (typeof mostrarConfirmacionEvento === 'function') {
        $("#modalglobal").modal('hide');
        setTimeout(function() {
            mostrarConfirmacionEvento(eventoid, folio);
        }, 300);
        return;
    }
    Swal.fire({
        title: '¿Finalizar evento?',
        html: '¿Estás seguro que deseas <b>finalizar</b> el evento <b>' + folio + '</b>?<br>Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, finalizar',
        cancelButtonText: 'Cancelar',
        customClass: { confirmButton: 'btn btn-success', cancelButton: 'btn btn-secondary' },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../ajax/eventos.update.statusgeneral.php',
                type: 'POST',
                data: { id: eventoid, status: 3 },
                beforeSend: function() { $("#loading").show(); },
                success: function(response) {
                    Swal.fire({
                        title: "¡Finalizado!",
                        text: "El evento " + folio + " ha sido finalizado.",
                        icon: "success",
                        customClass: { confirmButton: 'btn btn-success' }
                    }).then(() => { location.reload(); });
                },
                error: function() {
                    Swal.fire({ title: "Error", text: "Error al finalizar el evento.", icon: "error" });
                },
                complete: function() { $("#loading").hide(); }
            });
        }
    });
}

// ======= VISTA PREVIA DE EVIDENCIAS =======
function verEvidenciaFull(url, tipo, fecha) {
    document.getElementById('imgEvidenciaFull').src = url;
    document.getElementById('lblEvidenciaInfo').textContent = tipo + (fecha ? ' — ' + fecha : '');
    var modal = document.getElementById('modalEvidenciaFull');
    modal.style.display = 'flex';
}
function cerrarEvidencia() {
    document.getElementById('modalEvidenciaFull').style.display = 'none';
    document.getElementById('imgEvidenciaFull').src = '';
}
// Cerrar con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') cerrarEvidencia();
});
// Cerrar al hacer clic fuera de la imagen
document.getElementById('modalEvidenciaFull').addEventListener('click', function(e) {
    if (e.target === this) cerrarEvidencia();
});
</script>
