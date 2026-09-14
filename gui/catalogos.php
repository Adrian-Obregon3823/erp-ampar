<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<!doctype html>
<html lang="es">
<?php include_once("../includes/head.php"); ?>
<style>
  .badge-activo {
    background: #198754;
  }

  .badge-inactivo {
    background: #6c757d;
  }

  .w-120 {
    width: 120px;
  }

  .card-header {
    position: relative;
    z-index: 2;
  }

  .nav-tabs .nav-link {
    cursor: pointer;
  }

  /* --- Select2 + Bootstrap (single) dentro de modal --- */
  .select2-container {
    width: 100% !important;
  }

  .select2-container--default .select2-selection--single {
    height: 38px;
    /* igual que .form-control (BS4) */
    border: 1px solid #ced4da;
    border-radius: .25rem;
    display: flex;
    /* centra verticalmente el texto */
    align-items: center;
    /* centra verticalmente el texto */
    padding: 0 2rem 0 .75rem;
    /* espacio izq y para la flecha */
    background-color: #fff;
    /* por si hay temas grises */
  }

  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: normal;
    /* evita que “empuje” hacia abajo */
    padding-left: 0;
    /* ya dimos padding al contenedor */
    width: 100%;
  }

  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 100%;
    top: 0;
    right: .5rem;
  }

  /* --- Select2 + Bootstrap (multiple) dentro de modal --- */
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
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, .25);
  }

  .select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #0b5ed7;
    /* Azul Bootstrap vibrante */
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
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  }

  .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: rgba(255, 255, 255, .8);
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

  /* Dropdown siempre encima del modal */
  .select2-container .select2-dropdown {
    z-index: 2000 !important;
  }

  /* --- Pestañas scrolleables en móvil --- */
  @media (max-width: 767px) {
    .nav-tabs.card-header-tabs {
      flex-wrap: nowrap;
      overflow-x: auto;
      overflow-y: hidden;
      white-space: nowrap;
      -webkit-overflow-scrolling: touch;
      padding-bottom: 5px;
      /* espacio para scroll */
    }

    .nav-tabs.card-header-tabs::-webkit-scrollbar {
      height: 4px;
    }

    .nav-tabs.card-header-tabs::-webkit-scrollbar-thumb {
      background-color: #ccc;
      border-radius: 4px;
    }

    .nav-tabs.card-header-tabs .nav-item {
      margin-bottom: 0;
      /* Bootstrap sometimes adds margin-bottom */
    }
  }
</style>

