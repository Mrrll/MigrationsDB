<?php
// Solicitar datos de conexión a las bases de datos.
echo "Introduce el nombre de la base de datos de origen: ";
$base_origin = trim(fgets(STDIN));
echo "Introduce para la base de datos de origen ($base_origin) el usuario: ";
$usuario_origin = trim(fgets(STDIN));
echo "Introduce para la base de datos de origen ($base_origin) la contraseña: ";
$contraseña_origin = trim(fgets(STDIN));
echo "Introduce el nombre de la base de datos de destino: ";
$base_destination = trim(fgets(STDIN));
echo "Introduce para la base de datos de destino ($base_destination) el usuario: ";
$usuario_destination = trim(fgets(STDIN));
echo "Introduce para la base de datos de destino ($base_destination) la contraseña: ";
$contraseña_destination = trim(fgets(STDIN));

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

// Obtener tablas y campos
$tablas_destination = obtenerTablasYCampos($pdo_destination, $base_destination);
$tablas_origin = obtenerTablasYCampos($pdo_origin, $base_origin);

// Mostrar las tablas de destino
function mostrarTablasDisponibles($tablas)
{
    echo "Tablas disponibles en destination:\n";
    foreach (array_keys($tablas) as $tabla) {
        echo "  - $tabla\n";
    }
}

