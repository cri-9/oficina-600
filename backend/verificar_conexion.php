<?php
// Este archivo verifica la conexión a la base de datos y responde en JSON
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/conexion.php';

try {
    // Si la conexión PDO existe, respondemos OK
    if (isset($pdo) && $pdo instanceof PDO) {
        echo json_encode([
            'success' => true,
            'message' => 'Conexión exitosa con la base de datos.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo crear la conexión PDO.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al conectar: ' . $e->getMessage()
    ]);
}
