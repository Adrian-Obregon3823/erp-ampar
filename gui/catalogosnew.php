<?php include_once ("../includes/sesion.php"); ?>
<?php include_once ("../includes/includes.php"); ?>
<!doctype html>
<html lang="es">
  <?php include_once("../includes/head.php"); ?>
  <style>
    .badge-activo{ background:#198754; }
    .badge-inactivo{ background:#6c757d; }
    .w-120{ width:120px; }
    .card-header { position: relative; z-index: 2; }
    .nav-tabs .nav-link{ cursor:pointer; }
    
    /* --- Select2 + Bootstrap (multiple) dentro de modal --- */
    .select2-container { width: 100% !important; }
    .select2-container--default .select2-selection--multiple {
      border: 1px solid #ced4da;
      border-radius: .25rem;
      min-height: 38px;
      padding: 2px 4px;
      background-color: #fff;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
      border-color: #80bdff;
      outline: 0;
      box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
      background-color: #0b5ed7; /* Azul Bootstrap vibrante */
      border: 1px solid #0a58ca;
      color: #ffffff;
      border-radius: 4px;
      padding: 3px 8px;
      margin-top: 4px;
      margin-right: 6px;
      font-size: 0.875rem;
      display: flex;
      flex-direction: row-reverse;
      align-items: center;
      gap: 6px;
      box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
      color: rgba(255,255,255,.8);
      position: static;
      background: transparent;
      border: none;
      font-size: 1.1rem;
      font-weight: 600;
      padding: 0;
      margin: 0;
      line-height: 1;
      display: flex;
      align-items: center;
      cursor: pointer;
      transition: color 0.15s ease-in-out;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
      color: #ffffff;
      background: transparent;
    }
    .select2-container .select2-dropdown {
      z-index: 2000 !important;
    }
  </style>
  <body class="d-flex flex-column min-vh-100" style="background:#f4f5f7">
    <?php include_once("../includes/header.php"); ?>
    <br><br><br><br>
    <div class="container py-4">
      <div class="card shadow-sm">
        <div class="card-header">
          <ul class="nav nav-tabs card-header-tabs" id="catTabs" role="tablist">
            <li class="nav-item">
              <a class="nav-link active" data-toggle="tab" href="#tab-hospitales" role="tab">Hospitales</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="tab" href="#tab-medicos" role="tab">Médicos</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="tab" href="#tab-grupos" role="tab">Grupos</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="tab" href="#tab-subgrupos" role="tab">Subgrupos</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="tab" href="#tab-tipoevento" role="tab">Tipo Evento</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="tab" href="#tab-clientes" role="tab">Clientes</a>
            </li>
          </ul>
        </div>

        <div class="card-body tab-content">
          <!-- Toolbar genérica -->
          <div class="d-flex gap-2 mb-3">
            <input id="txtSearch" class="form-control" placeholder="Buscar..." style="max-width:320px">
            <button id="btnNew" class="btn btn-primary"><i class="fa fa-plus"></i> Nuevo</button>
          </div>

          <!-- TABLAS -->
          <div class="tab-pane fade show active" id="tab-hospitales" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="tbl-hospitales">
                <thead><tr>
                  <th>ID</th><th>Nombre</th><th>Dirección</th><th>MunicipioID</th><th>Teléfono</th><th>Contacto</th><th>Almacén</th><th class="w-120">Activo</th><th class="w-120">Acciones</th>
                </tr></thead><tbody></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-medicos" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="tbl-medicos">
                <thead><tr>
                  <th>ID</th><th>Nombre</th><th>Teléfono</th><th>Correo</th><th>Almacén</th><th>Tipo Evento</th><th class="w-120">Activo</th><th class="w-120">Acciones</th>
                </tr></thead><tbody></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-grupos" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="tbl-grupos">
                <thead><tr>
                  <th>ID</th><th>Nombre</th><th class="w-120">Activo</th><th class="w-120">Acciones</th>
                </tr></thead><tbody></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-subgrupos" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="tbl-subgrupos">
                <thead><tr>
                  <th>ID</th><th>Nombre</th><th>GrupoID</th><th class="w-120">Activo</th><th class="w-120">Acciones</th>
                </tr></thead><tbody></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-tipoevento" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="tbl-tipoevento">
                <thead><tr>
                  <th>ID</th><th>Nombre</th><th>SubgrupoID</th><th class="w-120">Activo</th><th class="w-120">Acciones</th>
                </tr></thead><tbody></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-clientes" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="tbl-clientes">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre Comercial</th>
                    <th>Razón Social</th>
                    <th>Contacto</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Ubicación</th>
                    <th class="w-120">Estatus</th>
                    <th class="w-120">Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <!-- Paginación simple -->
          <div class="d-flex justify-content-between mt-3">
            <div><small id="lblTotal"></small></div>
            <div class="btn-group">
              <button class="btn btn-outline-secondary btn-sm" id="btnPrev">«</button>
              <button class="btn btn-outline-secondary btn-sm" id="btnNext">»</button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Modal genérico -->
    <div class="modal fade" id="mdlForm" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form id="frmItem">
            <div class="modal-header">
              <h5 class="modal-title" id="mdlTitle">Nuevo</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <input type="hidden" id="f_id">
              <div id="formFields" class="form-row"></div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Guardar</button>
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <?php include_once("../includes/footer.php"); ?>
    <?php include_once("../includes/foot.php"); ?>

    <script>
    // Fix para que los inputs funcionen dentro de modales anidados en Bootstrap
    $(document).on('focusin', function(e) {
        if ($(e.target).closest('.modal').length) {
            e.stopImmediatePropagation();
        }
    });

// =================== CONFIG ===================
const API = '../api/catalogos.php'; // AJUSTA si difiere

const EP = {
  paises:            '../ajax/get.paises.php',
  estados:           '../ajax/get.estados.php',
  municipios:        '../ajax/get.municipios.php',
  // ELIMINADO: sucursales: '../ajax/get.sucursales.php',
  grupos:            '../ajax/get.eventos.tipogrupo.php',
  subgruposByGrupo:  '../ajax/get.eventos.tiposubgrupo.php',
  lineageMunicipio:  '../ajax/get.lineage.municipio.php',
  grupoBySubgrupo:   '../ajax/get.grupo.by.subgrupo.php',
  almacenes: '../ajax/get.catalogoalmacenes.php?tipoalmacen=1,2',
  hospitales:        '../ajax/get.hospitales.php',
  tipoeventoBySubgrupo: '../ajax/get.eventos.tipoevento.php',
  eventoLineage:     '../ajax/get.lineage.tipoevento.php'
};

const state = { cat:'hospitales', page:1, limit:20, q:'' };

// ============== Helpers genéricos ==============
function ajaxJSON(url, data={}, onOk=()=>{}, onErr=()=>{}) {
  $.ajax({ url, data, dataType:'json',
    success:onOk,
    error:(xhr)=>{ console.error('AJAX', url, xhr?.responseText||xhr); onErr(xhr); }
  });
}
function toOptions(arr, idKey='ID', nameKey='NOMBRE', selected=null) {
  let html = '<option value="">-- Seleccione --</option>';
  (arr||[]).forEach(r=>{
    const v = r[idKey], t = r[nameKey];
    const isSelected = Array.isArray(selected) 
        ? selected.map(String).includes(String(v))
        : (String(v) === String(selected));
    const sel = isSelected ? ' selected' : '';
    html += `<option value="${v}"${sel}>${t}</option>`;
  });
  return html;
}
// Reemplaza tu enable() por esta:
function enable($el, on=true){
  // Quita atributo y estado
  $el.prop('disabled', !on).removeAttr('disabled');

  // Si ya está montado Select2, sincroniza el estado del contenedor
  if ($.fn.select2 && $el.hasClass('select2-hidden-accessible')) {
    $el.prop('disabled', !on).trigger('change.select2');
    const inst = $el.data('select2');
    if (inst && inst.$container) {
      inst.$container.toggleClass('select2-container--disabled', !on);
    }
  }
}

// Reemplaza tu initSelect2() por esta:
function initSelect2($sel){
  if (!$.fn.select2) return;
  // Evita instancias duplicadas
  if ($sel.hasClass('select2-hidden-accessible')) {
    try { $sel.select2('destroy'); } catch(_){}
  }
  $sel.select2({ width:'100%', placeholder:'Seleccione...', allowClear:true, dropdownParent: $('#mdlForm') });
}


// =================== Cat configs ===================
const CAT_CFG = {
  'hospitales':{
    table:'#tbl-hospitales', pk:'HOSPITAL_ID', activo:'HOSPITAL_ACTIVO',
    fields:[
      {name:'HOSPITAL_NOMBRE',     label:'Nombre',      type:'text',   required:true},
      {name:'HOSPITAL_DIRECCION',  label:'Dirección',   type:'text'},
      {name:'HOSPITAL_PAISID_UI',  label:'País',        type:'select', data:'paises'},
      {name:'HOSPITAL_ESTADOID_UI',label:'Estado',      type:'select', dependsOn:'HOSPITAL_PAISID_UI', loader:(paisId,$el)=>loadEstados(paisId,$el)},
      {name:'HOSPITAL_MUNICIPIOID',label:'Municipio',   type:'select', dependsOn:'HOSPITAL_ESTADOID_UI', required:true, loader:(edoId,$el)=>loadMunicipios(edoId,$el)},
      {name:'HOSPITAL_TELEFONO',   label:'Teléfono',    type:'text'},
      {name:'HOSPITAL_CONTACTO',   label:'Contacto',    type:'text'},
      // Cambiado de Sucursales a Almacenes
      // Dentro de CAT_CFG para 'hospitales'
      {name:'HOSPITAL_ALMACEN', label:'Almacén', type:'select', data:'almacenes'}
    ],
    drawRow:(r)=>`
      <tr>
        <td>${r.HOSPITAL_ID}</td><td>${r.HOSPITAL_NOMBRE??''}</td><td>${r.HOSPITAL_DIRECCION??''}</td>
        <td>${r.HOSPITAL_MUNICIPIOID??''}</td><td>${r.HOSPITAL_TELEFONO??''}</td><td>${r.HOSPITAL_CONTACTO??''}</td>
        <td>${r.HOSPITAL_ALMACEN??''}</td> <td><span class="badge ${(+r.HOSPITAL_ACTIVO? 'badge-activo':'badge-inactivo')}">${+r.HOSPITAL_ACTIVO? 'Sí':'No'}</span></td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-editar="${r.HOSPITAL_ID}">Editar</button>
            <button type="button" class="btn btn-outline-${+r.HOSPITAL_ACTIVO? 'secondary':'success'}" data-toggle-activo="${r.HOSPITAL_ID}" data-next="${+r.HOSPITAL_ACTIVO?0:1}">${+r.HOSPITAL_ACTIVO? 'Desactivar':'Activar'}</button>
          </div>
        </td>
      </tr>`
  },
  'medicos':{
    table:'#tbl-medicos', pk:'MEDICO_ID', activo:'MEDICO_ACTIVO',
    fields:[
      {name:'MEDICO_NOMBRE',        label:'Nombre',       type:'text', required:true},
      {name:'MEDICO_TELEFONO',      label:'Teléfono',     type:'text'},
      {name:'MEDICO_CORREO',        label:'Correo',       type:'email'},
      // Nuevos campos de enlace
      {name:'MEDICO_ALMACENID',    label:'Almacén',      type:'select', data:'almacenes'},
      {name:'MEDICO_GRUPOID_UI',    label:'Grupo',        type:'select', data:'grupos'},
      {name:'MEDICO_SUBGRUPOID_UI', label:'Subgrupo',     type:'select', dependsOn:'MEDICO_GRUPOID_UI', loader:(gId,$el)=>loadSubgrupos(gId,$el)},
      {name:'MEDICO_TIPOEVENTOID',  label:'Tipo Evento',  type:'select', dependsOn:'MEDICO_SUBGRUPOID_UI', loader:(sId,$el)=>loadTipoEvento(sId,$el)}
    ],
    drawRow:(r)=>`
      <tr>
        <td>${r.MEDICO_ID}</td><td>${r.MEDICO_NOMBRE??''}</td><td>${r.MEDICO_TELEFONO??''}</td><td>${r.MEDICO_CORREO??''}</td>
        <td>${r.ALMACEN_NOMBRE??''}</td><td>${r.SUBGRUPO_NOMBRE??''}</td>
        <td><span class="badge ${(+r.MEDICO_ACTIVO? 'badge-activo':'badge-inactivo')}">${+r.MEDICO_ACTIVO? 'Sí':'No'}</span></td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-editar="${r.MEDICO_ID}">Editar</button>
            <button type="button" class="btn btn-outline-${+r.MEDICO_ACTIVO? 'secondary':'success'}" data-toggle-activo="${r.MEDICO_ID}" data-next="${+r.MEDICO_ACTIVO?0:1}">${+r.MEDICO_ACTIVO? 'Desactivar':'Activar'}</button>
          </div>
        </td>
      </tr>`
  },
  'grupos':{
    table:'#tbl-grupos', pk:'TIPOEVENTOGRUPO_ID', activo:'TIPOEVENTOGRUPO_ACTIVO',
    fields:[ {name:'TIPOEVENTOGRUPO_NOMBRE', label:'Nombre', type:'text', required:true} ],
    drawRow:(r)=>`
      <tr>
        <td>${r.TIPOEVENTOGRUPO_ID}</td><td>${r.TIPOEVENTOGRUPO_NOMBRE??''}</td>
        <td><span class="badge ${(+r.TIPOEVENTOGRUPO_ACTIVO? 'badge-activo':'badge-inactivo')}">${+r.TIPOEVENTOGRUPO_ACTIVO? 'Sí':'No'}</span></td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-editar="${r.TIPOEVENTOGRUPO_ID}">Editar</button>
            <button type="button" class="btn btn-outline-${+r.TIPOEVENTOGRUPO_ACTIVO? 'secondary':'success'}" data-toggle-activo="${r.TIPOEVENTOGRUPO_ID}" data-next="${+r.TIPOEVENTOGRUPO_ACTIVO?0:1}">
              ${+r.TIPOEVENTOGRUPO_ACTIVO? 'Desactivar':'Activar'}
            </button>
          </div>
        </td>
      </tr>`
  },
  'subgrupos':{
    table:'#tbl-subgrupos', pk:'TIPOEVENTOSUBGRUPO_ID', activo:'TIPOEVENTOSUBGRUPO_ACTIVO',
    fields:[
      {name:'TIPOEVENTOSUBGRUPO_NOMBRE', label:'Nombre',  type:'text', required:true},
      {name:'TIPOEVENTOSUBGRUPO_GRUPOID',label:'Grupo',   type:'select', data:'grupos', required:true}
    ],
    drawRow:(r)=>`
      <tr>
        <td>${r.TIPOEVENTOSUBGRUPO_ID}</td><td>${r.TIPOEVENTOSUBGRUPO_NOMBRE??''}</td><td>${r.TIPOEVENTOSUBGRUPO_GRUPOID??''}</td>
        <td><span class="badge ${(+r.TIPOEVENTOSUBGRUPO_ACTIVO? 'badge-activo':'badge-inactivo')}">${+r.TIPOEVENTOSUBGRUPO_ACTIVO? 'Sí':'No'}</span></td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-editar="${r.TIPOEVENTOSUBGRUPO_ID}">Editar</button>
            <button type="button" class="btn btn-outline-${+r.TIPOEVENTOSUBGRUPO_ACTIVO? 'secondary':'success'}" data-toggle-activo="${r.TIPOEVENTOSUBGRUPO_ID}" data-next="${+r.TIPOEVENTOSUBGRUPO_ACTIVO?0:1}">
              ${+r.TIPOEVENTOSUBGRUPO_ACTIVO? 'Desactivar':'Activar'}
            </button>
          </div>
        </td>
      </tr>`
  },
  'tipoevento':{
    table:'#tbl-tipoevento', pk:'TIPOEVENTO_ID', activo:'TIPOEVENTO_ACTIVO',
    // Guarda solo SUBGRUPOID; Grupo es UI para filtrar
    fields:[
      {name:'TIPOEVENTO_NOMBRE',      label:'Nombre',    type:'text', required:true},
      {name:'TIPOEVENTO_GRUPOID_UI',  label:'Grupo',     type:'select', data:'grupos', required:true},  // UI
      {name:'TIPOEVENTO_SUBGRUPOID',  label:'Subgrupo',  type:'select', dependsOn:'TIPOEVENTO_GRUPOID_UI',
        loader:(grupoId,$el)=>loadSubgrupos(grupoId,$el), required:true}
    ],
    drawRow:(r)=>`
      <tr>
        <td>${r.TIPOEVENTO_ID}</td><td>${r.TIPOEVENTO_NOMBRE??''}</td><td>${r.TIPOEVENTO_SUBGRUPOID??''}</td>
        <td><span class="badge ${(+r.TIPOEVENTO_ACTIVO? 'badge-activo':'badge-inactivo')}">${+r.TIPOEVENTO_ACTIVO? 'Sí':'No'}</span></td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-editar="${r.TIPOEVENTO_ID}">Editar</button>
            <button type="button" class="btn btn-outline-${+r.TIPOEVENTO_ACTIVO? 'secondary':'success'}" data-toggle-activo="${r.TIPOEVENTO_ID}" data-next="${+r.TIPOEVENTO_ACTIVO?0:1}">
              ${+r.TIPOEVENTO_ACTIVO? 'Desactivar':'Activar'}
            </button>
          </div>
        </td>
      </tr>`
  },
  'clientes': {
    table: '#tbl-clientes',
    pk: 'CLIENTE_ID',
    activo: 'ESTATUS', // A, S, etc.
    fields: [{
        name: 'NOMBRE_COMERCIAL',
        label: 'Nombre Comercial',
        type: 'text',
        readonly: true
      },
      {
        name: 'RAZON_SOCIAL',
        label: 'Razón Social',
        type: 'text',
        readonly: true
      },
      {
        name: 'NOMBRE_CONTACTO',
        label: 'Nombre de Contacto',
        type: 'text',
        readonly: true
      },
      {
        name: 'TELEFONO',
        label: 'Teléfono',
        type: 'text',
        readonly: true
      },
      {
        name: 'CORREO',
        label: 'Correo',
        type: 'email',
        readonly: true
      },
      {
        name: 'UBICACION',
        label: 'Ubicación',
        type: 'text',
        readonly: true
      }
    ],
    drawRow: (r) => {
      let statusBadge = (r.ESTATUS === 'A') ? 'badge-activo' : 'badge-inactivo';
      let statusText = (r.ESTATUS === 'A') ? 'Activo' : ((r.ESTATUS === 'S') ? 'Suspendido' : r.ESTATUS);
      return `
  <tr>
    <td>${r.CLIENTE_ID}</td>
    <td>${r.NOMBRE_COMERCIAL??''}</td>
    <td>${r.RAZON_SOCIAL??''}</td>
    <td>${r.NOMBRE_CONTACTO??''}</td>
    <td>${r.TELEFONO??''}</td>
    <td>${r.CORREO??''}</td>
    <td>${r.UBICACION??''}</td>
    <td><span class="badge ${statusBadge}">${statusText}</span></td>
    <td>
      <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-outline-primary" data-editar="${r.CLIENTE_ID}">Ver</button>
      </div>
    </td>
  </tr>`;
    }
  },
};

// =============== Render inputs ===============
function renderField(f){
  const req = f.required ? 'required' : '';
  if (f.type==='select'){
    const multiple = f.multiple ? 'multiple="multiple"' : '';
    const nameAttr = f.multiple ? `${f.name}[]` : f.name;
    return `
      <div class="form-group col-md-6">
        <label class="form-label">${f.label}${f.required?' *':''}</label>
        <select class="form-control" id="${f.name}" name="${nameAttr}" ${multiple} ${req}></select>
      </div>
    `;
  }
  return `
    <div class="form-group col-md-6">
      <label class="form-label">${f.label}${f.required?' *':''}</label>
      <input class="form-control" type="${f.type}" id="${f.name}" name="${f.name}" ${req}>
    </div>
  `;
}

// =============== Cargas de catálogos ===============
function loadPaises($sel, selected=null){
  enable($sel,false);
  ajaxJSON(EP.paises, {}, rows=>{
    $sel.html(toOptions(rows,'ID','NOMBRE',selected));
    initSelect2($sel);
    enable($sel,true);
  });
}

function loadEstados(paisId, $sel, selected=null){
  if(!paisId){
    $sel.html('<option value="">-- Seleccione país primero --</option>');
    initSelect2($sel); enable($sel,false); return;
  }
  enable($sel,false);
  ajaxJSON(EP.estados, {paisid:paisId}, rows=>{
    $sel.html(toOptions(rows,'ID','NOMBRE',selected));
    initSelect2($sel);
    enable($sel,true);
  });
}

function loadMunicipios(estadoId, $sel, selected=null){
  if(!estadoId){
    $sel.html('<option value="">-- Seleccione estado primero --</option>');
    initSelect2($sel); enable($sel,false); return;
  }
  enable($sel,false);
  ajaxJSON(EP.municipios, {estadoid:estadoId}, rows=>{
    $sel.html(toOptions(rows,'ID','NOMBRE',selected));
    initSelect2($sel);
    enable($sel,true);
  });
}

function loadAlmacenes($sel, selected=null){
  enable($sel,false);
  ajaxJSON(EP.almacenes, {}, rows=>{
    $sel.html(toOptions(rows,'ID','NOMBRE',selected));
    initSelect2($sel);
    enable($sel,true);
  });
}

function loadHospitales($sel, selected=null){
  enable($sel,false);
  ajaxJSON(EP.hospitales, {}, rows=>{
    $sel.html(toOptions(rows,'ID','NOMBRE',selected));
    initSelect2($sel); enable($sel,true);
  });
}

function loadTipoEvento(subgrupoId, $sel, selected=null){
  if(!subgrupoId){
    $sel.html('<option value="">-- Seleccione subgrupo primero --</option>');
    initSelect2($sel); enable($sel,false); return;
  }
  enable($sel,false);
  ajaxJSON(EP.tipoeventoBySubgrupo, {subgrupoid:subgrupoId}, rows=>{
    $sel.html(toOptions(rows,'ID','NOMBRE',selected));
    initSelect2($sel); enable($sel,true);
  });
}

function loadGrupos($sel, selected=null){
  enable($sel,false);
  ajaxJSON(EP.grupos, {}, rows=>{
    $sel.html(toOptions(rows,'ID','NOMBRE',selected));
    initSelect2($sel);
    enable($sel,true);
  });
}
function loadSubgrupos(grupoId, $sel, selected=null){
  if(!grupoId){
    $sel.html('<option value="">-- Seleccione grupo primero --</option>');
    initSelect2($sel); enable($sel,false); return;
  }
  enable($sel,false);
  ajaxJSON(EP.subgruposByGrupo, {grupoid:grupoId}, rows=>{
    $sel.html(toOptions(rows,'ID','NOMBRE',selected));
    initSelect2($sel);
    enable($sel,true);
  });
}

// =============== Listado ===============
function currentCfg(){ return CAT_CFG[state.cat]; }

function loadList(){
  const cfg = currentCfg();
  const $tbody = $(cfg.table+' tbody').empty();
  $('#lblTotal').text('Cargando...');
  $.ajax({
    url: API, data: {action:'list', cat:state.cat, q:state.q, page:state.page, limit:state.limit}, dataType:'json',
    success: function(res){
      if(!res.ok){ alert(res.msg||'Error'); return; }
      (res.data.items||[]).forEach(r=> $tbody.append(cfg.drawRow(r)) );
      $('#lblTotal').text(`Total: ${res.data.total}  |  Página ${res.data.page}`);
    },
    error: function(xhr){
      console.error('LIST XHR:', xhr?.responseText || xhr);
      alert('Error cargando la lista (revisa consola).');
      $('#lblTotal').text('Error');
    }
  });
}

// =============== Formulario ===============
function openForm(id=0){
  const cfg = currentCfg();
  const $form = $('#frmItem');
  $form[0].reset();
  $('#f_id').val(id);
  const $fields = $('#formFields').empty();

  cfg.fields.forEach(f=> $fields.append( renderField(f) ));

  // Inicializa selects (deshabilita hijos hasta tener padre)
  cfg.fields.forEach(f=>{
    if(f.type==='select'){
      const $sel = $('#'+f.name);

      // hijos: dejar deshabilitado hasta que el padre tenga valor
      if (f.dependsOn) { $sel.html('<option value="">-- Seleccione primero --</option>'); enable($sel,false); }
      else {
        // catálogo base
        if (f.data==='paises')      loadPaises($sel);
        if (f.data==='grupos')      loadGrupos($sel);
        if (f.data==='almacenes')   loadAlmacenes($sel);   // Nuevo
        if (f.data==='hospitales')  loadHospitales($sel);
      }

      // wire de cascada
      if (f.dependsOn && typeof f.loader==='function'){
        $('#'+f.dependsOn).off('change._cascade_'+f.name).on('change._cascade_'+f.name, function(){
          const val = $(this).val();
          f.loader(val, $sel); // este hace enable/disable adentro
          $sel.val('').trigger('change');
        });
      }

      initSelect2($sel);
    }
  });

  // Abrir modal
  $('#mdlTitle').text(id>0 ? 'Editar' : 'Nuevo');
  $('#mdlForm').modal('show');

  // ===== Edición: precargar valores + cascadas completas =====
  if(id>0){
    $.ajax({
      url: API, data:{action:'get', cat:state.cat, id}, dataType:'json',
      success: function(res){
        if(!(res?.ok && res.data)){ alert(res?.msg||'No encontrado'); $('#mdlForm').modal('hide'); return; }
        const row = res.data;

        // 1) Campos simples
        cfg.fields.forEach(f=>{ if(f.type!=='select') $('#'+f.name).val(row[f.name] ?? ''); });

        // 2) Catálogos no dependientes + selección
        if (state.cat==='hospitales'){
          loadAlmacenes($('#HOSPITAL_ALMACEN'), row['HOSPITAL_ALMACEN'] ?? null); // Cambio
          if (row['HOSPITAL_MUNICIPIOID']){
            // ... (mantener lógica del municipio)
          } else {
            loadPaises($('#HOSPITAL_PAISID_UI'), null);
          }
        }

        if (state.cat==='medicos'){
          loadAlmacenes($('#MEDICO_ALMACENID'), row['MEDICO_ALMACENID'] ?? null);
          
          if (row['MEDICO_TIPOEVENTOID']){
            // Precargar jerarquía: TipoEvento -> Subgrupo -> Grupo
            ajaxJSON(EP.eventoLineage, {tipoeventoid: row['MEDICO_TIPOEVENTOID']}, function(info){
              loadGrupos($('#MEDICO_GRUPOID_UI'), info.grupo_id);
              loadSubgrupos(info.grupo_id, $('#MEDICO_SUBGRUPOID_UI'), info.subgrupo_id);
              loadTipoEvento(info.subgrupo_id, $('#MEDICO_TIPOEVENTOID'), info.tipoevento_id);
            });
          } else {
            loadGrupos($('#MEDICO_GRUPOID_UI'), null);
            enable($('#MEDICO_SUBGRUPOID_UI'), false);
            enable($('#MEDICO_TIPOEVENTOID'), false);
          }
        }

        if (state.cat==='subgrupos'){
          loadGrupos($('#TIPOEVENTOSUBGRUPO_GRUPOID'), row['TIPOEVENTOSUBGRUPO_GRUPOID'] ?? null);
        }

        if (state.cat==='tipoevento'){
          // tenemos SUBGRUPOID; necesitamos conocer su GRUPO para preseleccionar ambos
          if (row['TIPOEVENTO_SUBGRUPOID']){
            ajaxJSON(EP.grupoBySubgrupo, {subgrupoid: row['TIPOEVENTO_SUBGRUPOID']}, function(info){
              // info: {grupo_id, grupo_nombre, subgrupo_id, subgrupo_nombre}
              loadGrupos($('#TIPOEVENTO_GRUPOID_UI'), info.grupo_id);
              // después cargar subgrupos del grupo, preseleccionando el del registro:
              loadSubgrupos(info.grupo_id, $('#TIPOEVENTO_SUBGRUPOID'), info.subgrupo_id);
            });
          }else{
            loadGrupos($('#TIPOEVENTO_GRUPOID_UI'), null);
            enable($('#TIPOEVENTO_SUBGRUPOID'), false);
          }
        }
      },
      error: function(xhr){
        console.error('GET XHR:', xhr?.responseText||xhr);
        alert('Error consultando el registro');
        $('#mdlForm').modal('hide');
      }
    });
  }
}

// =============== Eventos UI / CRUD ===============
// Tabs (BS4)
$('#catTabs a[data-toggle="tab"]').on('shown.bs.tab', function(e){
  const id = $(e.target).attr('href'); // #tab-xxx
  state.cat = id.replace('#tab-','');
  state.page = 1; state.q = '';
  $('#txtSearch').val('');
  loadList();
});

$('#txtSearch').on('keyup', function(e){
  if(e.key==='Enter'){ state.q = $(this).val(); state.page=1; loadList(); }
});
$('#btnPrev').on('click', ()=>{ if(state.page>1){ state.page--; loadList(); } });
$('#btnNext').on('click', ()=>{ state.page++; loadList(); });

$('#btnNew').on('click', ()=> openForm(0));

$(document).on('click','[data-editar]',function(){
  openForm( parseInt($(this).data('editar')) );
});
$(document).on('click','[data-toggle-activo]',function(){
  const id = parseInt($(this).data('toggle-activo')), next = parseInt($(this).data('next'));
  $.ajax({
    url: API, method:'POST', data: {action:'set_activo', cat:state.cat, id, activo:next}, dataType:'json',
    success: function(res){ if(res.ok){ loadList(); } else { alert(res.msg||'Error'); } },
    error: function(xhr){ console.error('SET_ACTIVO XHR:', xhr?.responseText||xhr); alert('Error cambiando estatus.'); }
  });
});

// Guardar
$('#frmItem').on('submit', function(e){
  e.preventDefault();
  const fd = new FormData(this);
  fd.append('action','save');
  fd.append('cat', state.cat);
  fd.append('id', $('#f_id').val()||0);

  $.ajax({
    url: API, method:'POST', data: fd, processData:false, contentType:false, dataType:'json',
    success: function(r){
      if(r.ok){ $('#mdlForm').modal('hide'); loadList(); }
      else{ alert(r.msg||'Error al guardar'); }
    },
    error: function(xhr){ console.error('SAVE XHR:', xhr?.responseText||xhr); alert('Error de red/servidor al guardar'); }
  });
});

// Inicial
loadList();

// Cascadas en ALTAS (cuando el usuario va eligiendo)
$(document).on('change', '#HOSPITAL_PAISID_UI', function(){
  loadEstados($(this).val(), $('#HOSPITAL_ESTADOID_UI'));
  $('#HOSPITAL_MUNICIPIOID').html('<option value="">-- Seleccione estado primero --</option>'); enable($('#HOSPITAL_MUNICIPIOID'), false);
});
$(document).on('change', '#HOSPITAL_ESTADOID_UI', function(){
  loadMunicipios($(this).val(), $('#HOSPITAL_MUNICIPIOID'));
});

$(document).on('change', '#TIPOEVENTO_GRUPOID_UI', function(){
  loadSubgrupos($(this).val(), $('#TIPOEVENTO_SUBGRUPOID'));
});

$(document).on('change', '#MEDICO_GRUPOID_UI', function(){
  loadSubgrupos($(this).val(), $('#MEDICO_SUBGRUPOID_UI'));
  $('#MEDICO_TIPOEVENTOID').html('<option value="">-- Seleccione subgrupo primero --</option>'); 
  enable($('#MEDICO_TIPOEVENTOID'), false);
});

$(document).on('change', '#MEDICO_SUBGRUPOID_UI', function(){
  loadTipoEvento($(this).val(), $('#MEDICO_TIPOEVENTOID'));
});
</script>


  </body>
</html>
