<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");
include_once("../class/oc.php");

$ocid = isset($_GET['ocid']) ? (int)$_GET['ocid'] : 0;

if ($ocid == 0) {
    echo '<div class="alert alert-danger">ID de Orden de Compra inválido.</div>';
    exit;
}

$oc = new oc();
$info = $oc->getocbyid($ocid);

if (empty($info)) {
    echo '<div class="alert alert-warning">No se encontraron detalles para esta Orden de Compra.</div>';
    exit;
}

$db = new FirebirdConnection();

// Obtenemos historial de recepciones para restar lo ya recibido
$sqlHistorial = "
    SELECT RECDET_ARTICULOID, SUM(RECDET_CANTIDAD_RECIBIDA) AS TOTAL_RECIBIDO
    FROM AMPAR_RECEPCIONDET
    JOIN AMPAR_RECEPCION ON RECEPCION_ID = RECDET_RECEPCIONID
    WHERE RECEPCION_OCID = ?
    GROUP BY RECDET_ARTICULOID
";
$historial = $db->query($sqlHistorial, [$ocid]);
$recibidoPorArticulo = [];
foreach ($historial as $h) {
    $recibidoPorArticulo[$h['RECDET_ARTICULOID']] = (float)$h['TOTAL_RECIBIDO'];
}
$db->close();

// Construir lista de artículos pendientes
$articulos_pendientes = [];
foreach ($info as $row) {
    $artid = $row['OCDET_ARTICULOID'];
    $pedido = (float)$row['OCDET_CANTIDAD'];
    $costo  = (float)$row['OCDET_PRECIO'];
    $yaRecibido = $recibidoPorArticulo[$artid] ?? 0;
    $pendiente = $pedido - $yaRecibido;

    if ($pendiente > 0) {
        $articulos_pendientes[] = [
            'artid'     => $artid,
            'clave'     => $row['CLAVE_ARTICULO'],
            'nombre'    => $row['ARTICULO_NOMBRE'],
            'pedido'    => $pedido,
            'pendiente' => $pendiente,
            'costo'     => $costo,
        ];
    }
}
?>

<style>
/* ── Tabla lista de artículos pendientes ── */
#tabla-articulos-pendientes thead th {
    background-color: #f4f6f8;
    color: #495057;
    font-size: 0.82rem;
    font-weight: 600;
    text-transform: uppercase;
    border-top: none;
    border-bottom: 2px solid #e9ecef;
}
#tabla-articulos-pendientes tbody td {
    vertical-align: middle;
    font-size: 0.9rem;
    border-color: #f1f3f5;
}

/* Badge cant. recibida en tabla */
.badge-cant-recibida {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.82rem;
    background: #e9ecef;
    color: #495057;
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 2px 8px;
    font-weight: 600;
}
.badge-cant-recibida.completado {
    background: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

/* Botón abrir modal Lote */
.btn-abrir-modal-lotes {
    font-size: 0.82rem;
    padding: 4px 12px;
    border-radius: 4px;
    font-weight: 600;
    background-color: #1c3a6b;
    color: #fff;
    border: none;
    transition: background .15s;
}
.btn-abrir-modal-lotes:hover:not(:disabled) { background-color: #15305a; color: #fff; }
.btn-abrir-modal-lotes:disabled { opacity: .5; cursor: not-allowed; }

/* ── Tabla artículos confirmados ── */
#tabla-articulos-confirmados thead th {
    background-color: #f4f6f8;
    color: #495057;
    font-size: 0.82rem;
    font-weight: 600;
    text-transform: uppercase;
    border-top: none;
    border-bottom: 2px solid #e9ecef;
}
#tabla-articulos-confirmados tbody td {
    vertical-align: middle;
    font-size: 0.88rem;
}
.btn-eliminar-lote {
    font-size: 0.72rem;
    padding: 2px 7px;
}

