<?php
// Función para verificar si una fuente está en los joins
function fuenteEnJoins($fuente, $joins)
{
    foreach ($joins as $join) {
        if (strpos($join, $fuente) !== false) {
            return true;
        }
    }
    return false;
}
function filtrarFuentes($joins, $fuentes)
{

    // Filtrar las fuentes eliminando las que están en los joins
    $fuentes_filtradas = array_filter($fuentes, function ($fuente) use ($joins) {
        return !fuenteEnJoins($fuente, $joins);
    });

    // Reindexar y ordenar el array resultante
    $fuentes_filtradas = array_values($fuentes_filtradas);
    sort($fuentes_filtradas);
    return $fuentes_filtradas;
}
