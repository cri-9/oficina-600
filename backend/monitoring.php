<?php
require_once 'config.php';
require_once 'security_utils.php';

class SystemMonitor {
    private $db;
    private $logFile;
    private $alertEmail;

    public function __construct($db) {
        $this->db = $db;
        $this->logFile = LOG_FILE;
        $this->alertEmail = ALERT_EMAIL;
    }

    public function checkSystemHealth() {
        $health = [
            'status' => 'healthy',
            'checks' => [],
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Verificar conexión a la base de datos
        try {
            $this->db->query('SELECT 1');
            $health['checks']['database'] = [
                'status' => 'ok',
                'message' => 'Conexión a la base de datos establecida'
            ];
        } catch (Exception $e) {
            $health['checks']['database'] = [
                'status' => 'error',
                'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()
            ];
            $health['status'] = 'unhealthy';
        }

        // Verificar espacio en disco
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsed = $diskTotal - $diskFree;
        $diskUsagePercent = ($diskUsed / $diskTotal) * 100;

        $health['checks']['disk'] = [
            'status' => $diskUsagePercent > 90 ? 'warning' : 'ok',
            'message' => sprintf(
                'Uso de disco: %.2f%% (%.2f GB libre de %.2f GB)',
                $diskUsagePercent,
                $diskFree / 1024 / 1024 / 1024,
                $diskTotal / 1024 / 1024 / 1024
            )
        ];

        if ($diskUsagePercent > 90) {
            $health['status'] = 'unhealthy';
        }

        // Verificar memoria del sistema
        $memInfo = file_get_contents('/proc/meminfo');
        preg_match_all('/^(\w+):\s+(\d+)/m', $memInfo, $matches);
        $memInfo = array_combine($matches[1], $matches[2]);

        $memTotal = $memInfo['MemTotal'];
        $memFree = $memInfo['MemFree'];
        $memUsed = $memTotal - $memFree;
        $memUsagePercent = ($memUsed / $memTotal) * 100;

        $health['checks']['memory'] = [
            'status' => $memUsagePercent > 90 ? 'warning' : 'ok',
            'message' => sprintf(
                'Uso de memoria: %.2f%% (%.2f MB libre de %.2f MB)',
                $memUsagePercent,
                $memFree / 1024,
                $memTotal / 1024
            )
        ];

        if ($memUsagePercent > 90) {
            $health['status'] = 'unhealthy';
        }

        // Verificar carga del CPU
        $loadAvg = sys_getloadavg();
        $cpuCores = (int)shell_exec('nproc');
        $loadPercent = ($loadAvg[0] / $cpuCores) * 100;

        $health['checks']['cpu'] = [
            'status' => $loadPercent > 80 ? 'warning' : 'ok',
            'message' => sprintf(
                'Carga del CPU: %.2f%% (%.2f de %d cores)',
                $loadPercent,
                $loadAvg[0],
                $cpuCores
            )
        ];

        if ($loadPercent > 80) {
            $health['status'] = 'unhealthy';
        }

        // Verificar logs de error
        $errorLog = file_get_contents($this->logFile);
        $errorCount = substr_count($errorLog, '[ERROR]');
        $warningCount = substr_count($errorLog, '[WARNING]');

        $health['checks']['logs'] = [
            'status' => ($errorCount > 0 || $warningCount > 0) ? 'warning' : 'ok',
            'message' => sprintf(
                'Errores: %d, Advertencias: %d',
                $errorCount,
                $warningCount
            )
        ];

        if ($errorCount > 0) {
            $health['status'] = 'unhealthy';
        }

        // Verificar sesiones activas
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM sesiones 
                WHERE expires_at > NOW()
            ");
            $stmt->execute();
            $activeSessions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            $health['checks']['sessions'] = [
                'status' => 'ok',
                'message' => sprintf('Sesiones activas: %d', $activeSessions)
            ];
        } catch (Exception $e) {
            $health['checks']['sessions'] = [
                'status' => 'error',
                'message' => 'Error al verificar sesiones: ' . $e->getMessage()
            ];
            $health['status'] = 'unhealthy';
        }

        // Verificar WebSocket
        try {
            $client = new \WebSocket\Client("ws://" . WS_HOST . ":" . WS_PORT);
            $client->send(json_encode(['type' => 'ping']));
            $response = $client->receive();
            $client->close();

            $health['checks']['websocket'] = [
                'status' => 'ok',
                'message' => 'WebSocket respondiendo correctamente'
            ];
        } catch (Exception $e) {
            $health['checks']['websocket'] = [
                'status' => 'error',
                'message' => 'Error en WebSocket: ' . $e->getMessage()
            ];
            $health['status'] = 'unhealthy';
        }

        // Enviar alerta si el sistema no está saludable
        if ($health['status'] === 'unhealthy') {
            $this->sendAlert($health);
        }

        return $health;
    }

    private function sendAlert($health) {
        $subject = "Alerta: Sistema no saludable - " . date('Y-m-d H:i:s');
        $message = "El sistema ha reportado problemas:\n\n";
        
        foreach ($health['checks'] as $check => $data) {
            if ($data['status'] !== 'ok') {
                $message .= sprintf(
                    "%s: %s - %s\n",
                    ucfirst($check),
                    $data['status'],
                    $data['message']
                );
            }
        }

        // Enviar email de alerta
        if (defined('SMTP_HOST')) {
            // Implementar envío de email usando SMTP
            // mail($this->alertEmail, $subject, $message);
        }

        // Registrar en el log
        error_log($message);
    }

    public function getMetrics() {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'disk' => [
                'free' => disk_free_space('/'),
                'total' => disk_total_space('/')
            ],
            'memory' => [
                'total' => $this->getMemoryTotal(),
                'free' => $this->getMemoryFree(),
                'used' => $this->getMemoryUsed()
            ],
            'cpu' => [
                'load' => sys_getloadavg(),
                'cores' => (int)shell_exec('nproc')
            ],
            'sessions' => $this->getActiveSessions(),
            'requests' => $this->getRequestStats()
        ];
    }

    private function getMemoryTotal() {
        $memInfo = file_get_contents('/proc/meminfo');
        preg_match('/^MemTotal:\s+(\d+)/m', $memInfo, $matches);
        return $matches[1] * 1024;
    }

    private function getMemoryFree() {
        $memInfo = file_get_contents('/proc/meminfo');
        preg_match('/^MemFree:\s+(\d+)/m', $memInfo, $matches);
        return $matches[1] * 1024;
    }

    private function getMemoryUsed() {
        return $this->getMemoryTotal() - $this->getMemoryFree();
    }

    private function getActiveSessions() {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM sesiones 
            WHERE expires_at > NOW()
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    private function getRequestStats() {
        // Implementar estadísticas de requests según tu sistema
        return [
            'total' => 0,
            'success' => 0,
            'error' => 0,
            'avg_response_time' => 0
        ];
    }
}

// Ejecutar monitoreo si se llama directamente
if (php_sapi_name() === 'cli') {
    $monitor = new SystemMonitor($db);
    $health = $monitor->checkSystemHealth();
    echo json_encode($health, JSON_PRETTY_PRINT);
} 