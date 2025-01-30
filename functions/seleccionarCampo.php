<?php
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