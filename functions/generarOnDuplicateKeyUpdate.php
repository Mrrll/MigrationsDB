<?php
function generarOnDuplicateKeyUpdate($pdo_destination, $base_destination, $tabla_destination) {
    // Verificar si la tabla de destino tiene columnas únicas
    $unique_columns = [];
    $result = $pdo_destination->query("SHOW INDEX FROM $base_destination.$tabla_destination WHERE Non_unique = 0");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        if ($row['Column_name'] != 'id') {
            $unique_columns[] = $row['Column_name'];
        }
    }
    // Generar cláusula ON DUPLICATE KEY UPDATE
    $on_duplicate_key_update = '';
    if (!empty($unique_columns)) {
        $updates = [];
        foreach ($unique_columns as $column) {
            $updates[] = "$column = VALUES($column)";
        }
        $on_duplicate_key_update = 'ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    }
    return $on_duplicate_key_update;
}