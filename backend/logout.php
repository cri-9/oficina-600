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
    // Requerir autenticación
    $user = requireAuth();
    
    // Validar CSRF
    requireCSRF();

    $security = new SecurityUtils($db);

    // Obtener token del header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches);
    $token = $matches[1] ?? '';

    if (!empty($token)) {
        try {
            // Decodificar JWT para obtener session_id
            $decoded = validateJWT($token);
            
            // Invalidar sesión
            if (isset($decoded['session_id'])) {
                $security->invalidateSession($decoded['session_id']);
            }

            // Registrar actividad
            $security->logActivity(
                $user['user_id'],
                'logout',
                'Cierre de sesión exitoso'
            );
        } catch (Exception $e) {
            // Ignorar errores de token inválido
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Sesión cerrada exitosamente'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 