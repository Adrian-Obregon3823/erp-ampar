<?php
/**
 * Demo: dos trabajos en un mismo script, cada uno forzando lenguaje con PJL.
 *  - Job ZGL: imprime "ZGL OK"
 *  - Job PGL: imprime "PGL OK" y lee EPC -> lo devuelve por socket con HOSTOUT e imprime "EPC: <...>"
 */

$ip   = "192.168.1.79";
$port = 9100;
$timeoutSec = 3.0;

// Usa el SFCC activo en tu impresora (126 "~" recomendado)
$SFCC = '~';

// ---- Utilidades de envío ----
function sendRaw9100($ip,$port,$raw,$tout=3.0){
  $s=@fsockopen($ip,$port,$e,$es,3.0);
  if(!$s) throw new RuntimeException("Socket $ip:$port $e $es");
  stream_set_timeout($s,(int)$tout,(int)(($tout-(int)$tout)*1e6));
  // Limpia residuos previos (si los hubiera)
  stream_get_contents($s);
  fwrite($s,$raw);
  $resp=''; $t0=microtime(true);
  do{
    $c=fread($s,8192);
    if($c!==false && $c!==''){ $resp.=$c; } else { usleep(120000); }
  } while(microtime(true)-$t0<$tout);
  fclose($s);
  return $resp;
}

// ---- Envoltura PJL ----
function pjlWrap($language, $payload){
  // UEL = ESC%-12345X
  $UEL = "\x1B%-12345X";
  // IMPORTANTE: En Printronix, PGL entra como IGP; ZGL entra como ZGL
  return $UEL."@PJL ENTER LANGUAGE=".$language."\r\n"
       .$payload
       .$UEL;
}

// ---- Jobs ----
function zglJob(){
  // ZPL/ZGL mínimo
  return "^XA^FO60,60^ADN,36,20^FDZGL OK^FS^XZ";
}

function pglHelloAndReadEpc($sfcc, $alsoPrint=true){
  // PGL mínimo + lectura EPC con HOSTOUT
  $CRLF = "\r\n";
  $pgl  =  $sfcc."EMULATION;P".$CRLF;            // por si el parser quedó en otra emulación
  $pgl .=  $sfcc."CREATE;HELLO;1".$CRLF;
  $pgl .= "SCALE;DOT;203;203".$CRLF."ALPHA".$CRLF;
  $pgl .= "#C17;50;120;0;0;*PGL OK*".$CRLF."STOP".$CRLF."END".$CRLF;
  $pgl .=  $sfcc."EXECUTE;HELLO".$CRLF;

  // Lectura EPC (usa EPC->DF1 y lo envía al host)
  $pgl .=  $sfcc."CREATE;READ_EPC;1".$CRLF;
  $pgl .= "SCALE;DOT;203;203".$CRLF;
  $pgl .= "RFRTAG;64;EPC".$CRLF;  // lee banco EPC a buffer 64
  $pgl .= "64;DF1;H".$CRLF."STOP".$CRLF;
  $pgl .= "HOSTOUT;*EPC:<DF1>\\r\\n*".$CRLF; // devuelve por socket
  if ($alsoPrint){
    $pgl .= "ALPHA".$CRLF."#C17;40;80;0;0;*EPC: <DF1>*".$CRLF."STOP".$CRLF;
  }
  $pgl .= "END".$CRLF;
  $pgl .=  $sfcc."EXECUTE;READ_EPC".$CRLF;

  return $pgl;
}

// ---- Envío secuencial: primero ZGL, luego PGL ----
try {
  // 1) ZGL forzado por PJL
  $jobZGL = pjlWrap("ZGL", zglJob());
  $respZ  = sendRaw9100($ip,$port,$jobZGL,$timeoutSec);

  // 2) PGL (IGP) forzado por PJL
  $jobPGL = pjlWrap("IGP", pglHelloAndReadEpc($SFCC, true));
  $respP  = sendRaw9100($ip,$port,$jobPGL,$timeoutSec);

  // Intenta extraer el EPC del retorno por socket
  $epc = null;
  if (preg_match('/EPC:([0-9A-F]{8,})/i', $respP, $m)) {
    $epc = strtoupper($m[1]);
  }

  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    "zgl_sent"     => true,
    "zgl_resp_len" => strlen($respZ),
    "pgl_sent"     => true,
    "pgl_resp_len" => strlen($respP),
    "epc_hex"      => $epc,
    "hint" => "Debe imprimir primero 'ZGL OK' y luego 'PGL OK' + 'EPC: <...>'. Si no sale 'PGL OK', revisa que Control PJL=Activar y Normal de PGL=Normal. Si EPC sale vacío en papel, calibra RFID, ajusta alineación/potencia."
  ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(["error"=>$e->getMessage()], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
}
