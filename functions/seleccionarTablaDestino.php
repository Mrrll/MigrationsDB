<?php
function seleccionarTablaDestino($pdo_destination, $base_destination, $tablas_destination) {
    $tabla_destination = null;
    while (true) {
        $tabla_destination = obtenerEntradaValida("> Selecciona una tabla de destino: $base_destination\n", array_keys($tablas_destination), true);
        echo "\nHas seleccionado la tabla: $tabla_destination\n";
        mostrarCampos($pdo_destination, $tabla_destination);
        $confirmar_tabla = obtenerEntradaValida("> ¿Es correcta esta tabla? (si/no): ", ['si', 'no']);
        if ($confirmar_tabla === 'si') {
            break;
        }
    }
    return $tabla_destination;
}