<?php
// ==========================================
// PROCESO INTERACTIVO
// ==========================================
function transformarValores($base_origin, $tablas_origin, $pdo_origin)
{
    while (true) {
        echo "\nProceso para transformar valores en la base de datos de origen ($base_origin):\n";
        $transformar = obtenerEntradaValida("> ¿Quieres transformar los valores de algún campo en la base de datos de origen ($base_origin)? (si/no): ", ['si', 'no']);
        if ($transformar !== 'si') {
            $cambiar_valor = obtenerEntradaValida("> ¿Quieres cambiar el valor de algún campo en la base de datos de origen ($base_origin)? (si/no): ", ['si', 'no']);
            if ($cambiar_valor !== 'si') {
                break;
            }
        }

        $tabla_origin = seleccionarTabla($base_origin, $tablas_origin, $pdo_origin);
        $campo_origin = seleccionarCampo($tabla_origin, $tablas_origin);

        if ($transformar === 'si') {
            transformarCampo($base_origin, $tabla_origin, $campo_origin, $pdo_origin);
        } else {
            actualizarCampo($base_origin, $tabla_origin, $campo_origin, $pdo_origin);
        }

        $continuar = obtenerEntradaValida("> ¿Deseas realizar otra operación? (si/no): ", ['si', 'no']);
        if ($continuar !== 'si') {
            echo "Proceso finalizado. ¡Hasta luego!\n";
            break;
        }
    }
}

function seleccionarTabla($base_origin, $tablas_origin, $pdo_origin)
{
    while (true) {
        echo "\nTablas disponibles en la base de datos de origen ($base_origin):\n";
        $tabla_origin = obtenerEntradaValida("> Selecciona una tabla de origen: $base_origin\n", array_keys($tablas_origin), true);
        echo "\nHas seleccionado la tabla: $tabla_origin\n";
        mostrarCampos($pdo_origin, $tabla_origin);
        $confirmar_tabla = obtenerEntradaValida("> ¿Es correcta esta tabla? (si/no): ", ['si', 'no']);
        if ($confirmar_tabla === 'si') {
            return $tabla_origin;
        }
    }
}

function seleccionarCampo($tabla_origin, $tablas_origin)
{
    while (true) {
        echo "\nCampos disponibles en la tabla $tabla_origin:\n";
        $campos_origin = array_column($tablas_origin[$tabla_origin], 'Field');
        $campo_origin = obtenerEntradaValida("> Selecciona el campo que deseas actualizar:\n", $campos_origin, true);
        echo "\nHas seleccionado el campo: $campo_origin\n";
        $confirmar_campo = obtenerEntradaValida("> ¿Es correcto este campo? (si/no): ", ['si', 'no']);
        if ($confirmar_campo === 'si') {
            return $campo_origin;
        }
    }
}

function transformarCampo($base_origin, $tabla_origin, $campo_origin, $pdo_origin)
{
    $opcion_transformar = obtenerEntradaValida("> Selecciona la transformación que deseas aplicar:\n", ['minusculas', 'mayusculas', 'capitalizacion'], true);
    $query = $pdo_origin->query("SELECT $campo_origin FROM $base_origin.$tabla_origin");
    $valores = $query->fetchAll(PDO::FETCH_COLUMN);

    $total_registros_modificados = 0;

    foreach ($valores as $valor) {
        switch ($opcion_transformar) {
            case 'minusculas':
                $nuevo_valor = strtolower($valor);
                break;
            case 'mayusculas':
                $nuevo_valor = strtoupper($valor);
                break;
            case 'capitalizacion':
                $nuevo_valor = ucwords(strtolower($valor));
                break;
        }

        $update_sql = "UPDATE $base_origin.$tabla_origin SET $campo_origin = :nuevo_valor WHERE $campo_origin = :valor";
        try {
            $stmt = $pdo_origin->prepare($update_sql);
            $stmt->execute([
                ':nuevo_valor' => $nuevo_valor,
                ':valor' => $valor,
            ]);
            $total_registros_modificados += $stmt->rowCount();
        } catch (PDOException $e) {
            echo "\nError al ejecutar la transformación: " . $e->getMessage() . "\n";
        }
    }

    echo "\nDatos transformados exitosamente en $tabla_origin. Registros afectados: $total_registros_modificados\n\n";
}

function actualizarCampo($base_origin, $tabla_origin, $campo_origin, $pdo_origin)
{
    echo "> Introduce el nuevo valor para $campo_origin (escribe 'NULL' para valores nulos): ";
    $nuevo_valor = trim(fgets(STDIN));
    $nuevo_valor = strtoupper($nuevo_valor) === 'NULL' ? null : $nuevo_valor;

    echo "> Introduce el valor de la condición WHERE ($campo_origin =): ";
    $condicion_where = trim(fgets(STDIN));

    $update_sql = "UPDATE $base_origin.$tabla_origin SET $campo_origin = :nuevo_valor WHERE $campo_origin = :condicion_where";

    echo "\nSQL generado:\n$update_sql\n";

    $confirmar = obtenerEntradaValida("> ¿Deseas ejecutar el UPDATE para la tabla $tabla_origin? (si/no): ", ['si', 'no']);

    if ($confirmar === 'si') {
        try {
            $stmt = $pdo_origin->prepare($update_sql);
            $stmt->execute([
                ':nuevo_valor' => $nuevo_valor,
                ':condicion_where' => $condicion_where,
            ]);
            $affected_rows = $stmt->rowCount();
            echo "\nDatos actualizados exitosamente en $tabla_origin. Registros afectados: $affected_rows\n\n";
        } catch (PDOException $e) {
            echo "\nError al ejecutar el UPDATE: " . $e->getMessage() . "\n";
        }
    }
}