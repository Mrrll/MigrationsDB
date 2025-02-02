<?php
// ==========================================
// CONFIGURACIÓN Y UTILIDADES
// ==========================================
require_once 'conexion.php';
require_once 'funciones.php';
require_once 'transformar.php';
require_once 'actualizar.php';
require_once 'migrar.php';

// Obtener tablas y campos
$tablas_origin = obtenerTablasYCampos($pdo_origin, $base_origin);
$tablas_destination = obtenerTablasYCampos($pdo_destination, $base_destination);

// ==========================================
// PROCESO INTERACTIVO
// ==========================================

while (true) {
    echo "\nBienvenido al proceso de migración de datos entre bases de datos:\n";
    $opcion = obtenerEntradaValida("> Selecciona una opción:\n", ["Transformar valores en la base de datos de origen ($base_origin)", "Actualizar valores en la base de datos de origen ($base_origin)", "Migrar datos de la base de datos de origen ($base_origin) a la base de datos de destino ($base_destination)", "Actualizar valores en la base de datos de destino ($base_destination)", "Salir"], true);
    if ($opcion === 'Salir') {
        echo "\nProceso finalizado. ¡Hasta luego!\n\n";
        break;
    }
    if ($opcion === "Transformar valores en la base de datos de origen ($base_origin)") {
        transformarValores($base_origin, $tablas_origin, $pdo_origin);
    }
    if ($opcion === "Actualizar valores en la base de datos de origen ($base_origin)") {
        actualizarValores($base_origin, $tablas_origin, $pdo_origin);
    }
    if ($opcion === "Migrar datos de la base de datos de origen ($base_origin) a la base de datos de destino ($base_destination)") {
        migrarDatos($base_origin, $base_destination, $tablas_origin, $tablas_destination, $pdo_origin, $pdo_destination);
    }
    if ($opcion === "Actualizar valores en la base de datos de destino ($base_destination)") {
        actualizarValores($base_destination, $tablas_destination, $pdo_destination);
    }
}

