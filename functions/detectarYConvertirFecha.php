<?php
// Función para convertir fechas al formato adecuado
function detectarYConvertirFecha($valor)
{
    $formatos_entrada = [
        'd-m-Y H:i:s',
        'd-m-Y,H:i:s',
        'd/m/Y H:i:s',
        'Y-m-d H:i:s',
        'd-m-Y',
        'Y-m-d'
    ];

    foreach ($formatos_entrada as $formato) {
        $fecha = DateTime::createFromFormat($formato, $valor);
        if ($fecha !== false) {
            return $fecha->format('Y-m-d H:i:s');
        }
    }

    return null;
}