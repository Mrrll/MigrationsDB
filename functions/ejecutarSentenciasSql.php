<?php
function ejecutarSentenciasSql($pdo_destination, $sentencias_sql) {
    $ejecutar_sentencias = false;
    try {
        $pdo_destination->exec("SET foreign_key_checks = 0;");
        foreach ($sentencias_sql as $sql) {
            $pdo_destination->exec($sql);
        }
        $pdo_destination->exec("SET foreign_key_checks = 1;");
        echo "\nDatos insertados exitosamente.\n";
        $ejecutar_sentencias = true;
    } catch (PDOException $e) {
        echo "\nError al ejecutar las sentencias SQL: " . $e->getMessage() . "\n";
    }
    return $ejecutar_sentencias;
}