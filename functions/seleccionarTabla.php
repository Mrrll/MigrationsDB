<?php
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