<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2025
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Clase Mail
*********************************************************************************
*/
?>
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class mail{

	function enviar($correo,$namecorreo,$titulo,$asunto,$adjuntos=''){
		$correo = 'rdzgarciamonica@gmail.com';
		try {
			$correo = "rdzgarciamonica@gmail.com";
			$mail = new PHPMailer(true);
			$mail->isSMTP();
			$mail->Host = $GLOBALS['correo_host'];
			$mail->Port = '587';
			$mail->SMTPSecure = 'tls';
			$mail->SMTPAuth   = true;
			$mail->Username = $GLOBALS['correo_usuario'];
			$mail->Password = $GLOBALS['correo_pwd'];
			$mail->From = $GLOBALS['correo_usuario'];
			$mail->FromName = $GLOBALS['correo_nombre'];

			$mail->addAddress($correo, $namecorreo);
			//$mail->SMTPDebug  = 2;
			//$mail->Debugoutput = function($str, $level) {echo "debug level $level; message: $str";}; //$mail->Debugoutput = 'echo';
			$mail->IsHTML(true);
			$mail->CharSet = 'UTF-8';
			$mail->Subject = $titulo;
			$mail->Body    = '<img src="https://evotek.com.mx/logo.png" width="400px"><br>'.$asunto.$GLOBALS['global_site'].'img/logo.png';
			if ($adjuntos <> ''){
				$archivo = explode(",", $adjuntos);
				foreach ($archivo as $a){
					if (file_exists($a)){
						$mail->AddAttachment($a);
					}
				}
			}

			// Asumiendo que $mail es el objeto PHPMailer
			if (!$mail->send()) {
				// Si el envío del correo falla, lanzamos una excepción
				throw new Exception('Algo ha salido mal. ' . $mail->ErrorInfo);
			}
		} catch (Exception $e) {
			// Captura la excepción y registra el error silenciosamente
			error_log('Error SMTP (mail.php): ' . $e->getMessage());
		}
	}


}
?>
