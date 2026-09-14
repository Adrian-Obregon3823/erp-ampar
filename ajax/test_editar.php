<?php
$_POST['articulo_id'] = 14389; // We will just run the include and see if it fails.
// Let's get a real ID.
require_once("../includes/sesion.php");
require_once("../includes/includes.php");
$db = new FirebirdConnection();
$res = $db->query("SELECT FIRST 1 ARTICULO_ID, NOMBRE, LINEA_ARTICULO_ID FROM ARTICULOS");
$id = $res[0]['ARTICULO_ID'];
$nombre = $res[0]['NOMBRE'];

$res_linea = $db->query("SELECT FIRST 1 LINEA_ARTICULO_ID FROM LINEAS_ARTICULOS");
$categoria_id = $res_linea[0]['LINEA_ARTICULO_ID'];
echo "Testing with ID: $id, Nombre: $nombre, Categoria: $categoria_id\n";

$sql = "UPDATE ARTICULOS SET NOMBRE = ?, LINEA_ARTICULO_ID = ? WHERE ARTICULO_ID = ?";
$success = $db->execute($sql, [$nombre . " Edit", $categoria_id, $id]);

if (!$success) {
    echo "Execute failed.\n";
} else {
    echo "Execute success!\n";
}

$db->close();
?>
