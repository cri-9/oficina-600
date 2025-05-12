<?php
$phpIniPath = 'C:\php\php.ini';

if (!file_exists($phpIniPath)) {
    die("Error: No se encontró el archivo php.ini en $phpIniPath\n");
}

$content = file_get_contents($phpIniPath);
$content = str_replace(';extension=zip', 'extension=zip', $content);

if (file_put_contents($phpIniPath, $content)) {
    echo "Extensión ZIP habilitada correctamente.\n";
    echo "Por favor, reinicia el servidor Apache para que los cambios surtan efecto.\n";
} else {
    echo "Error al modificar el archivo php.ini. Asegúrate de tener permisos de escritura.\n";
} 