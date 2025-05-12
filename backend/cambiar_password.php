<?php
require_once 'config.php';
require_once 'security_utils.php';
require_once 'auth_middleware.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    // Aplicar rate limiting
    requireRateLimit('cambiar_password', 5, 3600); // 5 intentos por hora

    $data = json_decode(file_get_contents('php://input'), true);
    $security = new SecurityUtils($db);

    // Validar y sanitizar entrada
    $token = $security->sanitizeInput($data['token'] ?? '');
    $newPassword = $data['new_password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';

    if (empty($token) || empty($newPassword) || empty($confirmPassword)) {
        throw new Exception('Todos los campos son requeridos');
    }

    if ($newPassword !== $confirmPassword) {
        throw new Exception('Las contraseñas no coinciden');
    }

    // Validar token
    $tokenData = $security->validatePasswordResetToken($token);
    if (!$tokenData) {
        throw new Exception('Token inválido o expirado');
    }

    // Validar contraseña
    if (!$security->validatePassword($newPassword)) {
        throw new Exception('La contraseña no cumple con los requisitos de seguridad');
    }

    // Iniciar transacción
    $db->beginTransaction();

    try {
        // Actualizar contraseña
        $hashedPassword = $security->hashPassword($newPassword);
        $stmt = $db->prepare("
            UPDATE usuarios 
            SET password = ?,
                password_changed_at = NOW(),
                failed_login_attempts = 0,
                account_locked_until = NULL
            WHERE id = ?
        ");
        $stmt->execute([$hashedPassword, $tokenData['user_id']]);

        // Marcar token como usado
        $security->markPasswordResetTokenAsUsed($token);

        // Invalidar todas las sesiones existentes
        $stmt = $db->prepare("DELETE FROM sesiones WHERE user_id = ?");
        $stmt->execute([$tokenData['user_id']]);

        // Registrar actividad
        $security->logActivity(
            $tokenData['user_id'],
            'cambio_password',
            'Cambio de contraseña exitoso'
        );

        // Confirmar transacción
        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Contraseña actualizada exitosamente'
        ]);

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 