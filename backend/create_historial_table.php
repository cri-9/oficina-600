<?php
require_once 'conexion.php';

try {
    // Leer el archivo SQL
    $sql = file_get_contents('create_historial_table.sql');
    
    // Ejecutar el SQL
    $pdo->exec($sql);
    
    echo "Tabla historial_turnos creada o actualizada correctamente\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 