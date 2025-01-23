<?php
// ==========================================
// CONFIGURACIÓN Y UTILIDADES
// ==========================================
require_once 'conexion.php';
require_once 'funciones.php';
require_once 'transformar.php';
require_once 'migrar.php';

// Obtener tablas y campos
$tablas_origin = obtenerTablasYCampos($pdo_origin, $base_origin);
$tablas_destination = obtenerTablasYCampos($pdo_destination, $base_destination);

// ==========================================
// PROCESO INTERACTIVO
// ==========================================

transformarValores($base_origin, $tablas_origin, $pdo_origin);
migrarDatos($base_origin, $base_destination, $tablas_origin, $tablas_destination, $pdo_origin, $pdo_destination);
