<?php
require_once "funciones.php";
// ==========================================
// PROCESO INTERACTIVO
// ==========================================
function actualizarValores($base, $tablas, $pdo)  {    
    while (true) {
        echo "\nProceso para actualizar valores en la base de datos ($base):\n";
        $actualizar = obtenerEntradaValida("> ¿Estas seguro que quieres actualizar los valores de algún campo en la base de datos ($base)? (si/no): ", ['si', 'no']);
        if ($actualizar !== 'si') {
            echo "\nProceso de actualización finalizado.\n";
            break;
        }
    
        $tabla = seleccionarTabla($base, $tablas, $pdo);
        $campo = seleccionarCampo($tabla, $tablas);
    
        actualizarCampo($base, $tabla, $campo, $pdo, $tablas);
    
        $continuar = obtenerEntradaValida("> ¿Deseas realizar otra operación? (si/no): ", ['si', 'no']);
        if ($continuar !== 'si') {
            echo "\nProceso de actualización finalizado.\n";
            break;
        }
    }
}