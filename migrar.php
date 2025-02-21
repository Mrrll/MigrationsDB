<?php
require_once "funciones.php";
// ==========================================
// PROCESO INTERACTIVO
// ==========================================
function migrarDatos($base_origin, $base_destination, $tablas_origin, $tablas_destination, $pdo_origin, $pdo_destination)
{
    $sentencias_sql = [];     
    $ejecutar_sentencias = false;
    $continuar = 'si';
    while (true) {
        echo "\nProceso para migrar de la base de datos de origen ($base_origin) a la base de datos de destino ($base_destination):\n";
        $continuar = obtenerEntradaValida("> ¿Deseas continuar con el proceso? (si/no): ", ['si', 'no']);
        if ($continuar === 'no') {
            break;
        }
        echo "\nTablas disponibles en la base de datos destino ($base_destination):\n";
        // Seleccionar tabla de destino
        $tabla_destination = seleccionarTablaDestino($pdo_destination, $base_destination, $tablas_destination);

        $añadir_campo = obtenerEntradaValida("> ¿Quieres añadir un campo adicional a la tabla $tabla_destination? (si/no): ", ['si', 'no']);
        if ($añadir_campo === 'si') {
            $campos_adicionales = crearCampoAdicional($base_destination, $tabla_destination);
        }

        $secuencial = obtenerEntradaValida("> ¿Deseas enumerar las filas secuencialmente de las tablas? (si/no): ", ['si', 'no']);
        
        while (true) {            
            echo "\nCampos disponibles en la tabla $tabla_destination:\n";         
    
            // Valores para insertar en la tabla de destino
            $mapeos = obtenerValores($pdo_origin, $base_origin, $tablas_origin, $pdo_destination, $base_destination, $tablas_destination, $tabla_destination, $secuencial);
    
            echo "\nResumen de mapeos para la tabla $tabla_destination:\n";
            // Resumen de mapeos
            resumenMapeos($mapeos);

            $confirmar_mapeos = obtenerEntradaValida("> ¿Deseas confirmar los mapeos para la tabla $tabla_destination? (si/no): ", ['si', 'no']);
            if ($confirmar_mapeos === 'si') {
                break;
            }
            echo "\n";        
        }

        $campos_dest = array_column($mapeos, 'campo_destination');
        
        // Generar valores y fuentes para la sentencia SQL
        $valores = generarValoresFuentes($mapeos);
        // Comprobar si hay campos de origen válidos
        if (empty($valores['valores'])) {
            echo "\nError: No se encontraron campos de origen válidos.\n";
            continue;
        }

        // Verificar si la tabla de destino tiene columnas únicas
        $on_duplicate_key_update = generarOnDuplicateKeyUpdate($pdo_destination, $base_destination, $tabla_destination);

        // Generar sentencia SQL
        $insert_sql = generarSentenciaSql($base_destination, $tabla_destination, $campos_dest, $valores['valores'], $valores['fuentes'], $valores['join_clauses'], $on_duplicate_key_update, $mapeos, $secuencial);
        $validar = obtenerEntradaValida("> ¿Deseas ver la sentencia para $tabla_destination? (si/no): ", ['si', 'no']);
        if ($validar === 'si') {
            echo "\nSentencia SQL generada para la tabla $tabla_destination:\n";
            echo "$insert_sql\n";
        }

        $sentencias_sql[] = $insert_sql;
        echo "\n";
        $anidar = obtenerEntradaValida("> ¿Deseas anidar otra sentencia SQL antes de ejecutar? (si/no): ", ['si', 'no']);
        if ($anidar === 'no') {
            break;
        }
    }
    if ($continuar === 'no') {
        echo "\nProceso de anidación finalizado.\n";        
    }
    // Mostrar sentencias SQL generadas
    echo "\nSentencias SQL generadas:\n";
    foreach ($sentencias_sql as $sql) {
        echo "$sql\n\n";
    }

    $confirmar = obtenerEntradaValida("> ¿Deseas ejecutar las sentencias SQL generadas? (si/no): ", ['si', 'no']);
    if ($confirmar === 'si') {
        // Ejecutar sentencias SQL
        $ejecutar_sentencias = ejecutarSentenciasSql($pdo_destination, $sentencias_sql);
    }

    if ($ejecutar_sentencias && !empty($campos_adicionales)) {
        // Eliminar campos adicionales
        eliminarCampoAdicional($pdo_destination, $base_destination, $campos_adicionales);
    }

    echo "Proceso finalizado. ¡Hasta luego!\n";
}
