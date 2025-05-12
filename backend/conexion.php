<?php
// Configuración de conexión a la base de datos
// Modo de entorno: cambiar a 'production' en servidor real
$environment = 'development'; 

$host = 'localhost';
$db = 'oficina600_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Mejor seguridad con consultas preparadas reales
];

// Asegurar que existe el directorio de logs
$logDir = __DIR__ . '/../logs';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Verificación básica de conexión
    $stmt = $pdo->query('SELECT 1');
    if (!$stmt) {
        throw new PDOException("La conexión parece estar establecida pero no responde correctamente");
    }
    
    // Solo en desarrollo: verificar versión del servidor
    if ($environment === 'development') {
        $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        // Opcional: registrar información de conexión exitosa en desarrollo
        @file_put_contents($logDir . '/conexion_success.log', 
            date('Y-m-d H:i:s') . " | Conexión exitosa. MySQL versión: $version\n", 
            FILE_APPEND);
    }
    
} catch (PDOException $e) {
    // Logging avanzado en archivo (no mostrar credenciales al usuario)
    $logPath = $logDir . '/conexion_errors.log';
    $msg = date('Y-m-d H:i:s') . " | Error de conexión: " . $e->getMessage() . 
           " | Código: " . $e->getCode() . "\n";
    @file_put_contents($logPath, $msg, FILE_APPEND);

    // Respuesta según el entorno
    if ($environment === 'development') {
        // En desarrollo: mostrar detalles para depuración
        die('Error de conexión: ' . $e->getMessage() . ' (Código: ' . $e->getCode() . ')');
    } else {
        // En producción: mensaje genérico
        header('Content-Type: application/json', true, 500);
        echo json_encode([
            'success' => false,
            'error' => 'No se pudo conectar a la base de datos. Por favor contacte al administrador.',
            'codigo' => $e->getCode()
        ]);
        exit;
    }
}

// Función auxiliar para verificar la conexión en cualquier momento
function checkDatabaseConnection($pdo) {
    try {
        $pdo->query('SELECT 1');
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
