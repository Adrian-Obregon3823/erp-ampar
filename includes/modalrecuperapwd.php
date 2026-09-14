<!-- Modal -->
<div class="modal fade" id="modalrecuperapwd" tabindex="-1" role="dialog" aria-labelledby="modalrecuperapwd" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h6 class="modal-title">Recupera tu contraseña </h6>
            </div>
            <div id="modaldivchange" class="modal-body">									
                <form id="miformpwd">
                    <div class="form-group">
                        <label for="rpwdcorreo">Captura tu usuario</label>
                        <input type="text" class="form-control input-square" id="rpwdcorreo" name="rpwdcorreo" placeholder="Correo electrónico">
                        <div id="textoprovisional">
                            <br><br><p style="font-weight: bold;">Se enviará un código a tu correo electrónico.</p>
                        </div>
                    </div>
                    <div class="form-group" id="divcodigo" style="display:none;">
                        <label for="rpwdcodigo">Código</label>
                        <input type="text" class="form-control input-square" id="rpwdcodigo" name="rpwdcodigo" placeholder="Captura código">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button id="btnrecuperar" type="button" class="btn btn-success" onclick="recuperarpwd()">Recuperar contraseña</button>
                <button id="btncambiar" type="button" class="btn btn-warning" onclick="cambiarpwd()" style="display:none;">Cambiar Contraseña</button>
                <button id="btnsalir" type="button" class="btn btn-secondary" data-dismiss="modal">Salir</button>
            </div>
        </div>
    </div>
</div>
<script>

    function recuperarpwd(){
        if ($("#rpwdcorreo").val() == ""){
            alert ("Captura tu usuario de correo electrónico");
            $("#rpwdcorreo").focus();
        }else if ($("#rpwdcorreo").val() != '' && validarcorreo($("#rpwdcorreo").val()) == 0){
            alert ("Proporciona un correo electrónico válido");
            $("#rpwdcorreo").focus();
        }else{
            $.ajax({
                url: "../ajax/login.recuperarpwd.php",
                type: "post",
                dataType: "html",
                data: {
                    'correo': $("#rpwdcorreo").val()
                },
                beforeSend: function() {
                    $("#loading").show();
                },
                success: function(data) {
                    if (data == ""){
                        alert ("Escribe el código que se te envió por correo para recuperar tu contraseña.")
                        //Mostrar input para capturar token enviado
                        $('#textoprovisional').hide();
                        $('#btnrecuperar').hide();
                        $('#btnsalir').hide();
                        $('#divcodigo').show();
                        $('#btncambiar').show();
                    }else{
                        alert (data);
                    }
                },
                complete: function(data) {
                    $("#loading").hide();
                }
            });
        }
        
    }

    function cambiarpwd(){
        if ($("#rpwdcorreo").val() == ""){
            alert ("Captura tu usuario de correo electrónico");
            $("#rpwdcorreo").focus();
        }else if ($("#rpwdcorreo").val() != '' && validarcorreo($("#rpwdcorreo").val()) == 0){
            alert ("Proporciona un correo electrónico válido");
            $("#rpwdcorreo").focus();
        }else if ($("#rpwdcodigo").val() == ""){
            alert ("Captura el código que se envió a tu correo");
            $("#rpwdcodigo").focus();
        }else{
            //validar código
            $.ajax({
                url: "../ajax/changepwd.validar.php",
                type: "post",
                dataType: "html",
                data: {
                    "correo": $("#rpwdcorreo").val(),
                    "codigo": $("#rpwdcodigo").val()
                },
                beforeSend: function() {
                    $("#loading").show();
                },
                success: function(data) {
                    if (data > 0){
                        $('#btncambiar').hide();
                        var html = '';
                        html += '<form id="miformpwdcpform">';
                            html += '<input type="hidden" id="correopwdcp" name="correopwdcp" value="'+$("#rpwdcorreo").val()+'">';
                            html += '<div class="form-group">';
                                html += '<label for="pwdcp1">Captura una contraseña</label>';
                                html += '<input type="password" class="form-control input-square" id="pwdcp1" name="pwdcp1">';
                            html += '</div>';
                            html += '<div class="form-group">';
                                html += '<label for="pwdcp2">Captura la contraseña</label>';
                                html += '<input type="password" class="form-control input-square" id="pwdcp2" name="pwdcp2">';
                            html += '</div>';
                            html += '<button id="btncpaux" type="button" class="btn btn-success" onclick="updatepwd()">Cambiar contraseña</button>';
                        html += '</form>';
                        
                        $("#modaldivchange").html(html);
                    }else{
                        alert ("No se encontró código");
                    }
                },
                complete: function(data) {
                    $("#loading").hide();
                }
            });
        }
    }
    
    function updatepwd(){
        if ($("#pwdcp1").val() == ""){
            alert ("Contraseña es un campo obligatorio");
            $("#pwdcp1").focus();
        }else if ($("#pwdcp2").val() == ""){
            alert ("Contraseña es un campo obligatorio");
            $("#pwdcp2").focus();
        }else if (validarContrasena($("#pwdcp1").val()) != true){
            alert ("La contraseña debe contar con:\n - Mínimo 8 y máximo 12 caracteres \n - Al menos una letra mayúscula \n - Al menos un número \n - Al menos un carácter especial");
            $("#pwdcp1").focus();
        }else if ($("#pwdcp1").val() != $("#pwdcp2").val()){
            alert ("Las contraseñas no coinciden");
            $("#pwdcp2").focus();
        }else{
            //update contraseña
            $.ajax({
                url: "../ajax/changepwd.update.php",
                type: "post",
                dataType: "html",
                data: {
                    "correo": $("#correopwdcp").val(),
                    "pwdcp1": $("#pwdcp1").val(),
                    "pwdcp2": $("#pwdcp2").val()
                },
                beforeSend: function() {
                    $("#loading").show();
                },
                success: function(data) {
                    if (data == ""){
                        alert ("Contraseña modificada con éxito");
                        location.reload();
                    }else{
                        alert ("Error:"+data);
                    }
                },
                complete: function(data) {
                    $("#loading").hide();
                }
            });
        }
    }
    
    function validarContrasena(contrasena) {
        const regex = /^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,12}$/;
        return regex.test(contrasena);
    }
</script>