<body class="d-flex flex-column min-vh-100" style="background:#f4f5f7">
  <?php include_once("../includes/header.php"); ?>
  <div class="container-fluid page-body-wrapper">
    <?php include_once("../includes/menu.sidebar.php") ?>
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
              <a class="nav-link" data-toggle="tab" href="#tab-conceptossalida" role="tab">Concepto Salida</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="tab" href="#tab-categorias" role="tab">Categorías (Artículos)</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="tab" href="#tab-clientes" role="tab">Clientes</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" data-toggle="tab" href="#tab-proveedores" role="tab">Proveedores</a>
            </li>
          </ul>
        </div>

        <div class="card-body tab-content">
          <!-- Toolbar genérica -->
          <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
            <select id="cboFiltroAlmacen" class="form-control custom-select form-select shadow-sm" style="max-width:250px; font-weight: 500;">
              <option value="0">Todas las sucursales</option>
            </select>
            <input id="txtSearch" class="form-control shadow-sm" placeholder="Buscar..." style="max-width:320px">
            <button id="btnNew" class="btn btn-primary"><i class="fa fa-plus"></i> Nuevo</button>
            <button id="btnDownloadTemplate" class="btn btn-outline-success"><i class="fa fa-download"></i> Plantilla</button>
            <button id="btnImportExcel" class="btn btn-outline-info"><i class="fa fa-upload"></i> Importar</button>
          </div>

          <input type="file" id="fileImportExcel" style="display:none" accept=".xlsx, .xls">

          <!-- TABLAS -->
          <div class="tab-pane fade show active" id="tab-hospitales" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="tbl-hospitales">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>MunicipioID</th>
                    <th>Teléfono</th>
                    <th>Contacto</th>
                    <th>Sucursal</th>
                    <th class="w-120">Activo</th>
                    <th class="w-120">Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-medicos" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="tbl-medicos">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Almacén</th>
                    <th>Subgrupo</th>
                    <th class="w-120">Activo</th>
                    <th class="w-120">Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-grupos" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="tbl-grupos">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th class="w-120">Activo</th>
                    <th class="w-120">Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-subgrupos" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="tbl-subgrupos">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>GrupoID</th>
                    <th class="w-120">Activo</th>
                    <th class="w-120">Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-tipoevento" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="tbl-tipoevento">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>SubgrupoID</th>
                    <th class="w-120">Activo</th>
                    <th class="w-120">Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-conceptossalida" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="tbl-conceptossalida">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Filtro</th>
                    <th class="w-120">Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-categorias" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="tbl-categorias">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre Categoría</th>
                    <th>Familia</th>
                    <th>División Asignada</th>
                    <th class="w-120">Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-clientes" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle text-nowrap" id="tbl-clientes">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th style="min-width: 250px;">Razon Social</th>
                    <th style="min-width: 150px;">Contacto</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th style="min-width: 400px;">Ubicación</th>
                    <th style="min-width: 120px;">Ciudad</th>
                    <th style="min-width: 120px;">Estado</th>
                    <th>C.P.</th>
                    <th style="min-width: 150px;">RFC/CURP</th>
                    <th class="w-120">Activo</th>
                    <th class="w-120">Acciones</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-proveedores" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="tbl-proveedores">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Razon Social</th>
                    <th>Contacto</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Ubicación</th>
                    <th>RFC</th>
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
              <button type="button" class="btn-close close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <input type="hidden" id="f_id">
              <div id="formFields" class="form-row"></div>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn btn-primary">Guardar</button>
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal">Cerrar</button>
            </div>
          </form>
        </div>
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
      paises: '../ajax/get.paises.php',
      estados: '../ajax/get.estados.php', // ?paisid=#
      municipios: '../ajax/get.municipios.php', // ?estadoid=#
      almacenes: '../ajax/get.catalogoalmacenes.php?tipoalmacen=1,2&todos=17',
      hospitales: '../ajax/get.hospitales.php',
      grupos: '../ajax/get.eventos.tipogrupo.php',
      subgruposByGrupo: '../ajax/get.eventos.tiposubgrupo.php', // params: grupoid
      conceptossalida: '../ajax/get.conceptossalida.php',
      monedas: '../api/catalogos.php?action=get_options&type=monedas&cat=clientes',
      condiciones_pago: '../api/catalogos.php?action=get_options&type=condiciones_pago&cat=clientes',
      tipos_clientes: '../api/catalogos.php?action=get_options&type=tipos_clientes&cat=clientes',
      cobradores: '../api/catalogos.php?action=get_options&type=cobradores&cat=clientes',
      vendedores: '../api/catalogos.php?action=get_options&type=vendedores&cat=clientes',
      familias: '../ajax/get.familias.php',
      divisionesByFamilia: '../ajax/get.divisiones.by.familia.php', // ?familiaid=#
      ciudades_opt: '../api/catalogos.php?action=get_options&type=ciudades&cat=clientes',
      estados_opt: '../api/catalogos.php?action=get_options&type=estados&cat=clientes',
      // NUEVOS (para edición):
      ciudadesByEstado: '../api/catalogos.php?action=get_options&type=ciudades&cat=clientes', // ?estado_id=#
      lineageMunicipio: '../ajax/get.lineage.municipio.php', // ?ciudadid=#
      grupoBySubgrupo: '../ajax/get.grupo.by.subgrupo.php' // ?subgrupoid=#
    };

    const state = {
      cat: 'hospitales',
      q: '',
      almacen: 0,
      page: 1,
      limit: 20
    };

    // ============== Helpers genéricos ==============
    function ajaxJSON(url, data = {}, onOk = () => {}, onErr = () => {}) {
      $.ajax({
        url,
        data,
        dataType: 'json',
        success: onOk,
        error: (xhr) => {
          console.error('AJAX', url, xhr?.responseText || xhr);
          onErr(xhr);
        }
      });
    }

    function toOptions(arr, idKey = 'ID', nameKey = 'NOMBRE', selected = null) {
      let html = '<option value="">-- Seleccione --</option>';
      (arr || []).forEach(r => {
        const v = r[idKey],
          t = r[nameKey];
        const isSelected = Array.isArray(selected) ?
          selected.map(String).includes(String(v)) :
          (String(v) === String(selected));
        const sel = isSelected ? ' selected' : '';
        html += `<option value="${v}"${sel}>${t}</option>`;
      });
      return html;
    }

    function enable($el, on = true) {
      $el = $($el); // Siempre asegurar que sea jQuery
      if (!$el.length) return;
      if (on) {
        $el.prop('disabled', false).removeAttr('disabled').attr('aria-disabled', 'false');
        if ($.fn.select2 && $el.hasClass('select2-hidden-accessible')) {
          const inst = $el.data('select2');
          const $c = inst?.$container || $el.siblings('.select2').first();
          if ($c && $c.length) $c.removeClass('select2-container--disabled');
          $el.trigger('change.select2');
        }
      } else {
        $el.prop('disabled', true).attr('disabled', 'disabled').attr('aria-disabled', 'true');
        if ($.fn.select2 && $el.hasClass('select2-hidden-accessible')) {
          const inst = $el.data('select2');
          const $c = inst?.$container || $el.siblings('.select2').first();
          if ($c && $c.length) $c.addClass('select2-container--disabled');
          $el.trigger('change.select2');
        }
      }
    }



    function initSelect2($sel) {
      if (!$.fn.select2) return;
      if (!$sel || !$sel.length) return;
      if (!$sel.is('select')) return;

      if ($sel.hasClass('select2-hidden-accessible')) {
        try {
          $sel.select2('destroy');
        } catch (_) {}
      }

      $sel.select2({
        width: '100%',
        placeholder: 'Seleccione...',
        allowClear: true,
        dropdownParent: $(document.body)
      });

      const inst = $sel.data('select2');
      const $c = inst?.$container || $sel.siblings('.select2').first();
      if ($c && $c.length) {
        $c.toggleClass('select2-container--disabled', $sel.is(':disabled'))
          .attr('aria-disabled', $sel.is(':disabled') ? 'true' : 'false');
      }
    }






    // =================== Cat configs ===================
    const CAT_CFG = {
      'hospitales': {
        table: '#tbl-hospitales',
        pk: 'HOSPITAL_ID',
        activo: 'HOSPITAL_ACTIVO',
        fields: [{
            name: 'HOSPITAL_NOMBRE',
            label: 'Nombre',
            type: 'text',
            required: true
          },
          {
            name: 'HOSPITAL_DIRECCION',
            label: 'Dirección',
            type: 'text'
          },
          // Cascada País -> Estado -> Municipio (se guarda solo MUNICIPIOID)
          {
            name: 'HOSPITAL_PAISID_UI',
            label: 'País',
            type: 'select',
            data: 'paises'
          },
          {
            name: 'HOSPITAL_ESTADOID_UI',
            label: 'Estado',
            type: 'select',
            dependsOn: 'HOSPITAL_PAISID_UI',
            loader: (paisId, $el) => loadEstados(paisId, $el)
          },
          {
            name: 'HOSPITAL_MUNICIPIOID',
            label: 'Municipio',
            type: 'select',
            dependsOn: 'HOSPITAL_ESTADOID_UI',
            required: true,
            loader: (edoId, $el) => loadMunicipios(edoId, $el)
          },
          {
            name: 'HOSPITAL_TELEFONO',
            label: 'Teléfono',
            type: 'text'
          },
          {
            name: 'HOSPITAL_CONTACTO',
            label: 'Contacto',
            type: 'text'
          },
          {
            name: 'HOSPITAL_ALMACEN',
            label: 'Almacén',
            type: 'select',
            data: 'almacenes'
          }
        ],
        drawRow: (r) => `
      <tr>
        <td>${r.HOSPITAL_ID}</td>
        <td>${r.HOSPITAL_NOMBRE??''}</td>
        <td>${r.HOSPITAL_DIRECCION??''}</td>
        <td>${r.HOSPITAL_MUNICIPIOID??''}</td>
        <td>${r.HOSPITAL_TELEFONO??''}</td>
        <td>${r.HOSPITAL_CONTACTO??''}</td>
        <td>${r.HOSPITAL_ALMACEN_NOMBRE??''}</td>
        <td><span class="badge ${(+r.HOSPITAL_ACTIVO? 'badge-activo':'badge-inactivo')}">${+r.HOSPITAL_ACTIVO? 'Sí':'No'}</span></td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-editar="${r.HOSPITAL_ID}">Editar</button>
            <button type="button" class="btn btn-outline-${+r.HOSPITAL_ACTIVO? 'danger':'success'}" data-toggle-activo="${r.HOSPITAL_ID}" data-next="${+r.HOSPITAL_ACTIVO?0:1}">
              ${+r.HOSPITAL_ACTIVO? 'Desactivar':'Activar'}
            </button>
          </div>
        </td>
      </tr>`
      },
      'medicos': {
        table: '#tbl-medicos',
        pk: 'MEDICO_ID',
        activo: 'MEDICO_ACTIVO',
        fields: [{
            name: 'MEDICO_NOMBRE',
            label: 'Nombre',
            type: 'text',
            required: true
          },
          {
            name: 'MEDICO_TELEFONO',
            label: 'Teléfono',
            type: 'text'
          },
          {
            name: 'MEDICO_CORREO',
            label: 'Correo',
            type: 'email'
          },
          {
            name: 'MEDICO_ALMACENID',
            label: 'Almacén',
            type: 'select',
            data: 'almacenes'
          },
          {
            name: 'MEDICO_GRUPOID_UI',
            label: 'Grupo',
            type: 'select',
            data: 'grupos'
          },
          {
            name: 'MEDICO_TIPOEVENTOID',
            label: 'Subgrupo',
            type: 'select',
            dependsOn: 'MEDICO_GRUPOID_UI',
            loader: (grupoId, $el) => loadSubgrupos(grupoId, $el)
          }
        ],
        drawRow: (r) => `
      <tr>
        <td>${r.MEDICO_ID}</td><td>${r.MEDICO_NOMBRE??''}</td><td>${r.MEDICO_TELEFONO??''}</td>
        <td>${r.MEDICO_CORREO??''}</td><td>${r.ALMACEN_NOMBRE??''}</td><td>${r.SUBGRUPO_NOMBRE??''}</td>
        <td><span class="badge ${(+r.MEDICO_ACTIVO? 'badge-activo':'badge-inactivo')}">${+r.MEDICO_ACTIVO? 'Sí':'No'}</span></td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-editar="${r.MEDICO_ID}">Editar</button>
            <button type="button" class="btn btn-outline-${+r.MEDICO_ACTIVO? 'danger':'success'}" data-toggle-activo="${r.MEDICO_ID}" data-next="${+r.MEDICO_ACTIVO?0:1}">
              ${+r.MEDICO_ACTIVO? 'Desactivar':'Activar'}
            </button>
          </div>
        </td>
      </tr>`
      },
      'grupos': {
        table: '#tbl-grupos',
        pk: 'TIPOEVENTOGRUPO_ID',
        activo: 'TIPOEVENTOGRUPO_ACTIVO',
        fields: [{
          name: 'TIPOEVENTOGRUPO_NOMBRE',
          label: 'Nombre',
          type: 'text',
          required: true
        }],
        drawRow: (r) => `
      <tr>
        <td>${r.TIPOEVENTOGRUPO_ID}</td><td>${r.TIPOEVENTOGRUPO_NOMBRE??''}</td>
        <td><span class="badge ${(+r.TIPOEVENTOGRUPO_ACTIVO? 'badge-activo':'badge-inactivo')}">${+r.TIPOEVENTOGRUPO_ACTIVO? 'Sí':'No'}</span></td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-editar="${r.TIPOEVENTOGRUPO_ID}">Editar</button>
            <button type="button" class="btn btn-outline-${+r.TIPOEVENTOGRUPO_ACTIVO? 'danger':'success'}" data-toggle-activo="${r.TIPOEVENTOGRUPO_ID}" data-next="${+r.TIPOEVENTOGRUPO_ACTIVO?0:1}">
              ${+r.TIPOEVENTOGRUPO_ACTIVO? 'Desactivar':'Activar'}
            </button>
          </div>
        </td>
      </tr>`
      },
      'subgrupos': {
        table: '#tbl-subgrupos',
        pk: 'TIPOEVENTOSUBGRUPO_ID',
        activo: 'TIPOEVENTOSUBGRUPO_ACTIVO',
        fields: [{
            name: 'TIPOEVENTOSUBGRUPO_NOMBRE',
            label: 'Nombre',
            type: 'text',
            required: true
          },
          {
            name: 'TIPOEVENTOSUBGRUPO_GRUPOID',
            label: 'Grupo',
            type: 'select',
            data: 'grupos',
            required: true
          }
        ],
        drawRow: (r) => `
      <tr>
        <td>${r.TIPOEVENTOSUBGRUPO_ID}</td><td>${r.TIPOEVENTOSUBGRUPO_NOMBRE??''}</td><td>${r.TIPOEVENTOSUBGRUPO_GRUPOID??''}</td>
        <td><span class="badge ${(+r.TIPOEVENTOSUBGRUPO_ACTIVO? 'badge-activo':'badge-inactivo')}">${+r.TIPOEVENTOSUBGRUPO_ACTIVO? 'Sí':'No'}</span></td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-editar="${r.TIPOEVENTOSUBGRUPO_ID}">Editar</button>
            <button type="button" class="btn btn-outline-${+r.TIPOEVENTOSUBGRUPO_ACTIVO? 'danger':'success'}" data-toggle-activo="${r.TIPOEVENTOSUBGRUPO_ID}" data-next="${+r.TIPOEVENTOSUBGRUPO_ACTIVO?0:1}">
              ${+r.TIPOEVENTOSUBGRUPO_ACTIVO? 'Desactivar':'Activar'}
            </button>
          </div>
        </td>
      </tr>`
      },
      'tipoevento': {
        table: '#tbl-tipoevento',
        pk: 'TIPOEVENTO_ID',
        activo: 'TIPOEVENTO_ACTIVO',
        // Guarda solo SUBGRUPOID; Grupo es UI para filtrar
        fields: [{
            name: 'TIPOEVENTO_NOMBRE',
            label: 'Nombre',
            type: 'text',
            required: true
          },
          {
            name: 'TIPOEVENTO_GRUPOID_UI',
            label: 'Grupo',
            type: 'select',
            data: 'grupos',
            required: true
          }, // UI
          {
            name: 'TIPOEVENTO_SUBGRUPOID',
            label: 'Subgrupo',
            type: 'select',
            dependsOn: 'TIPOEVENTO_GRUPOID_UI',
            loader: (grupoId, $el) => loadSubgrupos(grupoId, $el),
            required: true
          }
        ],
        drawRow: (r) => `
      <tr>
        <td>${r.TIPOEVENTO_ID}</td><td>${r.TIPOEVENTO_NOMBRE??''}</td><td>${r.TIPOEVENTO_SUBGRUPOID??''}</td>
        <td><span class="badge ${(+r.TIPOEVENTO_ACTIVO? 'badge-activo':'badge-inactivo')}">${+r.TIPOEVENTO_ACTIVO? 'Sí':'No'}</span></td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-editar="${r.TIPOEVENTO_ID}">Editar</button>
            <button type="button" class="btn btn-outline-${+r.TIPOEVENTO_ACTIVO? 'danger':'success'}" data-toggle-activo="${r.TIPOEVENTO_ID}" data-next="${+r.TIPOEVENTO_ACTIVO?0:1}">
              ${+r.TIPOEVENTO_ACTIVO? 'Desactivar':'Activar'}
            </button>
          </div>
        </td>
      </tr>`
      },
      'conceptossalida': {
        table: '#tbl-conceptossalida',
        pk: 'CONCEPTOSAL_ID',
        activo: '1',
        /* Dummy */
        fields: [{
            name: 'CONCEPTOSAL_NOMBRE',
            label: 'Nombre',
            type: 'text',
            required: true
          },
          {
            name: 'CONCEPTOSAL_FILTRO',
            label: 'Filtro Artículos (Salida)',
            type: 'select',
            data: 'conceptofiltros',
            required: false,
            multiple: true
          }
        ],
        drawRow: (r) => {
          let filtroNames = [];
          if (r.CONCEPTOSAL_FILTRO) {
            const fArr = r.CONCEPTOSAL_FILTRO.split(',');
            if (fArr.includes('caducados')) filtroNames.push('Caducados');
            if (fArr.includes('proximos')) filtroNames.push('Próximos a caducar');
            if (fArr.includes('vigentes')) filtroNames.push('Vigentes');
          }
          let textFiltro = filtroNames.join(', ');
          return `
      <tr>
        <td>${r.CONCEPTOSAL_ID}</td>
        <td>${r.CONCEPTOSAL_NOMBRE??''}</td>
        <td>${textFiltro}</td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-editar="${r.CONCEPTOSAL_ID}">Editar</button>
          </div>
        </td>
      </tr>`;
        }
      },
      'categorias': {
        table: '#tbl-categorias',
        pk: 'LINEA_ARTICULO_ID',
        activo: '1',
        fields: [{
            name: 'NOMBRE',
            label: 'Nombre Categoría',
            type: 'text',
            required: true
          },
          {
            name: 'GRUPO_LINEA_ID',
            label: 'Familia',
            type: 'select',
            data: 'familias',
            required: true
          },
          {
            name: 'DIVISION_ID',
            label: 'División',
            type: 'select',
            dependsOn: 'GRUPO_LINEA_ID',
            loader: (familiaId, $el) => loadDivisionesByFamilia(familiaId, $el),
            required: false
          }
        ],
        drawRow: (r) => `
      <tr>
        <td>${r.LINEA_ARTICULO_ID}</td>
        <td>${r.NOMBRE??''}</td>
        <td>${r.FAMILIA_NOMBRE ? `<span class="badge bg-light text-dark border border-secondary">${r.FAMILIA_NOMBRE}</span>` : '<span class="text-muted">Sin asignar</span>'}</td>
        <td>${r.DIVISION_NOMBRE ? `<span class="badge bg-primary text-white">${r.DIVISION_NOMBRE}</span>` : '<span class="badge bg-secondary text-white">Sin asignar</span>'}</td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-editar="${r.LINEA_ARTICULO_ID}">Editar</button>
            <button type="button" class="btn btn-outline-danger" data-toggle-activo="${r.LINEA_ARTICULO_ID}" data-next="0">Desactivar</button>
          </div>
        </td>
      </tr>`
      },
      'clientes': {
        table: '#tbl-clientes',
        pk: 'CLIENTE_ID',
        activo: 'ESTATUS', // A, S, etc.
        fields: [{
            tab: 'Inf. General',
            name: 'NOMBRE_COMERCIAL',
            label: 'Razon Social',
            type: 'text',
            required: true
          },
          {
            tab: 'Inf. General',
            name: 'NOMBRE_CONTACTO',
            label: 'Nombre de Contacto',
            type: 'text'
          },
          {
            tab: 'Inf. General',
            name: 'TELEFONO',
            label: 'Teléfono',
            type: 'text'
          },
          {
            tab: 'Inf. General',
            name: 'CORREO',
            label: 'Correo',
            type: 'email'
          },
          {
            tab: 'Inf. General',
            name: 'CALLE',
            label: 'Calle',
            type: 'text',
            col: 8
          },
          {
            tab: 'Inf. General',
            name: 'NUM_EXTERIOR',
            label: 'Núm. Exterior',
            type: 'text',
            col: 4
          },
          {
            tab: 'Inf. General',
            name: 'COLONIA',
            label: 'Colonia',
            type: 'text',
            col: 6
          },
          {
            tab: 'Inf. General',
            name: 'POBLACION',
            label: 'Municipio',
            type: 'text',
            col: 6
          },
          {
            tab: 'Inf. General',
            name: 'ESTADO_ID',
            label: 'Estado',
            type: 'select',
            data: 'estados',
            col: 6
          },
          {
            tab: 'Inf. General',
            name: 'CIUDAD_ID',
            label: 'Ciudad',
            type: 'select',
            data: 'ciudades',
            dependsOn: 'ESTADO_ID',
            loader: loadCiudadesByEstado,
            col: 6
          },
          {
            tab: 'Inf. Fiscal',
            name: 'CODIGO_POSTAL',
            label: 'Código Postal',
            type: 'text'
          },
          {
            tab: 'Inf. Fiscal',
            name: 'RFC_CURP',
            label: 'RFC / CURP',
            type: 'text'
          },
          {
            tab: 'Condiciones',
            name: 'MONEDA_ID',
            label: 'Moneda',
            type: 'select',
            data: 'monedas',
            required: true
          },
          {
            tab: 'Condiciones',
            name: 'COND_PAGO_ID',
            label: 'Condición de Pago',
            type: 'select',
            data: 'condiciones_pago',
            required: true
          },
          {
            tab: 'Condiciones',
            name: 'TIPO_CLIENTE_ID',
            label: 'Tipo de Cliente',
            type: 'select',
            data: 'tipos_clientes',
            required: true
          },
          {
            tab: 'Condiciones',
            name: 'COBRADOR_ID',
            label: 'Cobrador',
            type: 'select',
            data: 'cobradores',
            required: true
          },
          {
            tab: 'Condiciones',
            name: 'VENDEDOR_ID',
            label: 'Vendedor',
            type: 'select',
            data: 'vendedores',
            required: true
          }
        ],
        drawRow: (r) => {
          const em = v => v || '<em class="text-muted">—</em>';
          const ubiParts = [];
          if (r.CALLE) ubiParts.push(r.CALLE + (r.NUM_EXTERIOR ? ' ' + r.NUM_EXTERIOR : ''));
          if (r.COLONIA) ubiParts.push(r.COLONIA);
          if (r.POBLACION) ubiParts.push(r.POBLACION);
          const ubi = ubiParts.length ? ubiParts.join(', ') : '';

          return `
      <tr>
        <td>${r.CLIENTE_ID}</td>
        <td><strong>${r.NOMBRE_COMERCIAL??''}</strong></td>
        <td>${r.NOMBRE_CONTACTO??''}</td>
        <td>${r.TELEFONO??''}</td>
        <td>${r.CORREO??''}</td>
        <td>${ubi || '<em class="text-muted">—</em>'}</td>
        <td>${em(r.CIUDAD_NOMBRE)}</td>
        <td>${em(r.ESTADO_NOMBRE)}</td>
        <td>${em(r.CODIGO_POSTAL)}</td>
        <td><strong>${em(r.RFC_CURP)}</strong></td>
        <td><span class="badge ${(+r.ACTIVO? 'badge-activo':'badge-inactivo')}">${+r.ACTIVO? 'Sí':'No'}</span></td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-editar="${r.CLIENTE_ID}">Editar</button>
            <button type="button" class="btn btn-outline-${+r.ACTIVO? 'danger':'success'}" data-toggle-activo="${r.CLIENTE_ID}" data-next="${+r.ACTIVO?0:1}">
              ${+r.ACTIVO? 'Desactivar':'Activar'}
            </button>
          </div>
        </td>
      </tr>`;
        }
      },
      'proveedores': {
        table: '#tbl-proveedores',
        pk: 'PROVEEDOR_ID',
        activo: 'ESTATUS',
        api: '../api/proveedores.php',
        fields: [
          // Inf. General
          {
            tab: 'Inf. General',
            name: 'NOMBRE',
            label: 'Razon Social',
            type: 'text',
            required: true
          },
          {
            tab: 'Inf. General',
            name: 'CONTACTO1',
            label: 'Nombre de Contacto',
            type: 'text'
          },
          {
            tab: 'Inf. General',
            name: 'TELEFONO1',
            label: 'Teléfono',
            type: 'text'
          },
          {
            tab: 'Inf. General',
            name: 'EMAIL',
            label: 'Correo',
            type: 'email'
          },
          {
            tab: 'Inf. General',
            name: 'CALLE',
            label: 'Calle',
            type: 'text'
          },
          {
            tab: 'Inf. General',
            name: 'COLONIA',
            label: 'Colonia',
            type: 'text'
          },
          {
            tab: 'Inf. General',
            name: 'POBLACION',
            label: 'Ciudad',
            type: 'text'
          },

          // Inf. Fiscal
          {
            tab: 'Inf. Fiscal',
            name: 'RFC_CURP',
            label: 'RFC',
            type: 'text'
          },
          {
            tab: 'Inf. Fiscal',
            name: 'CODIGO_POSTAL',
            label: 'Código Postal Fiscal',
            type: 'text'
          },
          {
            tab: 'Inf. Fiscal',
            name: 'CLAVE_REGIMEN_FISCAL',
            label: 'Régimen Fiscal',
            type: 'select',
            options: [{
                id: '601',
                text: '601 - General de Ley Personas Morales'
              },
              {
                id: '603',
                text: '603 - Personas Morales con Fines no Lucrativos'
              },
              {
                id: '606',
                text: '606 - Arrendamiento'
              },
              {
                id: '612',
                text: '612 - Personas Físicas con Actividades Empresariales y Profesionales'
              },
              {
                id: '626',
                text: '626 - Régimen Simplificado de Confianza'
              }
            ]
          },
          {
            tab: 'Inf. Fiscal',
            name: 'USO_CFDI',
            label: 'Uso de CFDI',
            type: 'select',
            options: [{
                id: 'G01',
                text: 'G01 - Adquisición de mercancías'
              },
              {
                id: 'G03',
                text: 'G03 - Gastos en general'
              },
              {
                id: 'I08',
                text: 'I08 - Otra maquinaria y equipo'
              },
              {
                id: 'P01',
                text: 'P01 - Por definir'
              }
            ]
          },

          // Condiciones
          {
            tab: 'Condiciones',
            name: 'MONEDA_ID',
            label: 'Moneda',
            type: 'select',
            data: 'monedas'
          },
          {
            tab: 'Condiciones',
            name: 'COND_PAGO_ID',
            label: 'Condición de Pago',
            type: 'select',
            data: 'condiciones_pago'
          },
          {
            tab: 'Condiciones',
            name: 'LIMITE_CREDITO',
            label: 'Límite de Crédito',
            type: 'number'
          }
        ],
        drawRow: (r) => {
          const em = v => v || '<em class="text-muted">—</em>';
          // Armar ubicacion desde CALLE + COLONIA + POBLACION
          const ubi = [r.CALLE, r.COLONIA, r.POBLACION].filter(Boolean).join(', ');
          return `
      <tr>
        <td>${r.PROVEEDOR_ID}</td>
        <td><strong>${r.NOMBRE??''}</strong></td>
        <td>${em(r.CONTACTO1)}</td>
        <td>${em(r.TELEFONO1)}</td>
        <td>${em(r.EMAIL)}</td>
        <td>${ubi || '<em class="text-muted">—</em>'}</td>
        <td><strong>${em(r.RFC_CURP)}</strong></td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-primary" data-editar="${r.PROVEEDOR_ID}">Editar</button>
            <button type="button" class="btn btn-outline-${+r.ACTIVO? 'danger':'success'}" data-toggle-activo="${r.PROVEEDOR_ID}" data-next="${+r.ACTIVO?0:1}">
              ${+r.ACTIVO? 'Desactivar':'Activar'}
            </button>
          </div>
        </td>
      </tr>`;
        }
      },
    };

    // =============== Render inputs ===============
    function renderField(f) {
      const req = f.required ? 'required' : '';
      const readonly = f.readonly ? 'readonly' : '';
      const disabled = f.readonly ? 'disabled' : ''; // for selects
      const colClass = f.col ? `col-md-${f.col}` : 'col-md-6';

      if (f.type === 'select') {
        const multiple = f.multiple ? 'multiple="multiple"' : '';
        const nameAttr = f.multiple ? `${f.name}[]` : f.name;
        let opts = '';
        if (f.options) {
          opts = '<option value="">-- Seleccione --</option>';
          f.options.forEach(o => {
            opts += `<option value="${o.id}">${o.text}</option>`;
          });
        }
        return `
      <div class="form-group ${colClass} mb-2">
        <label class="form-label font-weight-bold" style="font-size: 0.9em; margin-bottom: 2px;">${f.label}${f.required?' <span class="text-danger">*</span>':''}</label>
        <select class="form-control form-control-sm" id="${f.name}" name="${nameAttr}" ${multiple} ${req} ${disabled}>${opts}</select>
      </div>
    `;
      }
      // inputs normales
      return `
    <div class="form-group ${colClass} mb-2">
      <label class="form-label font-weight-bold" style="font-size: 0.9em; margin-bottom: 2px;">${f.label}${f.required?' <span class="text-danger">*</span>':''}</label>
      <input class="form-control form-control-sm" type="${f.type}" id="${f.name}" name="${f.name}" ${req} ${readonly}>
    </div>
  `;
    }

    // =============== Cargas de catálogos ===============
    function loadPaises($sel, selected = null) {
      enable($sel, false);
      ajaxJSON(EP.paises, {}, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        enable($sel, true); // <- PRIMERO habilitar
        initSelect2($sel); // <- LUEGO inicializar
      }, _err => {
        $sel.html('<option value="">(Error cargando países)</option>');
        enable($sel, true);
        initSelect2($sel);
      });
    }

    function loadEstados(paisId, $sel, selected = null) {
      if (!paisId) {
        $sel.html('<option value="">-- Seleccione país primero --</option>');
        initSelect2($sel);
        enable($sel, false);
        return;
      }
      enable($sel, false);
      ajaxJSON(EP.estados, {
        paisid: paisId
      }, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      }, _err => {
        $sel.html('<option value="">(Error cargando estados)</option>');
        initSelect2($sel);
        // déjalo habilitado para que el usuario note el error pero no se “congele”
        enable($sel, true);
      });
    }


    function loadMunicipios(estadoId, $sel, selected = null) {
      if (!estadoId) {
        $sel.html('<option value="">-- Seleccione estado primero --</option>');
        initSelect2($sel);
        enable($sel, false);
        return;
      }
      enable($sel, false);
      ajaxJSON(EP.municipios, {
        estadoid: estadoId
      }, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      });
    }

    function loadAlmacenes($sel, selected = null) {
      enable($sel, false);
      ajaxJSON(EP.almacenes, {}, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      }, _err => {
        $sel.html('<option value="">(Error cargando almacenes)</option>');
        initSelect2($sel);
        enable($sel, true);
      });
    }

    function loadHospitales($sel, selected = null) {
      enable($sel, false);
      ajaxJSON(EP.hospitales, {}, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      }, _err => {
        $sel.html('<option value="">(Error cargando hospitales)</option>');
        initSelect2($sel);
        enable($sel, true);
      });
    }

    function loadGrupos($sel, selected = null) {
      enable($sel, false);
      ajaxJSON(EP.grupos, {}, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      });
    }

    function loadSubgrupos(grupoId, $sel, selected = null) {
      if (!grupoId) {
        $sel.html('<option value="">-- Seleccione grupo primero --</option>');
        initSelect2($sel);
        enable($sel, false);
        return;
      }
      enable($sel, false);
      ajaxJSON(EP.subgruposByGrupo, {
        grupoid: grupoId
      }, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      });
    }

    function loadFamilias($sel, selected = null) {
      enable($sel, false);
      ajaxJSON(EP.familias, {}, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      }, _err => {
        $sel.html('<option value="">(Error)</option>');
        initSelect2($sel);
        enable($sel, true);
      });
    }

    function loadDivisionesByFamilia(familiaId, $sel, selected = null) {
      if (!familiaId) {
        $sel.html('<option value="">-- Seleccione familia primero --</option>');
        initSelect2($sel);
        enable($sel, false);
        return;
      }
      enable($sel, false);
      ajaxJSON(EP.divisionesByFamilia, {
        familiaid: familiaId
      }, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      });
    }

    function loadConceptoFiltros($sel, selected = null) {
      const rows = [{
          ID: 'caducados',
          NOMBRE: 'Caducados'
        },
        {
          ID: 'proximos',
          NOMBRE: 'Próximos a caducar'
        },
        {
          ID: 'vigentes',
          NOMBRE: 'Vigentes'
        }
      ];
      $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
      initSelect2($sel);
      enable($sel, true);
    }

    function loadMonedas($sel, selected = null) {
      enable($sel, false);
      ajaxJSON(EP.monedas, {}, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      });
    }

    function loadCiudadesByEstado(estado_id, $sel, selected = null) {
      if (!estado_id) {
        $sel.html('<option value="">-- Seleccione estado --</option>');
        $sel.val('').trigger('change.select2');
        enable($sel, false);
        return;
      }
      enable($sel, false);
      ajaxJSON(EP.ciudadesByEstado, { estado_id: estado_id }, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      });
    }

    function loadEstadosClientes($sel, selected = null) {
      enable($sel, false);
      ajaxJSON(EP.estados_opt, {}, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      });
    }

    function loadCondicionesPago($sel, selected = null) {
      enable($sel, false);
      ajaxJSON(EP.condiciones_pago, {}, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      });
    }

    function loadTiposClientes($sel, selected = null) {
      enable($sel, false);
      ajaxJSON(EP.tipos_clientes, {}, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      });
    }

    function loadCobradores($sel, selected = null) {
      enable($sel, false);
      ajaxJSON(EP.cobradores, {}, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      });
    }

    function loadVendedores($sel, selected = null) {
      enable($sel, false);
      ajaxJSON(EP.vendedores, {}, rows => {
        $sel.html(toOptions(rows, 'ID', 'NOMBRE', selected));
        initSelect2($sel);
        enable($sel, true);
      });
    }

    // =============== Listado ===============
    function currentCfg() {
      return CAT_CFG[state.cat];
    }

    function loadList() {
      const cfg = currentCfg();
      const $tbody = $(cfg.table + ' tbody').empty();
      $('#lblTotal').text('Cargando...');

      // Proveedores usa su propia API
      const apiUrl = cfg.api || API;
      const params = cfg.api ? {
        action: 'list',
        q: state.q,
        page: state.page,
        limit: state.limit,
        almacen: state.almacen
      } : {
        action: 'list',
        cat: state.cat,
        q: state.q,
        page: state.page,
        limit: state.limit,
        almacen: state.almacen
      };

      $.ajax({
        url: apiUrl,
        data: params,
        dataType: 'json',
        success: function(res) {
          if (!res.ok) {
            alert(res.msg || 'Error');
            return;
          }
          (res.data.items || []).forEach(r => $tbody.append(cfg.drawRow(r)));
          $('#lblTotal').text(`Total: ${res.data.total}  |  Página ${res.data.page}`);
        },
        error: function(xhr) {
          console.error('LIST XHR:', xhr?.responseText || xhr);
          alert('Error cargando la lista (revisa consola).');
          $('#lblTotal').text('Error');
        }
      });
    }

    // =============== Excel (Plantilla e Importación) ===============
    function downloadTemplate() {
      const cat = state.cat;
      if (['hospitales', 'medicos'].includes(cat)) {
        window.location.href = `../api/catalogos_excel.php?action=template&cat=${cat}`;
      } else {
        Swal.fire('Info', 'Este catálogo no soporta plantillas de Excel por ahora.', 'info');
      }
    }

    function initImportExcel() {
      const cat = state.cat;
      if (!['hospitales', 'medicos'].includes(cat)) {
        Swal.fire('Info', 'Este catálogo no soporta importación de Excel por ahora.', 'info');
        return;
      }
      $('#fileImportExcel').click();
    }

    $('#fileImportExcel').on('change', function() {
      const file = this.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append('file', file);

      Swal.fire({
        title: 'Importando...',
        text: 'Por favor espera mientras se procesa el archivo.',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      $.ajax({
        url: `../api/catalogos_excel.php?action=import&cat=${state.cat}`,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
          if (res.status === 'success') {
            Swal.fire('Éxito', res.message, 'success');
            loadList();
          } else {
            Swal.fire('Error', res.message, 'error');
          }
        },
        error: function() {
          Swal.fire('Error', 'Error de conexión con el servidor.', 'error');
        },
        complete: function() {
          $('#fileImportExcel').val(''); // Limpiar input
        }
      });
    });

    $('#btnDownloadTemplate').on('click', downloadTemplate);
    $('#btnImportExcel').on('click', initImportExcel);

    // =============== Formulario ===============
    function openForm(id = 0) {
      const cfg = currentCfg();
      const $form = $('#frmItem');
      $form[0].reset();
      $('#f_id').val(id);
      const $fields = $('#formFields').empty();

      // ====== RENDER TABS SI EXISTEN ======
      const tabs = [...new Set(cfg.fields.map(f => f.tab).filter(Boolean))];
      if (tabs.length > 0) {
        let navHtml = '<ul class="nav nav-tabs mb-3" role="tablist" style="width: 100%; border-bottom: 2px solid #dee2e6;">';
        let contentHtml = '<div class="tab-content w-100">';

        tabs.forEach((tab, index) => {
          const tabId = 'tab-dyn-' + index;
          const activeClass = index === 0 ? 'active' : '';
          const showClass = index === 0 ? 'show active' : '';

          navHtml += `
            <li class="nav-item" role="presentation">
              <a class="nav-link ${activeClass}" data-toggle="tab" href="#${tabId}" role="tab" style="font-weight: 500;">${tab}</a>
            </li>`;

          contentHtml += `
            <div class="tab-pane fade ${showClass}" id="${tabId}" role="tabpanel">
              <div class="form-row" id="tab-pane-dyn-${index}"></div>
            </div>`;
        });

        navHtml += '</ul>';
        contentHtml += '</div>';

        $fields.append(navHtml + contentHtml);

        // Agregar campos a sus pestañas
        cfg.fields.forEach(f => {
          const tabIndex = tabs.indexOf(f.tab);
          if (tabIndex !== -1) {
            $(`#tab-pane-dyn-${tabIndex}`).append(renderField(f));
          } else {
            $fields.append(renderField(f));
          }
        });
      } else {
        // Renderizado normal sin tabs
        cfg.fields.forEach(f => $fields.append(renderField(f)));
      }

      // Inicializa selects (deshabilita hijos hasta tener padre)
      cfg.fields.forEach(f => {
        if (f.type !== 'select') return;
        const $sel = $('#' + f.name);

        if (f.dependsOn) {
          // hijos: placeholder y deshabilitado de entrada
          $sel.html('<option value="">-- Seleccione primero --</option>');
          enable($sel, false);
          initSelect2($sel); // se puede montar deshabilitado
        } else {
          // padres: cargan su catálogo y ahí mismo se habilitan + initSelect2
          if (f.data === 'paises') loadPaises($sel);
          if (f.data === 'almacenes') loadAlmacenes($sel);
          if (f.data === 'hospitales') loadHospitales($sel);
          if (f.data === 'grupos') loadGrupos($sel);
          if (f.data === 'conceptofiltros') loadConceptoFiltros($sel);
          if (f.data === 'familias') loadFamilias($sel);
          if (f.data === 'monedas') loadMonedas($sel);
          if (f.data === 'condiciones_pago') loadCondicionesPago($sel);
          if (f.data === 'tipos_clientes') loadTiposClientes($sel);
          if (f.data === 'cobradores') loadCobradores($sel);
          if (f.data === 'vendedores') loadVendedores($sel);
          // if (f.data === 'ciudades') loadCiudades($sel); // now depends on estado
          if (f.data === 'estados') loadEstadosClientes($sel);
          if (f.options) {
            initSelect2($sel);
            enable($sel, true);
          }
          // (NO llames initSelect2 aquí; lo llama el loader)
        }

        // wiring de cascada
        if (f.dependsOn && typeof f.loader === 'function') {
          $('#' + f.dependsOn)
            .off('change._cascade_' + f.name)
            .on('change._cascade_' + f.name, function() {
              const val = $(this).val();
              f.loader(val, $sel); // el loader hará enable/initSelect2
              $sel.val('').trigger('change');
            });
        }
      });

      // Abrir modal
      $('#mdlTitle').text(id > 0 ? 'Editar' : 'Nuevo');
      $('#mdlForm').modal('show');

      if (id === 0 && state.cat === 'categorias') {
        // Es nuevo, no debe ser readonly
        $('#NOMBRE').prop('readonly', false);
        $('#GRUPO_LINEA_ID').prop('disabled', false);
      }

      // ===== Edición: precargar valores + cascadas completas =====
      if (id > 0) {
        const apiUrl = cfg.api || API;
        const params = cfg.api ? {
          action: 'get',
          id
        } : {
          action: 'get',
          cat: state.cat,
          id
        };

        $.ajax({
          url: apiUrl,
          data: params,
          dataType: 'json',
          success: function(res) {
            if (!(res?.ok && res.data)) {
              alert(res?.msg || 'No encontrado');
              $('#mdlForm').modal('hide');
              return;
            }
            const row = res.data;

            // 1) Campos simples
            cfg.fields.forEach(f => {
              if (f.type !== 'select') $('#' + f.name).val(row[f.name] ?? '');
            });

            // 2) Catálogos no dependientes + selección
            if (state.cat === 'hospitales') {
              loadAlmacenes($('#HOSPITAL_ALMACEN'), row['HOSPITAL_ALMACEN'] ?? null);
              if (row['HOSPITAL_MUNICIPIOID']) {
                const ciudadId = row['HOSPITAL_MUNICIPIOID'];
                ajaxJSON(EP.lineageMunicipio, {
                  ciudadid: ciudadId
                }, function(info) {
                  loadPaises($('#HOSPITAL_PAISID_UI'), info.pais_id);
                  loadEstados(info.pais_id, $('#HOSPITAL_ESTADOID_UI'), info.estado_id);
                  loadMunicipios(info.estado_id, $('#HOSPITAL_MUNICIPIOID'), info.ciudad_id);
                });
              } else {
                loadPaises($('#HOSPITAL_PAISID_UI'), null);
              }
            }

            if (state.cat === 'medicos') {
              loadAlmacenes($('#MEDICO_ALMACENID'), row['MEDICO_ALMACENID'] ?? null);
              if (row['MEDICO_TIPOEVENTOID']) {
                ajaxJSON(EP.grupoBySubgrupo, {
                  subgrupoid: row['MEDICO_TIPOEVENTOID']
                }, function(info) {
                  loadGrupos($('#MEDICO_GRUPOID_UI'), info.grupo_id);
                  loadSubgrupos(info.grupo_id, $('#MEDICO_TIPOEVENTOID'), info.subgrupo_id);
                });
              } else {
                loadGrupos($('#MEDICO_GRUPOID_UI'), null);
                enable($('#MEDICO_TIPOEVENTOID'), false);
              }
            }

            if (state.cat === 'subgrupos') {
              loadGrupos($('#TIPOEVENTOSUBGRUPO_GRUPOID'), row['TIPOEVENTOSUBGRUPO_GRUPOID'] ?? null);
            }

            if (state.cat === 'tipoevento') {
              if (row['TIPOEVENTO_SUBGRUPOID']) {
                ajaxJSON(EP.grupoBySubgrupo, {
                  subgrupoid: row['TIPOEVENTO_SUBGRUPOID']
                }, function(info) {
                  loadGrupos($('#TIPOEVENTO_GRUPOID_UI'), info.grupo_id);
                  loadSubgrupos(info.grupo_id, $('#TIPOEVENTO_SUBGRUPOID'), info.subgrupo_id);
                });
              } else {
                loadGrupos($('#TIPOEVENTO_GRUPOID_UI'), null);
                enable($('#TIPOEVENTO_SUBGRUPOID'), false);
              }
            }

            if (state.cat === 'conceptossalida') {
              let filtroVal = row['CONCEPTOSAL_FILTRO'];
              if (filtroVal && typeof filtroVal === 'string') filtroVal = filtroVal.split(',');
              loadConceptoFiltros($('#CONCEPTOSAL_FILTRO'), filtroVal ?? []);
            }

            if (state.cat === 'categorias') {
              loadFamilias($('#GRUPO_LINEA_ID'), row['GRUPO_LINEA_ID'] ?? null);
              if (row['DIVISION_ID']) {
                loadDivisionesByFamilia(row['GRUPO_LINEA_ID'], $('#DIVISION_ID'), row['DIVISION_ID'] ?? null);
              } else {
                $('#DIVISION_ID').html('<option value="">-- Seleccione división --</option>');
                enable($('#DIVISION_ID'), false);
                loadDivisionesByFamilia(row['GRUPO_LINEA_ID'], $('#DIVISION_ID'), null);
              }
            }

            if (state.cat === 'clientes') {
              loadMonedas($('#MONEDA_ID'), row['MONEDA_ID'] ?? null);
              loadCondicionesPago($('#COND_PAGO_ID'), row['COND_PAGO_ID'] ?? null);
              loadTiposClientes($('#TIPO_CLIENTE_ID'), row['TIPO_CLIENTE_ID'] ?? null);
              loadCobradores($('#COBRADOR_ID'), row['COBRADOR_ID'] ?? null);
              loadVendedores($('#VENDEDOR_ID'), row['VENDEDOR_ID'] ?? null);
              loadEstadosClientes($('#ESTADO_ID'), row['ESTADO_ID'] ?? null);
              if (row['ESTADO_ID']) {
                loadCiudadesByEstado(row['ESTADO_ID'], $('#CIUDAD_ID'), row['CIUDAD_ID'] ?? null);
              } else {
                $('#CIUDAD_ID').html('<option value="">-- Seleccione estado --</option>');
                enable($('#CIUDAD_ID'), false);
              }
            }

            if (state.cat === 'proveedores') {
              loadMonedas($('#MONEDA_ID'), row['MONEDA_ID'] ?? null);
            }
          },
          error: function(xhr) {
            console.error('GET XHR:', xhr?.responseText || xhr);
            alert('Error consultando el registro');
            $('#mdlForm').modal('hide');
          }
        });
      }
    }

    // =============== Eventos UI / CRUD ===============
    function onTabChange(cat) {
      state.cat = cat;
      state.q = '';
      state.page = 1;
      $('#txtSearch').val('');

      loadList();
    }

    // Tabs (BS4)
    $('#catTabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
      const id = $(e.target).attr('href'); // #tab-xxx
      const cat = id.replace('#tab-', '');
      
      onTabChange(cat);

      // Visibilidad de botones de Excel
      const isExcelSupported = ['hospitales', 'medicos'].includes(state.cat);
      $('#btnDownloadTemplate, #btnImportExcel').toggle(isExcelSupported);

      $('#btnNew').show();
    });

    $('#txtSearch').on('keyup', function(e) {
      if (e.key === 'Enter') {
        state.q = $(this).val();
        state.page = 1;
        loadList();
      }
    });
    $('#btnPrev').on('click', () => {
      if (state.page > 1) {
        state.page--;
        loadList();
      }
    });
    $('#btnNext').on('click', () => {
      state.page++;
      loadList();
    });

    $('#btnNew').on('click', () => openForm(0));

    $(document).on('click', '[data-editar]', function() {
      openForm(parseInt($(this).data('editar')));
    });
    $(document).on('click', '[data-toggle-activo]', function() {
      const id = parseInt($(this).data('toggle-activo'));
      const next = parseInt($(this).data('next'));
      const cfg = currentCfg();

      if (next === 0 && !confirm('¿Estás seguro de que deseas desactivar este registro?')) return;

      const apiUrl = cfg.api || API;
      const params = cfg.api ? {
        action: 'set_activo',
        id,
        activo: next
      } : {
        action: 'set_activo',
        cat: state.cat,
        id,
        activo: next
      };

      $.ajax({
        url: apiUrl,
        method: 'POST',
        data: params,
        dataType: 'json',
        success: function(res) {
          if (res.ok) {
            loadList();
          } else {
            alert(res.msg || 'Error');
          }
        },
        error: function(xhr) {
          console.error('SET_ACTIVO XHR:', xhr?.responseText || xhr);
          alert('Error cambiando estatus.');
        }
      });
    });

    // Guardar
    $('#frmItem').on('submit', function(e) {
      e.preventDefault();
      const cfg = currentCfg();
      const fd = new FormData(this);
      fd.append('action', 'save');
      fd.append('id', $('#f_id').val() || 0);
      if (!cfg.api) fd.append('cat', state.cat); // solo para la API genérica

      $.ajax({
        url: cfg.api || API,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(r) {
          if (r.ok) {
            $('#mdlForm').modal('hide');
            loadList();
          } else {
            alert(r.msg || 'Error al guardar');
          }
        },
        error: function(xhr) {
          console.error('SAVE XHR:', xhr?.responseText || xhr);
          alert('Error de red/servidor al guardar');
        }
      });
    });

    // Inicial (Removido para evitar doble carga)

    // Cascadas en ALTAS (cuando el usuario va eligiendo)
    $(document).on('change', '#HOSPITAL_PAISID_UI', function() {
      loadEstados($(this).val(), $('#HOSPITAL_ESTADOID_UI'));
      $('#HOSPITAL_MUNICIPIOID').html('<option value="">-- Seleccione estado primero --</option>');
      enable($('#HOSPITAL_MUNICIPIOID'), false);
    });
    $(document).on('change', '#HOSPITAL_ESTADOID_UI', function() {
      loadMunicipios($(this).val(), $('#HOSPITAL_MUNICIPIOID'));
    });

    $(document).on('change', '#TIPOEVENTO_GRUPOID_UI', function() {
      loadSubgrupos($(this).val(), $('#TIPOEVENTO_SUBGRUPOID'));
    });

    $(document).on('change', '#MEDICO_GRUPOID_UI', function() {
      loadSubgrupos($(this).val(), $('#MEDICO_TIPOEVENTOID'));
    });

    // Carga inicial
    $(function() {
      // Aplicar visibilidad inicial de botones de Excel
      const isExcelSupported = ['hospitales', 'medicos'].includes(state.cat);
      $('#btnDownloadTemplate, #btnImportExcel').toggle(isExcelSupported);

      // Cargar sucursales para el filtro (excluyendo maletas (tipo 3) por parámetro y caducados por nombre)
      $.getJSON("../ajax/get.catalogoalmacenes.php?tipoalmacen=1,2,4&todos=17", function(data) {
        let itemsal = "<option value='0'>Todas las sucursales</option>";
        $.each(data, function(index, item) {
          const nombre = item.NOMBRE.toUpperCase();
          // Ignorar si contiene CADUCADO
          if (nombre.indexOf('CADUCADO') === -1) {
            itemsal += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
          }
        });
        $("#cboFiltroAlmacen").html(itemsal);
      });

      $('#cboFiltroAlmacen').on('change', function() {
        state.almacen = $(this).val();
        state.page = 1;
        loadList();
      });

      onTabChange(state.cat);
    });
  </script>
</body>

</html>