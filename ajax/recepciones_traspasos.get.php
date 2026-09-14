<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");
include_once("../class/bdfirebird.php");

$traspasoid = isset($_GET['traspasoid']) ? (int)$_GET['traspasoid'] : 0;

if ($traspasoid == 0) {
    echo '<div class="alert alert-danger">ID de Traspaso inválido.</div>';
    exit;
}

$db = new FirebirdConnection();

// Calcular número de páginas del PDF
$numPages = 1;
try {
    include_once("../class/traspasos.php");
    require_once '../vendor/autoload.php';
    $traspasosCls = new traspasos();
    $resT = $traspasosCls->getinfotraspasobyid($traspasoid);
    if (!empty($resT)) {
        $pdf = new TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', '', 9);
        $html = '<br><h3 align="center" style="color: #1a3a6b;">TRASPASO DE ALMACÉN</h3><br>
        <table width="100%" cellpadding="3"><tr><td></td><td></td></tr><tr><td></td><td></td></tr><tr><td></td><td></td></tr><tr><td></td><td></td></tr></table>
        <br><br><b>Detalle de Artículos</b><br><br>
        <table width="100%" cellpadding="4">
            <tr class="th"><th width="20%">FOLIO</th><th width="80%">DESCRIPCIÓN</th></tr>';
        
        foreach ($resT as $re) {
            $html .= '
            <tr>
                <td width="20%" class="b1" align="center"><strong>' . $re['STOCK_FOLIO'] . '</strong></td>
                <td width="80%" class="b1">
                    <span style="font-size: 10px;"><b>' . $re['CLAVE_ARTICULO'] . '</b> - ' . $re['ARTICULO_NOMBRE'] . '</span>
                    <br>
                    <table width="100%" border="0" cellpadding="1">
                        <tr>
                            <td width="35%" style="font-size:9px;"><strong>Lote:</strong> ' . $re['ESDET_LOTE'] . '</td>
                            <td width="35%" style="font-size:9px;"><strong>Caducidad:</strong> ' . $re['ESDET_CADUCIDAD'] . '</td>
                            <td width="30%" style="font-size:9px;"><strong>Serie:</strong> ' . $re['ESDET_SERIE'] . '</td>
                        </tr>
                    </table>
                </td>
            </tr>';
        }
        $html .= '</table><br><br><br><br><table width="100%" border="0" cellpadding="4"><tr><td><br><br><br></td></tr></table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $numPages = $pdf->getNumPages();
    }
} catch (Exception $e) {
    // Default to 1 if error
}

$sql = "
    SELECT 
    td.TRASPASODET_ID,
    st.STOCK_FOLIO,
    AR.NOMBRE AS ARTICULO_NOMBRE, 
    X.CLAVE_ARTICULO,
    ED.ESDET_LOTE,
    ED.ESDET_CADUCIDAD,
    ED.ESDET_SERIE,
    (SELECT COUNT(*) FROM AMPAR_RECEPCIONDET WHERE RECDET_TRASPASODETID = td.TRASPASODET_ID) AS YA_RECIBIDO
    FROM AMPAR_HIS_TRASPASODET td
    LEFT JOIN AMPAR_HIS_STOCK st ON st.STOCK_ID = td.TRASPASODET_STOCKID
    LEFT JOIN AMPAR_HIS_ESDET ED ON ED.ESDET_ID = st.STOCK_ESDETID
    LEFT JOIN ARTICULOS AR ON AR.ARTICULO_ID = st.STOCK_ARTICULOID 
    LEFT JOIN (SELECT CLAVE_ARTICULO_ID, CLAVE_ARTICULO, ARTICULO_ID FROM claves_articulos WHERE ROL_CLAVE_ART_ID = 17) X ON X.ARTICULO_ID = AR.ARTICULO_ID
    WHERE td.TRASPASODET_TRASPASOID = ?
";
$info = $db->query($sql, [$traspasoid]);
$db->close();

