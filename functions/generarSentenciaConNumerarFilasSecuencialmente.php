<?php
function generarSentenciaConNumerarFilasSecuencialmente($mapeos) {     
    $encontrado = false;
    $ultimo_index = array_key_last($mapeos);
    $sentencia = "SELECT ";
    foreach ($mapeos as $index => $mapeo) {
        if (isset($mapeo['alias'])) {
            if ($index !== $ultimo_index) {                
                $sentencia .= $mapeo['alias'] . "." . $mapeo['campo']. " AS " . $mapeo['campo_destination']. ", ";
            } else {
                $sentencia .= $mapeo['alias'] . "." . $mapeo['campo'] . " AS " . $mapeo['campo_destination'];
            }            
        }
        if (isset($mapeo['valor_manual'])) {
            $valor_manual = $mapeo['valor_manual'];
            if ($index !== $ultimo_index) {                
                $sentencia .= "'$valor_manual'" . " AS " . $mapeo['campo_destination']. ", ";
            } else {
                $sentencia .= "'$valor_manual'" . " AS " . $mapeo['campo_destination'];
            }
        }                
    }
    foreach ($mapeos as $index => $mapeo) {        
        if (isset($mapeo['base_datos'])) {

            if (!$encontrado) {
                $sentencia .= " FROM (SELECT " . $mapeo['campo'] . ", ROW_NUMBER() OVER () AS row_num FROM " . $mapeo['tabla'] . ") AS " . $mapeo['alias'] . " ";
                $encontrado = true;
                continue;
            }
            while (true) {
                echo "\n\nCompleta las opciones para la enumeración secuencial de las filas de la tabla " . $mapeo['tabla'] . "....\n";
                echo "\nIngresa el operador para la clausura WHERE del campo " . $mapeo['campo'] . ": \n";
                $operador = obtenerEntradaValida("> ¿Qué operador deseas utilizar para la clausura WHERE del campo " . $mapeo['campo'] . "? (= /!= /< / > / >= / <= ): ", ['=', '!=', '<', '>', '>=', '<='], true);
                echo "\nIngresa el valor para la clausura WHERE del campo " . $mapeo['campo'] . ": \n";
                $valor = trim(fgets(STDIN));

                $confirmar = obtenerEntradaValida("> ¿Deseas confirmar la clausura WHERE con el operador $operador y el valor $valor? (si/no): ", ['si', 'no']);
                if ($confirmar === 'si') {
                    $sentencia .= "JOIN (SELECT " . $mapeo['campo'] . ", ROW_NUMBER() OVER () AS row_num FROM " . $mapeo['tabla'] . " WHERE " . $mapeo['campo'] . " " . $operador . " " . $valor . ") AS " . $mapeo['alias'] . " ";
                    break;
                }
            }
        }
    }
    while (true) {
        echo "\n\nIngresa el campo de unión entre las tablas: \n";
        $campo_uno = obtenerEntradaValida("> ¿Especificar la condición de unión entre dos tablas selecciona el primer campo? ", array_column($mapeos, 'alias'), true);
        $campo_dos= obtenerEntradaValida("> ¿Especificar la condición de unión entre dos tablas selecciona el segundo campo? ", array_column($mapeos, 'alias'), true);
        $confirmarUnion = obtenerEntradaValida("> ¿Deseas confirmar la unión entre los campos $campo_uno y $campo_dos? (si/no): ", ['si', 'no']);
        if ($confirmarUnion === 'si') {
            $sentencia .= "ON " . $campo_uno . ".row_num = " . $campo_dos. ".row_num;";
            break;
        }
    }
    return $sentencia;
}