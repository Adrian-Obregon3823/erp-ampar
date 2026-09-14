<?php include_once("../includes/sesion.php"); ?>
<?php include_once("../includes/includes.php"); ?>
<div class="col-12">
    <div class="card">
        <div class="card-header">
            <h4>Datos de Usuario</h4>
        </div>
        <div class="card-body">
            <form class="forms-sample" id="form-usuarios" autocomplete="off">
                <div class="form-group">
                    Foto de perfil<br><br>
                    <img id="imagenPredeterminada" src="../img/nodisponible.jpg" width="120px" style="cursor:pointer" accept="image/*">
                    <input type="file" id="inputImagen" accept="image/*" style="display:none;">
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="uusuario">Usuario</label>
                            <input type="text" class="form-control" id="uusuario" name="uusuario" placeholder="Correo electrónico" onblur="usuariocorreo(this.value);" maxlength="150" autocomplete="new-password" value="">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-8">
                        <div class="form-group">
                            <label for="unombre">Nombre</label>
                            <input type="text" id="unombre" name="unombre" class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label for="ualias">Alias</label>
                            <input type="text" id="ualias" name="ualias" class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="utelefono">Teléfono WhatsApp <small class="text-muted">(10 dígitos, ej: 5512345678)</small></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="mdi mdi-whatsapp" style="color:#25D366"></i></span>
                                </div>
                                <input type="tel" id="utelefono" name="utelefono" class="form-control" placeholder="5512345678" maxlength="15" autocomplete="off">
                            </div>
                            <small class="text-muted">Se usará para recibir notificaciones del sistema vía WhatsApp.</small>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="urpwd">Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control input-square" id="urpwd" name="urpwd" placeholder="********" maxlength="12" onkeyup="verificarContrasena()" autocomplete="new-password">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="togglePassword('urpwd', this)">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="validacionPwd" style="font-size: 0.85rem; color: #555; margin-top: 5px;">
                            <ul style="list-style: none; padding-left: 0;">
                                <li id="val-length">❌ Mínimo 8 y máximo 12 caracteres</li>
                                <li id="val-mayus">❌ Al menos una letra mayúscula</li>
                                <li id="val-num">❌ Al menos un número</li>
                                <li id="val-especial">❌ Al menos un carácter especial</li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label for="urpwd2">Confirma Contraseña</label>
                            <div class="input-group">
                                <input type="password" class="form-control input-square" id="urpwd2" name="urpwd2" placeholder="********" maxlength="12" onkeyup="compararContrasenas()">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="togglePassword('urpwd2', this)">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <small id="mensajePwd2" style="font-size: 0.85rem; color: red; display: none;">Las contraseñas no coinciden.</small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="usucursales">Almacenes</label>
                            <select class="form-control" id="usucursales" name="usucursales[]" multiple></select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="utipo">Tipo</label>
                            <select class="form-control" id="utipo" name="utipo[]" multiple></select>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-success" onclick="guardar();">Guardar</button>
            </form>
        </div>
    </div>
