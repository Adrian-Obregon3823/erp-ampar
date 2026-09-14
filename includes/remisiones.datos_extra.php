<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$remisionid = (int) base64_decode($_GET['remisionid']);
$remisiones = new remisiones();
$res = $remisiones->getremisionbyid(null, $remisionid);

if (!$res || count($res) == 0) {
  die('<div class="alert alert-danger">No se encontró la remisión.</div>');
}

$filaCab = $res[0];
if ((int)$filaCab['REMISION_STATUS'] !== 1) {
    die('<div class="alert alert-warning m-3">La remisión ya fue enviada a recepción (o procesada), por lo que sus datos complementarios y formato no pueden modificarse.</div>');
}
$folioRem = $filaCab['REMISION_FOLIO'];

// Calcular el total de la remisión para validaciones
$db = new FirebirdConnection();
$sqlTotal = "
    SELECT 
    (
        COALESCE((
            SELECT SUM(REMISIONARTICULO_TOTAL) 
            FROM AMPAR_HIS_REMISIONESARTICULOS 
            WHERE REMISIONARTICULO_REMISIONID = {$remisionid}
        ), 0) 
        +
        COALESCE((
            SELECT SUM(REMISIONPROVARTICULO_TOTAL) 
            FROM AMPAR_HIS_REMISIONESPARTICULOS 
            WHERE REMISIONPROVARTICULO_REMISIONID = {$remisionid}
        ), 0)
    ) AS TOTAL
    FROM RDB\$DATABASE
";
$resTotal = $db->query($sqlTotal);
$totalRemision = ($resTotal && count($resTotal) > 0) ? (float)$resTotal[0]['TOTAL'] : 0;
$db->close();
?>
<div class="col-12">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Datos Complementarios <small class="text-muted">Folio: <?= htmlentities($folioRem) ?></small></h4>
    </div>

    <div class="card-body">
      <form id="form-datos-extra">
        <input type="hidden" name="remisionid" value="<?= $remisionid ?>">

        <div class="row">
          <!-- Datos Principales -->
          <div class="col-md-12 mb-3">
            <label>Nombre del paciente <span class="text-danger">*</span></label>
            <input type="text" name="nombrepaciente" class="form-control" value="<?= htmlentities($filaCab['EVENTO_NOMBREPARTICULAR'] ?? '') ?>" required>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label># de Paciente</label>
            <input type="text" name="numpaciente" class="form-control" value="<?= htmlentities($filaCab['REMISION_NUMPACIENTE'] ?? '') ?>">
          </div>

          <div class="col-md-6 mb-3">
            <label>RFC (Hospital)</label>
            <input type="text" name="rfc" class="form-control" value="<?= htmlentities($filaCab['REMISION_RFC'] ?? '') ?>">
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
          <label>No. de Proveedor AMPAR</label>
          <input type="text" class="form-control" name="numclientehos" value="<?= htmlentities($filaCab['REMISION_NUMCLIENTEHOS'] ?? '') ?>">
        </div>
        </div>

        <div class="row">
          <!-- Totales Parciales -->
          <div class="col-md-6 mb-3">
            <label>Anticipo ($)</label>
            <input type="number" step="0.01" name="anticipo" id="inputAnticipo" class="form-control" value="<?= htmlentities($filaCab['REMISION_ANTICIPO'] ?? '') ?>" min="0" max="<?= $totalRemision ?>">
            <small class="text-muted">Total remisión: $<?= number_format($totalRemision, 2) ?></small>
          </div>
          <div class="col-md-6 mb-3">
            <label>Resto ($)</label>
            <input type="number" step="0.01" name="resto" id="inputResto" class="form-control" value="<?= htmlentities($filaCab['REMISION_RESTO'] ?? '') ?>" readonly>
          </div>
        </div>

        <hr>

        <div class="row">
          <div class="col-md-4 mb-3">
            <label>Representante</label>
            <input type="text" name="representante" class="form-control" value="<?= htmlentities($filaCab['REMISION_REPRESENTANTE'] ?? '') ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label>Médico 2do. Op.</label>
            <input type="text" name="medico2" class="form-control" value="<?= htmlentities($filaCab['REMISION_MEDICO2'] ?? '') ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label>Enfermería</label>
            <input type="text" name="enfermeria" class="form-control" value="<?= htmlentities($filaCab['REMISION_ENFERMERIA'] ?? '') ?>">
          </div>
        </div>

        <hr>

        <div class="row">
          <div class="col-md-12 mb-3">
            <label>Observaciones</label>
            <textarea name="observaciones" class="form-control" rows="3" maxlength="250"><?= htmlentities($filaCab['REMISION_OBSERVACIONES'] ?? '') ?></textarea>
            <small class="text-muted">Máximo 250 caracteres.</small>
          </div>
        </div>

        <div class="text-right">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-primary" id="btnGuardarExtra">Guardar Datos</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  $(document).ready(function() {
    var totalRemision = <?= json_encode($totalRemision) ?>;

    function calcularResto() {
      var anticipo = parseFloat($("#inputAnticipo").val()) || 0;
      if (anticipo > totalRemision) {
        anticipo = totalRemision;
        $("#inputAnticipo").val(anticipo);
      } else if (anticipo < 0) {
        anticipo = 0;
        $("#inputAnticipo").val(anticipo);
      }
      var resto = totalRemision - anticipo;
      $("#inputResto").val(resto.toFixed(2));
    }

    $("#inputAnticipo").on("input change", function() {
      calcularResto();
    });

    $("#btnGuardarExtra").click(function() {
      var anticipo = parseFloat($("#inputAnticipo").val()) || 0;
      if (anticipo > totalRemision) {
        if (typeof Swal !== 'undefined') {
          Swal.fire('Atención', 'El anticipo no puede ser mayor al total de la remisión ($' + totalRemision.toFixed(2) + ').', 'warning');
        } else {
          alert('El anticipo no puede ser mayor al total de la remisión ($' + totalRemision.toFixed(2) + ').');
        }
        return false;
      }

      var $btn = $(this);
      $btn.prop('disabled', true).text('Guardando...');

      $.ajax({
        url: '../includes/remisiones.guardar_extra.php',
        type: 'POST',
        data: $("#form-datos-extra").serialize(),
        dataType: 'json',
        success: function(response) {
          if (response.status === 'success') {
            // Forzar cierre del modal
            $("#modalglobal").modal('hide');
            $('.modal').modal('hide');
            $('[data-dismiss="modal"]').click();

            if (typeof Swal !== 'undefined') {
              Swal.fire({
                title: "¡Guardado!",
                text: "Datos extra guardados exitosamente.",
                icon: "success",
                timer: 1500,
                showConfirmButton: false
              });
            }
            $btn.prop('disabled', false).text('Guardar Datos');
          } else {
            alert('Error: ' + response.message);
            $btn.prop('disabled', false).text('Guardar Datos');
          }
        },
        error: function(xhr, status, error) {
          console.error("AJAX Error:", error);
          console.error("Response:", xhr.responseText);
          alert('Ocurrió un error en la solicitud. Revisa la consola para más detalles.');
          $btn.prop('disabled', false).text('Guardar Datos');
        }
      });
    });
  });
</script>