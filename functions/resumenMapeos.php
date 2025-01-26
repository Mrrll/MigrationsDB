<?php
function resumenMapeos($mapeos) {
    // Mostrar mapeos
    foreach ($mapeos as $mapeo) {
        if (isset($mapeo['valor_manual'])) {
            echo " - {$mapeo['campo_destination']} ← Valor manual: {$mapeo['valor_manual']}\n";
        } elseif (isset($mapeo['valor_concatenar'])) {
            echo " - {$mapeo['campo_destination']} ← CONCAT({$mapeo['campo_destination']}, '{$mapeo['valor_concatenar']}')\n";
        } else {
            echo " - {$mapeo['campo_destination']} ← {$mapeo['base_datos']}.{$mapeo['tabla']}.{$mapeo['campo']}\n";
        }
    }
}