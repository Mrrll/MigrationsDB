<?php
function generarSentenciaSql($base_destination, $tabla_destination, $campos_dest, $valores, $fuentes, $join_clauses, $on_duplicate_key_update, $mapeos, $secuencial, $base_origin,$tablas_origin, $tablas_destination) {
    // Generar sentencia SQL
    $insert_sql = 'SET FOREIGN_KEY_CHECKS = 0; ';
    $insert_sql .= "INSERT INTO $base_destination.$tabla_destination (" . implode(", ", $campos_dest) . ") ";
    if ($secuencial === 'si') {
        $insert_sql .= generarSentenciaConNumerarFilasSecuencialmente($mapeos,$base_origin, $tablas_origin, $tablas_destination);
    } else {
        $fuentes_filtradas = []; 
        $insert_sql .= "SELECT " . implode(", ", $valores);
        // Filtrar las tablas que ya están en los join_clauses        
        if (!empty($join_clauses)) {            
            // Extraer todas las tablas unidas de los join_clauses
            $tablas_unidas = [];
            foreach ($join_clauses as $join) {
                if (preg_match('/JOIN\s+([a-zA-Z0-9\._]+)/i', $join, $matches)) {
                    $tablas_unidas[] = $matches[1];
                }
            }
    
            $fuentes_filtradas = array_values(array_filter($fuentes, function ($fuente) use ($tablas_unidas) {
                return !in_array($fuente, $tablas_unidas);
            }));
        }
        
        if (!empty($fuentes_filtradas)) {
            $insert_sql .= " FROM " . implode(", ", array_unique($fuentes_filtradas));
        } elseif (!empty($fuentes)) {
            $insert_sql .= " FROM " . implode(", ", array_unique($fuentes));
        }
        if (!empty($join_clauses)) {
            $insert_sql .= " ". implode(" ", $join_clauses);
        }
        $insert_sql .= " " . $on_duplicate_key_update . ";";
    }
    $insert_sql .= ' SET FOREIGN_KEY_CHECKS = 1;';    
    return $insert_sql;
}