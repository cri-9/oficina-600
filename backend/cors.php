<?php
// Detectar entorno (puedes cambiar por una variable ENV)
$isProduction = false; // Cambia a true en producción o usa getenv('APP_ENV') === 'production'

// Lista de orígenes permitidos según el entorno
$allowedOrigins = $isProduction
    ? [ 'https://tu-dominio.com' ] // Dominio de producción
    : [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:3000',
        'http://localhost'
      ];

// Obtener el origen de la solicitud
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Verificar si el origen está permitido
if (in_array($origin, $allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Max-Age: 86400'); // 24 horas
}

// Manejar solicitudes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
} 
