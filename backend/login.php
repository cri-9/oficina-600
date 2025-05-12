<?php
require_once 'config.php';
require_once 'security_utils.php';



include 'conexion.php';

$datos = json_decode(file_get_contents("php://input"), true);

$usuario = $datos['usuario'];
$clave = $datos['clave'];

$security = new SecurityUtils($conn);

try {
    // Validar y sanitizar entrada
    $username = $security->sanitizeInput($usuario);
    $password = $clave;
    $csrfToken = $datos['csrf_token'] ?? '';

    if (empty($username) || empty($password)) {
        throw new Exception('Usuario y contraseña son requeridos');
    }

    // Validar token CSRF
    if (!$security->validateCSRFToken($csrfToken)) {
        throw new Exception('Token CSRF inválido');
    }

    // Verificar IP en lista negra
    $ip = $_SERVER['REMOTE_ADDR'];
    if ($security->isIPBlacklisted($ip)) {
        throw new Exception('Acceso denegado');
    }

    // Verificar intentos de login
    if (!$security->checkLoginAttempts($username, $ip)) {
        throw new Exception('Demasiados intentos fallidos. Intente más tarde');
    }

    // Buscar usuario
    $stmt = $conn->prepare("
        SELECT id, username, password, rol, account_locked_until 
        FROM usuarios 
        WHERE username = ? 
        LIMIT 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $security->recordLoginAttempt($username, $ip, false);
        throw new Exception('Credenciales inválidas');
    }

    // Verificar si la cuenta está bloqueada
    if ($user['account_locked_until'] && strtotime($user['account_locked_until']) > time()) {
        throw new Exception('Cuenta bloqueada temporalmente');
    }

    // Verificar contraseña
    if (!$security->verifyPassword($password, $user['password'])) {
        $security->recordLoginAttempt($username, $ip, false);
        
        // Actualizar intentos fallidos
        $stmt = $conn->prepare("
            UPDATE usuarios 
            SET failed_login_attempts = failed_login_attempts + 1,
                account_locked_until = CASE 
                    WHEN failed_login_attempts + 1 >= ? 
                    THEN DATE_ADD(NOW(), INTERVAL ? MINUTE)
                    ELSE NULL 
                END
            WHERE id = ?
        ");
        $stmt->execute([
            $security->config['max_login_attempts'],
            $security->config['lockout_duration'],
            $user['id']
        ]);

        throw new Exception('Credenciales inválidas');
    }

    // Login exitoso
    $security->recordLoginAttempt($username, $ip, true);

    // Resetear intentos fallidos
    $stmt = $conn->prepare("
        UPDATE usuarios 
        SET failed_login_attempts = 0,
            account_locked_until = NULL,
            last_login = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$user['id']]);

    // Crear sesión
    $sessionId = $security->createSession($user['id'], $ip, $_SERVER['HTTP_USER_AGENT']);

    // Generar JWT
    $token = generateJWT([
        'user_id' => $user['id'],
        'username' => $user['username'],
        'rol' => $user['rol'],
        'session_id' => $sessionId
    ]);

    // Registrar actividad
    $security->logActivity($user['id'], 'login', 'Login exitoso');

    // Enviar respuesta
    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'rol' => $user['rol']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
