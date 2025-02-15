<?php
require_once "funciones.php";
// ==========================================
// PROCESO INTERACTIVO
// ==========================================
function transformarValores($base, $tablas, $pdo)
{    
    while (true) {
        echo "\nBienvenido al proceso de transformar valores en la base de datos de  ($base):\n";
        $transformar = obtenerEntradaValida("> ¿Estas seguro que quieres transformar los valores de algún campo en la base de datos de  ($base)? (si/no): ", ['si', 'no']);        

        
        if ($transformar !== 'si') {
            echo "\nProceso de transformación finalizado.\n";
            break;
        }

        $tabla = seleccionarTabla($base, $tablas, $pdo);
        $campo = seleccionarCampo($tabla, $tablas);
        
        transformarCampo($base, $tabla, $campo, $pdo);

        $continuar = obtenerEntradaValida("> ¿Deseas realizar otra operación? (si/no): ", ['si', 'no']);
        if ($continuar !== 'si') {
            echo "\nProceso de transformación finalizado.\n";
            break;
        }
    }
}