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
    requireRateLimit('recuperar_password', 3, 3600); // 3 intentos por hora

    $data = json_decode(file_get_contents('php://input'), true);
    $security = new SecurityUtils($db);

    // Validar y sanitizar entrada
    $email = $security->sanitizeInput($data['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }

    // Buscar usuario
    $stmt = $db->prepare("
        SELECT id, username, email 
        FROM usuarios 
        WHERE email = ? 
        AND estado = 'activo'
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // No revelar si el email existe o no
        echo json_encode([
            'success' => true,
            'message' => 'Si el email existe en nuestra base de datos, recibirás instrucciones para recuperar tu contraseña'
        ]);
        exit;
    }

    // Generar token de recuperación
    $token = $security->generatePasswordResetToken($user['id']);

    // Registrar actividad
    $security->logActivity(
        $user['id'],
        'solicitud_recuperacion',
        'Solicitud de recuperación de contraseña'
    );

    // Enviar email (implementar según el sistema de email que uses)
    $resetLink = ALLOWED_ORIGIN . "/reset-password?token=" . $token;
    $subject = "Recuperación de contraseña";
    $message = "
        Hola {$user['username']},

        Has solicitado recuperar tu contraseña. Para continuar, haz clic en el siguiente enlace:

        {$resetLink}

        Este enlace expirará en 1 hora.

        Si no solicitaste este cambio, puedes ignorar este mensaje.

        Saludos,
        El equipo de Oficina 600
    ";

    // Implementar el envío de email según tu sistema
    // mail($user['email'], $subject, $message);

    echo json_encode([
        'success' => true,
        'message' => 'Si el email existe en nuestra base de datos, recibirás instrucciones para recuperar tu contraseña'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 