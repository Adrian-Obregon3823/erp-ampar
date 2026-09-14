<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once("../includes/head.php");?>
  </head>
  <body>
    <div class="container-scroller"> 
      <?php include_once("../includes/header.php"); ?>
      <div class="container-fluid page-body-wrapper">
        <?php include_once ("../includes/menu.sidebar.php")?>
        <div class="main-panel">
          <div class="content-wrapper">
            <!-- Inicia contenido principal -->
            <div class="container-fluid">

              <div class="row g-4">

                  <!-- Gestión de Perfiles -->
                  <div class="col-md-4">
                      <div class="card shadow-sm">
                          <div class="card-header">Gestión de Perfiles</div>
                          <div class="card-body">
                              <form id="formPerfil">
                                  <div class="mb-3">
                                      <label class="form-label">Nombre del Perfil</label>
                                      <input type="text" class="form-control" name="nombre" required>
                                  </div>
                                  <button type="submit" class="btn btn-primary w-100">Crear Perfil</button>
                              </form>
                              <hr>
                              <h6>Perfiles existentes</h6>
                              <ul id="listaPerfiles" class="list-group"></ul>
                          </div>
                      </div>
                  </div>

                  <!-- Gestión de Menús -->
                  <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-header">Gestión de Menús por Perfil</div>
                        <div class="card-body">
                            <form id="formMenuPerfil">
                                <div class="mb-3">
                                    <label class="form-label">Perfil</label>
                                    <select id="perfilMenu" name="perfil" class="form-select" required></select>
                                </div>
                                <div class="mb-3" id="menuTreeContainer">
                                    <!-- Aquí se cargará el árbol de menús dinámicamente -->
                                </div>
                                <button type="submit" class="btn btn-success w-100">Guardar Permisos</button>
                            </form>
                        </div>
                    </div>
                </div>

                  <!-- Gestión de Usuarios -->
                  <div class="col-md-4">
                      <div class="card shadow-sm">
                          <div class="card-header">Gestión de Usuarios</div>
                          <div class="card-body">
                              <form id="formUsuarioPerfil">
                                  <div class="mb-3">
                                      <label class="form-label">Usuario</label>
                                      <select id="usuarioSelect" name="usuario" class="form-select" required></select>
                                  </div>
                                  <div class="mb-3">
                                      <label class="form-label">Perfil</label>
                                      <select id="perfilUsuario" name="perfil" class="form-select" required></select>
                                  </div>
                                  <button type="submit" class="btn btn-warning w-100">Asignar Perfil a Usuario</button>
                              </form>
                          </div>
                      </div>
                  </div>

              </div>
            </div>
            <!-- Finaliza contenido princial -->
          </div>
          <?php include_once("../includes/footer.php");?>
        </div>
      </div>
    </div>
    <?php include_once("../includes/foot.php");?>
  </body>
