<?php
function crearCampoAdicional($base_destination, $tabla_destination) {
    $campos_adicionales = [];
    while (true) {
        echo "> Introduce el nombre del campo adicional: ";
        $nombre_campo_adicional = trim(fgets(STDIN));
        echo "> Introduce el tipo del campo adicional: ";
        $tipo_campo_adicional = trim(fgets(STDIN));
        echo "\n";
        $confirmar = obtenerEntradaValida("> ¿Confirmas el campo adicional $nombre_campo_adicional de tipo $tipo_campo_adicional a la tabla $tabla_destination? (si/no): ", ['si', 'no']);
        if ($confirmar === 'si') {
            break;
        }
    }
    

    $alter_sql = "ALTER TABLE $base_destination.$tabla_destination ADD $nombre_campo_adicional $tipo_campo_adicional;";
    // $sentencias_sql[] = $alter_sql;
    $campos_adicionales = [
        'tabla' => $tabla_destination,
        'campo' => $nombre_campo_adicional,
        'tipo' => $tipo_campo_adicional,
        'sql' => $alter_sql
    ];

    echo "\nSQL generado para añadir el campo:\n$alter_sql\n";
    return $campos_adicionales;
}