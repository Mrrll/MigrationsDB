<?php

// ==========================================
// CONFIGURACIÓN Y UTILIDADES
// ==========================================

// Configuración inicial
$base_origin = "euros";
$usuario_origin = "root";
$contraseña_origin = "1234";
$base_destination = "mybilling";
$usuario_destination = "root";
$contraseña_destination = "1234";

// Conexión a las bases de datos
try {
    $pdo_origin = new PDO("mysql:host=localhost;dbname=$base_origin", $usuario_origin, $contraseña_origin);
    $pdo_origin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo_destination = new PDO("mysql:host=localhost;dbname=$base_destination", $usuario_destination, $contraseña_destination);
    $pdo_destination->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "\nConexión exitosa a ambas bases de datos.\n";
} catch (PDOException $e) {
    die("Error al conectar con las bases de datos: " . $e->getMessage() . "\n");
}

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

// Validación del input del usuario con opción de rectificación
function obtenerEntradaValida($prompt, $opciones, $numerar = false, $permitir_rectificar = false)
{
    while (true) {
        if ($numerar) {
            foreach ($opciones as $index => $opcion) {
                echo "  [" . ($index + 1) . "] $opcion\n";
            }
        }
        echo $prompt;
        $entrada = trim(fgets(STDIN));

        if ($permitir_rectificar && strtolower($entrada) === 'rectificar') {
            return 'rectificar';
        }

        if ($numerar && is_numeric($entrada) && isset($opciones[$entrada - 1])) {
            return $opciones[$entrada - 1];
        } elseif (in_array($entrada, $opciones)) {
            return $entrada;
        } else {
            echo "Entrada inválida. Por favor, selecciona una opción válida.\n";
        }
    }
}

// Función para convertir fechas al formato adecuado
function detectarYConvertirFecha($valor)
{
    $formatos_entrada = [
        'd-m-Y H:i:s',
        'd-m-Y,H:i:s',
        'd/m/Y H:i:s',
        'Y-m-d H:i:s',
        'd-m-Y',
        'Y-m-d'
    ];

    foreach ($formatos_entrada as $formato) {
        $fecha = DateTime::createFromFormat($formato, $valor);
        if ($fecha !== false) {
            return $fecha->format('Y-m-d H:i:s');
        }
    }

    return null;
}

// Obtener tablas y campos
$tablas_origin = obtenerTablasYCampos($pdo_origin, $base_origin);
$tablas_destination = obtenerTablasYCampos($pdo_destination, $base_destination);

// ==========================================
// PROCESO INTERACTIVO
// ==========================================

function transformarValores($base_origin, $tablas_origin, $pdo_origin) {
    while (true) {
        echo "\nProceso para transformar valores en la base de datos de origen ($base_origin):\n";
        $transformar = obtenerEntradaValida("> ¿Quieres transformar los valores de algún campo en la base de datos de origen ($base_origin)? (si/no): ", ['si', 'no']);
        if ($transformar !== 'si') {
            break;
        }

        while (true) {
            echo "\nTablas disponibles en la base de datos de origen ($base_origin):\n";
            $tabla_origin = obtenerEntradaValida("> Selecciona una tabla de origen: $base_origin\n", array_keys($tablas_origin), true);
            echo "\nHas seleccionado la tabla: $tabla_origin\n";
            $confirmar_tabla = obtenerEntradaValida("> ¿Es correcta esta tabla? (si/no): ", ['si', 'no']);
            if ($confirmar_tabla === 'si') {
                break;
            }
        }

        while (true) {
            echo "\nCampos disponibles en la tabla $tabla_origin:\n";
            $campos_origin = array_column($tablas_origin[$tabla_origin], 'Field');
            $campo_origin = obtenerEntradaValida("> Selecciona el campo que deseas actualizar:\n", $campos_origin, true);
            echo "\nHas seleccionado el campo: $campo_origin\n";
            $confirmar_campo = obtenerEntradaValida("> ¿Es correcto este campo? (si/no): ", ['si', 'no']);
            if ($confirmar_campo === 'si') {
                break;
            }
        }

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

        $continuar = obtenerEntradaValida("> ¿Deseas realizar otra operación? (si/no): ", ['si', 'no']);
        if ($continuar !== 'si') {
            echo "Proceso finalizado. ¡Hasta luego!\n";
            break;
        }
    }
}

