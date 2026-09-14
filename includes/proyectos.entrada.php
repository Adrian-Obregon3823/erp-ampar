<?php
include_once("../includes/sesion.php");
include_once("../includes/includes.php");

$proyectoid = isset($_GET['proyectoid']) ? (int)base64_decode($_GET['proyectoid']) : 0;
if (!$proyectoid) { echo '<div class="alert alert-danger">Proyecto no válido.</div>'; exit; }

$db = new FirebirdConnection(true);
$proy = $db->query("
    SELECT P.PROYECTO_ID, P.PROYECTO_FOLIO, P.PROYECTO_SUCURSALID,
           P.PROYECTO_ALMACEN_CONSIGNA_ID,
           AD.ALMACEN_NOMBRE AS ALMACEN_DESTINO_NOMBRE,
           AO.ALMACEN_NOMBRE AS ALMACEN_ORIGEN_NOMBRE,
           AO.ALMACEN_FOLIO  AS ALMACEN_ORIGEN_FOLIO
    FROM AMPAR_HIS_PROYECTOS P
    LEFT JOIN AMPAR_HIS_ALMACEN AD ON AD.ALMACEN_ID = P.PROYECTO_ALMACEN_CONSIGNA_ID
    LEFT JOIN AMPAR_HIS_ALMACEN AO ON AO.ALMACEN_ID = P.PROYECTO_SUCURSALID
    WHERE P.PROYECTO_ID = ?
", [$proyectoid]);

if (!$proy || empty($proy[0]['PROYECTO_ALMACEN_CONSIGNA_ID'])) {
    echo '<div class="alert alert-warning">Este proyecto no tiene almacén asignado.</div>'; exit;
}

$p            = $proy[0];
$almacenOrigen  = (int)$p['PROYECTO_SUCURSALID'];
$almacenDestino = (int)$p['PROYECTO_ALMACEN_CONSIGNA_ID'];
$proyFolio      = $p['PROYECTO_FOLIO'];
$nombreOrigen   = $p['ALMACEN_ORIGEN_NOMBRE']  ?? 'Almacén Origen';
$nombreDestino  = $p['ALMACEN_DESTINO_NOMBRE'] ?? 'Almacén Proyecto';

// IDs de artículos permitidos (definidos en el proyecto)
$artsPerm = $db->query("
    SELECT PROYECTOMALETA_MALETAID AS AID
    FROM AMPAR_HIS_PROYECTOSMALETAS
    WHERE PROYECTOMALETA_PROYECTOID = ? AND PROYECTOMALETA_MALETAID > 0
", [$proyectoid]) ?: [];
$allowedIds = array_column($artsPerm, 'AID');

$db->close();
?>
<style>
  .ui-autocomplete { z-index:10001!important; max-height:280px; overflow-y:auto; overflow-x:hidden; box-shadow:0 8px 20px rgba(0,0,0,.15); background:#fff; border:1px solid #ccc; }
  #tblTraspasoProy td, #tblTraspasoProy th { vertical-align:middle; font-size:.84rem; }
</style>

<div class="modal-header py-2 px-3">
  <h5 class="modal-title" style="font-size:.95rem;">
    Traspasar artículos — <strong><?= htmlspecialchars($proyFolio) ?></strong>
  </h5>
</div>

<div class="modal-body p-3">

  <!-- Buscador -->
  <div class="row align-items-end mb-2">
    <div class="col-9 col-md-10">
      <label class="mb-1" style="font-size:.83rem; font-weight:600;">Buscar artículo del proyecto</label>
      <input type="hidden" id="proy_invdetid">
      <input type="hidden" id="proy_artid">
      <input type="hidden" id="proy_folio_art">
      <input type="hidden" id="proy_clave_art">
      <input type="hidden" id="proy_nombre_art">
      <input type="hidden" id="proy_caducidad">
      <input type="text"   id="proy_buscador" class="form-control form-control-sm" placeholder="Escribe folio, clave o nombre del artículo…">
    </div>
    <div class="col-3 col-md-2">
      <button type="button" id="btnAgregarProy" class="btn btn-warning btn-sm w-100">
        Agregar
      </button>
    </div>
  </div>

  <!-- Tabla de artículos -->
  <div class="table-responsive">
    <table class="table table-sm table-bordered" id="tblTraspasoProy">
      <thead class="thead-light">
        <tr>
          <th width="110">Folio</th>
          <th width="110">Clave</th>
          <th>Artículo</th>
          <th width="110">Caducidad</th>
          <th width="40"></th>
        </tr>
      </thead>
      <tbody id="tbodyTraspasoProy"></tbody>
    </table>
  </div>
  <small class="text-muted">
    <span id="cntFilasProy">0</span> artículo(s) por traspasar
  </small>

  <hr class="my-2">
  <div class="text-right">
    <button type="button" class="btn btn-secondary btn-sm mr-2" onclick="$('#modalglobal').modal('hide')">Cancelar</button>
    <button type="button" id="btnGuardarTraspasoProy" class="btn btn-primary btn-sm">
      <i class="mdi mdi-transfer"></i> Crear Traspaso
    </button>
  </div>
</div>

<script>
(function(){
  const PROYECTOID   = <?= $proyectoid ?>;
  const DEALMACEN    = <?= $almacenOrigen ?>;
  const AALMACEN     = <?= $almacenDestino ?>;
  const PROYFOLIO    = <?= json_encode($proyFolio) ?>;
  const ALLOWED_IDS  = <?= json_encode(array_map('strval', $allowedIds)) ?>;

  // ----- Helpers -----
  function normalizar(t){ return (t||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toUpperCase(); }

  function estadoCaducidad(f){
    if(!f) return {bg:'#dff4e4',fg:'#2f6d3f',lbl:'Sin caducidad'};
    const hoy=new Date(); hoy.setHours(0,0,0,0);
    const d=new Date(f+'T00:00:00');
    if(isNaN(d)) return {bg:'#dff4e4',fg:'#2f6d3f',lbl:'Sin caducidad'};
    const mes1=new Date(hoy); mes1.setMonth(mes1.getMonth()+1);
    if(d<hoy)   return {bg:'#f8d7da',fg:'#8a1f2d',lbl:'Caducado'};
    if(d<=mes1) return {bg:'#fff3cd',fg:'#8a6d1f',lbl:'Menos de 1 mes'};
    return {bg:'#dff4e4',fg:'#2f6d3f',lbl:'Vigente'};
  }

  function getIdsEnTabla(){
    const ids=new Set();
    document.querySelectorAll('#tbodyTraspasoProy input.hidden-invdetid').forEach(i=>ids.add(i.value));
    return ids;
  }

  function actualizarConteo(){
    document.getElementById('cntFilasProy').textContent=document.querySelectorAll('#tbodyTraspasoProy tr').length;
  }

  // ----- Cargar stock del almacén origen -----
  let articulosDisponibles = [];

  $.getJSON('../ajax/get.articulosfiltrados.catalogo.php', {
    almacenid: DEALMACEN,
    incluirMaletas: 1,
    filtros: 'vigentes,proximos,caducados'
  }, function(data){
    // Filtrar solo artículos del proyecto
    articulosDisponibles = (data || []).filter(function(a){
      return ALLOWED_IDS.includes(String(a.ID));
    });
    inicializarAutocomplete();
  });

  // ----- Autocomplete -----
  function inicializarAutocomplete(){
    if($('#proy_buscador').data('ui-autocomplete')) $('#proy_buscador').autocomplete('destroy');

    $('#proy_buscador').autocomplete({
      appendTo: '#modalglobal .modal-body',
      position: { my:'left top+6', at:'left bottom', collision:'flipfit' },
      minLength: 1,
      source: function(req, res){
        const term = normalizar(req.term);
        const usados = getIdsEnTabla();
        let resultados = articulosDisponibles
          .filter(function(a){
            const hoy=new Date(); hoy.setHours(0,0,0,0);
            const d=a.CADUCIDAD?new Date(a.CADUCIDAD+'T00:00:00'):null;
            // Excluir caducados
            if(d && !isNaN(d) && d<hoy) return false;
            if(usados.has(String(a.INVDETID))) return false;
            const searchable=normalizar((a.STOCK_FOLIO||'')+' '+(a.CLAVE_ARTICULO||'')+' '+(a.NOMBRE||''));
            return searchable.includes(term);
          })
          .map(function(a){
            return {
              label: a.STOCK_FOLIO+' – '+a.NOMBRE,
              id: a.ID,
              invdetid: a.INVDETID,
              nombre: a.NOMBRE,
              folio: a.STOCK_FOLIO,
              clave: a.CLAVE_ARTICULO,
              caducidad: a.CADUCIDAD||null
            };
          });

        // Ordenar: próximos a caducar primero
        resultados.sort(function(a,b){
          const fa=a.caducidad?new Date(a.caducidad+'T00:00:00').getTime():Infinity;
          const fb=b.caducidad?new Date(b.caducidad+'T00:00:00').getTime():Infinity;
          return fa-fb;
        });
        res(resultados);
      },
      open: function(){
        const $m=$('#proy_buscador').autocomplete('widget');
        $m.css({width:Math.min(Math.max($('#proy_buscador').outerWidth(),640),880)+'px'});
      },
      select: function(e,ui){
        $('#proy_buscador').val(ui.item.nombre);
        $('#proy_invdetid').val(ui.item.invdetid);
        $('#proy_artid').val(ui.item.id);
        $('#proy_folio_art').val(ui.item.folio);
        $('#proy_clave_art').val(ui.item.clave);
        $('#proy_nombre_art').val(ui.item.nombre);
        $('#proy_caducidad').val(ui.item.caducidad||'');
        return false;
      }
    });

    // Render personalizado igual que traspasos
    $('#proy_buscador').autocomplete('instance')._renderItem=function(ul,item){
      const est=estadoCaducidad(item.caducidad);
      const cad=item.caducidad||'Sin caducidad';
      return $('<li>').append(
        '<div style="display:flex;justify-content:space-between;align-items:flex-start;padding:10px 12px;border-bottom:1px solid #f1f5f9;cursor:pointer;background:#fff;" onmouseover="this.style.background=\'#f8fafc\'" onmouseout="this.style.background=\'#fff\'">'
        +'<div style="flex:1;"><div style="font-weight:600;color:#1e293b;font-size:14px;margin-bottom:4px;">'+(item.nombre||'').toUpperCase()+'</div>'
        +'<div style="font-size:12px;color:#64748b;display:flex;gap:10px;">'
        +'<span><b style="color:#94a3b8;">Folio:</b> '+(item.folio||'')+'</span>'
        +'<span><b style="color:#94a3b8;">Clave:</b> '+(item.clave||'')+'</span>'
        +'</div></div>'
        +'<div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">'
        +'<span style="background:'+est.bg+';color:'+est.fg+';font-size:11px;padding:3px 8px;border-radius:12px;font-weight:600;border:1px solid rgba(0,0,0,.05);">'+est.lbl+'</span>'
        +'<span style="font-size:12px;color:#475569;"><i class="mdi mdi-calendar"></i> '+cad+'</span>'
        +'</div></div>'
      ).appendTo(ul);
    };
  }

  // ----- Agregar a tabla -----
  $('#btnAgregarProy').on('click', function(){
    const invdetid = $('#proy_invdetid').val();
    const nombre   = $('#proy_nombre_art').val();
    const folio    = $('#proy_folio_art').val();
    const clave    = $('#proy_clave_art').val();
    const cad      = $('#proy_caducidad').val();

    if(!invdetid || !nombre){
      Swal.fire('Atención','Selecciona un artículo válido del buscador.','warning'); return;
    }
    if(getIdsEnTabla().has(String(invdetid))){
      Swal.fire('Atención','Este artículo ya está en la lista.','warning'); return;
    }

    const cadTxt = cad||'Sin caducidad';
    const fila = `<tr>
      <td><input type="hidden" class="hidden-invdetid" value="${invdetid}">
          <code>${folio}</code></td>
      <td><small>${clave||'—'}</small></td>
      <td>${(nombre||'').toUpperCase()}</td>
      <td><small class="d-block text-muted">${cadTxt}</small></td>
      <td class="text-center">
        <img src="../img/eliminar.png" class="btn-quitar-fila-proy" style="cursor:pointer; width:20px; height:20px; object-fit:contain;" title="Quitar">
      </td>
    </tr>`;

    $('#tbodyTraspasoProy').append(fila);

    // Limpiar buscador
    $('#proy_buscador,#proy_invdetid,#proy_artid,#proy_folio_art,#proy_clave_art,#proy_nombre_art,#proy_caducidad').val('');
    actualizarConteo();
  });

  $(document).on('click','.btn-quitar-fila-proy',function(){
    $(this).closest('tr').remove();
    actualizarConteo();
  });

  // ----- Guardar (crear traspaso) -----
  $('#btnGuardarTraspasoProy').on('click', function(){
    const stockids = [...document.querySelectorAll('#tbodyTraspasoProy .hidden-invdetid')].map(i=>i.value);
    if(stockids.length===0){
      Swal.fire('Atención','Agrega al menos un artículo.','warning'); return;
    }

    Swal.fire({
      text:'¿Crear el traspaso de '+stockids.length+' artículo(s) al almacén del proyecto '+PROYFOLIO+'?',
      icon:'question',
      showCancelButton:true,
      confirmButtonText:'Sí, crear',
      cancelButtonText:'Cancelar',
      customClass:{confirmButton:'btn btn-success',cancelButton:'btn btn-secondary'},
      buttonsStyling:false
    }).then(r=>{
      if(!r.isConfirmed) return;
      const btn=$('#btnGuardarTraspasoProy')[0];
      btn.disabled=true;
      btn.innerHTML='<i class="mdi mdi-loading mdi-spin"></i> Guardando...';

      $.ajax({
        url:'../ajax/proyectos.traspaso.guardar.php',
        type:'POST',
        contentType:'application/json',
        data:JSON.stringify({proyectoid:PROYECTOID,dealmacen:DEALMACEN,aalmacen:AALMACEN,stockids:stockids}),
        success:function(res){
          if(res.ok){
            Swal.fire({
              icon:'success',
              title:'Traspaso creado',
              html:'Folio: <strong>'+res.folio+'</strong><br>'+stockids.length+' artículo(s) se han traspasado directamente al almacén del proyecto.',
              confirmButtonText:'Aceptar'
            }).then(() => window.location.reload());
          } else {
            Swal.fire('Error',res.msg||'No se pudo crear el traspaso.','error');
            btn.disabled=false;
            btn.innerHTML='<i class="mdi mdi-transfer"></i> Crear Traspaso';
          }
        },
        error:function(){
          Swal.fire('Error','Error de conexión al servidor.','error');
          btn.disabled=false;
          btn.innerHTML='<i class="mdi mdi-transfer"></i> Crear Traspaso';
        }
      });
    });
  });

})();
</script>
