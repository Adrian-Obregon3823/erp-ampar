<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
/*
*********************************************************************************
* EVOTEK
* Todos los derechos reservados. 2024
* DESARROLLADOR: MONICA SOFIA RODRIGUEZ GARCIA
* Ajax para cambiar status de remisiones
*********************************************************************************
*/
?>
<?php   
    $remisiones = new remisiones();
    $id = $_POST['id'];
    $status = $_POST['status'];

    // Validación antes de enviar a recepción (status 29) o finalizar (status 3)
    if ($status == 29 || $status == 3) {
        $res = $remisiones->getremisionbyid(null, $id);
        if ($res && count($res) > 0) {
            $fila = $res[0];
            $conceptoRemision = strtoupper(trim((string)($fila['CONCEPTO_REMISION'] ?? '')));
            
            if ($conceptoRemision !== 'VENTA_DIRECTA') {
                $required = [
                    'REMISION_NUMPACIENTE', 
                    'REMISION_RFC', 
                    'REMISION_REPRESENTANTE', 
                    'REMISION_MEDICO2', 
                    'REMISION_ENFERMERIA'
                ];
                $missing = false;
                foreach ($required as $req) {
                    if (!isset($fila[$req]) || trim((string)$fila[$req]) === '') {
                        $missing = true;
                        break;
                    }
                }
                if ($missing) {
                    $accionText = ($status == 29) ? "enviar a recepción" : "finalizar";
                    echo "Error: No se puede {$accionText} la remisión. Debes completar todos los 'Datos Extra' (Paciente, RFC, Firmas, etc.).";
                    exit;
                }
            }

            if ($status == 3) {
                // Validación para finalizar (status 3): debe tener archivo subido
                try {
                    $dbRem = new FirebirdConnection();
                    try { $dbRem->execute("ALTER TABLE AMPAR_HIS_REMISIONES ADD REMISION_ARCHIVO VARCHAR(500)"); } catch (Throwable $t) {}
                    $rr = $dbRem->query("SELECT REMISION_ARCHIVO FROM AMPAR_HIS_REMISIONES WHERE REMISION_ID = {$id}");
                    $archivoRem = trim((string)($rr[0]['REMISION_ARCHIVO'] ?? ''));
                    if ($archivoRem === '') {
                        $evId = (int)($fila['REMISION_EVENTOID'] ?? 0);
                        $reEv = $dbRem->query("SELECT EVENTO_ARCHIVO_REMISION FROM AMPAR_HIS_EVENTOS WHERE EVENTO_ID = {$evId}");
                        $archivoRem = trim((string)($reEv[0]['EVENTO_ARCHIVO_REMISION'] ?? ''));
                    }
                    $dbRem->close();
                    if ($archivoRem === '') {
                        echo "Error: No se puede finalizar la remisión. Primero debes subir la factura de la remisión haciendo clic en el icono de la nube (Subir Factura) en las opciones de la tabla.";
                        exit;
                    }
                } catch (Throwable $e) {}
            }
        }
    }

    $remisiones->updatestatusremisiones($id, $status);
?>