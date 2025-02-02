<?php
function generarSentenciaSql($base_destination, $tabla_destination, $campos_dest, $valores, $fuentes, $join_clauses, $on_duplicate_key_update, $mapeos, $secuencial) {
    // Generar sentencia SQL
    $insert_sql = 'SET FOREIGN_KEY_CHECKS = 0; ';
    $insert_sql .= "INSERT INTO $base_destination.$tabla_destination (" . implode(", ", $campos_dest) . ") ";
    if ($secuencial === 'si') {
        $insert_sql .= generarSentenciaConNumerarFilasSecuencialmente($mapeos);
    } else {       
        $insert_sql .= "SELECT " . implode(", ", $valores);
        if (!empty($fuentes)) {
            $insert_sql .= " FROM " . implode(", ", array_unique($fuentes));
        }
        if (!empty($join_clauses)) {
            $insert_sql .= " ". implode(" ", $join_clauses);
        }
        $insert_sql .= " " . $on_duplicate_key_update . ";";
    }
    $insert_sql .= ' SET FOREIGN_KEY_CHECKS = 1;';
    echo "\nSQL generado:\n$insert_sql\n";
    return $insert_sql;
}