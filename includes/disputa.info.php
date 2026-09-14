<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$esAdmin = false;
if (isset($_SESSION['ampar']['perfiles']) && is_array($_SESSION['ampar']['perfiles'])) {
    foreach ($_SESSION['ampar']['perfiles'] as $p) {
        if (isset($p['PERFIL_ID']) && $p['PERFIL_ID'] == 1) {
            $esAdmin = true;
            break;
        }
    }
}

$db = new FirebirdConnection();

// Traer info de la disputa
$sql = "
    SELECT D.*, 
           T.TRASPASO_FOLIO, T.TRASPASO_FACTURA, T.TRASPASO_DOCUMENTO2, T.TRASPASO_EVIDENCIA, T.TRASPASO_GUIA,
           U1.USUARIO_NOMBRE as NOMBRE_CREADOR,
           U2.USUARIO_NOMBRE as NOMBRE_DESTINO
    FROM AMPAR_DISPUTAS D
    LEFT JOIN AMPAR_HIS_TRASPASO T ON T.TRASPASO_ID = D.DISPUTA_TRASPASOID
    LEFT JOIN AMPAR_CAT_USUARIOS U1 ON U1.USUARIO_ID = D.DISPUTA_USUARIO_CREADOR
    LEFT JOIN AMPAR_CAT_USUARIOS U2 ON U2.USUARIO_ID = D.DISPUTA_USUARIO_DESTINO
    WHERE D.DISPUTA_ID = ?
";
$res = $db->query($sql, [$id]);

if(empty($res)) {
    echo "<div class='modal-body'><div class='alert alert-danger'>Disputa no encontrada.</div></div>";
    exit;
}

$d = $res[0];

// Traer evidencias
$sqlEv = "SELECT * FROM AMPAR_DISPUTAS_EVIDENCIAS WHERE EVIDENCIA_DISPUTAID = ?";
$evidencias = $db->query($sqlEv, [$id]);
$db->close();
?>

