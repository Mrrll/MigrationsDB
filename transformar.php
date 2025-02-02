<?php
require_once "funciones.php";
// ==========================================
// PROCESO INTERACTIVO
// ==========================================
function transformarValores($base_origin, $tablas_origin, $pdo_origin)
{    
    while (true) {
        echo "\nBienvenido al proceso de transformar valores en la base de datos de origen ($base_origin):\n";
        $transformar = obtenerEntradaValida("> ¿Estas seguro que quieres transformar los valores de algún campo en la base de datos de origen ($base_origin)? (si/no): ", ['si', 'no']);        

        
        if ($transformar !== 'si') {
            echo "\nProceso de transformación finalizado.\n";
            break;
        }

        $tabla_origin = seleccionarTabla($base_origin, $tablas_origin, $pdo_origin);
        $campo_origin = seleccionarCampo($tabla_origin, $tablas_origin);
        
        transformarCampo($base_origin, $tabla_origin, $campo_origin, $pdo_origin);

        $continuar = obtenerEntradaValida("> ¿Deseas realizar otra operación? (si/no): ", ['si', 'no']);
        if ($continuar !== 'si') {
            echo "\nProceso de transformación finalizado.\n";
            break;
        }
    }
}