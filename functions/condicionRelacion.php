<?php
function condicionRelacion($base_origin, $base_destination, $tablas_origin, $tablas_destination, $pdo_origin, $pdo_destination) {
    echo "\nPara la condición de relación, selecciona el primer campo:\n";
    // Base de datos primer campo
    while (true) {
        $base_datos_origen = obtenerEntradaValida("> ¿En qué base de datos se encuentra la tabla para el primer campo? ($base_origin/$base_destination): ", [$base_origin, $base_destination], false, true);
        echo "\nHas seleccionado la base de datos: $base_datos_origen\n";
        $confirmar_base_origen = obtenerEntradaValida("> ¿Es correcta esta base de datos? (si/no): ", ['si', 'no']);
        if ($confirmar_base_origen === 'si') {
            break;
        }
    }
    $tablas_origen = ($base_datos_origen === $base_origin) ? $tablas_origin : $tablas_destination;
    $pdo = ($base_datos_origen === $base_origin) ? $pdo_origin : $pdo_destination;
    // Tabla primer campo
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
    // Primer campo
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

    // Base de datos segundo campo
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
    // Tabla segundo campo
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
    // Segundo campo
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
    
    return "$base_datos_origen.$tabla_origen.$campo_origen = $base_datos_destino.$tabla_destino.$campo_destino";
}