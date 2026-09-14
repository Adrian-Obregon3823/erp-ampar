<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<?php
$remisionid = (int) base64_decode($_GET['remisionid']);
$remisiones = new remisiones();
$res = $remisiones->getremisionbyid(null, $remisionid);
if (!$res || $res == 0) { echo "Remision no encontrada."; exit; }
$remision = $res[0];
if ((int)$remision['REMISION_STATUS'] !== 1) {
    die('<div class="alert alert-warning m-3">Esta remision ya se encuentra en recepcion o finalizada y no puede editarse.</div>');
}
$tipoSalida = $remision['CONCEPTO_REMISION'];
$almacenId  = $remision['REMISION_ALMACENID'];
$folioRem   = $remision['REMISION_FOLIO'];
$almacenNom = $remision['REMISION_ALMACEN_NOMBRE'];

$sumaInicial = 0;
foreach ($res as $r) {
    if (!empty($r['REMISIONARTICULO_ID'])) {
        $sumaInicial += (float)$r['REMISIONARTICULO_TOTAL'];
    }
}
?>
<style>
    /* ---- Buscador custom ---- */
    .art-search-wrap { position:relative; }
    .art-search-wrap .art-dropdown {
        display:none; position:absolute; top:calc(100% + 4px); left:0; right:0;
        background:#fff; border:1px solid #ced4da; border-radius:6px;
        box-shadow:0 4px 16px rgba(0,0,0,0.1); z-index:9999;
        max-height:280px; overflow-y:auto;
    }
    .art-search-wrap .art-dropdown.open { display:block; }
    .art-dropdown-header { padding:6px 12px; font-size:0.75rem; color:#6c757d; background:#f8f9fa; border-bottom:1px solid #e9ecef; border-radius:6px 6px 0 0; }
    .art-dropdown-item { display:flex; align-items:center; gap:10px; padding:8px 12px; cursor:pointer; border-bottom:1px solid #f1f1f1; transition:background .12s; }
    .art-dropdown-item:last-child { border-bottom:none; }
    .art-dropdown-item:hover { background:#f0f4ff; }
    .art-dropdown-folio { background:#e8edff; color:#1a338b; font-size:0.72rem; font-weight:700; padding:3px 7px; border-radius:4px; white-space:nowrap; min-width:70px; text-align:center; flex-shrink:0; }
    .art-dropdown-info { flex:1; min-width:0; }
    .art-dropdown-nombre { font-size:0.85rem; font-weight:600; color:#212529; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .art-dropdown-sub { font-size:0.73rem; color:#6c757d; margin-top:1px; }
    .art-dropdown-price { font-size:0.85rem; font-weight:700; color:#1a338b; white-space:nowrap; flex-shrink:0; }
    .art-dropdown-cad { font-size:0.68rem; font-weight:600; padding:1px 5px; border-radius:8px; white-space:nowrap; flex-shrink:0; }
    .art-dropdown-empty { padding:14px; text-align:center; color:#adb5bd; font-size:0.83rem; }
    /* input precio editable en tabla */
    .input-precio-edit { border:1px solid #dee2e6; border-radius:4px; padding:3px 7px; font-size:0.85rem; width:90px; text-align:right; }
    .input-precio-edit:focus { outline:none; border-color:#1a338b; box-shadow:0 0 0 2px rgba(26,51,139,0.1); }
    .badge-delete-circle {
        background:#ff6b6b; color:white; border-radius:50%; width:30px; height:30px;
        display:flex; align-items:center; justify-content:center; border:none; transition:background .18s;
    }
    .badge-delete-circle:hover { background:#fa5252; }
</style>

<div class="col-12">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Editar Remision &nbsp;<small class="text-muted">Folio: <?= htmlentities($folioRem) ?></small></h4>
      <div class="text-right" style="font-size:0.87rem;">
        <div><b>Almacen:</b> <?= htmlentities($almacenNom) ?></div>
        <div><b>Tipo:</b> <?= str_replace('_',' ',$tipoSalida) ?></div>
        <div><b>Total:</b> $<span id="rmTotalHeader"><?= number_format($sumaInicial,2) ?></span></div>
      </div>
    </div>
    <div class="card-body">
      <form id="form-remision-mostrador-edit">
        <input type="hidden" name="remisionid" value="<?= $remisionid ?>">
        <input type="hidden" id="tiposalida" name="tiposalida" value="<?= $tipoSalida ?>">
        <input type="hidden" id="invalmacen"  name="invalmacen"  value="<?= $almacenId ?>">

        <!-- Buscador -->
        <div class="form-group">
          <label>Agregar articulo del almacen</label>
          <div class="form-row">
            <div class="col-12 col-md-9 mb-2">
              <div class="art-search-wrap">
                <input type="text" id="articulo" class="form-control form-control-lg"
                       placeholder="Buscar articulo por folio, nombre o clave" autocomplete="off">
                <div class="art-dropdown" id="artDropdown"></div>
              </div>
              <small class="text-muted">Escribe para buscar articulos disponibles.</small>
            </div>
            <div class="col-12 col-md-3 mb-2">
              <button type="button" id="btnAgregarArticuloMostrador"
                      class="btn btn-primary btn-block btn-lg"
                      style="background-color:#1a338b;border-color:#1a338b;" disabled>
                Agregar a remision
              </button>
            </div>
          </div>
        </div>

        <!-- Hiddens temporales -->
        <input type="hidden" id="idarticulo">
        <input type="hidden" id="invdetid">
        <input type="hidden" id="subtotaltmp">
        <input type="hidden" id="ivatmp">
        <input type="hidden" id="totaltmp">
        <input type="hidden" id="foliotmp">
        <input type="hidden" id="cvetmp">
        <input type="hidden" id="maletatmp">

        <!-- Tabla -->
        <div class="table-responsive mt-4">
          <table class="table">
            <thead class="bg-light">
              <tr>
                <th>Folio Stock</th>
                <th>Clave / Articulo</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">IVA</th>
                <th class="text-right">Total</th>
                <th class="text-center" style="width:60px;">Accion</th>
              </tr>
            </thead>
            <tbody id="articulosMostradorBody">
              <?php foreach ($res as $r): ?>
                <?php if (empty($r['REMISIONARTICULO_ID'])) continue; ?>
                <?php
                  $tot = (float)$r['REMISIONARTICULO_TOTAL'];
                  $sub = (float)($r['REMISIONARTICULO_SUBTOTAL'] ?? ($tot / 1.16));
                  $iva = $tot - $sub;
                ?>
                <tr data-id="<?= $r['REMISIONARTICULO_ID'] ?>" data-invdetid="<?= $r['REMISIONARTICULO_STOCKID'] ?? '' ?>">
                  <td><?= htmlentities($r['STOCK_FOLIO']) ?></td>
                  <td>
                    <b><?= htmlentities($r['CLAVE_ARTICULO']) ?></b> - <?= htmlentities($r['ARTICULO_NOMBRE']) ?>
                    <?php if ($res[0]['CONCEPTO_REMISION']==='PROCEDIMIENTO' && !empty($r['MALETA_NOMBRE'])): ?>
                      <br><small class="text-muted">Maleta: <?= htmlentities($r['MALETA_FOLIO']) ?> - <?= htmlentities($r['MALETA_NOMBRE']) ?></small>
                    <?php endif; ?>
                  </td>
                  <td class="text-right">
                    <input type="number" step="0.01" class="input-precio-edit subtotal-edit"
                           data-detalleid="<?= $r['REMISIONARTICULO_ID'] ?>"
                           value="<?= number_format($sub,2,'.','') ?>">
                  </td>
                  <td class="text-right td-iva">$<?= number_format($iva,2) ?></td>
                  <td class="text-right td-total">$<?= number_format($tot,2) ?></td>
                  <td class="text-center">
                    <button type="button" class="badge-delete-circle btnQuitarArticulo"
                            data-id="<?= $r['REMISIONARTICULO_ID'] ?>">
                      <i class="mdi mdi-delete"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="4" class="text-right py-3">Total articulos AMPAR</th>
                <th class="text-right py-3">$<span id="rmTotal"><?= number_format($sumaInicial,2) ?></span></th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="row mt-4">
          <div class="col-12 text-right">
            <button type="button" class="btn btn-light btn-lg px-5 border" data-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var articulosDisponibles = [];
var articuloSeleccionado = null;
var dropdownItems = [];

/* ── Totales globales ── */
function actualizarTotales() {
    var total = 0;
    $("#articulosMostradorBody tr").each(function() {
        var txt = $(this).find(".td-total").text().replace("$","").replace(/,/g,"");
        total += parseFloat(txt) || 0;
    });
    var fmt = total.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
    $("#rmTotal").text(fmt);
    $("#rmTotalHeader").text(fmt);
}

/* ── IDs ya en tabla ── */
function getIdsEnTabla() {
    var ids = new Set();
    $("#articulosMostradorBody tr").each(function() {
        var id = $(this).data("invdetid");
        if (id) ids.add(String(id));
    });
    return ids;
}

/* ── Caducidad ── */
function estadoCad(fecha) {
    if (!fecha) return {color:"#6c757d",bg:"#f8f9fa",label:"Sin cad."};
    var hoy = new Date(); hoy.setHours(0,0,0,0);
    var f   = new Date(fecha+"T00:00:00");
    if (isNaN(f)) return {color:"#6c757d",bg:"#f8f9fa",label:"Sin cad."};
    var m = new Date(hoy); m.setMonth(m.getMonth()+1);
    if (f < hoy)  return {color:"#721c24",bg:"#f8d7da",label:"Caducado"};
    if (f <= m)   return {color:"#856404",bg:"#fff3cd",label:"< 1 mes"};
    return {color:"#155724",bg:"#d4edda",label:"Vigente"};
}

/* ── Render dropdown ── */
function renderDropdown(items) {
    var $d = $("#artDropdown");
    dropdownItems = items;
    if (!items.length) {
        $d.html('<div class="art-dropdown-empty">Sin resultados</div>').addClass("open");
        return;
    }
    var h = '<div class="art-dropdown-header">'+items.length+' resultado'+(items.length!==1?'s':'')+'</div>';
    items.forEach(function(item, i) {
        var cad = estadoCad(item.CADUCIDAD);
        var tot = parseFloat(item.TOTAL||0).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
        var lote = item.LOTE ? ' &middot; Lote: '+item.LOTE : '';
        h += '<div class="art-dropdown-item" data-idx="'+i+'">'
           + '<div class="art-dropdown-folio">'+(item.STOCK_FOLIO||'')+'</div>'
           + '<div class="art-dropdown-info">'
           +   '<div class="art-dropdown-nombre">'+(item.NOMBRE||'')+'</div>'
           +   '<div class="art-dropdown-sub">'+(item.CLAVE_ARTICULO||'')+lote+'</div>'
           + '</div>'
           + '<span class="art-dropdown-cad" style="color:'+cad.color+';background:'+cad.bg+'">'+cad.label+'</span>'
           + '<div class="art-dropdown-price">$'+tot+'</div>'
           + '</div>';
    });
    $d.html(h).addClass("open");
}

/* ── Seleccionar ── */
function seleccionar(item) {
    articuloSeleccionado = item;
    $("#articulo").val((item.STOCK_FOLIO||'') + ' - ' + (item.NOMBRE||''));
    $("#idarticulo").val(item.ID||"");
    $("#invdetid").val(item.INVDETID||"");
    $("#foliotmp").val(item.STOCK_FOLIO||"");
    $("#cvetmp").val(item.CLAVE_ARTICULO||"");
    $("#subtotaltmp").val(item.SUBTOTAL||0);
    $("#ivatmp").val(item.IVA||0);
    $("#totaltmp").val(item.TOTAL||0);
    $("#maletatmp").val(item.MALETA_NOMBRE ? (item.MALETA_FOLIO+" - "+item.MALETA_NOMBRE) : "");
    $("#btnAgregarArticuloMostrador").prop("disabled",false);
    $("#artDropdown").removeClass("open");
}

function limpiarSeleccion() {
    articuloSeleccionado = null;
    $("#articulo").val("");
    $("#idarticulo,#invdetid,#subtotaltmp,#ivatmp,#totaltmp,#foliotmp,#cvetmp,#maletatmp").val("");
    $("#btnAgregarArticuloMostrador").prop("disabled",true);
    $("#artDropdown").removeClass("open");
}

/* ── Catalogo ── */
function cargarCatalogo() {
    var almacenId  = $("#invalmacen").val();
    var tipoSalida = $("#tiposalida").val();
    /* REMISION_CLIENTEID es positivo (guardado con abs()); precios de clientes
       usan ID negativo en ARTPRECIO_CLIENTEID → negamos.
       Hospitales usan positivo → los dejamos como vienen de EVENTO_HOSPITALID. */
    var rawCliente  = '<?= (int)($remision["REMISION_CLIENTEID"] ?? 0) ?>';
    var rawHospital = '<?= (int)($remision["EVENTO_HOSPITALID"] ?? 0) ?>';
    var clienteId;
    if (rawCliente > 0) {
        clienteId = rawCliente * -1;          // cliente → negativo
    } else if (rawHospital > 0) {
        clienteId = rawHospital;              // hospital → positivo
    } else {
        clienteId = '';
    }
    if (!almacenId || !tipoSalida) return;
    $.getJSON("../ajax/get.articulos.remision.mostrador.php", {
        almacenid: almacenId, tiposalida: tipoSalida, clienteid: clienteId
    }, function(data) { articulosDisponibles = data || []; });
}

$(document).ready(function() {
    actualizarTotales();
    cargarCatalogo();

    /* ── Editar subtotal en tabla → recalc IVA y Total ── */
    $(document).on("input", ".subtotal-edit", function() {
        var tr   = $(this).closest("tr");
        var sub  = parseFloat($(this).val()) || 0;
        var iva  = sub * 0.16;
        var tot  = sub + iva;
        tr.find(".td-iva").text("$"+iva.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}));
        tr.find(".td-total").text("$"+tot.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}));
        actualizarTotales();

        /* Guardar en BD al perder foco */
        $(this).off("change.save").on("change.save", function() {
            var detalleid = $(this).data("detalleid");
            $.ajax({
                url: "../ajax/remisiones.mostrador.detalle.actualizar.php",
                type: "POST",
                data: { detalleid: detalleid, subtotal: sub.toFixed(2), iva: iva.toFixed(2), total: tot.toFixed(2) },
                dataType: "json",
                success: function(r) {
                    if (r.status !== "success") Swal.fire({icon:"warning", html: r.message||"Error al guardar precio."});
                }
            });
        });
    });

    /* ── Buscador ── */
    var st;
    $("#articulo").on("input", function() {
        var val = $(this).val().trim();
        if (!val) { limpiarSeleccion(); return; }
        if (articuloSeleccionado &&
            val !== (articuloSeleccionado.STOCK_FOLIO||'') + ' - ' + (articuloSeleccionado.NOMBRE||'')) {
            articuloSeleccionado = null;
            $("#btnAgregarArticuloMostrador").prop("disabled",true);
            $("#idarticulo,#invdetid").val("");
        }
        clearTimeout(st);
        st = setTimeout(function() {
            var term = val.toUpperCase();
            var ids  = getIdsEnTabla();
            var res  = articulosDisponibles.filter(function(item) {
                var s = ((item.STOCK_FOLIO||"")+" "+(item.CLAVE_ARTICULO||"")+" "+(item.NOMBRE||"")+" "+(item.LOTE||"")).toUpperCase();
                return s.indexOf(term) !== -1 && !ids.has(String(item.INVDETID));
            });
            renderDropdown(res);
        }, 180);
    });

    $("#articulo").on("focus", function() {
        if ($(this).val().trim() && !articuloSeleccionado) $(this).trigger("input");
    });

    $(document).on("click","#artDropdown .art-dropdown-item", function() {
        var i = parseInt($(this).data("idx"));
        if (dropdownItems[i]) seleccionar(dropdownItems[i]);
    });

    $(document).on("click", function(e) {
        if (!$(e.target).closest(".art-search-wrap").length) $("#artDropdown").removeClass("open");
    });

    /* ── Agregar ── */
    $("#btnAgregarArticuloMostrador").on("click", function() {
        if (!articuloSeleccionado) { Swal.fire({icon:"warning",html:"Selecciona un articulo valido."}); return; }
        var invdetid = articuloSeleccionado.INVDETID;
        if (getIdsEnTabla().has(String(invdetid))) { Swal.fire({icon:"warning",html:"El articulo ya esta en la lista."}); return; }
        var sub = parseFloat(articuloSeleccionado.SUBTOTAL||0);
        var iv  = parseFloat(articuloSeleccionado.IVA||0);
        var tot = parseFloat(articuloSeleccionado.TOTAL||0);
        var remisionid = $("input[name='remisionid']").val();
        var self = $(this);
        self.prop("disabled",true).text("Guardando...");
        $.ajax({
            url:"../ajax/remisiones.mostrador.detalle.agregar.php",
            type:"POST",
            data:{remisionid:remisionid,invdetid:invdetid,subtotal:sub,iva:iv,total:tot},
            dataType:"json",
            success: function(resp) {
                if (resp.status==="success") {
                    var newId = resp.id;
                    var tipoS = $("#tiposalida").val()||"";
                    var malTxt = articuloSeleccionado.MALETA_NOMBRE ? (articuloSeleccionado.MALETA_FOLIO+" - "+articuloSeleccionado.MALETA_NOMBRE) : "";
                    var malH  = (tipoS==='PROCEDIMIENTO' && malTxt) ? '<br><small class="text-muted">Maleta: '+malTxt+'</small>' : "";
                    var subFmt = sub.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
                    var ivFmt  = iv.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
                    var totFmt = tot.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
                    var row = '<tr data-id="'+newId+'" data-invdetid="'+invdetid+'">'
                        + '<td>'+(articuloSeleccionado.STOCK_FOLIO||'')+'</td>'
                        + '<td><b>'+(articuloSeleccionado.CLAVE_ARTICULO||'')+'</b> - '+(articuloSeleccionado.NOMBRE||'')+malH+'</td>'
                        + '<td class="text-right">'
                        +   '<input type="number" step="0.01" class="input-precio-edit subtotal-edit" data-detalleid="'+newId+'" value="'+sub.toFixed(2)+'">'
                        + '</td>'
                        + '<td class="text-right td-iva">$'+ivFmt+'</td>'
                        + '<td class="text-right td-total">$'+totFmt+'</td>'
                        + '<td class="text-center"><button type="button" class="badge-delete-circle btnQuitarArticulo" data-id="'+newId+'"><i class="mdi mdi-delete"></i></button></td>'
                        + '</tr>';
                    $("#articulosMostradorBody").append(row);
                    actualizarTotales();
                    limpiarSeleccion();
                } else {
                    Swal.fire({icon:"error",html:resp.message||"Error al agregar."});
                    self.prop("disabled",false).text("Agregar a remision");
                }
            },
            error: function() {
                Swal.fire({icon:"error",html:"Error de conexion."});
                self.prop("disabled",false).text("Agregar a remision");
            }
        });
    });

    /* ── Eliminar ── */
    $(document).on("click",".btnQuitarArticulo", function() {
        var btn = $(this); var detalleid = btn.data("id");
        Swal.fire({
            title:"Eliminar este articulo?", icon:"warning",
            showCancelButton:true, confirmButtonText:"Si, eliminar",
            cancelButtonText:"Cancelar", confirmButtonColor:"#fa5252"
        }).then(function(r) {
            if (r.isConfirmed) {
                $.ajax({
                    url:"../ajax/remisiones.mostrador.detalle.eliminar.php",
                    type:"POST", data:{detalleid:detalleid}, dataType:"json",
                    success: function(resp) {
                        if (resp.status==="success") { btn.closest("tr").remove(); actualizarTotales(); }
                        else Swal.fire({icon:"error",html:resp.message||"Error."});
                    }
                });
            }
        });
    });

    /* ── Guardar (boton externo) ── */
    $(document).on("click","#btnGuardarEdicionRemisionMostrador", function() {
        if ($("#articulosMostradorBody tr").length===0) { Swal.fire({icon:"warning",html:"Agregue al menos un articulo."}); return; }
        var fd = new FormData(document.getElementById("form-remision-mostrador-edit"));
        $.ajax({
            url:"../ajax/remisiones.mostrador.actualizar.php",
            type:"POST",data:fd,processData:false,contentType:false,dataType:"json",
            beforeSend:function(){$("#loading").show();},
            success:function(resp){
                if(resp&&resp.status==="success") Swal.fire({icon:"success",html:resp.message}).then(function(){location.reload();});
                else Swal.fire({icon:"warning",html:resp.message||"Error al actualizar."});
            },
            error:function(){Swal.fire({icon:"error",html:"Error en el servidor."});},
            complete:function(){$("#loading").hide();}
        });
    });
});
</script>
