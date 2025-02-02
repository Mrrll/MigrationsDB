<?php
function seleccionarTabla($base, $tablas, $pdo)
{
    while (true) {
        echo "\nTablas disponibles en la base de datos ($base):\n";
        $tabla_origin = obtenerEntradaValida("> Selecciona una tabla de : $base\n", array_keys($tablas), true);
        echo "\nHas seleccionado la tabla: $tabla_origin\n";
        mostrarCampos($pdo, $tabla_origin);
        $confirmar_tabla = obtenerEntradaValida("> ¿Es correcta esta tabla? (si/no): ", ['si', 'no']);
        if ($confirmar_tabla === 'si') {
            return $tabla_origin;
        }
    }
}