<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'cors.php';
require_once 'conexion.php';
require_once 'security_utils.php';
require_once 'auth_middleware.php';
require_once __DIR__ . '/websocket_client.php';
require_once __DIR__.'/cors.php';

// Asegurar que no haya salida antes del JSON
ob_clean();

// Establecer headers
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener y validar datos de entrada
    $input = file_get_contents('php://input');
    error_log("Input recibido en finalizar_turno: " . $input);
    
    if (empty($input)) {
        throw new Exception('No se recibieron datos');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }

    error_log("Datos decodificados: " . print_r($data, true));

    // Validar campos requeridos
    if (!isset($data['id'])) {
        throw new Exception('ID del turno (campo id) es requerido');
    }

    // Sanitizar entrada
    $idTurno = filter_var($data['id'], FILTER_SANITIZE_NUMBER_INT);
    if ($idTurno === false || $idTurno === '') {
        throw new Exception('ID del turno inválido');
    }

    // Buscar el turno sin importar el estado
    $stmt = $pdo->prepare("
        SELECT t.*, m.nombre as modulo_nombre
        FROM turnos t
        JOIN modulos m ON t.id_modulo = m.id
        WHERE t.id = ?
        LIMIT 1
    ");
    $stmt->execute([$idTurno]);
    $turno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$turno) {
        // Si ni siquiera existe, sigue siendo un error real
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'El turno no existe'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($turno['estado'] === 'finalizado') {
    // Ya está finalizado, es idempotente (éxito true, no fatal)
    error_log("Turno $idTurno ya estaba finalizado.");
    echo json_encode([
        'success' => true,
        'message' => 'Turno ya fue finalizado anteriormente',
        'turno' => [
            'id' => $turno['id'],
            'numero_turno' => $turno['numero_turno'],
            'tipo_atencion' => $turno['tipo_atencion'],
            'modulo' => $turno['modulo_nombre'],
            'estado' => 'finalizado',
            'fecha_finalizado' => $turno['fecha_finalizado'] ?? date('Y-m-d H:i:s')
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
    if ($turno['estado'] !== 'en_atencion') {
        // Si no está en atención ni finalizado, no debe intentar finalizarse
        error_log("Turno $idTurno no se puede finalizar, estado actual: " . $turno['estado']);
        echo json_encode([
            'success' => false,
            'error' => 'El turno no está en atención',
            'turno' => [
                'id' => $turno['id'],
                'numero_turno' => $turno['numero_turno'],
                'tipo_atencion' => $turno['tipo_atencion'],
                'modulo' => $turno['modulo_nombre'],
                'estado' => $turno['estado']
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Iniciar transacción para finalizar turno
    $pdo->beginTransaction();

    try {

        // Actualizar el estado del turno a finalizado y registrar fecha de finalización
        $stmt = $pdo->prepare("
            UPDATE turnos 
            SET estado = 'finalizado',
                fecha_finalizado = NOW(),
                fecha_actualizacion = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$idTurno]);

        // Confirmar transacción
        $pdo->commit();

        // Preparar datos para WebSocket
        $wsData = [
            'type' => 'turno_finalizado',
            'numero_turno' => $turno['numero_turno'],
            'tipo_atencion' => $turno['tipo_atencion'],
            'modulo' => $turno['modulo_nombre'],
            'id' => $turno['id'],
            'id_modulo' => $turno['id_modulo']
        ];

        error_log("Datos WebSocket: " . print_r($wsData, true));

        // Enviar notificación usando el helper
        $result = enviar_websocket_evento($wsData);
        // Si falla, registrar el error
        if (!$result['success']) {
            error_log('[WS-CLIENT-ERROR] ' . $result['response']);
        }

        // Preparar respuesta exitosa
        $response = [
            'success' => true,
            'message' => 'Turno finalizado exitosamente',
            'turno' => [
                'id' => $turno['id'],
                'numero_turno' => $turno['numero_turno'],
                'tipo_atencion' => $turno['tipo_atencion'],
                'modulo' => $turno['modulo_nombre'],
                'estado' => 'finalizado',
                'fecha_finalizado' => date('Y-m-d H:i:s')
            ]
        ];

        error_log("Respuesta exitosa: " . print_r($response, true));
        echo json_encode($response, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $pdo->rollBack();
        error_log("Error en transacción: " . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    

} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