if (empty($info)) {
    echo '<div class="alert alert-warning">No se encontraron detalles para este Traspaso.</div>';
    exit;
}
?>
<input type="hidden" id="pdf_pages_count" value="<?= htmlspecialchars($numPages) ?>">
<style>
@media (max-width: 767px) {
    #tabla-desglose-traspaso {
        width: 100% !important;
        border: none !important;
        margin-bottom: 0 !important;
    }
    #tabla-desglose-traspaso thead {
        display: none !important;
    }
    #tabla-desglose-traspaso tbody,
    #tabla-desglose-traspaso tr,
    #tabla-desglose-traspaso td {
        display: block !important;
        width: 100% !important;
    }
    #tabla-desglose-traspaso tr {
        margin-bottom: 1.25rem !important;
        border: 1px solid #dee2e6 !important;
        border-radius: 10px !important;
        padding: 0.75rem !important;
        background-color: #ffffff !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08) !important;
    }
    #tabla-desglose-traspaso td {
        border: none !important;
        border-bottom: 1px solid #f1f3f5 !important;
        padding: 0.6rem 0.2rem !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        width: 100% !important;
        text-align: left !important;
        white-space: normal !important;
    }
    #tabla-desglose-traspaso td:last-child {
        border-bottom: none !important;
    }
    #tabla-desglose-traspaso td::before {
        content: attr(data-label);
        font-weight: bold;
        color: #334155;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.3rem;
        display: block;
    }
    #tabla-desglose-traspaso td:first-child {
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        background-color: #f1f5f9;
        padding: 0.75rem !important;
        border-radius: 6px;
        margin-bottom: 0.5rem;
        border: 1px solid #e2e8f0 !important;
    }
    #tabla-desglose-traspaso td:first-child::before {
        margin-bottom: 0;
        font-size: 0.95rem;
        color: #0f172a;
        text-transform: none;
    }
    #tabla-desglose-traspaso td:first-child input[type="checkbox"] {
        transform: scale(1.4);
        margin-left: 1rem;
    }
}
</style>
<div class="d-flex justify-content-end mb-2 d-md-none">
    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-check-all-mobile">
        <i class="mdi mdi-check-all"></i> Seleccionar Todos
    </button>
</div>
<div class="table-responsive">
    <table class="table table-bordered table-sm table-hover" id="tabla-desglose-traspaso">
        <thead class="thead-light">
            <tr>
                <th width="4%"><input type="checkbox" id="check-all-traspaso" title="Seleccionar Todos"></th>
                <th width="12%">Folio Stock</th>
                <th width="10%">Clave</th>
                <th width="25%">Artículo</th>
                <th width="15%">Lote</th>
                <th width="15%">Serie</th>
                <th width="15%">Caducidad</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $hayPendientes = false;
            $fila_idx = 0;

            foreach ($info as $row) {
                // If it's already received, skip or show as received
                if ((int)$row['YA_RECIBIDO'] > 0) {
                    continue;
                }

                $hayPendientes = true;
            ?>
                <tr>
                    <td class="text-center" data-label="¿Recibir esta unidad?">
                        <input type="checkbox" class="chk-recibir" name="detalles[<?= $fila_idx ?>][recibida]" value="1">
                        <input type="hidden" name="detalles[<?= $fila_idx ?>][traspasodetid]" value="<?= $row['TRASPASODET_ID'] ?>">
                    </td>
                    <td data-label="Folio Stock"><?= htmlspecialchars($row['STOCK_FOLIO']) ?></td>
                    <td data-label="Clave"><?= htmlspecialchars($row['CLAVE_ARTICULO']) ?></td>
                    <td data-label="Artículo"><?= htmlspecialchars($row['ARTICULO_NOMBRE']) ?></td>
                    <td data-label="Lote"><?= htmlspecialchars($row['ESDET_LOTE']) ?></td>
                    <td data-label="Serie"><?= htmlspecialchars($row['ESDET_SERIE']) ?></td>
                    <td data-label="Caducidad"><?= htmlspecialchars($row['ESDET_CADUCIDAD']) ?></td>
                </tr>
            <?php
                $fila_idx++;
            }

            if (!$hayPendientes) {
                echo '<tr><td colspan="7" class="text-center text-success"><strong>Este Traspaso ya fue recibido en su totalidad o no contiene artículos pendientes.</strong></td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<script>
    $('#check-all-traspaso').on('change', function() {
        $('.chk-recibir').prop('checked', $(this).is(':checked'));
    });
    
    $('#btn-check-all-mobile').on('click', function() {
        let allChecked = $('.chk-recibir:not(:checked)').length === 0;
        $('.chk-recibir').prop('checked', !allChecked);
        if(!allChecked) {
            $(this).html('<i class="mdi mdi-close"></i> Deseleccionar Todos').removeClass('btn-outline-primary').addClass('btn-outline-danger');
            $('#check-all-traspaso').prop('checked', true);
        } else {
            $(this).html('<i class="mdi mdi-check-all"></i> Seleccionar Todos').removeClass('btn-outline-danger').addClass('btn-outline-primary');
            $('#check-all-traspaso').prop('checked', false);
        }
    });
</script>
