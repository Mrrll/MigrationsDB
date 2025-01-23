<?php
// ==========================================
// PROCESO INTERACTIVO
// ==========================================
function migrarDatos($base_origin, $base_destination, $tablas_origin, $tablas_destination, $pdo_origin, $pdo_destination)
{
    $sentencias_sql = [];
    $campos_adicionales = [];
    $ejecutar_sentencias = false;

    while (true) {
        echo "\nProceso para migrar de la base de datos de origen ($base_origin) a la base de datos de destino ($base_destination):\n";
        echo "\nTablas disponibles en la base de datos destino ($base_destination):\n";

        while (true) {
            $tabla_destination = obtenerEntradaValida("> Selecciona una tabla de destino: $base_destination\n", array_keys($tablas_destination), true);
            echo "\nHas seleccionado la tabla: $tabla_destination\n";
            mostrarCampos($pdo_destination, $tabla_destination);
            $confirmar_tabla = obtenerEntradaValida("> ¿Es correcta esta tabla? (si/no): ", ['si', 'no']);
            if ($confirmar_tabla === 'si') {
                break;
            }
        }

        $añadir_campo = obtenerEntradaValida("> ¿Quieres añadir un campo adicional a la tabla $tabla_destination? (si/no): ", ['si', 'no']);
        if ($añadir_campo === 'si') {
            echo "> Introduce el nombre del campo adicional: ";
            $nombre_campo_adicional = trim(fgets(STDIN));
            echo "> Introduce el tipo del campo adicional: ";
            $tipo_campo_adicional = trim(fgets(STDIN));

            $alter_sql = "ALTER TABLE $base_destination.$tabla_destination ADD $nombre_campo_adicional $tipo_campo_adicional;";
            $sentencias_sql[] = $alter_sql;
            $campos_adicionales[] = [
                'tabla' => $tabla_destination,
                'campo' => $nombre_campo_adicional
            ];

            echo "\nSQL generado para añadir el campo:\n$alter_sql\n";
        }

        echo "\nCampos disponibles en la tabla $tabla_destination:\n";
        $mapeos = [];

        foreach ($tablas_destination[$tabla_destination] as $campo_dest) {
            $campo_dest_name = $campo_dest['Field'];
            $campo_dest_type = $campo_dest['Type'];

            $omitir = obtenerEntradaValida("> ¿Deseas omitir el campo $campo_dest_name? (si/no) [no]: ", ['si', 'no']);
            if ($omitir === 'si') {
                echo "Campo $campo_dest_name omitido.\n";
                echo "\nSiguiente campo....\n\n";
                continue;
            }

            $manual = obtenerEntradaValida("> ¿Quieres establecer un valor manual para $campo_dest_name? (si/no): ", ['si', 'no']);
            if ($manual === 'si') {
                echo "> Introduce el valor manual para $campo_dest_name: ";
                $valor_manual = trim(fgets(STDIN));
                $mapeos[] = [
                    'campo_destination' => $campo_dest_name,
                    'valor_manual' => $valor_manual
                ];

                $concatenar = obtenerEntradaValida("> ¿Quieres concatenar un valor a este campo $campo_dest_name? (si/no): ", ['si', 'no']);
                if ($concatenar === 'si') {
                    echo "> Introduce el valor a concatenar: ";
                    $valor_concatenar = trim(fgets(STDIN));
                    $mapeos[count($mapeos) - 1]['valor_concatenar'] = $valor_concatenar;
                }

                $relacionar_condicion = obtenerEntradaValida("> ¿Quieres agregar una condición de relación para este campo $campo_dest_name? (si/no): ", ['si', 'no']);
                if ($relacionar_condicion === 'si') {
                    echo "\nPara la condición de relación, selecciona el primer campo:\n";

                    while (true) {
                        $base_datos_origen = obtenerEntradaValida("> ¿En qué base de datos se encuentra la tabla para el primer campo? ($base_origin/$base_destination): ", [$base_origin, $base_destination], false, true);
                        if ($base_datos_origen === 'rectificar') {
                            continue;
                        }
                        echo "\nHas seleccionado la base de datos: $base_datos_origen\n";
                        $confirmar_base_origen = obtenerEntradaValida("> ¿Es correcta esta base de datos? (si/no): ", ['si', 'no']);
                        if ($confirmar_base_origen === 'si') {
                            break;
                        }
                    }

                    $tablas_origen = ($base_datos_origen === $base_origin) ? $tablas_origin : $tablas_destination;
                    $pdo = ($base_datos_origen === $base_origin) ? $pdo_origin : $pdo_destination;

                    while (true) {
                        echo "\nTablas disponibles en $base_datos_origen:\n";
                        $tabla_origen = obtenerEntradaValida("> Selecciona la tabla para el primer campo:\n", array_keys($tablas_origen), true);
                        echo "\nHas seleccionado la tabla: $tabla_origen\n";
                        mostrarCampos($pdo, $tabla_origen);
                        $confirmar_tabla_origen = obtenerEntradaValida("> ¿Es correcta esta tabla? (si/no): ", ['si', 'no']);
                        if ($confirmar_tabla_origen === 'si') {
                            break;
                        }
                    }

                    while (true) {
                        echo "\nCampos disponibles en la tabla $tabla_origen:\n";
                        $campos_origen = array_column($tablas_origen[$tabla_origen], 'Field');
                        $campo_origen = obtenerEntradaValida("> Selecciona el primer campo:\n", $campos_origen, true);
                        echo "\nHas seleccionado el campo: $campo_origen\n";
                        $confirmar_campo_origen = obtenerEntradaValida("> ¿Es correcto este campo? (si/no): ", ['si', 'no']);
                        if ($confirmar_campo_origen === 'si') {
                            break;
                        }
                    }

                    echo "\nAhora selecciona el segundo campo:\n";

                    while (true) {
                        $base_datos_destino = obtenerEntradaValida("> ¿En qué base de datos se encuentra la tabla para el segundo campo? ($base_origin/$base_destination): ", [$base_origin, $base_destination], false, true);
                        if ($base_datos_destino === 'rectificar') {
                            continue;
                        }
                        echo "\nHas seleccionado la base de datos: $base_datos_destino\n";
                        $confirmar_base_destino = obtenerEntradaValida("> ¿Es correcta esta base de datos? (si/no): ", ['si', 'no']);
                        if ($confirmar_base_destino === 'si') {
                            break;
                        }
                    }

                    $tablas_destino = ($base_datos_destino === $base_origin) ? $tablas_origin : $tablas_destination;                    
                    $pdo = ($base_datos_destino === $base_origin) ? $pdo_origin : $pdo_destination;

                    while (true) {
                        echo "\nTablas disponibles en $base_datos_destino:\n";
                        $tabla_destino = obtenerEntradaValida("> Selecciona la tabla para el segundo campo:\n", array_keys($tablas_destino), true);
                        echo "\nHas seleccionado la tabla: $tabla_destino\n";
                        mostrarCampos($pdo, $tabla_destino);
                        $confirmar_tabla_destino = obtenerEntradaValida("> ¿Es correcta esta tabla? (si/no): ", ['si', 'no']);
                        if ($confirmar_tabla_destino === 'si') {
                            break;
                        }
                    }

                    while (true) {
                        echo "\nCampos disponibles en la tabla $tabla_destino:\n";
                        $campos_destino = array_column($tablas_destino[$tabla_destino], 'Field');
                        $campo_destino = obtenerEntradaValida("> Selecciona el segundo campo:\n", $campos_destino, true);
                        echo "\nHas seleccionado el campo: $campo_destino\n";
                        $confirmar_campo_destino = obtenerEntradaValida("> ¿Es correcto este campo? (si/no): ", ['si', 'no']);
                        if ($confirmar_campo_destino === 'si') {
                            break;
                        }
                    }

                    $condicion_relacion = "$base_datos_origen.$tabla_origen.$campo_origen = $base_datos_destino.$tabla_destino.$campo_destino";

                    $mapeo['condicion_relacion'] = $condicion_relacion;

                    echo "Condición de relación agregada: $condicion_relacion\n";

                    $concatenar_relacion = obtenerEntradaValida("> ¿Quieres concatenar un valor a este campo $campo_dest_name en la condición de relación? (si/no): ", ['si', 'no']);
                    if ($concatenar_relacion === 'si') {
                        echo "> Introduce el valor a concatenar: ";
                        $valor_concatenar_relacion = trim(fgets(STDIN));
                        $mapeo['valor_concatenar_relacion'] = $valor_concatenar_relacion;
                    }
                }

                echo "\nSiguiente campo....\n\n";
                continue;
            }

            while (true) {
                $base_datos = obtenerEntradaValida("> ¿En qué base de datos se encuentra el dato para $campo_dest_name? ($base_origin/$base_destination): ", [$base_origin, $base_destination]);
                echo "\nHas seleccionado la base de datos: $base_datos\n";
                $confirmar_base = obtenerEntradaValida("> ¿Es correcta esta base de datos? (si/no): ", ['si', 'no']);
                if ($confirmar_base === 'si') {
                    break;
                }
            }

            $tablas_fuente = ($base_datos === $base_origin) ? $tablas_origin : $tablas_destination;
            $pdo = ($base_datos === $base_origin) ? $pdo_origin : $pdo_destination;

            while (true) {
                echo "\nTablas disponibles en $base_datos:\n";
                $tabla_fuente = obtenerEntradaValida("> Selecciona la tabla fuente para $campo_dest_name:\n", array_keys($tablas_fuente), true);
                echo "\nHas seleccionado la tabla: $tabla_fuente\n";
                mostrarCampos($pdo, $tabla_fuente);
                $confirmar_tabla_fuente = obtenerEntradaValida("> ¿Es correcta esta tabla? (si/no): ", ['si', 'no']);
                if ($confirmar_tabla_fuente === 'si') {
                    break;
                }
            }

            while (true) {
                echo "\nCampos disponibles en la tabla $tabla_fuente:\n";
                $campo_fuente = obtenerEntradaValida("> Selecciona el campo fuente para $campo_dest_name:\n", array_column($tablas_fuente[$tabla_fuente], 'Field'), true);
                echo "\nHas seleccionado el campo: $campo_fuente\n";
                $confirmar_campo_fuente = obtenerEntradaValida("> ¿Es correcto este campo? (si/no): ", ['si', 'no']);
                if ($confirmar_campo_fuente === 'si') {
                    break;
                }
            }

            $mapeo = [
                'campo_destination' => $campo_dest_name,
                'tabla' => $tabla_fuente,
                'campo' => $campo_fuente,
                'base_datos' => $base_datos
            ];

            $concatenar = obtenerEntradaValida("> ¿Quieres concatenar un valor a este campo $campo_dest_name? (si/no): ", ['si', 'no']);
            if ($concatenar === 'si') {
                echo "> Introduce el valor a concatenar: ";
                $valor_concatenar = trim(fgets(STDIN));
                $mapeo['valor_concatenar'] = $valor_concatenar;
            }

            $relacionar_condicion = obtenerEntradaValida("> ¿Quieres agregar una condición de relación para este campo $campo_dest_name? (si/no): ", ['si', 'no']);
            if ($relacionar_condicion === 'si') {
                echo "\nPara la condición de relación, selecciona el primer campo:\n";

                while (true) {
                    $base_datos_origen = obtenerEntradaValida("> ¿En qué base de datos se encuentra la tabla para el primer campo? ($base_origin/$base_destination): ", [$base_origin, $base_destination], false, true);
                    if ($base_datos_origen === 'rectificar') {
                        continue;
                    }
                    echo "\nHas seleccionado la base de datos: $base_datos_origen\n";
                    $confirmar_base_origen = obtenerEntradaValida("> ¿Es correcta esta base de datos? (si/no): ", ['si', 'no']);
                    if ($confirmar_base_origen === 'si') {
                        break;
                    }
                }

                $tablas_origen = ($base_datos_origen === $base_origin) ? $tablas_origin : $tablas_destination;
                $pdo = ($base_datos_origen === $base_origin) ? $pdo_origin : $pdo_destination;

                while (true) {
                    echo "\nTablas disponibles en $base_datos_origen:\n";
                    $tabla_origen = obtenerEntradaValida("> Selecciona la tabla para el primer campo:\n", array_keys($tablas_origen), true);
                    echo "\nHas seleccionado la tabla: $tabla_origen\n";
                    mostrarCampos($pdo, $tabla_origen);
                    $confirmar_tabla_origen = obtenerEntradaValida("> ¿Es correcta esta tabla? (si/no): ", ['si', 'no']);
                    if ($confirmar_tabla_origen === 'si') {
                        break;
                    }
                }

                while (true) {
                    echo "\nCampos disponibles en la tabla $tabla_origen:\n";
                    $campos_origen = array_column($tablas_origen[$tabla_origen], 'Field');
                    $campo_origen = obtenerEntradaValida("> Selecciona el primer campo:\n", $campos_origen, true);
                    echo "\nHas seleccionado el campo: $campo_origen\n";
                    $confirmar_campo_origen = obtenerEntradaValida("> ¿Es correcto este campo? (si/no): ", ['si', 'no']);
                    if ($confirmar_campo_origen === 'si') {
                        break;
                    }
                }

                echo "\nAhora selecciona el segundo campo:\n";

                while (true) {
                    $base_datos_destino = obtenerEntradaValida("> ¿En qué base de datos se encuentra la tabla para el segundo campo? ($base_origin/$base_destination): ", [$base_origin, $base_destination], false, true);
                    if ($base_datos_destino === 'rectificar') {
                        continue;
                    }
                    echo "\nHas seleccionado la base de datos: $base_datos_destino\n";
                    $confirmar_base_destino = obtenerEntradaValida("> ¿Es correcta esta base de datos? (si/no): ", ['si', 'no']);
                    if ($confirmar_base_destino === 'si') {
                        break;
                    }
                }

                $tablas_destino = ($base_datos_destino === $base_origin) ? $tablas_origin : $tablas_destination;
                $pdo = ($base_datos_destino === $base_origin) ? $pdo_origin : $pdo_destination;                
                while (true) {
                    echo "\nTablas disponibles en $base_datos_destino:\n";
                    $tabla_destino = obtenerEntradaValida("> Selecciona la tabla para el segundo campo:\n", array_keys($tablas_destino), true);
                    echo "\nHas seleccionado la tabla: $tabla_destino\n";
                    mostrarCampos($pdo, $tabla_destino);
                    $confirmar_tabla_destino = obtenerEntradaValida("> ¿Es correcta esta tabla? (si/no): ", ['si', 'no']);
                    if ($confirmar_tabla_destino === 'si') {
                        break;
                    }
                }

                while (true) {
                    echo "\nCampos disponibles en la tabla $tabla_destino:\n";
                    $campos_destino = array_column($tablas_destino[$tabla_destino], 'Field');
                    $campo_destino = obtenerEntradaValida("> Selecciona el segundo campo:\n", $campos_destino, true);
                    echo "\nHas seleccionado el campo: $campo_destino\n";
                    $confirmar_campo_destino = obtenerEntradaValida("> ¿Es correcto este campo? (si/no): ", ['si', 'no']);
                    if ($confirmar_campo_destino === 'si') {
                        break;
                    }
                }

                $condicion_relacion = "$base_datos_origen.$tabla_origen.$campo_origen = $base_datos_destino.$tabla_destino.$campo_destino";

                $mapeo['condicion_relacion'] = $condicion_relacion;

                echo "Condición de relación agregada: $condicion_relacion\n";

                $concatenar_relacion = obtenerEntradaValida("> ¿Quieres concatenar un valor a este campo $campo_dest_name en la condición de relación? (si/no): ", ['si', 'no']);
                if ($concatenar_relacion === 'si') {
                    echo "> Introduce el valor a concatenar: ";
                    $valor_concatenar_relacion = trim(fgets(STDIN));
                    $mapeo['valor_concatenar_relacion'] = $valor_concatenar_relacion;
                }
            }

            if (strpos($campo_dest_type, 'timestamp') !== false || strpos($campo_dest_type, 'datetime') !== false) {
                echo "El campo $campo_dest_name es de tipo TIMESTAMP o DATETIME. Verificando formato y convirtiendo si es necesario.\n";

                $query = $pdo_origin->prepare("SELECT `$campo_fuente` FROM `$tabla_fuente`");
                $query->execute();
                $valores_origen = $query->fetchAll(PDO::FETCH_COLUMN);

                $valores_convertidos = [];
                foreach ($valores_origen as $valor_origen) {
                    $fecha_convertida = detectarYConvertirFecha($valor_origen);
                    if ($fecha_convertida === null) {
                        echo "Advertencia: No se pudo convertir la fecha '$valor_origen'. Usando NULL.\n";
                        $valores_convertidos[] = 'NULL';
                    } else {
                        $valores_convertidos[] = "'$fecha_convertida'";
                    }
                }
                $valores[] = implode(", ", $valores_convertidos);
            } else {
                $valores[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}";
            }
            echo "\nSiguiente campo....\n\n";
            $mapeos[] = $mapeo;
        }

        echo "\nResumen de mapeos para la tabla $tabla_destination:\n";
        foreach ($mapeos as $mapeo) {
            if (isset($mapeo['valor_manual'])) {
                echo " - {$mapeo['campo_destination']} ← Valor manual: {$mapeo['valor_manual']}\n";
            } elseif (isset($mapeo['valor_concatenar'])) {
                echo " - {$mapeo['campo_destination']} ← CONCAT({$mapeo['campo_destination']}, '{$mapeo['valor_concatenar']}')\n";
            } else {
                echo " - {$mapeo['campo_destination']} ← {$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}\n";
            }
        }

        $validar = obtenerEntradaValida("> ¿Deseas ver la sentencia para $tabla_destination? (si/no): ", ['si', 'no']);
        if ($validar === 'si') {
            $campos_dest = array_column($mapeos, 'campo_destination');
            $valores = [];
            $fuentes = [];
            $join_clauses = [];

            foreach ($mapeos as $mapeo) {
                if (isset($mapeo['condicion_relacion'])) {
                    $join_clauses[] = "LEFT JOIN {$mapeo['base_datos']}.{$mapeo['tabla']} ON {$mapeo['condicion_relacion']}";
                }
                if (isset($mapeo['valor_manual'])) {
                    $valores[] = "'{$mapeo['valor_manual']}'";
                } elseif (isset($mapeo['valor_concatenar'])) {
                    $valores[] = "CONCAT({$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}, '{$mapeo['valor_concatenar']}')";
                } elseif (
                    strpos($mapeo['campo_destination'], 'date') !== false ||
                    strpos($mapeo['campo_destination'], 'time') !== false ||
                    strpos($mapeo['campo_destination'], 'created_at') !== false ||
                    strpos($mapeo['campo_destination'], 'updated_at') !== false
                ) {
                    $campo_euros = "{$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}";
                    $valores[] = "IF(LOCATE('-', $campo_euros) = 5, $campo_euros, STR_TO_DATE($campo_euros, '%d-%m-%Y,%H:%i:%s'))";

                    $search_string = "{$mapeo['base_datos']}.{$mapeo['tabla']}";

                    $found = false;

                    foreach ($join_clauses as $clause) {
                        if (strpos($clause, $search_string) !== false) {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        $fuentes[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}";
                    }
                } else {
                    $valores[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}";
                    $search_string = "{$mapeo['base_datos']}.{$mapeo['tabla']}";

                    $found = false;

                    foreach ($join_clauses as $clause) {
                        if (strpos($clause, $search_string) !== false) {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        $fuentes[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}";
                    }
                }
            }
            if (empty($fuentes)) {
                echo "\nError: No se encontraron tablas de origen válidas.\n";
                continue;
            }

            $unique_columns = [];
            $result = $pdo_destination->query("SHOW INDEX FROM $base_destination.$tabla_destination WHERE Non_unique = 0");
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                if ($row['Column_name'] != 'id') {
                    $unique_columns[] = $row['Column_name'];
                }
            }

            $on_duplicate_key_update = '';
            if (!empty($unique_columns)) {
                $updates = [];
                foreach ($unique_columns as $column) {
                    $updates[] = "$column = VALUES($column)";
                }
                $on_duplicate_key_update = 'ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
            }

            $insert_sql = "INSERT INTO $base_destination.$tabla_destination (" . implode(", ", $campos_dest) . ") ";
            $insert_sql .= "SELECT " . implode(", ", $valores) . " FROM " . implode(", ", array_unique($fuentes)) . " ";

            if (!empty($join_clauses)) {
                $insert_sql .= implode(" ", $join_clauses);
            }
            $insert_sql .= " " . $on_duplicate_key_update . ";";

            echo "\nSQL generado:\n$insert_sql\n";
        }

        $sentencias_sql[] = $insert_sql;

        $anidar = obtenerEntradaValida("> ¿Deseas anidar otra sentencia SQL antes de ejecutar? (si/no): ", ['si', 'no']);
        if ($anidar === 'no') {
            break;
        }
    }

    echo "\nSentencias SQL generadas:\n";
    foreach ($sentencias_sql as $sql) {
        echo "$sql\n";
    }

    $confirmar = obtenerEntradaValida("> ¿Deseas ejecutar las sentencias SQL generadas? (si/no): ", ['si', 'no']);
    if ($confirmar === 'si') {
        try {
            $pdo_destination->exec("SET foreign_key_checks = 0;");
            foreach ($sentencias_sql as $sql) {
                $pdo_destination->exec($sql);
            }
            $pdo_destination->exec("SET foreign_key_checks = 1;");
            echo "\nDatos insertados exitosamente.\n";
            $ejecutar_sentencias = true;
        } catch (PDOException $e) {
            echo "\nError al ejecutar las sentencias SQL: " . $e->getMessage() . "\n";
        }
    }

    if ($ejecutar_sentencias) {
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

    echo "Proceso finalizado. ¡Hasta luego!\n";
}
