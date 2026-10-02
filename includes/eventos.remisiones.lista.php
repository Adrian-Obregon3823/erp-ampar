<?php
require_once '../includes/includes.php';
$eventoid = isset($_GET['eventoid']) ? base64_decode($_GET['eventoid']) : 0;
$remisiones_obj = new remisiones();
$todas = $remisiones_obj->getremisiones();
$res = [];
if ($todas) {
    foreach ($todas as $t) {
        if ($t['REMISION_EVENTOID'] == $eventoid) {
            $res[] = $t;
        }
    }
}
?>
<?php if (isset($_GET['format']) && $_GET['format'] === 'row'): ?>
    <div class="table-responsive p-3 bg-light rounded" style="box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);">
        <h6 class="font-weight-bold mb-3" style="color: #2c3e50;"><i class="mdi mdi-format-list-bulleted mr-2"></i> Remisiones del Evento</h6>
<?php else: ?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Remisiones del Evento</h4>
        </div>
        <div class="card-body">
<?php endif; ?>
            <!-- Desktop Table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover m-0" style="border-collapse: separate; border-spacing: 0;">
                    <thead style="background-color: #e2e8f0; color: #334155;">
                        <tr>
                            <th style="border: none; padding: 12px 16px; width: 10%;">Folio</th>
                            <th style="border: none; padding: 12px 16px; width: 15%;">Status</th>
                            <th style="border: none; padding: 12px 16px; width: 20%;">Fecha</th>
                            <th style="border: none; padding: 12px 16px; width: 25%;">Sucursal</th>
                            <th style="border: none; padding: 12px 16px; width: 15%;">Total</th>
                            <th style="border: none; padding: 12px 16px; width: 15%; text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($res)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No hay remisiones para este evento.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($res as $r): ?>
                                <tr>
                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><b><?= $r['REMISION_FOLIO'] ?></b></td>
                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9; font-weight:bolder; color:#<?= $r['STATUS_COLOR'] ?>"><?= strtoupper($r['STATUS_NOMBRE']) ?></td>
                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><?= substr($r['REMISION_FECHA'], 0, 10) ?></td>
                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><?= $r['NOMBRE'] ?></td>
                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9;"><b>$<?= number_format($r['TOTAL'], 2, ".", ",") ?></b></td>
                                    <td style="padding: 14px 16px; vertical-align: middle; border-top: 1px solid #f1f5f9; text-align: right;">
                                        <div class="d-flex justify-content-end align-items-center" style="gap: 8px;">
                                            <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Nota de Remisión" data-url="../includes/remisiones.info.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:25px; height:auto; cursor:pointer;" title="Información"></a>
                                            <?php if ($r['REMISION_STATUS'] != 1) { ?>
                                                <a href="../gdocs/remisiones.nuevo.formato.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" target="_blank"><img src="../img/pdf.png" style="width:25px; height:auto;" title="Nota de Remisión"></a>
                                            <?php } ?>
                                            <?php if ($r['REMISION_STATUS'] == 1) { ?>
                                                <a data-toggle="modal" data-target="#modalglobal" data-title="Datos Complementarios" data-url="../includes/remisiones.datos_extra.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/editar.webp" style="width:25px; height:auto; cursor:pointer;" title="Datos Extra"></a>
                                                <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Nota de Remisión" data-url="../includes/remisiones.editar.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:25px; height:auto; cursor:pointer;" title="Editar"></a>
                                                
                                                <?php
                                                $tieneDatosExtra = !empty(trim($r['EVENTO_NOMBREPARTICULAR'] ?? '')) || !empty(trim($r['REMISION_NUMPACIENTE'] ?? '')) || !empty(trim($r['REMISION_RFC'] ?? ''));
                                                ?>
                                                <?php if ($tieneDatosExtra): ?>
                                                    <img src="../img/send.png" style="width:25px; height:auto; cursor:pointer; margin-left:12px;" onclick="changeremisionstatus('<?= $r['REMISION_ID'] ?>',29,'<?= $r['REMISION_FOLIO'] ?>')" title="Enviar a Recepción">
                                                <?php else: ?>
                                                    <img src="../img/send.png" style="width:25px; height:auto; cursor:pointer; opacity:0.5; margin-left:12px;" onclick="Swal.fire('Atención', 'Debe llenar los Datos Complementarios (Nombre del Paciente, etc.) antes de pasar a recepción.', 'warning')" title="Debe llenar Datos Extra">
                                                <?php endif; ?>
                                                
                                                <img src="../img/rechazar.png" style="width:25px; height:auto; cursor:pointer;" onclick="changeremisionstatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                            <?php } ?>
                                            <?php if ($r['REMISION_STATUS'] == 29) { ?>
                                                <a data-toggle="modal" data-target="#modalglobal" data-title="Archivo de Remisión y Evidencias" data-url="../includes/eventos.archivos_recepcion.php?eventoid=<?= base64_encode($r['REMISION_EVENTOID']) ?>&remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/upload.jpg" style="width:25px; height:auto; cursor:pointer;" title="Subir Archivo de esta Remisión"></a>
                                                <img src="../img/check.png" style="width:25px; height:auto; cursor:pointer;" onclick="changeremisionstatus('<?= $r['REMISION_ID'] ?>',3,'<?= $r['REMISION_FOLIO'] ?>')" title="Finalizar Remisión">
                                                <img src="../img/rechazar.png" style="width:25px; height:auto; cursor:pointer;" onclick="changeremisionstatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile View (Cards) -->
            <div class="d-block d-md-none mt-2">
                <?php if (empty($res)): ?>
                    <div class="alert alert-info text-center">No hay remisiones para este evento.</div>
                <?php else: ?>
                    <?php foreach ($res as $r): ?>
                        <div class="card mb-3" style="border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 1px solid #f1f5f9;">
                                    <h5 class="m-0 font-weight-bold" style="color: #0f172a;"><?= $r['REMISION_FOLIO'] ?></h5>
                                    <span style="font-size: 0.75rem; font-weight: bold; padding: 4px 8px; border-radius: 4px; border: 1px solid #<?= $r['STATUS_COLOR'] ?>; color: #<?= $r['STATUS_COLOR'] ?>; background-color: rgba(var(--<?= $r['STATUS_COLOR'] ?>-rgb), 0.1);"><?= strtoupper($r['STATUS_NOMBRE']) ?></span>
                                </div>
                                
                                <div class="row mb-2">
                                    <div class="col-4 text-muted small">Fecha</div>
                                    <div class="col-8 text-right font-weight-bold" style="color: #334155;"><?= substr($r['REMISION_FECHA'], 0, 10) ?></div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-4 text-muted small">Sucursal</div>
                                    <div class="col-8 text-right" style="color: #334155;"><?= $r['NOMBRE'] ?></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-4 text-muted small">Total</div>
                                    <div class="col-8 text-right font-weight-bold" style="font-size: 1.1rem; color: #0f172a;">$<?= number_format($r['TOTAL'], 2, ".", ",") ?></div>
                                </div>

                                <div class="d-flex justify-content-around align-items-center pt-3" style="border-top: 1px solid #f1f5f9;">
                                    <a data-toggle="modal" data-target="#modalglobal" data-title="Información de Nota de Remisión" data-url="../includes/remisiones.info.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/info.png" style="width:30px; height:auto; cursor:pointer;" title="Información"></a>
                                    
                                    <?php if ($r['REMISION_STATUS'] != 1) { ?>
                                        <a href="../gdocs/remisiones.nuevo.formato.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" target="_blank"><img src="../img/pdf.png" style="width:30px; height:auto;" title="Nota de Remisión"></a>
                                    <?php } ?>
                                    
                                    <?php if ($r['REMISION_STATUS'] == 1) { ?>
                                        <a data-toggle="modal" data-target="#modalglobal" data-title="Datos Complementarios" data-url="../includes/remisiones.datos_extra.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/editar.webp" style="width:30px; height:auto; cursor:pointer;" title="Datos Extra"></a>
                                        <a data-toggle="modal" data-target="#modalglobal" data-title="Edición de Nota de Remisión" data-url="../includes/remisiones.editar.php?remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/edit.png" style="width:30px; height:auto; cursor:pointer;" title="Editar"></a>
                                        
                                        <?php
                                        $tieneDatosExtra = !empty(trim($r['EVENTO_NOMBREPARTICULAR'] ?? '')) || !empty(trim($r['REMISION_NUMPACIENTE'] ?? '')) || !empty(trim($r['REMISION_RFC'] ?? ''));
                                        ?>
                                        <?php if ($tieneDatosExtra): ?>
                                            <img src="../img/send.png" style="width:30px; height:auto; cursor:pointer;" onclick="changeremisionstatus('<?= $r['REMISION_ID'] ?>',29,'<?= $r['REMISION_FOLIO'] ?>')" title="Enviar a Recepción">
                                        <?php else: ?>
                                            <img src="../img/send.png" style="width:30px; height:auto; cursor:pointer; opacity:0.5;" onclick="Swal.fire('Atención', 'Debe llenar los Datos Complementarios (Nombre del Paciente, etc.) antes de pasar a recepción.', 'warning')" title="Debe llenar Datos Extra">
                                        <?php endif; ?>
                                        
                                        <img src="../img/rechazar.png" style="width:30px; height:auto; cursor:pointer;" onclick="changeremisionstatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                    <?php } ?>
                                    
                                    <?php if ($r['REMISION_STATUS'] == 29) { ?>
                                        <a data-toggle="modal" data-target="#modalglobal" data-title="Archivo de Remisión y Evidencias" data-url="../includes/eventos.archivos_recepcion.php?eventoid=<?= base64_encode($r['REMISION_EVENTOID']) ?>&remisionid=<?= base64_encode($r['REMISION_ID']) ?>" aria-selected="false"><img src="../img/upload.jpg" style="width:30px; height:auto; cursor:pointer;" title="Subir Archivo de esta Remisión"></a>
                                        <img src="../img/check.png" style="width:30px; height:auto; cursor:pointer;" onclick="changeremisionstatus('<?= $r['REMISION_ID'] ?>',3,'<?= $r['REMISION_FOLIO'] ?>')" title="Finalizar Remisión">
                                        <img src="../img/rechazar.png" style="width:30px; height:auto; cursor:pointer;" onclick="changeremisionstatus('<?= $r['REMISION_ID'] ?>',5,'<?= $r['REMISION_FOLIO'] ?>')" title="Cancelar">
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
<?php if (isset($_GET['format']) && $_GET['format'] === 'row'): ?>
<?php else: ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function changeremisionstatus(id, status, folio) {
    var accion = "";
    var done = "";

    switch (status) {
        case 29:
            accion = "enviar a recepción";
            done = "enviada a recepción";
            break;
        case 3:
            accion = "finalizar";
            done = "finalizada";
            break;
        case 5:
            accion = "cancelar";
            done = "cancelada";
            break;
        default:
    }

    Swal.fire({
        text: '¿Seguro que deseas ' + accion + ' la nota de Remisión con Folio: ' + folio + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, ' + accion,
        cancelButtonText: 'Cancelar',
        customClass: {
            confirmButton: 'btn btn-success',
            cancelButton: 'btn btn-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../ajax/remisiones.update.status.php',
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
                    if (response.trim() === "") {
                        Swal.fire({
                            html: "Nota de remisión " + done + " con éxito",
                            icon: "success",
                            customClass: {
                                confirmButton: 'btn btn-success'
                            }
                        }).then(() => {
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
                error: function(xhr, status, error) {
                    console.error('Error en la solicitud:', error);
                },
                complete: function(data) {
                    $("#loading").hide();
                }
            });
        }
    });
}
</script>