// Iniciar el proceso interactivo
while (true) {
    mostrarTablasDisponibles($tablas_destination);

    echo "\n¿Qué tabla del destino quieres mapear? (Escribe 'salir' para finalizar): ";
    $tabla_destination = trim(fgets(STDIN));

    if ($tabla_destination === 'salir') {
        echo "Proceso finalizado. ¡Hasta luego!\n";
        break;
    }

    if (!array_key_exists($tabla_destination, $tablas_destination)) {
        echo "La tabla $tabla_destination no existe en la base destino. Intenta de nuevo.\n";
        continue;
    }

    echo "Campos disponibles en la tabla $tabla_destination de la base destino:\n";
    foreach ($tablas_destination[$tabla_destination] as $campo) {
        echo "  - {$campo['Field']} ({$campo['Type']})\n";
    }

    $mapeos = []; // Guardará los mapeos para esta tabla

    foreach ($tablas_destination[$tabla_destination] as $campo_destination) {
        $campo_dest_name = $campo_destination['Field'];
        $campo_dest_type = $campo_destination['Type'];

        echo "\n¿Quieres establecer manualmente el valor para $campo_dest_name? (si/no): ";
        $valor_manual = trim(fgets(STDIN));

        if (strtolower($valor_manual) === 'si') {
            echo "Introduce el valor manual para $campo_dest_name: ";
            $valor = trim(fgets(STDIN));
            $mapeos[] = [
                'campo_destination' => $campo_dest_name,
                'valor_manual' => $valor,
            ];
            echo "Valor manual establecido: $campo_dest_name ← '$valor'.\n";
            continue;
        }

        echo "¿En qué base de datos quieres buscar el dato para $campo_dest_name? ($base_origin/$base_destination): ";
        $base_datos = trim(fgets(STDIN));

        if ($base_datos !== $base_origin && $base_datos !== $base_destination) {
            echo "Base de datos no válida. Campo $campo_dest_name omitido.\n";
            continue;
        }

        $tablas = ($base_datos === $base_origin) ? $tablas_origin : $tablas_destination;
        echo "Tablas disponibles en $base_datos:\n";
        foreach (array_keys($tablas) as $tabla) {
            echo "  - $tabla\n";
        }

        echo "\n¿En qué tabla de $base_datos se encuentra el dato para $campo_dest_name? (Presiona 'Enter' para omitir este campo): ";
        $tabla = trim(fgets(STDIN));

        if ($tabla === '') {
            echo "Campo $campo_dest_name omitido.\n";
            continue;
        }

        if (!array_key_exists($tabla, $tablas)) {
            echo "La tabla $tabla no existe en $base_datos. Intenta de nuevo.\n";
            continue;
        }

        echo "Campos disponibles en la tabla $tabla de $base_datos:\n";
        foreach ($tablas[$tabla] as $campo) {
            echo "  - {$campo['Field']}\n";
        }

        echo "\n¿Qué campo de $tabla quieres usar como fuente para $campo_dest_name? (Presiona 'Enter' para omitir este campo): ";
        $campo = trim(fgets(STDIN));

        if ($campo === '') {
            echo "Campo $campo_dest_name omitido.\n";
            continue;
        }

        $mapeo = [
            'campo_destination' => $campo_dest_name,
            'tabla' => $tabla,
            'campo' => $campo,
            'base_datos' => $base_datos,
        ];

        if (strpos($campo_dest_type, 'timestamp') !== false || strpos($campo_dest_type, 'datetime') !== false) {
            echo "El campo $campo_dest_name es de tipo TIMESTAMP o DATETIME. Verificando formato y convirtiendo si es necesario.\n";

            // Recuperar valores del campo fuente para analizar formato
            $query = $pdo_origin->prepare("SELECT `$campo` FROM `$tabla`");
            $query->execute();
            $valores_origen = $query->fetchAll(PDO::FETCH_COLUMN);

            $valores_convertidos = [];
            foreach ($valores_origen as $valor_origen) {
                // Intentar convertir el formato de fecha
                $fecha = DateTime::createFromFormat('d-m-Y,H:i:s', $valor_origen); // Formato original con coma
                if (!$fecha) {
                    // Si no funciona con la coma, probar con el formato sin coma
                    $valor_origen = str_replace(' ', ',', $valor_origen);  // Reemplazar espacios por coma si no está presente
                    $fecha = DateTime::createFromFormat('d-m-Y,H:i:s', $valor_origen);
                }

                if (!$fecha) {
                    $fecha = DateTime::createFromFormat('d-m-Y H:i:s', $valor_origen); // Formato sin coma
                }

                if ($fecha) {
                    $valores_convertidos[] = $fecha->format('Y-m-d H:i:s');
                } else {
                    $valores_convertidos[] = '0000-00-00 00:00:00'; // Valor predeterminado en caso de error.
                }
            }

            // Agregar los valores convertidos al mapeo
            $mapeo['valores_convertidos'] = $valores_convertidos;
        }

        $mapeos[] = $mapeo;
        echo "Mapeo establecido: $tabla_destination.$campo_dest_name ← $base_datos.$tabla.$campo\n";
    }

    // Si hay mapeos, generar el INSERT
    if (!empty($mapeos)) {
        echo "\n¿Quieres generar el INSERT para la tabla $tabla_destination ahora? (si/no): ";
        $respuesta = trim(fgets(STDIN));

        if (strtolower($respuesta) === 'si') {
            $campos_destination = [];
            $valores = [];
            $fuentes = [];

            foreach ($mapeos as $mapeo) {
                $campos_destination[] = $mapeo['campo_destination'];

                if (isset($mapeo['valor_manual'])) {
                    // Caso 1: Valor manual proporcionado por el usuario
                    $valores[] = "'{$mapeo['valor_manual']}'";
                } elseif (
                    isset($mapeo['valores_convertidos']) &&
                    isset($mapeo['base_datos']) &&
                    isset($mapeo['tabla']) &&
                    isset($mapeo['campo'])
                ) {
                    // Caso 2: Campo de tipo DATETIME/TIMESTAMP con conversión
                    $valores[] = "'{$mapeo['valores_convertidos'][0]}'"; // Usar el primer valor convertido
                    $fuentes[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}";

                    // Depuración para mostrar la consulta SQL generada
                    echo "SQL generado para $campo_dest_name: '{$mapeo['valores_convertidos'][0]}'\n";
                } elseif (
                    isset($mapeo['base_datos']) &&
                    isset($mapeo['tabla']) &&
                    isset($mapeo['campo'])
                ) {
                    // Caso 3: Campo directo
                    $valores[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}";
                    $fuentes[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}";
                } else {
                    // Caso 4: Campo no definido, usar fecha predeterminada
                    $valores[] = "'0000-00-00 00:00:00'";
                    echo "Advertencia: El campo {$mapeo['campo_destination']} no tiene una fuente definida. Se usará un valor predeterminado.\n";
                }
            }

            if (empty($fuentes)) {
                echo "Error: No se encontraron tablas de origen válidas.\n";
                continue;
            }

            // Generar SQL del INSERT
            $insert_sql = "INSERT INTO $base_destination.$tabla_destination (" . implode(", ", $campos_destination) . ") ";
            $insert_sql .= "SELECT " . implode(", ", $valores) . " FROM " . implode(", ", array_unique($fuentes)) . ";";

            echo "Ejecutando el siguiente SQL:\n$insert_sql\n";

            try {
                // Desactivar las restricciones de claves foráneas
                $pdo_destination->exec("SET foreign_key_checks = 0;");
                // Ejecutar el INSERT
                $pdo_destination->exec($insert_sql);
                // Restaurar las restricciones de claves foráneas
                $pdo_destination->exec("SET foreign_key_checks = 1;");
                echo "Datos insertados exitosamente en la tabla $tabla_destination.\n";
            } catch (PDOException $e) {
                echo "Error al ejecutar el INSERT: " . $e->getMessage() . "\n";
            }
        }
    }
    echo "\n¿Quieres continuar con otra tabla? (si/no): ";
    $continuar = trim(fgets(STDIN));

    if (strtolower($continuar) !== 'si') {
        echo "Proceso finalizado. ¡Hasta luego!\n";
        break;
    }
}
