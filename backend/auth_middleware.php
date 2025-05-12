<?php
require_once 'config.php';
require_once 'security_utils.php';

class AuthMiddleware {
    private $db;
    private $security;

    public function __construct($db) {
        $this->db = $db;
        $this->security = new SecurityUtils($db);
    }

    public function authenticate() {
        // Obtener token del header
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            throw new Exception('Token no proporcionado');
        }

        $token = $matches[1];
        
        try {
            // Decodificar y validar JWT
            $decoded = validateJWT($token);
            
            // Validar sesión
            $session = $this->security->validateSession(
                $decoded['session_id'],
                $_SERVER['REMOTE_ADDR']
            );

            if (!$session) {
                throw new Exception('Sesión inválida o expirada');
            }

            // Registrar actividad
            $this->security->logActivity(
                $decoded['user_id'],
                'request',
                $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']
            );

            return $decoded;

        } catch (Exception $e) {
            throw new Exception('Token inválido: ' . $e->getMessage());
        }
    }

    public function requireRole($roles) {
        $user = $this->authenticate();
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        if (!in_array($user['rol'], $roles)) {
            throw new Exception('No tiene permisos para acceder a este recurso');
        }

        return $user;
    }

    public function validateCSRF() {
        $headers = getallheaders();
        $csrfToken = $headers['X-CSRF-Token'] ?? '';

        if (!$this->security->validateCSRFToken($csrfToken)) {
            throw new Exception('Token CSRF inválido');
        }
    }

    public function rateLimit($key, $maxRequests = 60, $window = 60) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $cacheKey = "rate_limit:{$ip}:{$key}";

        // Implementar rate limiting usando Redis o similar
        // Por ahora usamos una implementación simple con archivos
        $cacheFile = sys_get_temp_dir() . '/' . md5($cacheKey);
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            
            if ($data['count'] >= $maxRequests && 
                (time() - $data['window_start']) < $window) {
                throw new Exception('Demasiadas solicitudes. Intente más tarde');
            }

            if ((time() - $data['window_start']) >= $window) {
                $data = ['count' => 0, 'window_start' => time()];
            }
        } else {
            $data = ['count' => 0, 'window_start' => time()];
        }

        $data['count']++;
        file_put_contents($cacheFile, json_encode($data));
    }
}

// Función helper para usar el middleware
function requireAuth($roles = null) {
    global $db;
    
    $middleware = new AuthMiddleware($db);
    
    try {
        if ($roles) {
            return $middleware->requireRole($roles);
        }
        return $middleware->authenticate();
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Función helper para validar CSRF
function requireCSRF() {
    global $db;
    
    $middleware = new AuthMiddleware($db);
    
    try {
        $middleware->validateCSRF();
    } catch (Exception $e) {
        http_response_code(403);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Función helper para rate limiting
function requireRateLimit($key, $maxRequests = 60, $window = 60) {
    global $db;
    
    $middleware = new AuthMiddleware($db);
    
    try {
        $middleware->rateLimit($key, $maxRequests, $window);
    } catch (Exception $e) {
        http_response_code(429);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
} 