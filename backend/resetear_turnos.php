<?php


require_once 'config.php';
require_once 'cors.php';
require_once 'conexion.php';
require_once 'security_utils.php';
require_once 'auth_middleware.php';
require_once 'websocket_client.php';
require_once __DIR__ . '/websocket_client.php';

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

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        // Primero, obtener todos los turnos en atención
        $stmt = $pdo->query("SELECT COUNT(*) FROM turnos WHERE estado = 'en_atencion'");
        $turnosEnAtencion = $stmt->fetchColumn();
        
        error_log("Turnos encontrados en atención: " . $turnosEnAtencion);

        // Resetear todos los turnos en atención
        $stmt = $pdo->prepare("
            UPDATE turnos 
            SET estado = 'pendiente',
                id_modulo = NULL,
                fecha_atencion = NULL,
                fecha_fin = NULL
            WHERE estado = 'en_atencion'
        ");
        
        $stmt->execute();
        $turnosReseteados = $stmt->rowCount();
        
        error_log("Turnos reseteados: " . $turnosReseteados);

        // Verificar que no queden turnos en atención
        $stmt = $pdo->query("SELECT COUNT(*) FROM turnos WHERE estado = 'en_atencion'");
        $turnosRestantes = $stmt->fetchColumn();
        
        if ($turnosRestantes > 0) {
            throw new Exception("Aún quedan {$turnosRestantes} turnos en atención después del reset");
        }

        // Confirmar transacción
        $pdo->commit();

        // Preparar mensaje WebSocket
        $mensaje = [
            'type' => 'turnos_reseteados',
            'count' => $turnosReseteados
        ];

        // Enviar mensaje WebSocket
        $client = new WebSocketClient('localhost', 8080);
        $client->send(json_encode($mensaje));
        error_log("Mensaje WebSocket enviado: " . json_encode($mensaje));

        // Preparar respuesta exitosa
        $response = [
            'success' => true,
            'message' => "Se resetearon {$turnosReseteados} turnos correctamente",
            'turnos_reseteados' => $turnosReseteados
        ];

        error_log("Respuesta exitosa: " . print_r($response, true));
        echo json_encode($response, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $pdo->rollBack();
        error_log("Error en transacción: " . $e->getMessage());
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} 