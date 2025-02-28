<?php
function obtenerValores($pdo_origin, $base_origin, $tablas_origin, $pdo_destination, $base_destination, $tablas_destination, $tabla_destination, $secuencial)
{
    $mapeos = [];
    $i = 0;
    $campos = array_values($tablas_destination[$tabla_destination]);
    while ($i < count($campos)) {
        $campo_dest = $campos[$i];
        $campo_dest_name = $campo_dest['Field'];
        $campo_dest_type = $campo_dest['Type'];
        $omitir = obtenerEntradaValida("> ¿Deseas omitir el campo $campo_dest_name? (si/no/volver) [no]: ", ['si', 'no', 'volver']);
        if ($omitir === 'si') {            
            echo "Campo $campo_dest_name omitido.\n";
            echo "\nSiguiente campo....\n\n";
            $i++;
        }
        if ($omitir === 'no') {
            $manual = obtenerEntradaValida("> ¿Quieres establecer un valor manual para $campo_dest_name? (si/no): ", ['si', 'no']);
            if ($manual === 'si') {
                echo "> Introduce el valor manual para $campo_dest_name: ";
                $valor_manual = trim(fgets(STDIN));
                $mapeos[] = [
                    'campo_destination' => $campo_dest_name,
                    'type_destination' => $campo_dest_type,
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

                    $condicion_relacion = condicionRelacion($base_origin, $base_destination, $tablas_origin, $tablas_destination, $pdo_origin, $pdo_destination);

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
                $i++;
            }
            if ($manual === 'no') {

                $mapeo = obtenerReferencia($campo_dest_name, $base_origin, $base_destination, $tablas_origin, $tablas_destination, $pdo_origin, $pdo_destination);
                $mapeo['type_destination'] = $campo_dest_type;
                $campo_fuente = $mapeo['campo'];
                $tabla_fuente = $mapeo['tabla'];                

                $concatenar = obtenerEntradaValida("> ¿Quieres concatenar un valor a este campo $campo_dest_name? (si/no): ", ['si', 'no']);
                if ($concatenar === 'si') {
                    echo "> Introduce el valor a concatenar: ";
                    $valor_concatenar = trim(fgets(STDIN));
                    $mapeo['valor_concatenar'] = $valor_concatenar;
                }
    
                $relacionar_condicion = obtenerEntradaValida("> ¿Quieres agregar una condición de relación para este campo $campo_dest_name? (si/no): ", ['si', 'no']);
                if ($relacionar_condicion === 'si') {
                    
                    $condicion_relacion = condicionRelacion($base_origin, $base_destination, $tablas_origin, $tablas_destination, $pdo_origin, $pdo_destination);
    
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
                    $pdo = $mapeo['base_datos'] === $base_origin ? $pdo_origin : $pdo_destination;
    
                    $query = $pdo->prepare("SELECT `$campo_fuente` FROM `$tabla_fuente`");
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
                if ($secuencial === 'si') {
                    while (true) {
                        echo "\nEstablece un alias para la secuencia en este campo " . $mapeo['campo'] . ":\n";
                        $alias = trim(fgets(STDIN));
                        $confirmar_alias = obtenerEntradaValida("> ¿Es correcto este alias? (si/no): ", ['si', 'no']);
                        if ($confirmar_alias === 'si') {
                            $mapeo['alias'] = $alias;
                            break;
                        }
                    }
                }
                echo "\nSiguiente campo....\n\n";
                $mapeos[] = $mapeo;
                $i++;
            }
        }
        
        if ($omitir === 'volver' && $i > 0) {
            // Eliminar el mapeo
            $valor_a_eliminar = $campos[$i-1]['Field'];            
            $mapeos = array_values(array_filter($mapeos, function ($item) use ($valor_a_eliminar) {
                return $item['campo_destination'] !== $valor_a_eliminar;
            }));                
            echo "Regresando al campo anterior...\n\n";
            $i--;
        } elseif ($omitir === 'volver' && $i === 0) {
            echo "No se puede volver al campo anterior porque es el primer campo.\n";
        }                
    }    
    return $mapeos;
}
