<?php
// Solicitar datos de conexión a las bases de datos.
echo "\nBienvenido al proceso de migración de datos entre bases de datos ....\n";
echo "\nIntroduce el nombre de la base de datos de origen: ";
$base_origin = trim(fgets(STDIN));
echo "Introduce para la base de datos de origen ($base_origin) el usuario: ";
$usuario_origin = trim(fgets(STDIN));
echo "Introduce para la base de datos de origen ($base_origin) la contraseña: ";
$contraseña_origin = trim(fgets(STDIN));
echo "Introduce el nombre de la base de datos de destino: ";
$base_destination = trim(fgets(STDIN));
echo "Introduce para la base de datos de destino ($base_destination) el usuario: ";
$usuario_destination = trim(fgets(STDIN));
echo "Introduce para la base de datos de destino ($base_destination) la contraseña: ";
$contraseña_destination = trim(fgets(STDIN));

// Configuración inicial
// $base_origin = "euros";
// $usuario_origin = "root";
// $contraseña_origin = "1234";
// $base_destination = "mybilling";
// $usuario_destination = "root";
// $contraseña_destination = "1234";

// Conexión a las bases de datos
try {
    $pdo_origin = new PDO("mysql:host=localhost;dbname=$base_origin", $usuario_origin, $contraseña_origin);
    $pdo_origin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo_destination = new PDO("mysql:host=localhost;dbname=$base_destination", $usuario_destination, $contraseña_destination);
    $pdo_destination->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "\nConexión exitosa a ambas bases de datos.\n";
} catch (PDOException $e) {
    die("Error al conectar con las bases de datos: " . $e->getMessage() . "\n");
}