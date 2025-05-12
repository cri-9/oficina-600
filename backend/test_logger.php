<?php
require_once 'config.php';
require_once 'logger.php';

// Función para imprimir resultados de prueba
function printTest($name, $result) {
    echo "Test: $name\n";
    echo "Resultado: " . ($result ? "✅ PASÓ" : "❌ FALLÓ") . "\n";
    echo "----------------------------------------\n";
}

// Función para imprimir datos
function printData($name, $data) {
    echo "Datos de $name:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
    echo "----------------------------------------\n";
}

try {
    echo "Iniciando pruebas del sistema de logging...\n\n";

    // 1. Prueba de logging básico
    $logger = new Logger(true); // Usar formato JSON
    $logger->info('Mensaje de prueba', ['contexto' => 'test']);
    $logger->error('Error de prueba', ['codigo' => 500]);
    $logger->warning('Advertencia de prueba', ['tipo' => 'test']);
    
    printTest("Logging básico", file_exists(LOG_FILE));

    // 2. Prueba de búsqueda de logs
    $logs = $logger->searchLogs('prueba', ['message']);
    printTest("Búsqueda de logs", count($logs) > 0);
    printData("Resultados de búsqueda", $logs);

    // 3. Prueba de filtrado
    $filteredLogs = $logger->getLogs(['level' => 'ERROR']);
    printTest("Filtrado por nivel", count($filteredLogs) > 0);
    printData("Logs filtrados", $filteredLogs);

    // 4. Prueba de exportación
    $jsonExport = $logger->exportLogs('json');
    $csvExport = $logger->exportLogs('csv');
    $xmlExport = $logger->exportLogs('xml');

    printTest("Exportación JSON", !empty($jsonExport));
    printTest("Exportación CSV", !empty($csvExport));
    printTest("Exportación XML", !empty($xmlExport));

    // 5. Prueba de métricas de rendimiento
    $metrics = $logger->getPerformanceMetrics();
    printTest("Métricas de rendimiento", isset($metrics['total_logs']));
    printData("Métricas", $metrics);

    // 6. Prueba de estadísticas
    $stats = $logger->getLogStats('1h');
    printTest("Estadísticas", isset($stats['total']));
    printData("Estadísticas", $stats);

    // 7. Prueba de logging con diferentes niveles
    $logger->debug('Mensaje de debug', ['nivel' => 'debug']);
    $logger->info('Mensaje informativo', ['nivel' => 'info']);
    $logger->notice('Mensaje de noticia', ['nivel' => 'notice']);
    $logger->warning('Mensaje de advertencia', ['nivel' => 'warning']);
    $logger->error('Mensaje de error', ['nivel' => 'error']);
    $logger->critical('Mensaje crítico', ['nivel' => 'critical']);
    $logger->alert('Mensaje de alerta', ['nivel' => 'alert']);
    $logger->emergency('Mensaje de emergencia', ['nivel' => 'emergency']);

    $allLevels = $logger->getLogs();
    printTest("Todos los niveles de log", count($allLevels) >= 7);
    printData("Logs por nivel", $allLevels);

    // 8. Prueba de contexto complejo
    $complexContext = [
        'usuario' => [
            'id' => 123,
            'nombre' => 'Test User',
            'roles' => ['admin', 'user']
        ],
        'sistema' => [
            'version' => '1.0.0',
            'ambiente' => 'testing'
        ],
        'datos' => [
            'timestamp' => time(),
            'ip' => '127.0.0.1'
        ]
    ];
    
    $logger->info('Mensaje con contexto complejo', $complexContext);
    $complexLogs = $logger->searchLogs('contexto complejo');
    printTest("Logging con contexto complejo", count($complexLogs) > 0);
    printData("Log con contexto complejo", $complexLogs);

    // 9. Prueba de rendimiento
    $startTime = microtime(true);
    for ($i = 0; $i < 100; $i++) {
        $logger->info("Mensaje de prueba $i", ['iteracion' => $i]);
    }
    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    
    printTest("Rendimiento de escritura", $executionTime < 1.0);
    printData("Tiempo de ejecución", ['segundos' => $executionTime]);

    // 10. Prueba de rotación de logs
    $logger->clearLogs(); // Limpiar logs existentes
    for ($i = 0; $i < 1000; $i++) {
        $logger->info("Mensaje para prueba de rotación $i");
    }
    
    $files = glob(dirname(LOG_FILE) . '/*');
    printTest("Rotación de logs", count($files) > 1);
    printData("Archivos de log", $files);

    echo "\nTodas las pruebas completadas.\n";

} catch (Exception $e) {
    echo "Error durante las pruebas: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 