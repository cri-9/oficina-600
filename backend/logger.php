<?php
require_once 'config.php';

class Logger {
    private $logFile;
    private $maxSize;
    private $maxFiles;
    private $logLevel;
    private $jsonFormat;
    private $performanceMetrics;

    public function __construct($jsonFormat = false) {
        $this->logFile = LOG_FILE;
        $this->maxSize = LOG_MAX_SIZE;
        $this->maxFiles = LOG_MAX_FILES;
        $this->logLevel = LOG_LEVEL;
        $this->jsonFormat = $jsonFormat;
        $this->performanceMetrics = [
            'total_logs' => 0,
            'start_time' => microtime(true),
            'last_rotation' => null,
            'write_operations' => 0,
            'read_operations' => 0
        ];

        // Crear directorio de logs si no existe
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    public function log($level, $message, $context = []) {
        // Verificar nivel de log
        if (!$this->shouldLog($level)) {
            return;
        }

        // Formatear mensaje
        $logEntry = $this->formatLogEntry($level, $message, $context);

        // Rotar logs si es necesario
        $this->rotateLogs();

        // Escribir log
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }

    public function emergency($message, $context = []) {
        $this->log('EMERGENCY', $message, $context);
    }

    public function alert($message, $context = []) {
        $this->log('ALERT', $message, $context);
    }

    public function critical($message, $context = []) {
        $this->log('CRITICAL', $message, $context);
    }

    public function error($message, $context = []) {
        $this->log('ERROR', $message, $context);
    }

    public function warning($message, $context = []) {
        $this->log('WARNING', $message, $context);
    }

    public function notice($message, $context = []) {
        $this->log('NOTICE', $message, $context);
    }

    public function info($message, $context = []) {
        $this->log('INFO', $message, $context);
    }

    public function debug($message, $context = []) {
        $this->log('DEBUG', $message, $context);
    }

    private function shouldLog($level) {
        $levels = [
            'DEBUG' => 0,
            'INFO' => 1,
            'NOTICE' => 2,
            'WARNING' => 3,
            'ERROR' => 4,
            'CRITICAL' => 5,
            'ALERT' => 6,
            'EMERGENCY' => 7
        ];

        return $levels[$level] >= $levels[$this->logLevel];
    }

    private function formatLogEntry($level, $message, $context) {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $requestId = uniqid();
        $userId = $_SESSION['user_id'] ?? 'guest';
        $executionTime = microtime(true) - $this->performanceMetrics['start_time'];

        if ($this->jsonFormat) {
            $logEntry = [
                'timestamp' => $timestamp,
                'level' => $level,
                'request_id' => $requestId,
                'ip' => $ip,
                'user_id' => $userId,
                'message' => $this->interpolate($message, $context),
                'context' => $context,
                'execution_time' => $executionTime,
                'memory_usage' => memory_get_usage(true),
                'url' => $_SERVER['REQUEST_URI'] ?? null,
                'method' => $_SERVER['REQUEST_METHOD'] ?? null
            ];
            return json_encode($logEntry) . "\n";
        }

        // Formatear contexto
        $contextStr = !empty($context) ? json_encode($context) : '';

        // Formatear mensaje
        $formattedMessage = $this->interpolate($message, $context);

        return sprintf(
            "[%s] [%s] [%s] [%s] [%s] %s %s\n",
            $timestamp,
            $level,
            $requestId,
            $ip,
            $userId,
            $formattedMessage,
            $contextStr
        );
    }

    private function interpolate($message, $context) {
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        return strtr($message, $replace);
    }

    private function rotateLogs() {
        if (!file_exists($this->logFile)) {
            return;
        }

        if (filesize($this->logFile) < $this->maxSize) {
            return;
        }

        // Rotar archivos existentes
        for ($i = $this->maxFiles - 1; $i >= 1; $i--) {
            $oldFile = $this->logFile . '.' . $i;
            $newFile = $this->logFile . '.' . ($i + 1);

            if (file_exists($oldFile)) {
                if ($i === $this->maxFiles - 1) {
                    unlink($oldFile);
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }

        // Rotar archivo actual
        rename($this->logFile, $this->logFile . '.1');
    }

    public function getLogs($filters = [], $limit = 100, $offset = 0) {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $logs = [];
        $handle = fopen($this->logFile, 'r');
        $this->performanceMetrics['read_operations']++;
        
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $logEntry = $this->parseLogLine($line);
                
                if ($logEntry && $this->matchesFilters($logEntry, $filters)) {
                    $logs[] = $logEntry;
                }
            }
            fclose($handle);
        }

        // Ordenar por timestamp descendente
        usort($logs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        // Aplicar paginación
        return array_slice($logs, $offset, $limit);
    }

    private function parseLogLine($line) {
        if ($this->jsonFormat) {
            return json_decode($line, true);
        }

        if (preg_match('/^\[(.*?)\] \[(.*?)\] \[(.*?)\] \[(.*?)\] \[(.*?)\] (.*?)(\{.*\})?$/', $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'request_id' => $matches[3],
                'ip' => $matches[4],
                'user_id' => $matches[5],
                'message' => $matches[6],
                'context' => isset($matches[7]) ? json_decode($matches[7], true) : []
            ];
        }
        return null;
    }

    private function matchesFilters($logEntry, $filters) {
        foreach ($filters as $key => $value) {
            if (!isset($logEntry[$key]) || $logEntry[$key] !== $value) {
                return false;
            }
        }
        return true;
    }

    public function exportLogs($format = 'json', $filters = []) {
        $logs = $this->getLogs($filters);
        
        switch ($format) {
            case 'json':
                return json_encode($logs, JSON_PRETTY_PRINT);
            
            case 'csv':
                $output = fopen('php://temp', 'r+');
                // Escribir encabezados
                $headers = ['timestamp', 'level', 'request_id', 'ip', 'user_id', 'message'];
                fputcsv($output, $headers, ',', '"', '\\');
                
                foreach ($logs as $log) {
                    $row = [
                        $log['timestamp'],
                        $log['level'],
                        $log['request_id'],
                        $log['ip'],
                        $log['user_id'],
                        $log['message']
                    ];
                    if (isset($log['context'])) {
                        $row[] = json_encode($log['context']);
                    }
                    fputcsv($output, $row, ',', '"', '\\');
                }
                rewind($output);
                $csv = stream_get_contents($output);
                fclose($output);
                return $csv;
            
            case 'xml':
                $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><logs></logs>');
                foreach ($logs as $log) {
                    $entry = $xml->addChild('log');
                    foreach ($log as $key => $value) {
                        if (is_array($value)) {
                            $entry->addChild($key, json_encode($value));
                        } else {
                            $entry->addChild($key, (string)$value);
                        }
                    }
                }
                return $xml->asXML();
            
            default:
                throw new Exception('Formato de exportación no soportado');
        }
    }

    public function getPerformanceMetrics() {
        $metrics = $this->performanceMetrics;
        $metrics['uptime'] = microtime(true) - $metrics['start_time'];
        $metrics['average_write_time'] = $metrics['write_operations'] > 0 
            ? $metrics['uptime'] / $metrics['write_operations'] 
            : 0;
        return $metrics;
    }

    public function searchLogs($query, $fields = ['message', 'context'], $limit = 100) {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $logs = [];
        $handle = fopen($this->logFile, 'r');
        $this->performanceMetrics['read_operations']++;
        
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $logEntry = $this->parseLogLine($line);
                
                if ($logEntry && $this->matchesSearch($logEntry, $query, $fields)) {
                    $logs[] = $logEntry;
                    if (count($logs) >= $limit) {
                        break;
                    }
                }
            }
            fclose($handle);
        }

        return $logs;
    }

