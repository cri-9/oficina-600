<?php

// Desactivar errores para evitar contaminación del JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Cabeceras CORS

// Buffer de salida para evitar que errores o caracteres inesperados contaminen la salida JSON
ob_start();

try {
    // Datos de prueba
    $datos = [
        'success' => true,
        'mensaje' => 'Test de JSON funcionando correctamente',
        'tiempo' => date('Y-m-d H:i:s'),
        'turnos_prueba' => [
            [
                'id' => 1,
                'numero_turno' => 'A001',
                'tipo_atencion' => 'Certificados',
                'estado' => 'pendiente'
            ],
            [
                'id' => 2,
                'numero_turno' => 'B002',
                'tipo_atencion' => 'Atención General',
                'estado' => 'en_atencion'
            ]
        ]
    ];
    
    // Limpiar cualquier salida previa para evitar contaminación
    ob_end_clean();
    
    // Generar JSON
    echo json_encode($datos);
} catch (Exception $e) {
    // Limpiar buffer anterior
    ob_end_clean();
    
    // Devolver error en formato JSON
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al generar JSON: ' . $e->getMessage()
    ]);
}