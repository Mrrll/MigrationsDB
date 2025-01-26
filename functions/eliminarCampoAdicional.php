<?php
function eliminarCampoAdicional($pdo_destination, $base_destination, $campos_adicionales) {
    foreach ($campos_adicionales as $campo) {
        $borrar_campo = obtenerEntradaValida("> ¿Deseas borrar el campo adicional {$campo['campo']} de la tabla {$campo['tabla']}? (si/no): ", ['si', 'no']);
        if ($borrar_campo === 'si') {
            $drop_sql = "ALTER TABLE $base_destination.{$campo['tabla']} DROP COLUMN {$campo['campo']};";
            try {
                $pdo_destination->exec($drop_sql);
                echo "\nCampo {$campo['campo']} borrado exitosamente de la tabla {$campo['tabla']}.\n";
            } catch (PDOException $e) {
                echo "\nError al borrar el campo {$campo['campo']} de la tabla {$campo['tabla']}: " . $e->getMessage() . "\n";
            }
        }
    }
}