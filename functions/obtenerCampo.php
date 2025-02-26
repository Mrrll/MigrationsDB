<?php
function obtenerCampo($tablas, $tabla, $pregunta = null) {
    $pregunta = $pregunta ?? "> Selecciona el campo:\n";
    $campo = null;
    while (true) {
        echo "\nCampos disponibles en la tabla $tabla:\n";
        $campo = obtenerEntradaValida($pregunta, array_column($tablas[$tabla], 'Field'), true);
        echo "\nHas seleccionado el campo: $campo\n";
        $confirmar_campo = obtenerEntradaValida("> ¿Es correcto este campo? (si/no): ", ['si', 'no']);
        if ($confirmar_campo === 'si') {
            break;
        }
    }
    return $campo;
}