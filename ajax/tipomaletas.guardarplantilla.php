<?php include_once("../includes/sesion.php");?>
<?php include_once("../includes/includes.php");?>
<?php
    if (empty($_POST['articulos'])) {
        echo "Debes de seleccionar al menos un artículo";
    } else {
        $male = new maletas();

        //Que borre todos los articulos que estan en tipo maleta, consulta primero la info de la maleta
        $infomaleta = $male->getinfotipomaletabyid($_POST['tipomaletaid']);
        if ($infomaleta[0]['TIPOMALETADET_ID'] <> ''){
            foreach ($infomaleta as $im){
                $male->eliminartipomaletadet($im['TIPOMALETADET_ID']);
            }
        }

        $ids = [];
        $cantidades = [];

        foreach ($_POST['articulos'] as $item) {
            $itemaux = json_decode($item);
            if ($itemaux && isset($itemaux->id) && isset($itemaux->cantidad)) {
                $ids[] = $itemaux->id;
                $cantidades[] = $itemaux->cantidad;
            }
        }

        if (!empty($ids) && !empty($cantidades)) {
            // Llama una sola vez con los arrays
            $male->nuevotipomaletadet($_POST['tipomaletaid'], $ids, $cantidades);
        } else {
            echo "No se pudo procesar la información";
        }
    }
?>