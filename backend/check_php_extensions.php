<?php
// Obtener la ubicación del php.ini
$phpIniPath = php_ini_loaded_file();
echo "Archivo php.ini cargado: " . ($phpIniPath ?: "No se encontró php.ini") . "\n";

// Extensiones requeridas
$requiredExtensions = ['zip', 'json', 'pdo', 'pdo_mysql'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    echo "\nExtensiones faltantes:\n";
    foreach ($missingExtensions as $ext) {
        echo "- $ext\n";
    }
    echo "\nPara habilitar estas extensiones:\n";
    echo "1. Abre el archivo php.ini en: $phpIniPath\n";
    echo "2. Busca y descomenta (quita el punto y coma del inicio) estas líneas:\n";
    foreach ($missingExtensions as $ext) {
        echo "   extension=$ext\n";
    }
    echo "3. Guarda el archivo y reinicia Apache\n";
} else {
    echo "\nTodas las extensiones requeridas están habilitadas.\n";
}

// Mostrar todas las extensiones cargadas
echo "\nExtensiones cargadas actualmente:\n";
$loadedExtensions = get_loaded_extensions();
sort($loadedExtensions);
foreach ($loadedExtensions as $ext) {
    echo "- $ext\n";
} 