/* ── Modal lotes ── */
#modal-lotes .modal-header {
    background-color: #4a90d9;
    color: #fff;
    padding: 12px 16px;
}
#modal-lotes .modal-header .modal-title { font-size: 1rem; font-weight: 600; }
#modal-lotes .modal-header .close { color: rgba(255,255,255,.85); opacity:1; text-shadow:none; }
#modal-lotes .modal-header .close:hover { color:#fff; }

/* Info box artículo */
#modal-lotes-info-box {
    background: #f8f9fa;
    border-left: 3px solid #4a90d9;
    border-radius: 3px;
    padding: 8px 12px;
    margin-bottom: 14px;
    font-size: .88rem;
    color: #333;
}
#modal-lotes-info-box .info-clave {
    font-weight: 700;
    font-size: .92rem;
}

/* Tarjetas de filas de lote */
.lotes-editor .fila-lote-card {
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,.03);
    transition: box-shadow .15s, border-color .15s;
}
.lotes-editor .fila-lote-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    border-color: #ced4da;
}
.lotes-editor .fila-lote-card .fila-num {
    font-size: .72rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.lotes-editor .fila-lote-card .campos-row {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    flex-wrap: wrap;
}
.lotes-editor .fila-lote-card .campo-group {
    display: flex;
    flex-direction: column;
    flex: 1 1 130px;
}
.lotes-editor .fila-lote-card .campo-group.campo-cant {
    flex: 0 0 80px;
}
.lotes-editor .fila-lote-card .campo-group label {
    font-size: .75rem;
    font-weight: 600;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: .3px;
    margin-bottom: 3px;
}
.lotes-editor .fila-lote-card .campo-group input {
    border: 1px solid #ced4da;
    border-radius: 4px;
    padding: 5px 9px;
    font-size: .88rem;
    height: 34px;
}
.lotes-editor .fila-lote-card .campo-group input:focus {
    border-color: #4a90d9;
    box-shadow: 0 0 0 2px rgba(74,144,217,.2);
    outline: none;
}
.lotes-editor .fila-lote-card .acciones-fila {
    display: flex;
    align-items: flex-end;
    gap: 6px;
    padding-bottom: 1px;
    flex-shrink: 0;
}

/* Botones + / - */
.btn-add-lote-fila {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: #ffb300;
    border: none;
    color: #fff;
    font-size: 1.15rem;
    line-height: 1;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s;
    flex-shrink: 0;
}
.btn-add-lote-fila:hover { background: #ffa000; }
.btn-remove-lote-fila {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: #fff;
    border: 1px solid #dc3545;
    color: #dc3545;
    font-size: .85rem;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .15s, color .15s;
    flex-shrink: 0;
}
.btn-remove-lote-fila:hover { background: #dc3545; color: #fff; }

#modal-lotes .modal-footer { border-top: 1px solid #dee2e6; padding: 10px 16px; }
#modal-lotes .btn-confirmar-modal {
    background-color: #4a90d9;
    border: none;
    color: #fff;
    font-weight: 600;
    padding: 7px 20px;
    border-radius: 4px;
    font-size: .9rem;
    transition: background .15s;
}
#modal-lotes .btn-confirmar-modal:hover { background-color: #357abd; }
#modal-lotes .resumen-asignado {
    font-size: .82rem;
    color: #495057;
    padding: 4px 10px;
    background: #e9ecef;
    border-radius: 20px;
    margin-right: auto;
}
</style>

<?php if (empty($articulos_pendientes)): ?>
    <div class="alert alert-success text-center"><strong>Esta Orden de Compra ya fue recibida en su totalidad.</strong></div>
<?php else: ?>

<!-- ═══════════════════════════════════════════════════
     SECCIÓN 1 – Lista de artículos esperados
════════════════════════════════════════════════════ -->
<h6 class="font-weight-bold text-secondary mb-2" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.4px;">
    <i class="mdi mdi-format-list-bulleted mr-1"></i> Lista de artículos esperados
</h6>
<div class="table-responsive mb-4">
    <table class="table table-bordered table-sm table-hover" id="tabla-articulos-pendientes">
        <thead>
            <tr>
                <th width="12%">Cant. Esperada</th>
                <th width="15%">Referencia</th>
                <th>Descripción</th>
                <th width="18%">Cant. Recibida</th>
                <th width="10%"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($articulos_pendientes as $idx => $art): ?>
            <tr id="fila-pendiente-<?= $idx ?>" data-artid="<?= $art['artid'] ?>" data-clave="<?= htmlspecialchars($art['clave']) ?>" data-nombre="<?= htmlspecialchars($art['nombre']) ?>" data-pendiente="<?= $art['pendiente'] ?>" data-costo="<?= $art['costo'] ?>">
                <td><span class="font-weight-bold"><?= number_format($art['pendiente'], 0) ?></span></td>
                <td><strong><?= htmlspecialchars($art['clave'] ?: 'S/K') ?></strong></td>
                <td><?= htmlspecialchars($art['nombre']) ?></td>
                <td>
                    <div class="d-flex align-items-center" style="gap:6px;">
                        <input type="number"
                            class="form-control form-control-sm input-cant-recibida"
                            id="cant-recibida-<?= $idx ?>"
                            data-idx="<?= $idx ?>"
                            min="0"
                            max="<?= $art['pendiente'] ?>"
                            value="0"
                            style="width:70px;text-align:center;"
                        >
                        <small class="text-muted">/ <?= number_format($art['pendiente'], 0) ?></small>
                    </div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-primary btn-sm btn-abrir-modal-lotes"
                        data-idx="<?= $idx ?>" data-esperada="<?= $art['pendiente'] ?>" disabled>
                        <i class="mdi mdi-tag-multiple mr-1" style="font-size:.8rem;"></i>Lote
                    </button>
                </td>
            </tr>
            <tr id="fila-lotes-container-<?= $idx ?>" class="fila-lotes-container" style="display:none; background-color:#f4f7f9;">
                <td colspan="5" class="p-3 border-bottom">
                    <div class="lotes-editor p-3 m-1 rounded" id="lotes-editor-<?= $idx ?>" style="background-color: #fafbfc; border: 1px solid #e9ecef;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 font-weight-bold" style="font-size: .88rem; color: #1c3a6b;"><i class="mdi mdi-tag-multiple"></i> Asignar Lotes / Series</h6>
                            <button type="button" class="btn btn-sm btn-light border btn-cerrar-lotes-inline" data-idx="<?= $idx ?>">Cerrar</button>
                        </div>
                        <div id="lotes-filas-<?= $idx ?>" class="mb-3"></div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <div class="resumen-asignado text-muted font-weight-bold" id="resumen-asignado-<?= $idx ?>" style="font-size: 0.85rem;">
                                Asignando 0 de 0
                            </div>
                            <button type="button" class="btn btn-primary btn-sm btn-confirmar-lotes-inline" data-idx="<?= $idx ?>" style="background-color: #1d3a8a; border-color: #1d3a8a; padding: 6px 16px;">
                                <i class="mdi mdi-check"></i> Confirmar
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ═══════════════════════════════════════════════════
     SECCIÓN 2 – Lista de artículos confirmados con todos los datos
════════════════════════════════════════════════════ -->
<h6 class="font-weight-bold mb-2" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;color:#1a5c37;">
    <i class="mdi mdi-check-circle-outline mr-1" style="color:#27ae60;"></i> Artículos con lote/serie asignados
</h6>
<div class="table-responsive">
    <table class="table table-bordered table-sm" id="tabla-articulos-confirmados">
        <thead>
            <tr>
                <th>Referencia</th>
                <th>Descripción</th>
                <th width="14%">Lote</th>
                <th width="14%">N° Serie</th>
                <th width="14%">Caducidad</th>
                <th width="8%"></th>
            </tr>
        </thead>
        <tbody id="tbody-confirmados">
            <tr id="fila-sin-datos">
                <td colspan="6" class="text-center text-muted py-3">
                    <i class="mdi mdi-inbox-outline" style="font-size:1.4rem;"></i><br>
                    <small>Aún no hay artículos con lote/serie/caducidad asignados.</small>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Inputs ocultos que se enviarán al guardar -->
<div id="inputs-ocultos-recepcion"></div>



<script>
(function() {
    // ── Estado global ──────────────────────────────────────────────────────────
    // lotesData[idx] = [ {lote, serie, caducidad, cantidad}, ... ]
    var lotesData = {};
    var idxActual = null;

    // ── Habilitar/deshabilitar botón Lote según cant. recibida ────────────────────
    $(document).on('input change', '.input-cant-recibida', function() {
        var idx      = $(this).data('idx');
        var val      = parseInt($(this).val()) || 0;
        var max      = parseInt($(this).attr('max')) || 0;
        var btn      = $('[data-idx="' + idx + '"].btn-abrir-modal-lotes');

        if (val < 0) { val = 0; $(this).val(0); }
        if (val > max) { val = max; $(this).val(max); }

        var cantAnterior = parseInt($(this).data('cant-prev')) || 0;
        if (val !== cantAnterior) {
            delete lotesData[idx];
            $(this).data('cant-prev', val);
            reconstruirConfirmados();
        }

        btn.prop('disabled', val <= 0);
    });

    // ── Abrir modal inline al pulsar el botón "Lote" ──────────────────────────
    $(document).on('click', '.btn-abrir-modal-lotes', function() {
        var idx      = $(this).data('idx');
        var recibida = parseInt($('#cant-recibida-' + idx).val()) || 0;

        if (recibida <= 0) return;

        $('.fila-lotes-container').hide(); // Ocultar otros abiertos
        $('#fila-lotes-container-' + idx).fadeIn(150);

        renderModalFilas(idx, recibida);
    });

    // ── Cerrar modal inline ───────────────────────────────────────────────────
    $(document).on('click', '.btn-cerrar-lotes-inline', function() {
        var idx = $(this).data('idx');
        $('#fila-lotes-container-' + idx).hide();
    });

    // ── Renderizar filas dentro del inline modal ──────────────────────────────
    function renderModalFilas(idx, recibida) {
        var filas = lotesData[idx] || [];

        if (filas.length === 0) {
            filas = [{lote:'', serie:'', caducidad:'', cantidad: Math.min(1, recibida)}];
            lotesData[idx] = filas;
        }

        var totalAsignado = filas.reduce(function(s,f){return s+(parseInt(f.cantidad)||0);},0);
        var html = '';
        filas.forEach(function(f, fi) {
            html += buildFilaModal(idx, fi, f, recibida, filas.length, totalAsignado);
        });

        $('#lotes-filas-' + idx).html(html);
        actualizarResumenModal(idx, recibida);
    }

    function buildFilaModal(idx, fi, f, recibida, totalFilas, cantidadYaAsignada) {
        var quedaDisponible = recibida - cantidadYaAsignada;
        var esUltimaFila   = (fi === totalFilas - 1);
        var mostrarAdd     = esUltimaFila && quedaDisponible > 0;
        var mostrarDel     = totalFilas > 1;

        var numLabel = totalFilas > 1
            ? '<div class="fila-num"><i class="mdi mdi-tag-outline mr-1"></i>Lote ' + (fi + 1) + ' de ' + totalFilas + '</div>'
            : '';

        return '<div class="fila-lote-card" data-fi="' + fi + '">' +
            numLabel +
            '<div class="campos-row">' +
                '<div class="campo-group">' +
                    '<label>Lote</label>' +
                    '<input type="text" data-campo="lote" placeholder="Ej. L-2025-01" value="' + escapeHtml(f.lote) + '">' +
                '</div>' +
                '<div class="campo-group">' +
                    '<label>N° Serie</label>' +
                    '<input type="text" data-campo="serie" placeholder="Ej. SN-1234" value="' + escapeHtml(f.serie) + '">' +
                '</div>' +
                '<div class="campo-group">' +
                    '<label>Caducidad</label>' +
                    '<input type="date" data-campo="caducidad" value="' + escapeHtml(f.caducidad) + '">' +
                '</div>' +
                '<div class="campo-group campo-cant">' +
                    '<label>Cantidad</label>' +
                    '<input type="number" data-campo="cantidad" min="1" max="' + recibida + '" value="' + (f.cantidad || 1) + '">' +
                '</div>' +
                '<div class="acciones-fila">' +
                    (mostrarAdd ? '<button type="button" class="btn-add-lote-fila" data-idx="'+idx+'" title="Agregar otro lote">+</button>' : '') +
                    (mostrarDel ? '<button type="button" class="btn-remove-lote-fila" data-idx="'+idx+'" title="Eliminar esta fila"><i class="mdi mdi-delete"></i></button>' : '') +
                '</div>' +
            '</div>' +
        '</div>';
    }

    function calcularTotalAsignado(idx) {
        var filas = lotesData[idx] || [];
        return filas.reduce(function(s, f) { return s + (parseInt(f.cantidad) || 0); }, 0);
    }

    function actualizarResumenModal(idx, recibida) {
        var total = calcularTotalAsignado(idx);
        var color = total === recibida && recibida > 0 ? '#27ae60' : (total > recibida ? '#e74c3c' : '#2d6a9f');
        var bgColor = color === '#27ae60' ? '#eafaf1' : '#eaf1fb';
        if (recibida === 0) {
            $('#resumen-asignado-' + idx).html('').css({color:'', background:''});
            return;
        }
        $('#resumen-asignado-' + idx)
            .html('<i class="mdi mdi-check-circle mr-1"></i>Asignando <strong>' + total + '</strong> de <strong>' + recibida + '</strong>')
            .css({color: color, background: bgColor, 'border-color': color === '#27ae60' ? '#a3e6c5' : '#c5d8f5'});
    }

    // ── Agregar fila ───────────────────────────────────────────────────────────
    $(document).on('click', '.btn-add-lote-fila', function() {
        var idx = $(this).data('idx');
        guardarEstadoFilas(idx);
        var recibida   = parseInt($('#cant-recibida-' + idx).val()) || 0;
        var totalUsado = calcularTotalAsignado(idx);
        var restante   = recibida - totalUsado;
        if (restante <= 0) return;
        lotesData[idx].push({lote:'', serie:'', caducidad:'', cantidad: Math.min(1, restante)});
        renderModalFilas(idx, recibida);
    });

    // ── Eliminar fila ──────────────────────────────────────────────────────────
    $(document).on('click', '.btn-remove-lote-fila', function() {
        var idx = $(this).data('idx');
        guardarEstadoFilas(idx);
        var fi       = parseInt($(this).closest('.fila-lote-card').data('fi'));
        var recibida = parseInt($('#cant-recibida-' + idx).val()) || 0;
        lotesData[idx].splice(fi, 1);
        renderModalFilas(idx, recibida);
    });

    // ── Re-render al cambiar cantidad de una fila ──────────────────────────────
    $(document).on('change', '.fila-lotes-container input[data-campo="cantidad"]', function() {
        var idx = $(this).closest('.lotes-editor').attr('id').replace('lotes-editor-', '');
        guardarEstadoFilas(idx);
        var recibida = parseInt($('#cant-recibida-' + idx).val()) || 0;
        renderModalFilas(idx, recibida);
    });

    // ── Guardar estado de los inputs en el objeto ──────────────────────────────
    function guardarEstadoFilas(idx) {
        if (idx === null || idx === undefined) return;
        var filas = [];
        $('#lotes-filas-' + idx + ' .fila-lote-card').each(function() {
            filas.push({
                lote:      $(this).find('[data-campo="lote"]').val().trim(),
                serie:     $(this).find('[data-campo="serie"]').val().trim(),
                caducidad: $(this).find('[data-campo="caducidad"]').val(),
                cantidad:  parseInt($(this).find('[data-campo="cantidad"]').val()) || 1
            });
        });
        lotesData[idx] = filas;
    }

    // ── Confirmar inline ───────────────────────────────────────────────────────
    $(document).on('click', '.btn-confirmar-lotes-inline', function() {
        var idx = $(this).data('idx');
        guardarEstadoFilas(idx);

        var recibida = parseInt($('#cant-recibida-' + idx).val()) || 0;
        if (recibida <= 0) {
            alert('Debe indicar la cantidad recibida en la tabla principal.');
            return;
        }

        var filas = lotesData[idx] || [];

        if (filas.length === 0) {
            alert('Debe agregar al menos un lote.');
            return;
        }

        // Validar al menos lote o serie en cada fila
        var error = false;
        filas.forEach(function(f, fi) {
            if (!error && f.lote === '' && f.serie === '') {
                alert('La fila ' + (fi+1) + ' debe tener al menos Lote o N° Serie.');
                error = true;
            }
        });
        if (error) return;

        // Validar que la suma de cantidades no supere la cant. recibida
        var totalCant = filas.reduce(function(s, f) { return s + (parseInt(f.cantidad)||0); }, 0);
        if (totalCant > recibida) {
            alert('La suma de cantidades de lotes (' + totalCant + ') supera la cantidad recibida (' + recibida + ').');
            return;
        }
        if (totalCant === 0) {
            alert('La cantidad total debe ser mayor a 0.');
            return;
        }

        // Reconstruir tabla confirmados e inputs ocultos
        reconstruirConfirmados();
        $('#fila-lotes-container-' + idx).hide();
        evaluarCaducidades();
    });

    // ── Reconstruir tabla de confirmados e inputs ocultos ──────────────────────
    function reconstruirConfirmados() {
        var tbody     = $('#tbody-confirmados');
        var inputsDiv = $('#inputs-ocultos-recepcion');
        tbody.empty();
        inputsDiv.empty();

        var globalIdx = 0;
        var hayDatos  = false;

        $('#tabla-articulos-pendientes tbody tr').each(function() {
            var artIdx = $(this).attr('id') ? $(this).attr('id').replace('fila-pendiente-', '') : null;
            if (artIdx === null) return;

            var artid    = $(this).data('artid');
            var clave    = $(this).data('clave');
            var nombre   = $(this).data('nombre');
            var costo    = $(this).data('costo');
            var esperada = $(this).data('pendiente');
            var recibida = parseInt($('#cant-recibida-' + artIdx).val()) || 0;

            var filas = lotesData[artIdx] || [];
            if (filas.length === 0 || recibida === 0) return;

            var totalCant = filas.reduce(function(s,f){return s+(parseInt(f.cantidad)||0);},0);
            if (totalCant === 0) return;

            hayDatos = true;

            filas.forEach(function(f, fi) {
                var cant = parseInt(f.cantidad) || 0;
                if (cant === 0) return;

                for (var i = 0; i < cant; i++) {
                    var row = $('<tr>').attr('id', 'conf-row-' + artIdx + '-' + fi + '-' + i);
                    row.append($('<td>').html('<strong>' + escapeHtml(clave) + '</strong>'));
                    row.append($('<td>').text(nombre));
                    row.append($('<td>').text(f.lote || '—'));
                    row.append($('<td>').text(f.serie || '—'));
                    row.append($('<td>').text(f.caducidad || '—'));
                    var btnEl = $('<button type="button" class="btn btn-outline-danger btn-eliminar-lote btn-sm">').html('<i class="mdi mdi-delete"></i>');
                    btnEl.attr('data-artidx', artIdx).attr('data-fi', fi);
                    row.append($('<td class="text-center">').append(btnEl));
                    tbody.append(row);

                    var prefix = 'detalles[' + globalIdx + ']';
                    inputsDiv.append('<input type="hidden" name="' + prefix + '[articuloid]" value="' + artid + '">');
                    inputsDiv.append('<input type="hidden" name="' + prefix + '[recibida]" value="1">');
                    inputsDiv.append('<input type="hidden" name="' + prefix + '[costo]" value="' + costo + '">');
                    inputsDiv.append('<input type="hidden" name="' + prefix + '[esperada]" value="' + esperada + '">');
                    inputsDiv.append('<input type="hidden" name="' + prefix + '[lote]" value="' + escapeHtml(f.lote) + '">');
                    inputsDiv.append('<input type="hidden" name="' + prefix + '[serie]" value="' + escapeHtml(f.serie) + '">');
                    inputsDiv.append('<input type="hidden" name="' + prefix + '[caducidad]" value="' + escapeHtml(f.caducidad) + '">');
                    inputsDiv.append('<input type="hidden" name="' + prefix + '[cantidad]" value="1">');
                    inputsDiv.append('<input type="hidden" name="' + prefix + '[motivo]" value="">');

                    globalIdx++;
                }
            });
        });

        if (!hayDatos) {
            tbody.html('<tr id="fila-sin-datos"><td colspan="7" class="text-center text-muted py-3"><i class="mdi mdi-inbox-outline" style="font-size:1.4rem;"></i><br><small>Aún no hay artículos con lote/serie/caducidad asignados.</small></td></tr>');
        }

        if (hayDatos) {
            inputsDiv.append('<input type="hidden" class="chk-recibir-lote-flag" value="1">');
        }
    }

    // ── Eliminar fila de confirmados ───────────────────────────────────────────
    $(document).on('click', '.btn-eliminar-lote', function() {
        var artIdx = $(this).data('artidx');
        var fi     = parseInt($(this).data('fi'));

        if (lotesData[artIdx]) {
            lotesData[artIdx].splice(fi, 1);
            if (lotesData[artIdx].length === 0) {
                delete lotesData[artIdx];
            }
        }

        // Si ya no hay lotes, resetear cant. recibida
        var esperada = parseFloat($('#fila-pendiente-' + artIdx).data('pendiente'));
        var totalCant = 0;
        if (lotesData[artIdx]) {
            totalCant = lotesData[artIdx].reduce(function(s,f){return s+(parseInt(f.cantidad)||0);},0);
        }
        if (totalCant === 0) {
            $('#cant-recibida-' + artIdx).val(0);
            $('#txt-cant-recibida-' + artIdx).text(0);
            $('#badge-cant-recibida-' + artIdx).removeClass('completado');
        }

        reconstruirConfirmados();
        evaluarCaducidades();
    });

    // ── Evaluar caducidades ────────────────────────────────────────────────────
    function evaluarCaducidades() {
        var hayCaducidadProxima = false;
        var hoy = new Date();
        hoy.setHours(0,0,0,0);
        var unAnioDespues = new Date(hoy);
        unAnioDespues.setFullYear(hoy.getFullYear() + 1);

        Object.keys(lotesData).forEach(function(artIdx) {
            (lotesData[artIdx] || []).forEach(function(f) {
                if (f.caducidad) {
                    var fc = new Date(f.caducidad + 'T00:00:00');
                    if (fc < unAnioDespues) hayCaducidadProxima = true;
                }
            });
        });

        if (hayCaducidadProxima) {
            $('#seccion-documentos-cc').fadeIn();
        } else {
            $('#seccion-documentos-cc').fadeOut();
            $('#archivo_carta_canje_general').val('');
            $('#num_delivery_general').val('');
            $('#archivo_carta_canje_general').removeClass('is-invalid');
            $('#num_delivery_general').removeClass('is-invalid');
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────────
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function number_format(n) {
        return parseInt(n).toLocaleString('es-MX');
    }

})();
</script>

<?php endif; ?>