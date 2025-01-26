<?php
function crearCampoAdicional($base_destination, $tabla_destination) {
    $campos_adicionales = [];
    echo "> Introduce el nombre del campo adicional: ";
    $nombre_campo_adicional = trim(fgets(STDIN));
    echo "> Introduce el tipo del campo adicional: ";
    $tipo_campo_adicional = trim(fgets(STDIN));

    $alter_sql = "ALTER TABLE $base_destination.$tabla_destination ADD $nombre_campo_adicional $tipo_campo_adicional;";
    $sentencias_sql[] = $alter_sql;
    $campos_adicionales[] = [
        'tabla' => $tabla_destination,
        'campo' => $nombre_campo_adicional
    ];

    echo "\nSQL generado para añadir el campo:\n$alter_sql\n";
    return $campos_adicionales;
}