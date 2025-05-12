<?php
// Verificar extensiones requeridas
$requiredExtensions = ['zip', 'json', 'pdo', 'pdo_mysql'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    die("Error: Las siguientes extensiones de PHP están faltando: " . implode(', ', $missingExtensions) . "\n" .
        "Por favor, habilítelas en su archivo php.ini y reinicie el servidor Apache.");
}

// Verificar que el archivo websocket_server.php existe
if (!file_exists(__DIR__ . '/websocket_server.php')) {
    die("Error: El archivo websocket_server.php no existe en " . __DIR__);
}

// Verificar que el directorio vendor existe
if (!file_exists(__DIR__ . '/vendor')) {
    die("Error: El directorio vendor no existe. Ejecute 'composer install' primero.");
}

// Verificar que el archivo autoload.php existe
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die("Error: El archivo vendor/autoload.php no existe. Ejecute 'composer install' primero.");
}

// Cargar el autoloader de Composer
require __DIR__ . '/vendor/autoload.php';

// Iniciar el servidor WebSocket
try {
    require __DIR__ . '/websocket_server.php';
} catch (Exception $e) {
    die("Error al iniciar el servidor WebSocket: " . $e->getMessage() . "\n");
}

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;

$loop = Factory::create();

// Crear el servidor WebSocket
$webSocket = new TurnoWebSocket();
$wsServer = new WsServer($webSocket);

// Configurar el servidor HTTP con CORS
$httpServer = new HttpServer(
    $wsServer
);

// Crear el socket del servidor
$socket = new Server('0.0.0.0:8080', $loop);

// Crear el servidor IO
$server = new IoServer(
    $httpServer,
    $socket,
    $loop
);

echo "Servidor WebSocket iniciado en ws://0.0.0.0:8080\n";
$loop->run(); 