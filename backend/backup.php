<?php

require_once 'config.php';
require_once 'security_utils.php';

class SystemBackup {
    private $db;
    private $backupPath;
    private $retentionDays;

    public function __construct($db) {
        $this->db = $db;
        $this->backupPath = BACKUP_PATH;
        $this->retentionDays = BACKUP_RETENTION_DAYS;

        // Crear directorio de backups si no existe
        if (!file_exists($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    public function createBackup() {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = $this->backupPath . "/backup_{$timestamp}.sql";
            $backupLog = $this->backupPath . "/backup_{$timestamp}.log";

            // Obtener lista de tablas
            $tables = $this->getTables();

            // Crear archivo de backup
            $output = "-- Backup generado el " . date('Y-m-d H:i:s') . "\n\n";
            $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            // Backup de estructura y datos de cada tabla
            foreach ($tables as $table) {
                $output .= $this->backupTable($table);
            }

            $output .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

            // Guardar backup
            file_put_contents($backupFile, $output);

            // Comprimir backup
            $zipFile = $backupFile . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
                $zip->addFile($backupFile, basename($backupFile));
                $zip->close();
                unlink($backupFile); // Eliminar archivo SQL original
            }

            // Registrar log
            $log = "Backup completado exitosamente\n";
            $log .= "Archivo: " . basename($zipFile) . "\n";
            $log .= "Tamaño: " . $this->formatSize(filesize($zipFile)) . "\n";
            file_put_contents($backupLog, $log);

            // Limpiar backups antiguos
            $this->cleanupOldBackups();

            return [
                'success' => true,
                'file' => basename($zipFile),
                'size' => $this->formatSize(filesize($zipFile)),
                'timestamp' => $timestamp
            ];

        } catch (Exception $e) {
            error_log("Error en backup: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function getTables() {
        $tables = [];
        $result = $this->db->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        return $tables;
    }

    private function backupTable($table) {
        $output = "-- Estructura de tabla `{$table}`\n";
        
        // Obtener estructura de la tabla
        $result = $this->db->query("SHOW CREATE TABLE `{$table}`");
        $row = $result->fetch(PDO::FETCH_NUM);
        $output .= $row[1] . ";\n\n";

        // Obtener datos de la tabla
        $result = $this->db->query("SELECT * FROM `{$table}`");
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $output .= "-- Datos de tabla `{$table}`\n";
            $output .= "INSERT INTO `{$table}` VALUES\n";

            $values = [];
            foreach ($rows as $row) {
                $rowValues = [];
                foreach ($row as $value) {
                    $rowValues[] = $value === null ? 'NULL' : $this->db->quote($value);
                }
                $values[] = '(' . implode(',', $rowValues) . ')';
            }

            $output .= implode(",\n", $values) . ";\n\n";
        }

        return $output;
    }

    private function cleanupOldBackups() {
        $files = glob($this->backupPath . "/*.zip");
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= $this->retentionDays * 86400) {
                    unlink($file);
                    // Eliminar también el archivo de log correspondiente
                    $logFile = str_replace('.zip', '.log', $file);
                    if (file_exists($logFile)) {
                        unlink($logFile);
                    }
                }
            }
        }
    }

    private function formatSize($size) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }

    public function restoreBackup($backupFile) {
        try {
            if (!file_exists($backupFile)) {
                throw new Exception("Archivo de backup no encontrado");
            }

            // Descomprimir backup
            $zip = new ZipArchive();
            if ($zip->open($backupFile) === TRUE) {
                $zip->extractTo($this->backupPath);
                $zip->close();
                $sqlFile = $this->backupPath . '/' . basename($backupFile, '.zip');
            } else {
                throw new Exception("Error al descomprimir el archivo de backup");
            }

            // Leer y ejecutar queries
            $queries = file_get_contents($sqlFile);
            $queries = explode(';', $queries);

            $this->db->beginTransaction();

            try {
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        $this->db->exec($query);
                    }
                }

                $this->db->commit();

                // Limpiar archivos temporales
                unlink($sqlFile);

                return [
                    'success' => true,
                    'message' => 'Backup restaurado exitosamente'
                ];

            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Error en restauración: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function listBackups() {
        $backups = [];
        $files = glob($this->backupPath . "/*.zip");

        foreach ($files as $file) {
            $backups[] = [
                'file' => basename($file),
                'size' => $this->formatSize(filesize($file)),
                'date' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }

        // Ordenar por fecha, más reciente primero
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $backups;
    }
}

// Ejecutar backup si se llama directamente
if (php_sapi_name() === 'cli') {
    $backup = new SystemBackup($db);
    $result = $backup->createBackup();
    echo json_encode($result, JSON_PRETTY_PRINT);
} 