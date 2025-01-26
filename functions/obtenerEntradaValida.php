<?php
// Validación del input del usuario con opción de rectificación
function obtenerEntradaValida($prompt, $opciones, $numerar = false, $permitir_rectificar = false)
{
    while (true) {
        if ($numerar) {
            foreach ($opciones as $index => $opcion) {
                echo " [" . ($index + 1) . "] $opcion\n";
            }
        }
        echo $prompt;
        $entrada = trim(fgets(STDIN));

        if ($permitir_rectificar && strtolower($entrada) === 'rectificar') {
            return 'rectificar';
        }

        if ($numerar && is_numeric($entrada) && isset($opciones[$entrada - 1])) {
            return $opciones[$entrada - 1];
        } elseif (in_array($entrada, $opciones)) {
            return $entrada;
        } else {
            echo "Entrada inválida. Por favor, selecciona una opción válida.\n";
        }
    }
}