<div class="modal-header" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: white; border-bottom: 0;">
    <h5 class="modal-title font-weight-bold" style="font-size: 1.1rem;">
        <i class="mdi mdi-alert-decagram mr-2" style="color: #f87171; font-size: 1.3rem; vertical-align: middle;"></i> 
        Disputa #<?= $d['DISPUTA_ID'] ?> 
        <span style="font-weight: 400; opacity: 0.8; font-size: 0.9rem; margin-left: 8px; border-left: 1px solid rgba(255,255,255,0.3); padding-left: 10px;">Traspaso <?= $d['TRASPASO_FOLIO'] ?></span>
    </h5>
    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="opacity: 0.8; text-shadow: none;">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<div class="modal-body text-left" style="background-color: #f8fafc; padding: 25px;">
    <div class="row bg-white p-3 rounded shadow-sm mb-4" style="border: 1px solid #e2e8f0; margin: 0;">
        <div class="col-md-6 mb-3 mb-md-0 border-right">
            <p class="mb-1 text-muted" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;"><strong>Iniciada por (Receptor):</strong></p>
            <h6 class="mb-0 text-dark font-weight-bold"><?= $d['NOMBRE_CREADOR'] ?></h6>
        </div>
        <div class="col-md-6">
            <p class="mb-1 text-muted" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;"><strong>Emisor del Traspaso:</strong></p>
            <h6 class="mb-0 text-dark font-weight-bold"><?= $d['NOMBRE_DESTINO'] ?></h6>
        </div>
    </div>
    
    <div class="alert shadow-sm" style="background: linear-gradient(to right, #fffbeb, #fef3c7); border-left: 4px solid #f59e0b; border-radius: 8px; color: #92400e;">
        <p class="mb-2"><i class="mdi mdi-comment-alert-outline mr-1"></i><strong>Motivo de la disputa:</strong> <?= htmlspecialchars($d['DISPUTA_MOTIVO']) ?></p>
        <div style="background: rgba(255,255,255,0.6); padding: 10px; border-radius: 6px; font-size: 0.9rem;">
            <strong>Detalle:</strong><br><?= nl2br(htmlspecialchars($d['DISPUTA_DETALLE'])) ?>
        </div>
    </div>

    <!-- EVIDENCIAS DEL EMISOR (Quien envió) -->
    <h6 class="mt-4 border-bottom pb-2 text-primary font-weight-bold"><i class="mdi mdi-upload text-primary mr-1"></i> Evidencias aportadas por el Emisor (al enviar)</h6>
    <?php 
    $emisorEvidencias = [];
    if(!empty($d['TRASPASO_FACTURA'])) $emisorEvidencias[] = ['ruta' => $d['TRASPASO_FACTURA'], 'titulo' => 'Factura'];
    if(!empty($d['TRASPASO_DOCUMENTO2'])) $emisorEvidencias[] = ['ruta' => $d['TRASPASO_DOCUMENTO2'], 'titulo' => 'Documento Adicional'];
    if(!empty($d['TRASPASO_EVIDENCIA'])) $emisorEvidencias[] = ['ruta' => $d['TRASPASO_EVIDENCIA'], 'titulo' => 'Evidencia'];
    if(!empty($d['TRASPASO_GUIA'])) $emisorEvidencias[] = ['ruta' => $d['TRASPASO_GUIA'], 'titulo' => 'Guía de Paquetería'];

    if(count($emisorEvidencias) > 0) { ?>
        <div class="row mt-3">
            <?php foreach($emisorEvidencias as $ev) { 
                $ext = strtolower(pathinfo($ev['ruta'], PATHINFO_EXTENSION));
            ?>
                <div class="col-4 col-md-3 mb-3 text-center">
                    <?php if(in_array($ext, ['pdf'])) { ?>
                        <a href="../<?= htmlspecialchars($ev['ruta']) ?>" target="_blank" class="d-block border p-2 rounded text-decoration-none bg-light">
                            <i class="mdi mdi-file-pdf" style="font-size:3rem; color:#dc3545;"></i><br>
                            <span style="font-size:0.75rem; color:#333;"><?= $ev['titulo'] ?></span>
                        </a>
                    <?php } else { ?>
                        <a href="../<?= htmlspecialchars($ev['ruta']) ?>" data-lightbox="disputa-emisor" data-title="<?= $ev['titulo'] ?>">
                            <div style="width:100%; height:80px; border-radius:6px; background:url('../<?= htmlspecialchars($ev['ruta']) ?>') center/cover;"></div>
                        </a>
                        <span style="font-size:0.75rem; color:#666;"><?= $ev['titulo'] ?></span>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <p class="text-muted">No se adjuntaron evidencias al crear el traspaso.</p>
    <?php } ?>

    <!-- EVIDENCIAS DEL RECEPTOR (Quien recibió y disputó) -->
    <h6 class="mt-4 border-bottom pb-2 text-danger font-weight-bold"><i class="mdi mdi-alert-circle text-danger mr-1"></i> Evidencias aportadas por el Receptor (al disputar)</h6>
    <?php if($evidencias <> 0 && count($evidencias) > 0) { ?>
        <div class="row mt-3">
            <?php foreach($evidencias as $ev) { 
                $ext = strtolower(pathinfo($ev['EVIDENCIA_RUTA'], PATHINFO_EXTENSION));
            ?>
                <div class="col-6 col-md-4 mb-3 text-center">
                    <?php if($ext == 'pdf') { ?>
                        <a href="../<?= $ev['EVIDENCIA_RUTA'] ?>" target="_blank" class="btn btn-outline-danger btn-sm w-100 h-100 d-flex flex-column align-items-center justify-content-center" style="min-height: 100px;">
                            <i class="mdi mdi-file-pdf" style="font-size: 2rem;"></i>
                            <span>Ver PDF</span>
                        </a>
                    <?php } else { ?>
                        <a href="../<?= $ev['EVIDENCIA_RUTA'] ?>" target="_blank">
                            <img src="../<?= $ev['EVIDENCIA_RUTA'] ?>" class="img-fluid rounded border" style="max-height: 150px; object-fit: cover; width: 100%;">
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <p class="text-muted">No se adjuntaron evidencias.</p>
    <?php } ?>

    <?php if($d['DISPUTA_STATUS'] == 2) { 
        $ganador = strpos($d['DISPUTA_RESOLUCION_ADMIN'], '[GANADOR: EMISOR]') !== false ? 'Emisor del Traspaso' : (strpos($d['DISPUTA_RESOLUCION_ADMIN'], '[GANADOR: RECEPTOR]') !== false ? 'Receptor del Traspaso' : 'No especificado');
        $resolucionLimpia = str_replace(['[GANADOR: EMISOR]', '[GANADOR: RECEPTOR]'], '', $d['DISPUTA_RESOLUCION_ADMIN']);
    ?>
        <h6 class="mt-5 border-bottom pb-2 text-success font-weight-bold"><i class="mdi mdi-check-decagram mr-1"></i> Resolución del Administrador</h6>
        <div class="alert alert-success mt-3 shadow-sm border-0" style="background: linear-gradient(to right, #ecfdf5, #d1fae5); border-left: 4px solid #10b981 !important; color: #065f46;">
            <p class="mb-2"><i class="mdi mdi-gavel mr-1"></i> <strong>Fallo a favor del:</strong> <?= $ganador ?></p>
            <div class="p-2 rounded mt-2" style="background: rgba(255,255,255,0.7);">
                <?= nl2br(htmlspecialchars(trim($resolucionLimpia))) ?>
            </div>
            <div class="text-right mt-2"><small class="text-muted"><i class="mdi mdi-calendar-clock"></i> Resuelta el: <?= date('d/m/Y H:i', strtotime($d['DISPUTA_FECHA_RESOLUCION'])) ?></small></div>
        </div>
    <?php } else if ($esAdmin) { ?>
        <!-- Formulario de Resolución para el Administrador -->
        <h6 class="mt-5 border-bottom pb-2 text-primary font-weight-bold"><i class="mdi mdi-gavel mr-1"></i> Resolver Disputa</h6>
        <div class="card bg-light border-0 shadow-sm mt-3">
            <div class="card-body p-4">
                <form id="form-resolver-disputa">
                    <input type="hidden" name="disputaid" value="<?= $id ?>">
                    
                    <div class="form-group mb-4">
                        <label class="text-dark"><strong>Fallo a favor del: <span class="text-danger">*</span></strong></label><br>
                        <div class="custom-control custom-radio custom-control-inline mt-2">
                            <input type="radio" id="ganadorEmisor" name="ganador" class="custom-control-input" value="EMISOR" required>
                            <label class="custom-control-label" for="ganadorEmisor">Emisor <span class="text-muted" style="font-size: 0.85rem;">(Quien envió)</span></label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline mt-2">
                            <input type="radio" id="ganadorReceptor" name="ganador" class="custom-control-input" value="RECEPTOR" required>
                            <label class="custom-control-label" for="ganadorReceptor">Receptor <span class="text-muted" style="font-size: 0.85rem;">(Quien inició la disputa)</span></label>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="text-dark"><strong>Decisión / Instrucciones: <span class="text-danger">*</span></strong></label>
                        <textarea name="resolucion" class="form-control" rows="4" required placeholder="Escriba la resolución del caso. Esta información será enviada a ambas partes y el traspaso se desbloqueará o cancelará según su fallo." style="border-radius: 8px; border: 1px solid #ced4da;"></textarea>
                    </div>
                </form>
            </div>
        </div>
    <?php } ?>
</div>

<div class="modal-footer bg-light" style="border-top: 1px solid #e2e8f0;">
    <button type="button" class="btn btn-light border shadow-sm" data-dismiss="modal">Cerrar</button>
    <?php if($esAdmin && $d['DISPUTA_STATUS'] == 1) { ?>
        <button type="button" class="btn btn-success shadow-sm" id="btn-guardar-resolucion" style="background-color: #10b981; border-color: #10b981;"><i class="mdi mdi-check-all mr-1"></i> Confirmar Resolución</button>
    <?php } ?>
</div>

<?php if($esAdmin && $d['DISPUTA_STATUS'] == 1) { ?>
<script>
    $('#btn-guardar-resolucion').click(function() {
        var form = $('#form-resolver-disputa')[0];
        if(!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        var resolucion = $(form).find('textarea[name="resolucion"]').val();
        if(!confirm('¿Estás seguro de cerrar esta disputa con la resolución escrita? Esta acción notificará a las partes y desbloqueará el traspaso.')) {
            return;
        }

        $.ajax({
            url: '../ajax/disputas.resolver.php',
            type: 'POST',
            data: $(form).serialize(),
            beforeSend: function() {
                $('#btn-guardar-resolucion').prop('disabled', true).text('Guardando...');
            },
            success: function(response) {
                if(response.trim() === 'OK') {
                    alert('Disputa resuelta exitosamente.');
                    $('#modalglobal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response);
                    $('#btn-guardar-resolucion').prop('disabled', false).html('<i class="mdi mdi-check-all mr-1"></i> Resolver Disputa');
                }
            },
            error: function() {
                alert('Error de conexión.');
                $('#btn-guardar-resolucion').prop('disabled', false).html('<i class="mdi mdi-check-all mr-1"></i> Resolver Disputa');
            }
        });
    });
</script>
<?php } ?>
