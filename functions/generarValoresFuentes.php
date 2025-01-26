<?php
function generarValoresFuentes($mapeos){
    $valores = [];
    $fuentes = [];
    $join_clauses = [];
    foreach ($mapeos as $mapeo) {
        if (isset($mapeo['condicion_relacion'])) {
            $join_clauses[] = "LEFT JOIN {$mapeo['base_datos']}.{$mapeo['tabla']} ON {$mapeo['condicion_relacion']}";
        }
        if (isset($mapeo['valor_manual'])) {
            $valores[] = "'{$mapeo['valor_manual']}'";
        } elseif (isset($mapeo['valor_concatenar'])) {
            $valores[] = "CONCAT({$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}, '{$mapeo['valor_concatenar']}')";
        } elseif (
            strpos($mapeo['campo_destination'], 'date') !== false ||
            strpos($mapeo['campo_destination'], 'time') !== false ||
            strpos($mapeo['campo_destination'], 'created_at') !== false ||
            strpos($mapeo['campo_destination'], 'updated_at') !== false
        ) {
            $campo_euros = "{$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}";
            $valores[] = "IF(LOCATE('-', $campo_euros) = 5, $campo_euros, STR_TO_DATE($campo_euros, '%d-%m-%Y,%H:%i:%s'))";

            $search_string = "{$mapeo['base_datos']}.{$mapeo['tabla']}";

            // $found = false;

            // foreach ($join_clauses as $clause) {
            //     echo "\n\n" . $clause . " != " . $search_string . "\n\n";
            //     if (strpos($clause, $search_string) !== false) {
            //         $found = true;
            //         break;
            //     }
            // }

            // if (!$found) {
                $fuentes[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}";
            // }
        } else {
            $valores[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}";
            $search_string = "{$mapeo['base_datos']}.{$mapeo['tabla']}";

            $found = false;

            foreach ($join_clauses as $clause) {
                echo "\n\n" . $clause . " != " . $search_string . "\n\n";
                if (strpos($clause, $search_string) !== false) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $fuentes[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}";
            }
        }
    }
    return [
        'valores' => $valores, 
        'fuentes' => $fuentes, 
        'join_clauses' => $join_clauses
    ];
}




