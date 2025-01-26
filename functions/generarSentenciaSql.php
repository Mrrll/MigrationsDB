<?php
function generarSentenciaSql($base_destination, $tabla_destination, $campos_dest, $valores, $fuentes, $join_clauses, $on_duplicate_key_update) {
    // Generar sentencia SQL
    $insert_sql = "INSERT INTO $base_destination.$tabla_destination (" . implode(", ", $campos_dest) . ") ";
    $insert_sql .= "SELECT " . implode(", ", $valores);
    if (!empty($fuentes)) {
        $insert_sql .= " FROM " . implode(", ", array_unique($fuentes));
    }
    if (!empty($join_clauses)) {
        $insert_sql .= " ". implode(" ", $join_clauses);
    }
    $insert_sql .= " " . $on_duplicate_key_update . ";";

    echo "\nSQL generado:\n$insert_sql\n";
    return $insert_sql;
}