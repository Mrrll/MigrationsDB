<?php
function generarValoresFuentes($mapeos)
{
    $valores = [];
    $fuentes = [];
    $join_clauses = [];
    $fuentes_filtradas = [];
    foreach ($mapeos as $mapeo) {        
        $alias = null;
        echo "\n";
        if (isset($mapeo['base_datos'])) {            
            $crear_alias = obtenerEntradaValida("> ¿Quieres crear un alias principal para la tabla {$mapeo['base_datos']}.{$mapeo['tabla']} que relaciona con el campo {$mapeo['campo']}? (si/no): ", ['si', 'no']);
            if ($crear_alias === 'si') {
                while (true) {
                    echo "> Introduce el alias principal para la tabla {$mapeo['base_datos']}.{$mapeo['tabla']}: ";
                    $alias_principal = trim(fgets(STDIN));
                    $confirmar_alias = obtenerEntradaValida("> ¿Es correcto este alias '$alias_principal'? (si/no): ", ['si', 'no']);
                    if ($confirmar_alias === 'si') {
                        break;
                    }
                }
            }
        }
        if (isset($mapeo['condicion_relacion'])) {
            $tablas_relacionadas = [];
            $campos_relacionados = [];
            if ($crear_alias === 'si') {
                preg_match_all('/(\w+\.\w+)\.\w+/', $mapeo['condicion_relacion'], $matches);
                $tablas_relacionadas[] = $matches[1][0];
                $tablas_relacionadas[] = $matches[1][1];
                preg_match_all('/\w+\.\w+\.(\w+)/', $mapeo['condicion_relacion'], $matches);
                $campos_relacionados[] = $matches[1][0];
                $campos_relacionados[] = $matches[1][1]; 
                             
                foreach ($tablas_relacionadas as $key => $tabla) {                    
                    if ($tabla === "{$mapeo['base_datos']}.{$mapeo['tabla']}") {
                        $condicion = "{$alias_principal}.{$campos_relacionados[$key]}";
                    } else {
                        echo "\n> La tabla $tabla está relacionada con la tabla {$mapeo['base_datos']}.{$mapeo['tabla']} mediante el campo {$campos_relacionados[$key]}\n\n";
                        $crear_alias_tabla_condicion = obtenerEntradaValida("> ¿Quieres crear un alias para la tabla $tabla? (si/no): ", ['si', 'no']);
                        if ($crear_alias_tabla_condicion === 'si') {
                            while (true) {
                                echo "> Introduce el alias para la tabla $tabla: ";
                                $alias = trim(fgets(STDIN));
                                $confirmar_alias_tabla = obtenerEntradaValida("> ¿Es correcto este alias '$alias.{$campos_relacionados[$key]}'? (si/no): ", ['si', 'no']);
                                if ($confirmar_alias_tabla === 'si') {
                                    break;
                                }
                            }
                            $condicion = "{$alias}.{$campos_relacionados[$key]}";
                            $fuentes[] = "$tabla AS $alias";
                        }

                    }
                    $mapeo['condicion_relacion'] = str_replace("{$tabla}.{$campos_relacionados[$key]}", $condicion, $mapeo['condicion_relacion']);
                }
                
                $join_clauses[] = "LEFT JOIN {$mapeo['base_datos']}.{$mapeo['tabla']} AS $alias_principal ON {$mapeo['condicion_relacion']}";
            } else {
                $join_clauses[] = "LEFT JOIN {$mapeo['base_datos']}.{$mapeo['tabla']} ON {$mapeo['condicion_relacion']}";
            }            
        }
        if (isset($mapeo['valor_manual'])) {
            if (isset($mapeo['valor_concatenar'])) {
                $valores[] = "CONCAT('{$mapeo['valor_manual']}', '{$mapeo['valor_concatenar']}')";
            } else {
                $valores[] = "'{$mapeo['valor_manual']}'";
            }
        } elseif (isset($mapeo['valor_concatenar'])) {
            if ($crear_alias === 'si') {
                $valores[] = "CONCAT($alias_principal.{$mapeo['campo']}, '{$mapeo['valor_concatenar']}')";
                $fuentes[] = "{$mapeo['base_datos']}.{$mapeo['tabla']} AS $alias_principal";
            } else {
                $valores[] = "CONCAT({$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}, '{$mapeo['valor_concatenar']}')";
                $fuentes[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}";
            }
        } elseif (
            strpos($mapeo['campo_destination'], 'date') !== false ||
            strpos($mapeo['campo_destination'], 'time') !== false ||
            strpos($mapeo['campo_destination'], 'created_at') !== false ||
            strpos($mapeo['campo_destination'], 'updated_at') !== false
        ) {
            $campo = ($crear_alias === 'si') ? "$alias_principal.{$mapeo['campo']}" : "{$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}";
            $valores[] = "IF(LOCATE('-', $campo) = 5, $campo, STR_TO_DATE($campo, '%d-%m-%Y,%H:%i:%s'))";           

            $fuentes[] = ($crear_alias === 'si') ? "{$mapeo['base_datos']}.{$mapeo['tabla']} AS $alias_principal" : "{$mapeo['base_datos']}.{$mapeo['tabla']}";
        } else {
            // Añadir campos valores desde fuentes
            if ($crear_alias === 'si') {
                 $valores[] = "$alias_principal.{$mapeo['campo']}";
                 $fuentes[] = "{$mapeo['base_datos']}.{$mapeo['tabla']} AS $alias_principal";
            } else {
                $valores[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}";
                $fuentes[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}";
            }            

        }
        $fuentes_filtradas = filtrarFuentes($join_clauses, $fuentes);     
    }
    return [
        'valores' => $valores,
        'fuentes' => $fuentes_filtradas,
        'join_clauses' => array_unique($join_clauses)
    ];
}
