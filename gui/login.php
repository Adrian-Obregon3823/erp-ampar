<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* INTERFAZ DE LOGIN
*********************************************************************************
*/
?>
<!DOCTYPE HTML>
<html>
	<head>
		<?php include_once("../includes/head.php"); ?>
		<style>
			html, body {
				margin: 0;
				padding: 0;
				height: 100%;
				font-family: 'Arial', sans-serif;
			}

			body {
				background: url('../img/bg1.jpg') no-repeat center center fixed;
				background-size: cover;
				display: flex;
				justify-content: center;
				align-items: center;
			}

			.login-wrapper {
				background-color: rgba(255, 255, 255, 0.88); /* fondo blanco con transparencia */
				padding: 40px 30px;
				border-radius: 10px;
				box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
				width: 100%;
				max-width: 400px;
			}

			.login-wrapper img {
				display: block;
				margin: 0 auto 20px;
				max-width: 160px;
			}

			.login-form h1 {
				font-size: 24px;
				text-align: center;
				margin-bottom: 20px;
				color: #333;
			}

			.form-group label {
				font-weight: bold;
				font-size: 14px;
			}

			.form-control {
				border-radius: 5px;
				padding: 10px;
				font-size: 14px;
			}

			.btn-primary {
				background-color: #007bff;
				border: none;
			}

			.btn-primary:hover {
				background-color: #0056b3;
			}

			.forgot-link {
				font-size: 0.75rem;
				display: inline-block;
				margin-top: 5px;
			}

			@media (max-width: 500px) {
				.login-wrapper {
					padding: 30px 20px;
					margin: 20px;
				}

				.login-form h1 {
					font-size: 20px;
				}
			}
			
			.password-container {
				position: relative;
				display: flex;
				align-items: center;
			}
			.password-container input {
				padding-right: 45px;
			}
			.password-container .toggle-password {
				position: absolute;
				right: 12px;
				top: 50%;
				transform: translateY(-50%);
				cursor: pointer;
				color: #adb5bd;
				font-size: 20px;
				transition: color 0.2s ease;
			}
			.password-container .toggle-password:hover {
				color: #495057;
			}
		</style>
	</head>
	<body>

		<div class="login-wrapper">
			<img src="../img/logo.png" alt="Logo">
			<form id="form_login" class="login-form">
				<div class="form-group">
					<label for="ulusuario">Usuario</label>
					<input type="text" class="form-control" id="ulusuario" name="ulusuario" placeholder="Correo electrónico">
				</div>
				<div class="form-group">
					<label for="ulpwd">Contraseña</label>
					<div class="password-container">
						<input type="password" class="form-control w-100" id="ulpwd" name="ulpwd" placeholder="********">
						<i class="mdi mdi-eye-outline toggle-password" id="togglePassword"></i>
					</div>
					<a href="#" data-toggle="modal" data-target="#modalrecuperapwd" class="forgot-link">He olvidado mi contraseña</a>
				</div>
				<br>
				<div class="form-group">
					<button type="button" class="btn btn-success w-100" onclick="login();">Iniciar Sesión</button>
				</div>
			</form>
		</div>

		<div id="loading" style="display:none">
			<img id="loading-image" src="../img/logo.png"/>
		</div>

		<?php include_once("../includes/foot.php"); ?>
		<?php include_once("../includes/modalrecuperapwd.php") ?>

		<script>
			$(document).ready(function() {
				$('#ulusuario, #ulpwd').keypress(function(e) {
					if (e.which == 13) {
						login();
						return false;
					}
				});

				$('#togglePassword').click(function() {
					const type = $('#ulpwd').attr('type') === 'password' ? 'text' : 'password';
					$('#ulpwd').attr('type', type);
					$(this).toggleClass('mdi-eye-outline mdi-eye-off-outline');
				});
			});

			function login(){
				if ($("#ulusuario").val() == ""){
					alert ("Usuario es un campo obligatorio");
					$("#ulusuario").focus();
				} else if ($("#ulusuario").val() != '' && validarcorreo($("#ulusuario").val()) == 0){
					alert ("Usuario inválido. Proporciona un correo electrónico como usuario");
					$("#ulusuario").focus();
				} else if ($("#ulpwd").val() == ""){
					alert ("Contraseña es un campo obligatorio");
					$("#ulpwd").focus();
				} else {
					$.ajax({
						url: "../ajax/login.php",
						type: "post",
						dataType: "html",
						data: {
							"ulusuario" : $("#ulusuario").val(),
							"ulpwd" : $("#ulpwd").val()
						},
						beforeSend: function() {
							$("#loading").show();
						},
						success: function(data) {
							if (data == "success"){
								window.location.href = '../gui/dashboard.php';
							} else {
								alert(data);
							}
						},
						complete: function(data) {
							$("#loading").hide();
						}
					});
				}
			}

			function validarcorreo(correos){
				var bandera = 1;
				var correo = correos.split(",");
				for (i = 0; i < correo.length; i++){
					if (!(/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(correo[i]))){
						bandera = 0;
					}
				}
				return bandera;
			}
		</script>

	</body>
</html>