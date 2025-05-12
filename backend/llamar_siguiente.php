<?php

require_once 'cors.php';
require_once 'config.php';
require_once 'security_utils.php';
require_once 'auth_middleware.php';

// Permitir solicitudes desde cualquier origen (en producción, puedes restringirlo a dominios específicos)

// Si la solicitud es un preflight (OPTIONS), retornar una respuesta vacía exitosa
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener y validar datos de entrada
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('No se recibieron datos');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }

    // Validar campos requeridos
    if (empty($data['operario_id']) || empty($data['perfil_id']) || empty($data['modulo_id'])) {
        throw new Exception('ID de operario, perfil y módulo son requeridos');
    }

    // Sanitizar entrada
    $operarioId = filter_var($data['operario_id'], FILTER_SANITIZE_NUMBER_INT);
    $perfilId = filter_var($data['perfil_id'], FILTER_SANITIZE_NUMBER_INT);
    $moduloId = filter_var($data['modulo_id'], FILTER_SANITIZE_NUMBER_INT);

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        // Obtener siguiente turno
        $stmt = $pdo->prepare("
            SELECT t.*, m.nombre as modulo_nombre
            FROM turnos t
            JOIN modulos m ON t.id_modulo = m.id
            WHERE t.id_modulo = ? 
            AND t.estado = 'pendiente'
            ORDER BY t.created_at ASC
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([$moduloId]);
        $turno = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$turno) {
            throw new Exception('No hay turnos pendientes');
        }

        // Actualizar estado del turno
        $stmt = $pdo->prepare("
            UPDATE turnos 
            SET estado = 'en_atencion',
                id_usuario = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$operarioId, $turno['id']]);

        // Registrar actividad
        logActivity(
            $operarioId,
            'llamar_turno',
            "Turno {$turno['numero_turno']} llamado en módulo {$turno['modulo_nombre']}"
        );

        // Confirmar transacción
        $pdo->commit();

        // Enviar respuesta exitosa
        echo json_encode([
            'success' => true,
            'turno' => [
                'id' => $turno['id'],
                'numero_turno' => $turno['numero_turno'],
                'tipo_atencion' => $turno['tipo_atencion'],
                'modulo' => $turno['modulo_nombre']
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
