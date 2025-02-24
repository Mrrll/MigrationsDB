<?php
function transformarCampo($base_origin, $tabla_origin, $campo_origin, $pdo_origin)
{
    $opcion_transformar = obtenerEntradaValida("> Selecciona la transformación que deseas aplicar:\n", ['minusculas', 'mayusculas', 'capitalizacion', 'decimales'], true);
    $query = $pdo_origin->query("SELECT $campo_origin FROM $base_origin.$tabla_origin");
    $valores = $query->fetchAll(PDO::FETCH_COLUMN);

    $total_registros_modificados = 0;
    if ($opcion_transformar === 'decimales') {
        while (true) {
            echo "\n> Introduce el número de decimales a truncar: ";
            $numero_decimales = trim(fgets(STDIN));
            
            if (!is_numeric($numero_decimales) && $numero_decimales < 0) {
                break;
            }
            echo "\nPASO.....\n";
            $update_sql = "UPDATE $base_origin.$tabla_origin SET $campo_origin = TRUNCATE($campo_origin, $numero_decimales)";
            // Mostrar sentencias SQL generadas
            echo "\nSentencias SQL generadas:\n";        
            echo "$update_sql\n\n";
            
            $confirmar = obtenerEntradaValida("> ¿Deseas ejecutar la sentencia SQL generada? (si/no): ", ['si', 'no']);
            if ($confirmar !== 'si') {
                echo "\nProceso de transformación finalizado.\n";
                return;
            }
            try {
                $stmt = $pdo_origin->prepare($update_sql);
                $stmt->execute();
                $total_registros_modificados = $stmt->rowCount();
                break;
            } catch (PDOException $e) {
                echo "\nError al ejecutar la transformación: " . $e->getMessage() . "\n";
                break;
            }
        }
    } else {
        foreach ($valores as $valor) {
            switch ($opcion_transformar) {
                case 'minusculas':
                    $nuevo_valor = strtolower($valor);
                    break;
                case 'mayusculas':
                    $nuevo_valor = strtoupper($valor);
                    break;
                case 'capitalizacion':
                    $nuevo_valor = ucwords(strtolower($valor));
                    break;
            }
    
            $update_sql = "UPDATE $base_origin.$tabla_origin SET $campo_origin = :nuevo_valor WHERE $campo_origin = :valor";
            try {
                $stmt = $pdo_origin->prepare($update_sql);
                $stmt->execute([
                    ':nuevo_valor' => $nuevo_valor,
                    ':valor' => $valor,
                ]);
                $total_registros_modificados += $stmt->rowCount();
            } catch (PDOException $e) {
                echo "\nError al ejecutar la transformación: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\nDatos transformados exitosamente en $tabla_origin. Registros afectados: $total_registros_modificados\n\n";
}