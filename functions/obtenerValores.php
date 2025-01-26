<?php
function obtenerValores ($pdo_origin, $base_origin, $tablas_origin, $pdo_destination, $base_destination, $tablas_destination, $tabla_destination) {
    $mapeos = [];
    // Valores para insertar en la tabla de destino
    foreach ($tablas_destination[$tabla_destination] as $campo_dest) {
        $campo_dest_name = $campo_dest['Field'];
        $campo_dest_type = $campo_dest['Type'];
    
        $omitir = obtenerEntradaValida("> ¿Deseas omitir el campo $campo_dest_name? (si/no) [no]: ", ['si', 'no']);
        if ($omitir === 'si') {
            echo "Campo $campo_dest_name omitido.\n";
            echo "\nSiguiente campo....\n\n";
            continue;
        }
    
        $manual = obtenerEntradaValida("> ¿Quieres establecer un valor manual para $campo_dest_name? (si/no): ", ['si', 'no']);
        if ($manual === 'si') {
            echo "> Introduce el valor manual para $campo_dest_name: ";
            $valor_manual = trim(fgets(STDIN));
            $mapeos[] = [
                'campo_destination' => $campo_dest_name,
                'valor_manual' => $valor_manual
            ];
    
            $concatenar = obtenerEntradaValida("> ¿Quieres concatenar un valor a este campo $campo_dest_name? (si/no): ", ['si', 'no']);
            if ($concatenar === 'si') {
                echo "> Introduce el valor a concatenar: ";
                $valor_concatenar = trim(fgets(STDIN));
                $mapeos[count($mapeos) - 1]['valor_concatenar'] = $valor_concatenar;
            }
    
            $relacionar_condicion = obtenerEntradaValida("> ¿Quieres agregar una condición de relación para este campo $campo_dest_name? (si/no): ", ['si', 'no']);
            if ($relacionar_condicion === 'si') {
                echo "\nPara la condición de relación, selecciona el primer campo:\n";
    
                while (true) {
                    $base_datos_origen = obtenerEntradaValida("> ¿En qué base de datos se encuentra la tabla para el primer campo? ($base_origin/$base_destination): ", [$base_origin, $base_destination], false, true);
                    if ($base_datos_origen === 'rectificar') {
                        continue;
                    }
                    echo "\nHas seleccionado la base de datos: $base_datos_origen\n";
                    $confirmar_base_origen = obtenerEntradaValida("> ¿Es correcta esta base de datos? (si/no): ", ['si', 'no']);
                    if ($confirmar_base_origen === 'si') {
                        break;
                    }
                }
    
                $tablas_origen = ($base_datos_origen === $base_origin) ? $tablas_origin : $tablas_destination;
                $pdo = ($base_datos_origen === $base_origin) ? $pdo_origin : $pdo_destination;
    
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
    
                $condicion_relacion = "$base_datos_origen.$tabla_origen.$campo_origen = $base_datos_destino.$tabla_destino.$campo_destino";
    
                $mapeo['condicion_relacion'] = $condicion_relacion;
    
                echo "Condición de relación agregada: $condicion_relacion\n";
    
                $concatenar_relacion = obtenerEntradaValida("> ¿Quieres concatenar un valor a este campo $campo_dest_name en la condición de relación? (si/no): ", ['si', 'no']);
                if ($concatenar_relacion === 'si') {
                    echo "> Introduce el valor a concatenar: ";
                    $valor_concatenar_relacion = trim(fgets(STDIN));
                    $mapeo['valor_concatenar_relacion'] = $valor_concatenar_relacion;
                }
            }
    
            echo "\nSiguiente campo....\n\n";
            continue;
        }
    
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
    
        $mapeo = [
            'campo_destination' => $campo_dest_name,
            'tabla' => $tabla_fuente,
            'campo' => $campo_fuente,
            'base_datos' => $base_datos
        ];
    
        $concatenar = obtenerEntradaValida("> ¿Quieres concatenar un valor a este campo $campo_dest_name? (si/no): ", ['si', 'no']);
        if ($concatenar === 'si') {
            echo "> Introduce el valor a concatenar: ";
            $valor_concatenar = trim(fgets(STDIN));
            $mapeo['valor_concatenar'] = $valor_concatenar;
        }
    
        $relacionar_condicion = obtenerEntradaValida("> ¿Quieres agregar una condición de relación para este campo $campo_dest_name? (si/no): ", ['si', 'no']);
        if ($relacionar_condicion === 'si') {
            echo "\nPara la condición de relación, selecciona el primer campo:\n";
    
            while (true) {
                $base_datos_origen = obtenerEntradaValida("> ¿En qué base de datos se encuentra la tabla para el primer campo? ($base_origin/$base_destination): ", [$base_origin, $base_destination], false, true);
                if ($base_datos_origen === 'rectificar') {
                    continue;
                }
                echo "\nHas seleccionado la base de datos: $base_datos_origen\n";
                $confirmar_base_origen = obtenerEntradaValida("> ¿Es correcta esta base de datos? (si/no): ", ['si', 'no']);
                if ($confirmar_base_origen === 'si') {
                    break;
                }
            }
    
            $tablas_origen = ($base_datos_origen === $base_origin) ? $tablas_origin : $tablas_destination;
            $pdo = ($base_datos_origen === $base_origin) ? $pdo_origin : $pdo_destination;
    
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
    
            $condicion_relacion = "$base_datos_origen.$tabla_origen.$campo_origen = $base_datos_destino.$tabla_destino.$campo_destino";
    
            $mapeo['condicion_relacion'] = $condicion_relacion;
    
            echo "Condición de relación agregada: $condicion_relacion\n";
    
            $concatenar_relacion = obtenerEntradaValida("> ¿Quieres concatenar un valor a este campo $campo_dest_name en la condición de relación? (si/no): ", ['si', 'no']);
            if ($concatenar_relacion === 'si') {
                echo "> Introduce el valor a concatenar: ";
                $valor_concatenar_relacion = trim(fgets(STDIN));
                $mapeo['valor_concatenar_relacion'] = $valor_concatenar_relacion;
            }
        }
    
        if (strpos($campo_dest_type, 'timestamp') !== false || strpos($campo_dest_type, 'datetime') !== false) {
            echo "El campo $campo_dest_name es de tipo TIMESTAMP o DATETIME. Verificando formato y convirtiendo si es necesario.\n";
    
            $query = $pdo_origin->prepare("SELECT `$campo_fuente` FROM `$tabla_fuente`");
            $query->execute();
            $valores_origen = $query->fetchAll(PDO::FETCH_COLUMN);
    
            $valores_convertidos = [];
            foreach ($valores_origen as $valor_origen) {
                $fecha_convertida = detectarYConvertirFecha($valor_origen);
                if ($fecha_convertida === null) {
                    echo "Advertencia: No se pudo convertir la fecha '$valor_origen'. Usando NULL.\n";
                    $valores_convertidos[] = 'NULL';
                } else {
                    $valores_convertidos[] = "'$fecha_convertida'";
                }
            }
            $valores[] = implode(", ", $valores_convertidos);
        } else {
            $valores[] = "{$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}";
        }
        echo "\nSiguiente campo....\n\n";
        $mapeos[] = $mapeo;
    }
    return $mapeos;
}