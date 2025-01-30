<?php
function actualizarCampo($base_origin, $tabla_origin, $campo_origin, $pdo_origin)
{
    echo "> Introduce el nuevo valor para $campo_origin (escribe 'NULL' para valores nulos): ";
    $nuevo_valor = trim(fgets(STDIN));
    $nuevo_valor = strtoupper($nuevo_valor) === 'NULL' ? null : $nuevo_valor;

    echo "> Introduce el valor de la condición WHERE ($campo_origin =): ";
    $condicion_where = trim(fgets(STDIN));

    $update_sql = "UPDATE $base_origin.$tabla_origin SET $campo_origin = :nuevo_valor WHERE $campo_origin = :condicion_where";

    echo "\nSQL generado:\n$update_sql\n";

    $confirmar = obtenerEntradaValida("> ¿Deseas ejecutar el UPDATE para la tabla $tabla_origin? (si/no): ", ['si', 'no']);

    if ($confirmar === 'si') {
        try {
            $stmt = $pdo_origin->prepare($update_sql);
            $stmt->execute([
                ':nuevo_valor' => $nuevo_valor,
                ':condicion_where' => $condicion_where,
            ]);
            $affected_rows = $stmt->rowCount();
            echo "\nDatos actualizados exitosamente en $tabla_origin. Registros afectados: $affected_rows\n\n";
        } catch (PDOException $e) {
            echo "\nError al ejecutar el UPDATE: " . $e->getMessage() . "\n";
        }
    }
}