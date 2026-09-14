<?php include_once("../includes/sesion.php");?>
<?php require '../vendor/autoload.php'; ?>
<?php include_once("../includes/includes.php");?>
<?php
$proveedores = $_POST['eproveedorid'] ?? [];
$observaciones = $_POST['eobservacionproveedor'] ?? [];
$emaletaid = (isset($_POST['emaletaid']) && is_array($_POST['emaletaid'])) ? $_POST['emaletaid'] : [];
$eequipocapitalid = (isset($_POST['eequipocapitalid']) && is_array($_POST['eequipocapitalid'])) ? $_POST['eequipocapitalid'] : [];

// Convertir equipos capital a IDs negativos para unificarlos con las maletas
foreach ($eequipocapitalid as $ecid) {
    $emaletaid[] = -abs((int)$ecid);
}

if (count($emaletaid) === 0) {
    // Se permite guardar sin maleta ni equipo capital (ambos opcionales)
    $emaletaid = []; // array vacío — nuevoevento acepta array vacío
}

// Se requiere al menos una maleta O equipo capital para registrar el evento:
// Dejamos que nuevoevento decida si es válido.

$eventos = new eventos();
$eventos->nuevoevento(
    $_POST['esucalmacen'],
    $_POST['etipoevento'],
    $_POST['edescripcion'],
    $_POST['tipoCliente'],
    $_POST['eclienteid'],
    null,
    $_POST['presupuesto'],
    $_POST['efechai'],
    $_POST['efechaf'],
    $_POST['elugar'],
    $_POST['emedicor'],
    $_POST['emedicoi'],
    (($_POST['eespecialista']=='')?'NULL':$_POST['eespecialista']),
    (($_POST['echofer']=='')?'NULL':$_POST['echofer']),
    $emaletaid,
    $proveedores,
    $observaciones,
    $_POST['ealmacen'] // <--- ALMACÉN
); 
?>