</div>
<script>
    (function() {

        // Variables locales para este bloque
        var imagenPredeterminada = document.getElementById('imagenPredeterminada');
        var inputImagen = document.getElementById('inputImagen');

        // Clonar elementos para eliminar eventos previos y evitar duplicados
        var nuevoImagenPredeterminada = imagenPredeterminada.cloneNode(true);
        imagenPredeterminada.parentNode.replaceChild(nuevoImagenPredeterminada, imagenPredeterminada);
        imagenPredeterminada = nuevoImagenPredeterminada;

        var nuevoInputImagen = inputImagen.cloneNode(true);
        inputImagen.parentNode.replaceChild(nuevoInputImagen, inputImagen);
        inputImagen = nuevoInputImagen;

        // Eventos

        inputImagen.addEventListener('change', function(event) {
            var archivo = event.target.files[0];

            if (archivo) {
                var nombreArchivo = archivo.name;
                var extension = nombreArchivo.split('.').pop().toLowerCase();

                if (['jpg', 'jpeg', 'png'].includes(extension)) {
                    console.log('Archivo válido: ' + nombreArchivo);
                } else {
                    alert('Solo se permiten archivos JPG, JPEG o PNG.');
                    inputImagen.value = '';
                    imagenPredeterminada.src = '../images/nodisponible.jpg';
                }
            }
        });

        imagenPredeterminada.addEventListener('click', function() {
            inputImagen.click();
        });

        inputImagen.addEventListener('change', function(event) {
            var file = event.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    imagenPredeterminada.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // ✅ Llenar combo de almacenes
        $.getJSON("../ajax/get.catalogoalmacenes.php?tipoalmacen=1,2&todos=17", function(data) {
            let items0 = "";
            $.each(data, function(index, item) {
                items0 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#usucursales").html(items0);
        });

        // ✅ Llenar combo de tipo de usuario
        $.getJSON("../ajax/get.usuarios.tipo.php", function(data) {
            let items1 = "";
            $.each(data, function(index, item) {
                items1 += "<option value='" + item.ID + "'>" + item.NOMBRE + "</option>";
            });
            $("#utipo").html(items1);
        });

        // VALIDAR QUE NO EXISTE EL CONSULTOR CON SU CORREO 
        window.usuariocorreo = function(mail) {
            $('#loading').show();

            $.getJSON("../ajax/usuarios.existecorreo.php?correo=" + mail, function(data) {
                if (data == 1) {
                    alert("Ya existe un correo registrado como " + mail);
                    $("#uusuario").val("");
                }
            }).fail(function() {
                alert("Error al validar el correo.");
            }).always(function() {
                $('#loading').hide();
            });
        };

        // Otras funciones que usas se pueden declarar aquí también
        window.guardar = function() {
            if ($("#uusuario").val() == "") {
                alert("Usuario es un campo obligatorio");
                $("#uusuario").focus();
            } else if ($("#uusuario").val() != '' && validarcorreo($("#uusuario").val()) == 0) {
                alert("Usuario inválido. Proporciona un correo electrónico válido");
                $("#uusuario").focus();
            } else if ($("#unombre").val() == "") {
                alert("Nombre es un campo obligatorio");
                $("#unombre").focus();
            } else if ($("#ualias").val() == "") {
                alert("Alias es un campo obligatorio");
                $("#ualias").focus();
            } else if ($("#urpwd").val() == "") {
                alert("Contraseña es un campo obligatorio");
                $("#urpwd").focus();
            } else if ($("#urpwd2").val() == "") {
                alert("Contraseña es un campo obligatorio");
                $("#urpwd2").focus();
            } else if (validarContrasena($("#urpwd").val()) != true) {
                alert("La contraseña debe contar con:\n - Mínimo 8 y máximo 12 caracteres \n - Al menos una letra mayúscula \n - Al menos un número \n - Al menos un carácter especial");
                $("#urpwd").focus();
            } else if ($("#urpwd").val() != $("#urpwd2").val()) {
                alert("Las contraseñas no coinciden.");
                $("#urpwd2").focus();
            } else {
                var formData = new FormData(document.getElementById("form-usuarios"));
                const file = inputImagen.files[0];
                formData.append('imagen', file);
                $.ajax({
                    url: "../ajax/usuarios.nuevo.php",
                    type: "post",
                    dataType: "html",
                    data: formData,
                    cache: false,
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                        $("#loading").show();
                    },
                    success: function(data) {
                        if (data == "") {
                            alert("Usuario guardado con éxito.");
                            location.reload();
                        } else {
                            alert(data);
                        }
                    },
                    complete: function(data) {
                        $("#loading").hide();
                    }
                });
            }
        };

        window.validarcorreo = function(correos) {
            var bandera = 1;
            var correo = correos.split(",");
            for (var i = 0; i < correo.length; i++) {
                if (!(/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(correo[i]))) {
                    bandera = 0;
                }
            }
            return bandera;
        };

        window.validarContrasena = function(contrasena) {
            var regex = /^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,12}$/;
            return regex.test(contrasena);
        };

        window.togglePassword = function(id, btn) {
            var input = document.getElementById(id);
            var icon = btn.querySelector('i');

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("la-eye-slash");
                icon.classList.add("la-eye");
            } else {
                input.type = "password";
                icon.classList.remove("la-eye");
                icon.classList.add("la-eye-slash");
            }
        };

        window.verificarContrasena = function() {
            var pwd = document.getElementById("urpwd").value;

            var length = pwd.length >= 8 && pwd.length <= 12;
            var mayus = /[A-Z]/.test(pwd);
            var num = /\d/.test(pwd);
            var especial = /[\W_]/.test(pwd);

            document.getElementById("val-length").innerHTML = (length ? "✅" : "❌") + " Mínimo 8 y máximo 12 caracteres";
            document.getElementById("val-mayus").innerHTML = (mayus ? "✅" : "❌") + " Al menos una letra mayúscula";
            document.getElementById("val-num").innerHTML = (num ? "✅" : "❌") + " Al menos un número";
            document.getElementById("val-especial").innerHTML = (especial ? "✅" : "❌") + " Al menos un carácter especial";
        };

        window.compararContrasenas = function() {
            var pwd1 = document.getElementById("urpwd").value;
            var pwd2 = document.getElementById("urpwd2").value;
            var mensaje = document.getElementById("mensajePwd2");

            if (pwd2 && pwd1 !== pwd2) {
                mensaje.style.display = "block";
            } else {
                mensaje.style.display = "none";
            }
        };

    })();

    // Aplica esto a todos tus multiselects
    document.querySelectorAll('select[multiple]').forEach(function(select) {
        select.addEventListener('mousedown', function(e) {
            e.preventDefault(); // Evita la selección por defecto
            let option = e.target;
            if (option.tagName === 'OPTION') {
                option.selected = !option.selected; // Cambia el estado
            }
        });
    });
</script>