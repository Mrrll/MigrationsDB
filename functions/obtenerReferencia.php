<?php
function obtenerReferencia($campo_dest_name, $base_origin, $base_destination, $tablas_origin, $tablas_destination, $pdo_origin, $pdo_destination) {
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

    return [
        'campo_destination' => $campo_dest_name,
        'tabla' => $tabla_fuente,
        'campo' => $campo_fuente,
        'base_datos' => $base_datos
    ];
}