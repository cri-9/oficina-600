<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'cors.php';
require_once 'conexion.php';

// Habilitar reporte de errores para debugging


header('Content-Type: application/json');

try {
    // Turnos actuales (pendientes y en atención)
    $stmtActual = $pdo->prepare("
        SELECT t.id,t.numero_turno, t.id_modulo, t.estado, t.tipo_atencion, m.nombre as modulo_nombre 
        FROM turnos t 
        LEFT JOIN modulos m ON t.id_modulo = m.id 
        WHERE t.estado IN ('pendiente','en_atencion') 
        ORDER BY t.id DESC
    ");
    $stmtActual->execute();
    $turnosActuales = $stmtActual->fetchAll(PDO::FETCH_ASSOC);

    if (!$turnosActuales) {
        $turnosActuales = [];
    }

    // Últimos 5 turnos finalizados
    $stmtUltimos = $pdo->prepare("
        SELECT t.id,t.numero_turno, t.id_modulo, t.tipo_atencion, m.nombre as modulo_nombre 
        FROM turnos t 
        LEFT JOIN modulos m ON t.id_modulo = m.id 
        WHERE t.estado = 'finalizado' 
        ORDER BY t.id DESC 
        LIMIT 5
    ");
    $stmtUltimos->execute();
    $ultimosTurnos = $stmtUltimos->fetchAll(PDO::FETCH_ASSOC);

    if (!$ultimosTurnos) {
        $ultimosTurnos = [];
    }

    // Validar valores nulos en el campo 'numero_turno'
    $turnosActuales = array_map(function($turno) {
        $turno['numero_turno'] = $turno['numero_turno'] ?? 'Sin número';
        return $turno;
    }, $turnosActuales);

    $ultimosTurnos = array_map(function($turno) {
        $turno['numero_turno'] = $turno['numero_turno'] ?? 'Sin número';
        return $turno;
    }, $ultimosTurnos);

    echo json_encode([
        'success' => true,
        'turnosActuales' => $turnosActuales,
        'ultimosTurnos' => $ultimosTurnos
    ]);

} catch (PDOException $e) {
    error_log('Error al obtener turnos: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener turnos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Error general: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error general: ' . $e->getMessage()
    ]);
}
