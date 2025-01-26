<?php
// Mostrar campos de una tabla
function mostrarCampos($pdo, $tabla)
{
    try {
        echo "Obteniendo campos de la tabla $tabla...\n";
        $query = $pdo->query("DESCRIBE $tabla");
        $campos = $query->fetchAll(PDO::FETCH_ASSOC);

        echo "Campos de la tabla $tabla:\n";
        foreach ($campos as $campo) {
            echo "- " . $campo['Field'] . " (" . $campo['Type'] . ")\n";
        }
        echo "\n";
    } catch (PDOException $e) {
        echo "Error al obtener los campos de la tabla $tabla: " . $e->getMessage() . "\n";
    }
}