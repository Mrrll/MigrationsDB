<?php
// Solicitar datos de conexión a las bases de datos.
// echo "Introduce el nombre de la base de datos de origen: ";
// $base_origin = trim(fgets(STDIN));
// echo "Introduce para la base de datos de origen ($base_origin) el usuario: ";
// $usuario_origin = trim(fgets(STDIN));
// echo "Introduce para la base de datos de origen ($base_origin) la contraseña: ";
// $contraseña_origin = trim(fgets(STDIN));
// echo "Introduce el nombre de la base de datos de destino: ";
// $base_destination = trim(fgets(STDIN));
// echo "Introduce para la base de datos de destino ($base_destination) el usuario: ";
// $usuario_destination = trim(fgets(STDIN));
// echo "Introduce para la base de datos de destino ($base_destination) la contraseña: ";
// $contraseña_destination = trim(fgets(STDIN));

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
function obtenerTablasYCampos($pdo, $base) {
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

// Validación del input del usuario
function obtenerEntradaValida($prompt, $opciones, $numerar = false) {
    while (true) {        
        if ($numerar) {
            foreach ($opciones as $index => $opcion) {
                echo "  [" . ($index + 1) . "] $opcion\n";
            }
        }
        echo $prompt;
        $entrada = trim(fgets(STDIN));

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
    // Formatos de entrada posibles
    $formatos_entrada = [
        'd-m-Y H:i:s',
        'd-m-Y,H:i:s',
        'd/m/Y H:i:s',
        'Y-m-d H:i:s',
        'd-m-Y',
        'Y-m-d'
    ];

    // Intentar detectar el formato
    foreach ($formatos_entrada as $formato) {
        $fecha = DateTime::createFromFormat($formato, $valor);
        if ($fecha !== false) {
            // Retornar la fecha en formato estándar SQL
            return $fecha->format('Y-m-d H:i:s');
        }
    }

    // Si no coincide, retornar un valor predeterminado
    return null;
}

// Obtener tablas y campos
$tablas_origin = obtenerTablasYCampos($pdo_origin, $base_origin);
$tablas_destination = obtenerTablasYCampos($pdo_destination, $base_destination);

// ==========================================
// PROCESO INTERACTIVO
// ==========================================

while (true) {
    echo "\nTablas disponibles en la base de datos destino ($base_destination):\n";
    $tabla_destination = obtenerEntradaValida("> Selecciona una tabla de destino: $base_destination\n", array_keys($tablas_destination), true);    
    echo "\nCampos disponibles en la tabla $tabla_destination:\n";
    $mapeos = [];

    foreach ($tablas_destination[$tabla_destination] as $campo_dest) {
        $campo_dest_name = $campo_dest['Field'];
        $campo_dest_type = $campo_dest['Type'];

        // Preguntar si se omite el campo
        $omitir = obtenerEntradaValida("> ¿Deseas omitir el campo $campo_dest_name? (si/no) [no]: ", ['si', 'no']);
        if ($omitir === 'si') {
            echo "Campo $campo_dest_name omitido.\n";
            continue;
        }

        // Preguntar si se asigna un valor manual
        $manual = obtenerEntradaValida("> ¿Quieres establecer un valor manual para $campo_dest_name? (si/no): ", ['si', 'no']);
        if ($manual === 'si') {
            echo "> Introduce el valor manual para $campo_dest_name: ";
            $valor_manual = trim(fgets(STDIN));
            $mapeos[] = [
                'campo_destination' => $campo_dest_name,
                'valor_manual' => $valor_manual
            ];
            continue;
        }

        // Seleccionar base de datos y tabla fuente
        $base_datos = obtenerEntradaValida("> ¿En qué base de datos se encuentra el dato para $campo_dest_name? ($base_origin/$base_destination): ", [$base_origin, $base_destination]);
        $tablas_fuente = ($base_datos === $base_origin) ? $tablas_origin : $tablas_destination;

        echo "\nTablas disponibles en $base_datos:\n";
        $tabla_fuente = obtenerEntradaValida("> Selecciona la tabla fuente para $campo_dest_name:\n", array_keys($tablas_fuente), true);

        echo "\nCampos disponibles en la tabla $tabla_fuente:\n";
        $campo_fuente = obtenerEntradaValida("> Selecciona el campo fuente para $campo_dest_name:\n", array_column($tablas_fuente[$tabla_fuente], 'Field'), true);

        $mapeo = [
            'campo_destination' => $campo_dest_name,
            'tabla' => $tabla_fuente,
            'campo' => $campo_fuente,
            'base_datos' => $base_datos
        ];

        // Validar y convertir formato de fechas si es necesario
        if (strpos($campo_dest_type, 'timestamp') !== false || strpos($campo_dest_type, 'datetime') !== false) {
            echo "El campo $campo_dest_name es de tipo TIMESTAMP o DATETIME. Verificando formato y convirtiendo si es necesario.\n";

            // Recuperar valores del campo fuente
            $query = $pdo_origin->prepare("SELECT `$campo_fuente` FROM `$tabla_fuente`");
            $query->execute();
            $valores_origen = $query->fetchAll(PDO::FETCH_COLUMN);

            $valores_convertidos = [];
            foreach ($valores_origen as $valor_origen) {
                // Intentar convertir el formato de fecha en PHP
                $fecha_convertida = detectarYConvertirFecha($valor_origen);
                if ($fecha_convertida === null) {
                    echo "Advertencia: No se pudo convertir la fecha '$valor_origen'. Usando NULL.\n";
                    $valores_convertidos[] = 'NULL';
                } else {
                    $valores_convertidos[] = "'$fecha_convertida'";
                }
            }
            // Usar los valores convertidos en el SQL
            $valores[] = implode(", ", $valores_convertidos);
            // // Usar el primer valor convertido como ejemplo para la inserción
            // if (!empty($valores_convertidos)) {
            //     $mapeo['valor_convertido'] = $valores_convertidos[0];
            // }
        } else {
            $valores[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}";
        }

        $mapeos[] = $mapeo;
    }

    // Confirmar ejecución del INSERT
    echo "\nResumen de mapeos para la tabla $tabla_destination:\n";
    foreach ($mapeos as $mapeo) {
        if (isset($mapeo['valor_manual'])) {
            echo "  - {$mapeo['campo_destination']} ← Valor manual: {$mapeo['valor_manual']}\n";
        } else {
            echo "  - {$mapeo['campo_destination']} ← {$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}\n";
        }
    }

    $confirmar = obtenerEntradaValida("> ¿Deseas ejecutar el INSERT para $tabla_destination? (si/no): ", ['si', 'no']);
    if ($confirmar === 'si') {
        // Generar y ejecutar el SQL
        $campos_dest = array_column($mapeos, 'campo_destination');
        $valores = [];

        foreach ($mapeos as $mapeo) {
            if (isset($mapeo['valor_manual'])) {
                // Valor manual definido por el usuario
                $valores[] = "'{$mapeo['valor_manual']}'";
            } elseif (
                strpos($mapeo['campo_destination'], 'date') !== false ||
                strpos($mapeo['campo_destination'], 'time') !== false ||
                strpos($mapeo['campo_destination'], 'created_at') !== false ||
                strpos($mapeo['campo_destination'], 'updated_at') !== false
            ) {
                // Detectar y convertir fechas usando SQL directamente
                $campo_euros = "{$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}";
                $valores[] = "IF(LOCATE('-', $campo_euros) = 5, $campo_euros, STR_TO_DATE($campo_euros, '%d-%m-%Y,%H:%i:%s'))";
            } else {
                // Para cualquier otro tipo de campo, usar directamente el mapeo
                $valores[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}";
            }
        }

        $insert_sql = "INSERT INTO $base_destination.$tabla_destination (" . implode(", ", $campos_dest) . ") ";
        $insert_sql .= "SELECT " . implode(", ", $valores) . " FROM $base_origin." . reset($mapeos)['tabla'] . ";";

        try {
            $pdo_destination->exec("SET foreign_key_checks = 0;");
            $pdo_destination->exec($insert_sql);
            $pdo_destination->exec("SET foreign_key_checks = 1;");
            echo "\nDatos insertados exitosamente en $tabla_destination.\n";
        } catch (PDOException $e) {
            echo "\nError al ejecutar el INSERT: " . $e->getMessage() . "\n";
        }
    }

    $continuar = obtenerEntradaValida("> ¿Deseas procesar otra tabla? (si/no): ", ['si', 'no']);
    if ($continuar !== 'si') {
        echo "Proceso finalizado. ¡Hasta luego!\n";
        break;
    }
}