function migrarDatos($base_origin, $base_destination, $tablas_origin, $tablas_destination, $pdo_origin, $pdo_destination) {
    $sentencias_sql = [];

    while (true) {
        echo "\nProceso para migrar de la base de datos de origen ($base_origin) a la base de datos de destino ($base_destination):\n";
        echo "\nTablas disponibles en la base de datos destino ($base_destination):\n";

        while (true) {
            $tabla_destination = obtenerEntradaValida("> Selecciona una tabla de destino: $base_destination\n", array_keys($tablas_destination), true);
            echo "\nHas seleccionado la tabla: $tabla_destination\n";
            $confirmar_tabla = obtenerEntradaValida("> ¿Es correcta esta tabla? (si/no): ", ['si', 'no']);
            if ($confirmar_tabla === 'si') {
                break;
            }
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

            while (true) {
                echo "\nTablas disponibles en $base_datos:\n";
                $tabla_fuente = obtenerEntradaValida("> Selecciona la tabla fuente para $campo_dest_name:\n", array_keys($tablas_fuente), true);
                echo "\nHas seleccionado la tabla: $tabla_fuente\n";
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

            $relacionar_condicion = obtenerEntradaValida("> ¿Quieres agregar una condición de relación para este campo $campo_dest_name? (si/no): ", ['si', 'no']);
            if ($relacionar_condicion === 'si') {
                echo "\nPara la condición de relación, selecciona el primer campo:\n";

                $base_datos_origen = obtenerEntradaValida("> ¿En qué base de datos se encuentra la tabla para el primer campo? ($base_origin/$base_destination): ", [$base_origin, $base_destination]);

                $tablas_origen = ($base_datos_origen === $base_origin) ? $tablas_origin : $tablas_destination;

                echo "\nTablas disponibles en $base_datos_origen:\n";
                $tabla_origen = obtenerEntradaValida("> Selecciona la tabla para el primer campo:\n", array_keys($tablas_origen), true);

                echo "\nCampos disponibles en la tabla $tabla_origen:\n";
                $campos_origen = array_column($tablas_origen[$tabla_origen], 'Field');
                $campo_origen = obtenerEntradaValida("> Selecciona el primer campo:\n", $campos_origen, true);

                echo "\nAhora selecciona el segundo campo:\n";

                $base_datos_destino = obtenerEntradaValida("> ¿En qué base de datos se encuentra la tabla para el segundo campo? ($base_origin/$base_destination): ", [$base_origin, $base_destination]);

                $tablas_destino = ($base_datos_destino === $base_origin) ? $tablas_origin : $tablas_destination;

                echo "\nTablas disponibles en $base_datos_destino:\n";
                $tabla_destino = obtenerEntradaValida("> Selecciona la tabla para el segundo campo:\n", array_keys($tablas_destino), true);

                echo "\nCampos disponibles en la tabla $tabla_destino:\n";
                $campos_destino = array_column($tablas_destino[$tabla_destino], 'Field');
                $campo_destino = obtenerEntradaValida("> Selecciona el segundo campo:\n", $campos_destino, true);

                $condicion_relacion = "$base_datos_origen.$tabla_origen.$campo_origen = $base_datos_destino.$tabla_destino.$campo_destino";

                $mapeo['condicion_relacion'] = $condicion_relacion;

                echo "Condición de relación agregada: $condicion_relacion\n";
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
                echo "  - {$mapeo['campo_destination']} ← Valor manual: {$mapeo['valor_manual']}\n";
            } else {
                echo "  - {$mapeo['campo_destination']} ← {$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}\n";
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

            $insert_sql = "INSERT IGNORE INTO $base_destination.$tabla_destination (" . implode(", ", $campos_dest) . ") ";
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
        } catch (PDOException $e) {
            echo "\nError al ejecutar las sentencias SQL: " . $e->getMessage() . "\n";
        }
    }

    echo "Proceso finalizado. ¡Hasta luego!\n";
}

transformarValores($base_origin, $tablas_origin, $pdo_origin);
migrarDatos($base_origin, $base_destination, $tablas_origin, $tablas_destination, $pdo_origin, $pdo_destination);