</html>
<script>
$(document).ready(function(){
    cargarPerfiles();
    cargarUsuarios();

    // Crear Perfil
    $("#formPerfil").submit(function(e){
        e.preventDefault();
        $.post("../ajax/perfil.nuevo.php", $(this).serialize(), function(){
            cargarPerfiles();
            $("#formPerfil")[0].reset();
        });
    });

    // Asignar Perfil a Usuario
    $("#formUsuarioPerfil").submit(function(e){
        e.preventDefault();
        $.post("../ajax/perfil.agregar.usuario.php", $(this).serialize(), function(){
            alert("Perfil asignado al usuario");
        });
    });

    function cargarPerfiles(){
        $.getJSON("../ajax/perfiles.get.php", function(data){
            let lista = "";
            let opciones = "<option value=''>Seleccione...</option>";
            $.each(data, function(i, perfil){
                lista += `<li class="list-group-item">${perfil.PERFIL_NOMBRE}</li>`;
                opciones += `<option value="${perfil.PERFIL_ID}">${perfil.PERFIL_NOMBRE}</option>`;
            });
            $("#listaPerfiles").html(lista);
            $("#perfilMenu").html(opciones);
            $("#perfilUsuario").html(opciones);
        });
    }

    function cargarUsuarios(){
        $.getJSON("../ajax/perfil.get.usuarios.php", function(data){
            let opciones = "<option value=''>Seleccione...</option>";
            $.each(data, function(i, usuario){
                opciones += `<option value="${usuario.USUARIO_ID}">${usuario.USUARIO_NOMBRE}</option>`;
            });
            $("#usuarioSelect").html(opciones);
        });
    }

    // Cuando cambie el perfil
    $("#perfilMenu").change(function(){
        let perfilId = $(this).val();
        if(!perfilId){
            $("#menuTreeContainer").html(""); 
            return;
        }
        $.getJSON("../ajax/perfil.get.menu.php", {perfil: perfilId}, function(data){
            let html = '<ul class="list-group">';
            data.forEach(categoria => {
                html += `<li class="list-group-item">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input cat-check" data-id="${categoria.MENU_ID}" ${categoria.checked ? 'checked':''}>
                                <label class="form-check-label fw-bold">${categoria.MENU_NOMBRE}</label>
                            </div>
                            <ul class="list-group ms-3 mt-1">`;
                categoria.children.forEach(n1 => {
                    html += `<li class="list-group-item">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input n1-check" data-id="${n1.MENU_ID}" data-parent="${categoria.MENU_ID}" ${n1.checked ? 'checked':''}>
                                    <label class="form-check-label">${n1.MENU_NOMBRE}</label>
                                </div>
                                <ul class="list-group ms-3 mt-1">`;
                    n1.children.forEach(n2 => {
                        html += `<li class="list-group-item">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input n2-check" data-id="${n2.MENU_ID}" data-parent="${n1.MENU_ID}" ${n2.checked ? 'checked':''}>
                                        <label class="form-check-label">${n2.MENU_NOMBRE}</label>
                                    </div>
                                </li>`;
                    });
                    html += '</ul></li>';
                });
                html += '</ul></li>';
            });
            html += '</ul>';
            $("#menuTreeContainer").html(html);
        });
    });

    // Checkbox interdependientes
    $(document).on("change", ".cat-check", function(){
        let checked = $(this).is(":checked");
        $(this).closest("li").find("input").prop("checked", checked);
    });

    $(document).on("change", ".n1-check", function(){
        let checked = $(this).is(":checked");
        $(this).closest("li").find(".n2-check").prop("checked", checked);
        if(checked) {
            let catCheck = $(`.cat-check[data-id='${$(this).data("parent")}']`);
            catCheck.prop("checked", true);
        } else {
            let catCheck = $(`.cat-check[data-id='${$(this).data("parent")}']`);
            let anyChecked = $(this).closest("ul").find(".n1-check:checked").length > 0;
            if(!anyChecked) catCheck.prop("checked", false);
        }
    });

    $(document).on("change", ".n2-check", function(){
        let checked = $(this).is(":checked");
        let n1Check = $(`.n1-check[data-id='${$(this).data("parent")}']`);
        if(checked){
            n1Check.prop("checked", true);
            let catCheck = $(`.cat-check[data-id='${n1Check.data("parent")}']`);
            catCheck.prop("checked", true);
        } else {
            let anyChecked = $(this).closest("ul").find(".n2-check:checked").length > 0;
            if(!anyChecked) n1Check.prop("checked", false);
            let catCheck = $(`.cat-check[data-id='${n1Check.data("parent")}']`);
            let anyN1Checked = n1Check.closest("ul").find(".n1-check:checked").length > 0;
            if(!anyN1Checked) catCheck.prop("checked", false);
        }
    });

    // Guardar cambios
    $("#formMenuPerfil").submit(function(e){
        e.preventDefault();
        let perfil = $("#perfilMenu").val();
        let menus = [];
        $("#menuTreeContainer input:checked").each(function(){
            menus.push($(this).data("id"));
        });
        $.post("../ajax/perfil.agregar.menu.php", {perfil: perfil, menus: menus}, function(){
            alert("Permisos actualizados correctamente");
        });
    });


});
</script>