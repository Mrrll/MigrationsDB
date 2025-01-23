<?php
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
// Validación del input del usuario con opción de rectificación
function obtenerEntradaValida($prompt, $opciones, $numerar = false, $permitir_rectificar = false)
{
    while (true) {
        if ($numerar) {
            foreach ($opciones as $index => $opcion) {
                echo " [" . ($index + 1) . "] $opcion\n";
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
