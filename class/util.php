<?php

class Util {
    public static function formatoFechaEspanol($fecha) {
        // Convertir la fecha en objeto DateTime
        try {
            $date = new DateTime($fecha);
        } catch (Exception $e) {
            return "Fecha inválida";
        }

        // Obtener el día de la semana (Ej: "Sábado")
        $dias = ["domingo", "lunes", "martes", "miércoles", "jueves", "viernes", "sábado"];
        $diaSemana = ucfirst($dias[$date->format('w')]);

        // Obtener el día del mes (Ej: 22)
        $dia = $date->format('j');

        // Obtener el mes en español (Ej: "Febrero")
        $meses = ["", "enero", "febrero", "marzo", "abril", "mayo", "junio", 
                  "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
        $mes = ucfirst($meses[(int)$date->format('n')]);

        // Obtener el año (Ej: 2025)
        $anio = $date->format('Y');

        // Obtener la hora solo si está presente en la fecha
        $hora = '';
        if (strpos($fecha, ':') !== false) {
            $hora = $date->format('g:i a'); // Formato de 12 horas con AM/PM
            $hora = str_replace(['am', 'pm'], ['a.m.', 'p.m.'], $hora); // Ajustar AM/PM
        }

        // Construcción del resultado final
        $resultado = "$diaSemana $dia de $mes de $anio";

        // Agregar la hora si está presente
        if (!empty($hora)) {
            $resultado .= " $hora";
        }

        return $resultado;
    }
}

?>