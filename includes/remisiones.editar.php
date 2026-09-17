<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$remisionid = (int) base64_decode($_GET['remisionid']);
$remisiones = new remisiones();
$res = $remisiones->getremisionbyid(null, $remisionid);
$resp = $remisiones->getremisionprovinfobyid($remisionid);
$eventoId   = $res[0]['REMISION_EVENTOID'];
$folioRem   = $res[0]['REMISION_FOLIO'];
$clienteNom = $res[0]['CLIENTE_NOMBRE'] ?? '';
// Obtenemos el nombre del almacén con su salvavidas
$almacenNom = $res[0]['REMISION_ALMACEN_NOMBRE'] ?? 'Almacén de ' . $res[0]['SUCURSAL_NOMBRE'];
if ((int)$res[0]['REMISION_STATUS'] !== 1) {
    die('<div class="alert alert-warning m-3">Esta remisión ya se encuentra en recepción o finalizada y no puede editarse.</div>');
}
?>
<style>
  /* pequeño gap entre botones cuando se envuelven en móviles */
  #boxAgregarProvInline .btn+.btn {
    margin-left: .5rem;
  }

  @media (max-width: 576px) {
    #boxAgregarProvInline .btn+.btn {
      margin-left: 0;
    }
  }
</style>
<div class="col-12">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Editar Remisión <small class="text-muted">Folio: <?= htmlentities($folioRem) ?></small></h4>
      <div class="text-right">
        <div><b>Almacén:</b> <?= htmlentities($almacenNom) ?></div>
        <div><b>Cliente:</b> <?= htmlentities($clienteNom ?: '—') ?></div>
        <div><b>Total Remisión:</b> $<span id="totalGeneral">0.00</span></div>
      </div>
    </div>

    <div class="card-body">
      <input type="hidden" id="remisionid" value="<?= $remisionid ?>">
      <input type="hidden" id="eventoid" value="<?= $eventoId ?>">
      <input type="hidden" id="almacenid" value="<?= $res[0]['REMISION_ALMACENID'] ?? 0 ?>">
      <input type="hidden" id="clienteid" value="<?= $res[0]['EVENTO_CLIENTEID'] ?? '' ?>">

      <ul class="nav nav-tabs" id="editTabs" role="tablist">
        <li class="nav-item">
          <a class="nav-link active" data-toggle="tab" href="#tab-articulos" role="tab">
            Artículos AMPAR
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-toggle="tab" href="#tab-proveedor" role="tab">
            Proveedor
          </a>
        </li>
      </ul>

      <div class="tab-content mt-3">

        <div class="tab-pane fade show active" id="tab-articulos" role="tabpanel">
          <div class="row">
            <div class="col-md-12">
              <label>Agregar artículo de maleta</label>
              <div class="form-row">
                <div class="col-12 col-md-8 mb-2">
                  <input type="hidden" id="editarticuloid">
                  <input type="text" id="editarticulo" class="form-control" placeholder="Buscar artículo por folio, nombre o clave">
                  <small class="text-muted">Sólo artículos disponibles en las maletas del evento.</small>
                </div>
                <div class="col-12 col-md-4">
                  <button type="button" class="btn btn-primary btn-block" id="btnAgregarArticulo">
                    Agregar a remisión
                  </button>
                </div>
              </div>
              
              <!-- Buscador de Equipo Capital Extra -->
              <div class="form-row mt-3">
                <div class="col-12">
                  <label><b>Agregar Equipo Capital Extra</b></label>
                  <div class="form-row">
                    <div class="col-12 col-md-8 mb-2">
                      <select id="selectExtraEq" class="form-control">
                        <option value="">Cargando Equipo Capital...</option>
                      </select>
                    </div>
                    <div class="col-12 col-md-4">
                      <button type="button" class="btn btn-success btn-block" id="btnAgregarExtraEq">
                        Agregar Extra a remisión
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              
            </div>
          </div>

          <!-- Buscador de Paquete UAP -->
          <div class="row mt-3">
            <div class="col-md-12">
              <label><b>Agregar Paquete UAP</b></label>
              <div class="form-row">
                <div class="col-12 col-md-8 mb-2">
                  <select id="selectPaqueteUap" class="form-control">
                    <option value="">Cargando Paquetes...</option>
                  </select>
                </div>
                <div class="col-12 col-md-4">
                  <button type="button" class="btn btn-info btn-block" id="btnAgregarPaqueteUap">
                    Agregar Paquete a remisión
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="table-responsive mt-3">
            <table class="table table-sm">
              <thead class="thead-light">
                <tr>
                  <th style="width:140px">Folio Stock</th>
                  <th>Clave / Artículo</th>
                  <th class="text-right" style="width:120px">Subtotal</th>
                  <th class="text-right" style="width:120px">IVA</th>
                  <th class="text-right" style="width:120px">Total</th>
                  <th style="width:80px">Acción</th>
                </tr>
              </thead>
              <tbody id="tbodyArticulos">
                <?php
                $sumaAmp = 0;
                foreach ($res as $re) {
                  if (empty($re['REMISIONARTICULO_ID'])) continue;
                  $sumaAmp += (float)$re['REMISIONARTICULO_TOTAL'];
                ?>
                  <tr data-row="ampar-<?= (int)$re['REMISIONARTICULO_ID'] ?>">
                    <td><?= htmlentities($re['STOCK_FOLIO']) ?></td>
                    <td>
                      <b><?= htmlentities($re['CLAVE_ARTICULO']) ?></b> - <?= htmlentities($re['ARTICULO_NOMBRE']) ?>
                    </td>
                    <td class="text-right">
                      <input type="number" step="0.01" class="form-control form-control-sm text-right input-subtotal-ampar" data-id="<?= (int)$re['REMISIONARTICULO_ID'] ?>" value="<?= number_format((float)$re['REMISIONARTICULO_SUBTOTAL'], 2, '.', '') ?>">
                    </td>
                    <td class="text-right">
                      <input type="number" step="0.01" class="form-control form-control-sm text-right input-iva-ampar" data-id="<?= (int)$re['REMISIONARTICULO_ID'] ?>" value="<?= number_format((float)$re['REMISIONARTICULO_IVA'], 2, '.', '') ?>" readonly>
                    </td>
                    <td class="text-right">
                      <input type="number" step="0.01" class="form-control form-control-sm text-right input-total-ampar" data-id="<?= (int)$re['REMISIONARTICULO_ID'] ?>" value="<?= number_format((float)$re['REMISIONARTICULO_TOTAL'], 2, '.', '') ?>" readonly>
                    </td>
                    <td>
                      <button type="button" class="btn btn-danger btn-sm"
                        onclick="eliminar('AMPAR','<?= (int)$re['REMISIONARTICULO_ID'] ?>')">
                        <i class="mdi mdi-delete"></i>
                      </button>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="2" class="text-right">Total artículos AMPAR</th>
                  <th class="text-right">$<span id="totalAmp"><?= number_format($sumaAmp, 2) ?></span></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <div class="tab-pane fade" id="tab-proveedor" role="tabpanel">
          <div class="row">
            <div class="col-md-12 mb-2">
              <label>Proveedor</label>
              <select id="editProveedor" class="form-control">
                <option value="">Cargando...</option>
              </select>
            </div>

            <div class="col-md-6 mb-2">
              <label>Artículo del catálogo</label>
              <input type="hidden" id="editProvArticuloID">
              <input type="text" class="form-control" id="editProvArticulo" placeholder="Buscar artículo proveedor">
            </div>
            <div class="col-md-3 mb-2">
              <label>Cantidad</label>
              <input type="number" id="editProvCantidad" class="form-control" min="1" value="1">
            </div>
            <div class="col-md-3 mb-2 d-flex align-items-end">
              <button type="button" class="btn btn-success btn-block" id="btnAgregarProveedor">
                Agregar a remisión
              </button>
            </div>
          </div>

          <div class="table-responsive mt-3">
            <table class="table table-sm">
              <thead class="thead-light">
                <tr>
                  <th>Proveedor</th>
                  <th>Artículo</th>
                  <th class="text-right" style="width:80px">Cantidad</th>
                  <th class="text-right" style="width:120px">Subtotal</th>
                  <th class="text-right" style="width:120px">IVA</th>
                  <th class="text-right" style="width:120px">Total</th>
                  <th style="width:80px">Acción</th>
                </tr>
              </thead>
              <tbody id="tbodyProveedor">
                <?php
                $sumaProv = 0;
                if ($resp != 0) {
                  foreach ($resp as $re) {
                    $subtotal = (float)($re['REMISIONPROVARTICULO_SUBTOTAL'] ?? 0);
                    $iva = (float)($re['REMISIONPROVARTICULO_IVA'] ?? 0);
                    $lineTotal = (float)($re['REMISIONPROVARTICULO_TOTAL'] ?? 0);
                    if ($lineTotal == 0) {
                      // fallback si aún no tenías guardado: precio_total * cantidad
                      $subtotal = ((float)($re['PRECIO_SUBTOTAL'] ?? 0)) * (float)$re['REMISIONPROVARTICULO_CANTIDAD'];
                      $iva = ((float)($re['PRECIO_IVA'] ?? 0)) * (float)$re['REMISIONPROVARTICULO_CANTIDAD'];
                      $lineTotal = ((float)($re['PRECIO_TOTAL'] ?? 0)) * (float)$re['REMISIONPROVARTICULO_CANTIDAD'];
                    }
                    if ($subtotal == 0 && $lineTotal > 0) {
                      $subtotal = $lineTotal / 1.16;
                      $iva = $lineTotal - $subtotal;
                    }
                    $sumaProv += $lineTotal;
                ?>
                    <tr data-row="prov-<?= (int)$re['REMISIONPROVARTICULO_ID'] ?>">
                      <td><?= htmlentities($re['NOMBREPROVEEDOR']) ?></td>
                      <td><?= htmlentities($re['NOMBRE_ARTICULO']) ?></td>
                      <td class="text-right cant-prov" data-id="<?= (int)$re['REMISIONPROVARTICULO_ID'] ?>"><?= (int)$re['REMISIONPROVARTICULO_CANTIDAD'] ?></td>
                      <td class="text-right">
                        <input type="number" step="0.01" class="form-control form-control-sm text-right input-subtotal-prov" data-id="<?= (int)$re['REMISIONPROVARTICULO_ID'] ?>" value="<?= number_format($subtotal, 2, '.', '') ?>">
                      </td>
                      <td class="text-right">
                        <input type="number" step="0.01" class="form-control form-control-sm text-right input-iva-prov" data-id="<?= (int)$re['REMISIONPROVARTICULO_ID'] ?>" value="<?= number_format($iva, 2, '.', '') ?>" readonly>
                      </td>
                      <td class="text-right">
                        <input type="number" step="0.01" class="form-control form-control-sm text-right input-total-prov" data-id="<?= (int)$re['REMISIONPROVARTICULO_ID'] ?>" value="<?= number_format($lineTotal, 2, '.', '') ?>" readonly>
                      </td>
                      <td>
                        <button type="button" class="btn btn-danger btn-sm"
                          onclick="eliminar('PROVEEDOR','<?= (int)$re['REMISIONPROVARTICULO_ID'] ?>')">
                          <i class="mdi mdi-delete"></i>
                        </button>
                      </td>
                    </tr>
                <?php }
                } ?>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="3" class="text-right">Total proveedor</th>
                  <th class="text-right">$<span id="totalProv"><?= number_format($sumaProv, 2) ?></span></th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

      </div>
      <div class="mt-4 text-right">
        <button type="button" class="btn btn-primary" id="btnGuardarEdicionRemision">Guardar Cambios</button>
      </div>
    </div>
  </div>
</div>

<script>
  let articulosData = [];

  function fmt(n) {
    return parseFloat(n || 0).toFixed(2);
  }

  function recalcTotales() {
    let tAmp = 0,
      tProv = 0;
    $("#tbodyArticulos tr").each(function() {
      const val = $(this).find(".input-total-ampar").val();
      tAmp += parseFloat(val || 0);
    });
    $("#tbodyProveedor tr").each(function() {
      const val = $(this).find(".input-total-prov").val();
      tProv += parseFloat(val || 0);
    });
    $("#totalAmp").text(fmt(tAmp));
    $("#totalProv").text(fmt(tProv));
    $("#totalGeneral").text(fmt(tAmp + tProv));
  }

  $(document).on("input", ".input-subtotal-ampar", function() {
    let subtotal = parseFloat($(this).val()) || 0;
    let iva = subtotal * 0.16;
    let total = subtotal + iva;
    $(this).closest('tr').find('.input-iva-ampar').val(iva.toFixed(2));
    $(this).closest('tr').find('.input-total-ampar').val(total.toFixed(2));
    recalcTotales();
  });

  $(document).on("input", ".input-subtotal-prov", function() {
    let subtotal = parseFloat($(this).val()) || 0;
    let iva = subtotal * 0.16;
    let total = subtotal + iva;
    $(this).closest('tr').find('.input-iva-prov').val(iva.toFixed(2));
    $(this).closest('tr').find('.input-total-prov').val(total.toFixed(2));
    recalcTotales();
  });

  $("#btnGuardarEdicionRemision").click(function() {
    let amparItems = [];
    $("#tbodyArticulos tr").each(function() {
      let id = $(this).find('.input-subtotal-ampar').data('id');
      let subtotal = parseFloat($(this).find('.input-subtotal-ampar').val()) || 0;
      let iva = parseFloat($(this).find('.input-iva-ampar').val()) || 0;
      let total = parseFloat($(this).find('.input-total-ampar').val()) || 0;
      if (id) amparItems.push({
        id: id,
        subtotal: subtotal,
        iva: iva,
        total: total
      });
    });

    let provItems = [];
    $("#tbodyProveedor tr").each(function() {
      let id = $(this).find('.input-subtotal-prov').data('id');
      let subtotal = parseFloat($(this).find('.input-subtotal-prov').val()) || 0;
      let iva = parseFloat($(this).find('.input-iva-prov').val()) || 0;
      let total = parseFloat($(this).find('.input-total-prov').val()) || 0;
      if (id) provItems.push({
        id: id,
        subtotal: subtotal,
        iva: iva,
        total: total
      });
    });

    const remisionid = $("#remisionid").val();

    $.ajax({
      url: "../ajax/remisiones.editar.guardar.php",
      type: "POST",
      data: {
        remisionid: remisionid,
        ampar: amparItems,
        prov: provItems
      },
      success: function(resp) {
        if (resp.trim() === "OK") {
          Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: 'Se han guardado los precios correctamente.',
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        } else {
          Swal.fire('Error', 'Error del servidor: ' + resp.trim().substring(0, 100), 'error');
        }
      }
    });
  });


  $(document).ready(function() {

    const remisionid = $("#remisionid").val();
    const eventoid = $("#eventoid").val();

    // Artículos del evento y equipo capital extra
    const almacenid = $("#almacenid").val();
    const clienteid = $("#clienteid").val();

    $.when(
      $.getJSON("../ajax/get.articulosbyevento.php?eventoid=" + eventoid),
      $.getJSON("../ajax/get.equipocapital.disponible.php?almacenid=" + almacenid + "&clienteid=" + clienteid)
    ).done(function(resEvent, resEq) {
      const dataEvent = resEvent[0] || [];
      const dataEq = resEq[0] || [];

      // Artículos normales del evento
      articulosData = dataEvent || [];

      $("#editarticulo").autocomplete({
        source: function(request, response) {
          const term = request.term.toUpperCase();
          response($.map(articulosData, function(o) {
            const label = ((o.FOLIO || '') + ' - ' + (o.ARTICULO_NOMBRE || '').toUpperCase() + ' (' + (o.CLAVE_ARTICULO || '').toUpperCase() + ')');
            if (label.indexOf(term) >= 0) {
              return {
                label: label,
                value: label,
                id: o.ID
              };
            }
            return null;
          }).filter(Boolean));
        },
        select: function(e, ui) {
          $("#editarticuloid").val(ui.item.id);
        }
      });

      // Rellenar select de Equipo Capital Extra
      window.equipoCapitalDisponibleLocal = dataEq || [];
      let optHtml = "<option value=''>Seleccione Equipo Capital Extra...</option>";
      $.each(dataEq, function(i, o) {
        const label = (o.FOLIO ? o.FOLIO + ' - ' : '') + (o.ARTICULO_NOMBRE || '').toUpperCase() + (o.SERIE ? ' (S/N: ' + o.SERIE + ')' : '') + (o.CLAVE_ARTICULO ? ' (' + o.CLAVE_ARTICULO + ')' : '');
        optHtml += `<option value="${o.ID}">${label}</option>`;
      });
      $("#selectExtraEq").html(optHtml);
    });

    // Proveedores agrupados (Evento vs Otros)
    $.when(
      $.getJSON("../ajax/get.proveedores.by.evento.php?eventoid=" + eventoid),
      $.getJSON("../ajax/get.proveedores.php")
    ).done(function(res1, res2) {
      let provEvento = res1[0] || [];
      let provGlobal = res2[0] || [];

      let html = "<option value=''>Seleccione...</option>";

      // Optgroup Evento
      if (provEvento.length > 0) {
        html += "<optgroup label='Proveedores del evento'>";
        $.each(provEvento, function(i, it) {
          html += `<option value="${it.ID}">${it.NOMBRE}</option>`;
        });
        html += "</optgroup>";
      }

      // Optgroup Otros
      html += "<optgroup label='Otros proveedores'>";
      $.each(provGlobal, function(i, it) {
        // Filtrar para no repetir
        if (!provEvento.some(p => String(p.ID) === String(it.ID))) {
          html += `<option value="${it.ID}">${it.NOMBRE}</option>`;
        }
      });
      html += "</optgroup>";

      $("#editProveedor").html(html);
    });

    // Catálogo de artículos (para proveedor)
    $.getJSON("../ajax/get.articulos.catalogo.php", function(data) {
      $("#editProvArticulo").autocomplete({
        source: function(req, resp) {
          const t = (req.term || '').toUpperCase();
          resp($.map(data || [], function(o) {
            const lab = (o.NOMBRE || '').toUpperCase();
            if (lab.indexOf(t) >= 0) {
              return {
                label: o.NOMBRE,
                value: o.NOMBRE,
                id: o.ID
              };
            }
          }));
        },
        select: function(e, ui) {
          $("#editProvArticuloID").val(ui.item.id);
        }
      });
    });

    // Agregar artículo AMPAR a remisión (server calcula montos)
    $("#btnAgregarArticulo").click(function() {
      const stockid = $("#editarticuloid").val();
      const remisionid = $("#remisionid").val();
      if (!stockid) return alert("Selecciona un artículo del evento.");
      $.post("../ajax/remisiones.detalle.agregar.php", {
        remisionid,
        stockid
      }, function(res) {
        if (res.trim() === "") {
          // Recuperar fila recién insertada para pintar (obtenemos el stock desde catálogo local)
          const found = articulosData.find(x => String(x.ID) === String(stockid));
          const nombre = found ? (`<b>${found.CLAVE_ARTICULO || ''}</b> - ${found.ARTICULO_NOMBRE || ''}`) : 'Artículo';
          // No sabemos el total exacto que calculó el server; pedimos fila “lightweight”
          $.getJSON("../ajax/remisiones.detalle.ultimo.php?remisionid=" + remisionid + "&stockid=" + stockid, function(r) {
            const row = r || {};
            const rid = row.REMISIONARTICULO_ID || ("tmp-" + Date.now());
            const subtotal = parseFloat(row.REMISIONARTICULO_SUBTOTAL || 0);
            const iva = parseFloat(row.REMISIONARTICULO_IVA || 0);
            const total = parseFloat(row.REMISIONARTICULO_TOTAL || 0);
            $("#tbodyArticulos").append(`
            <tr data-row="ampar-${rid}">
              <td>${row.STOCK_FOLIO || ''}</td>
              <td>${nombre}</td>
              <td class="text-right">
                  <input type="number" step="0.01" class="form-control form-control-sm text-right input-subtotal-ampar" data-id="${rid}" value="${subtotal.toFixed(2)}">
              </td>
              <td class="text-right">
                  <input type="number" step="0.01" class="form-control form-control-sm text-right input-iva-ampar" data-id="${rid}" value="${iva.toFixed(2)}" readonly>
              </td>
              <td class="text-right">
                  <input type="number" step="0.01" class="form-control form-control-sm text-right input-total-ampar" data-id="${rid}" value="${total.toFixed(2)}" readonly>
              </td>
              <td>
                <button type="button" class="btn btn-danger btn-sm" onclick="eliminar('AMPAR','${rid}')">
                  <i class="mdi mdi-delete"></i>
                </button>
              </td>
            </tr>
          `);
            $("#editarticulo").val("");
            $("#editarticuloid").val("");
            recalcTotales();
          });
        } else if (res.trim() === "YA") {
          alert("Este artículo ya está en la remisión.");
        } else {
          alert(res);
        }
      });
    });

    // Agregar Equipo Capital Extra a remisión
    $("#btnAgregarExtraEq").click(function() {
      const stockid = $("#selectExtraEq").val();
      const remisionid = $("#remisionid").val();
      if (!stockid) return alert("Selecciona un artículo de Equipo Capital.");
      
      $.post("../ajax/remisiones.detalle.agregar.php", {
        remisionid,
        stockid
      }, function(res) {
        if (res.trim() === "") {
          const found = (window.equipoCapitalDisponibleLocal || []).find(x => String(x.ID) === String(stockid));
          const nombre = found ? (`<b>${found.CLAVE_ARTICULO || ''}</b> - ${found.ARTICULO_NOMBRE || ''}`) : 'Artículo';
          
          $.getJSON("../ajax/remisiones.detalle.ultimo.php?remisionid=" + remisionid + "&stockid=" + stockid, function(r) {
            const row = r || {};
            const rid = row.REMISIONARTICULO_ID || ("tmp-" + Date.now());
            const subtotal = parseFloat(row.REMISIONARTICULO_SUBTOTAL || 0);
            const iva = parseFloat(row.REMISIONARTICULO_IVA || 0);
            const total = parseFloat(row.REMISIONARTICULO_TOTAL || 0);
            $("#tbodyArticulos").append(`
              <tr data-row="ampar-${rid}">
                <td>${row.STOCK_FOLIO || ''}</td>
                <td>${nombre} <span class="badge badge-success text-white" style="font-size:0.75em; padding:3px 6px;">EXTRA</span></td>
                <td class="text-right">
                    <input type="number" step="0.01" class="form-control form-control-sm text-right input-subtotal-ampar" data-id="${rid}" value="${subtotal.toFixed(2)}">
                </td>
                <td class="text-right">
                    <input type="number" step="0.01" class="form-control form-control-sm text-right input-iva-ampar" data-id="${rid}" value="${iva.toFixed(2)}" readonly>
                </td>
                <td class="text-right">
                    <input type="number" step="0.01" class="form-control form-control-sm text-right input-total-ampar" data-id="${rid}" value="${total.toFixed(2)}" readonly>
                </td>
                <td>
                  <button type="button" class="btn btn-danger btn-sm" onclick="eliminar('AMPAR','${rid}')">
                    <i class="mdi mdi-delete"></i>
                  </button>
                </td>
              </tr>
            `);
            $("#selectExtraEq").val("");
            recalcTotales();
            
            Swal.fire({
              icon: 'success',
              title: 'Agregado',
              text: 'Equipo Capital agregado a la remisión.',
              timer: 2000,
              showConfirmButton: false
            });
          });
        } else if (res.trim() === "YA") {
          alert("Este artículo ya está en la remisión.");
        } else {
          alert(res);
        }
      });
    });

    // Obtener y llenar Paquetes UAP
    $.getJSON("../ajax/get.articulos.catalogo.php", function(data) {
      var opcionesPaquete = '<option value="">Selecciona un paquete...</option>';
      $.each(data, function(i, obj) {
          var ref = (obj.CLAVE_ARTICULO || "").toUpperCase();
          if (ref.endsWith("-UAP")) {
              opcionesPaquete += `<option value="${obj.ID}">${obj.NOMBRE} (${obj.CLAVE_ARTICULO})</option>`;
          }
      });
      $("#selectPaqueteUap").html(opcionesPaquete);
    });

    $("#btnAgregarPaqueteUap").click(function() {
      const articuloId = $("#selectPaqueteUap").val();
      const cantidad = 1;
      const remisionid = $("#remisionid").val();
      if (!articuloId) return alert("Seleccione un paquete UAP.");

      $.post("../ajax/remisiones.proveedor.detalle.agregar.php", {
          remisionid,
          proveedorid: 0,
          articuloid: articuloId,
          cantidad
        },
        function(res) {
          if (res.trim() === "") {
            $.getJSON("../ajax/remisiones.proveedor.detalle.ultimo.php?remisionid=" + remisionid, function(r) {
              const row = r || {};
              const rid = row.REMISIONPROVARTICULO_ID || ("tmpp-" + Date.now());
              const subtotal = parseFloat(row.REMISIONPROVARTICULO_SUBTOTAL || 0);
              const iva = parseFloat(row.REMISIONPROVARTICULO_IVA || 0);
              const total = parseFloat(row.REMISIONPROVARTICULO_TOTAL || 0);
              $("#tbodyProveedor").append(`
              <tr data-row="prov-${rid}">
                <td>PAQUETE UAP</td>
                <td><b>${row.CLAVE_ARTICULO || ''}</b> - ${row.ARTICULO_NOMBRE || ''}</td>
                <td class="text-center">1</td>
                <td class="text-right">
                    <input type="number" step="0.01" class="form-control form-control-sm text-right input-subtotal-prov" data-id="${rid}" value="${subtotal.toFixed(2)}">
                </td>
                <td class="text-right">
                    <input type="number" step="0.01" class="form-control form-control-sm text-right input-iva-prov" data-id="${rid}" value="${iva.toFixed(2)}" readonly>
                </td>
                <td class="text-right">
                    <input type="number" step="0.01" class="form-control form-control-sm text-right input-total-prov" data-id="${rid}" value="${total.toFixed(2)}" readonly>
                </td>
                <td>
                  <button type="button" class="btn btn-danger btn-sm" onclick="eliminar('PROV','${rid}')">
                    <i class="mdi mdi-delete"></i>
                  </button>
                </td>
              </tr>
            `);
              $("#selectPaqueteUap").val("");
              recalcTotales();
              
              Swal.fire({
                icon: 'success',
                title: 'Agregado',
                text: 'Paquete UAP agregado a la remisión.',
                timer: 2000,
                showConfirmButton: false
              });
            });
          } else {
            alert(res);
          }
        });
    });

    // Agregar renglón proveedor a remisión (server calcula montos)
    $("#btnAgregarProveedor").click(function() {
      const provId = $("#editProveedor").val();
      if (!provId) {
        return alert("Primero seleccione un proveedor.");
      }
      const articuloId = $("#editProvArticuloID").val();
      const cantidad = parseInt($("#editProvCantidad").val() || "0", 10);
      const remisionid = $("#remisionid").val();
      if (!articuloId || !cantidad || cantidad <= 0) return alert("Complete artículo y cantidad.");

      $.post("../ajax/remisiones.proveedor.detalle.agregar.php", {
          remisionid,
          proveedorid: provId,
          articuloid: articuloId,
          cantidad
        },
        function(res) {
          if (res.trim() === "") {
            // Traer fila recién agregada para pintar simple
            $.getJSON("../ajax/remisiones.proveedor.detalle.ultimo.php?remisionid=" + remisionid, function(r) {
              const row = r || {};
              const rid = row.REMISIONPROVARTICULO_ID || ("tmp-" + Date.now());
              let subtotal = parseFloat(row.REMISIONPROVARTICULO_SUBTOTAL || ((row.PRECIO_SUBTOTAL || 0) * (row.REMISIONPROVARTICULO_CANTIDAD || 1)));
              let iva = parseFloat(row.REMISIONPROVARTICULO_IVA || ((row.PRECIO_IVA || 0) * (row.REMISIONPROVARTICULO_CANTIDAD || 1)));
              let total = parseFloat(row.REMISIONPROVARTICULO_TOTAL || ((row.PRECIO_TOTAL || 0) * (row.REMISIONPROVARTICULO_CANTIDAD || 1)));

              if (subtotal == 0 && total > 0) {
                subtotal = total / 1.16;
                iva = total - subtotal;
              }

              $("#tbodyProveedor").append(`
              <tr data-row="prov-${rid}">
                <td>${row.NOMBREPROVEEDOR || ''}</td>
                <td>${row.NOMBRE_ARTICULO || ''}</td>
                <td class="text-right cant-prov" data-id="${rid}">${row.REMISIONPROVARTICULO_CANTIDAD || cantidad}</td>
                <td class="text-right">
                    <input type="number" step="0.01" class="form-control form-control-sm text-right input-subtotal-prov" data-id="${rid}" value="${subtotal.toFixed(2)}">
                </td>
                <td class="text-right">
                    <input type="number" step="0.01" class="form-control form-control-sm text-right input-iva-prov" data-id="${rid}" value="${iva.toFixed(2)}" readonly>
                </td>
                <td class="text-right">
                    <input type="number" step="0.01" class="form-control form-control-sm text-right input-total-prov" data-id="${rid}" value="${total.toFixed(2)}" readonly>
                </td>
                <td>
                  <button type="button" class="btn btn-danger btn-sm" onclick="eliminar('PROVEEDOR','${rid}')">
                    <i class="mdi mdi-delete"></i>
                  </button>
                </td>
              </tr>
            `);
              $("#editProvArticulo").val("");
              $("#editProvArticuloID").val("");
              $("#editProvCantidad").val("1");
              recalcTotales();
            });
          } else {
            alert(res);
          }
        }
      );
    });

    // Calcular totales iniciales
    recalcTotales();
  });

  // Eliminar renglones (AMPAR / PROVEEDOR)
  function eliminar(tipo, id) {
    if (!confirm("¿Eliminar este renglón?")) return;
    $.post("../ajax/remisiones.eliminardetalle.php", {
      tipo,
      id
    }, function(res) {
      if (res.trim() === "") {
        $(`[data-row="${tipo==='AMPAR'?'ampar':'prov'}-${id}"]`).remove();
        recalcTotales();
      } else {
        alert(res);
      }
    });
  }
</script>