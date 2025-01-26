<?php
// Función para obtener tablas y campos de una base de datos
function obtenerTablasYCampos($pdo, $base)
{
    $query = $pdo->query("SHOW TABLES FROM $base");
    $tablas = $query->fetchAll(PDO::FETCH_ASSOC);

    $tablas_y_campos = [];
    foreach ($tablas as $tabla) {
        $tabla_name = $tabla["Tables_in_$base"];
        $query = $pdo->query("DESCRIBE $base.$tabla_name");
        $campos = $query->fetchAll(PDO::FETCH_ASSOC);
        $tablas_y_campos[$tabla_name] = [];

        foreach ($campos as $campo) {
            $tablas_y_campos[$tabla_name][] = [
                'Field' => $campo['Field'],
                'Type' => $campo['Type'],
            ];
        }
    }
    return $tablas_y_campos;
}