    private function matchesSearch($logEntry, $query, $fields) {
        foreach ($fields as $field) {
            if (isset($logEntry[$field])) {
                $value = is_array($logEntry[$field]) 
                    ? json_encode($logEntry[$field]) 
                    : $logEntry[$field];
                if (stripos($value, $query) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getLogStats($timeRange = '24h') {
        if (!file_exists($this->logFile)) {
            return [
                'total' => 0,
                'by_level' => [],
                'by_hour' => [],
                'by_user' => [],
                'by_ip' => [],
                'error_rate' => 0,
                'average_response_time' => 0
            ];
        }

        $stats = [
            'total' => 0,
            'by_level' => [],
            'by_hour' => [],
            'by_user' => [],
            'by_ip' => [],
            'error_count' => 0,
            'total_response_time' => 0
        ];

        $timeLimit = strtotime("-{$timeRange}");
        $handle = fopen($this->logFile, 'r');
        
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $logEntry = $this->parseLogLine($line);
                
                if ($logEntry && strtotime($logEntry['timestamp']) >= $timeLimit) {
                    $stats['total']++;
                    
                    // Estadísticas por nivel
                    $level = $logEntry['level'];
                    $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
                    
                    // Estadísticas por hora
                    $hour = date('H', strtotime($logEntry['timestamp']));
                    $stats['by_hour'][$hour] = ($stats['by_hour'][$hour] ?? 0) + 1;
                    
                    // Estadísticas por usuario
                    $userId = $logEntry['user_id'];
                    $stats['by_user'][$userId] = ($stats['by_user'][$userId] ?? 0) + 1;
                    
                    // Estadísticas por IP
                    $ip = $logEntry['ip'];
                    $stats['by_ip'][$ip] = ($stats['by_ip'][$ip] ?? 0) + 1;
                    
                    // Contar errores
                    if (in_array($level, ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'])) {
                        $stats['error_count']++;
                    }
                    
                    // Tiempo de respuesta
                    if (isset($logEntry['execution_time'])) {
                        $stats['total_response_time'] += $logEntry['execution_time'];
                    }
                }
            }
            fclose($handle);
        }

        // Calcular métricas finales
        $stats['error_rate'] = $stats['total'] > 0 
            ? ($stats['error_count'] / $stats['total']) * 100 
            : 0;
        $stats['average_response_time'] = $stats['total'] > 0 
            ? $stats['total_response_time'] / $stats['total'] 
            : 0;

        return $stats;
    }

    public function clearLogs() {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }

        // Eliminar archivos rotados
        for ($i = 1; $i <= $this->maxFiles; $i++) {
            $file = $this->logFile . '.' . $i;
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}

// Crear instancia global del logger
$logger = new Logger();

// Función helper para logging
function log_message($level, $message, $context = []) {
    global $logger;
    $logger->log($level, $message, $context);
} 