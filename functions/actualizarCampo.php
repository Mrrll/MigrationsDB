<?php
function actualizarCampo($base, $tabla, $campo, $pdo, $tablas)
{
    echo "> Introduce el nuevo valor para $campo (escribe 'NULL' para valores nulos): ";
    $nuevo_valor = trim(fgets(STDIN));
    $nuevo_valor = strtoupper($nuevo_valor) === 'NULL' ? null : $nuevo_valor;
    
    $where = obtenerEntradaValida("> ¿Deseas agregar una clausura WHERE para la actualización? (si/no): ", ['si', 'no']);

    if ($where === 'si') {        
        echo "\nSelecciona el Campo de la clausura WHERE de la tabla $tabla ....\n";
        $campo_where = seleccionarCampo($tabla, $tablas);
    
        $operador = obtenerEntradaValida("> ¿Que tipo de operador quieres usar para la condición WHERE? (=, !=, >=, <=, >, <, IN, BETWEEN): ", ['=', '!=', '>=', '<=', '>', '<', 'IN', 'BETWEEN']);
       
        if ($operador === 'BETWEEN') {        
            echo "> Introduce el valor de la condición WHERE ($campo_where $operador): ";
            $condicion_between = trim(fgets(STDIN));
            echo "> Introduce el valor de la condición BETWEEN ($campo_where AND): ";
            $condicion_and = trim(fgets(STDIN));
            $update_sql = "UPDATE $base.$tabla SET $campo = :nuevo_valor WHERE $campo_where BETWEEN :condicion_between AND :condicion_and;";
        } elseif ($operador === 'IN') {
            echo "> Introduce los valores de la condición IN (separados por comas): ";
            $condicion_in = trim(fgets(STDIN));
            $condicion_in = "($condicion_in)";
            $update_sql = "UPDATE $base.$tabla SET $campo = :nuevo_valor WHERE $campo_where IN :condicion_in;";
        } else {
            echo "> Introduce el valor de la condición WHERE ($campo_where $operador): ";
            $condicion_where = trim(fgets(STDIN));
            $update_sql = "UPDATE $base.$tabla SET $campo = :nuevo_valor WHERE $campo_where $operador :condicion_where;";
        }
    } else {
        $update_sql = "UPDATE $base.$tabla SET $campo = :nuevo_valor;";
    }


    echo "\nSQL generado:\n$update_sql\n";

    $confirmar = obtenerEntradaValida("> ¿Deseas ejecutar el UPDATE para la tabla $tabla? (si/no): ", ['si', 'no']);

    if ($confirmar === 'si') {
        try {
            $stmt = $pdo->prepare($update_sql);
            $stmt->execute([
                ':nuevo_valor' => $nuevo_valor,
                ':condicion_where' => $condicion_where,
            ]);
            $affected_rows = $stmt->rowCount();
            echo "\nDatos actualizados exitosamente en $tabla. Registros afectados: $affected_rows\n\n";
        } catch (PDOException $e) {
            echo "\nError al ejecutar el UPDATE: " . $e->getMessage() . "\n";
        }
    }
}