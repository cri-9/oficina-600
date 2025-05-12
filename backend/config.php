<?php
// Configuración de seguridad
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Configuración de zona horaria
date_default_timezone_set('America/Santiago');

// Configuración de CORS
define('ALLOWED_ORIGIN', 'http://localhost:5173'); // Puerto de desarrollo de Vite
define('ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('ALLOWED_HEADERS', 'Content-Type, Authorization, X-Requested-With');

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'oficina_600');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuración de JWT
define('JWT_SECRET', 'tu_clave_secreta_aqui');
define('TOKEN_EXPIRATION', 3600); // 1 hora

// Configuración de rate limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_WINDOW', 60); // 60 segundos
define('RATE_LIMIT_MAX_REQUESTS', 60); // 60 solicitudes por ventana

// Configuración de WebSocket
define('WS_HOST', 'localhost');
define('WS_PORT', 8080);

// Configuración de logging
define('LOG_ENABLED', true);
define('LOG_FILE', __DIR__ . '/logs/app.log');
define('LOG_LEVEL', 'DEBUG'); // DEBUG, INFO, WARNING, ERROR

// Función para validar origen
function isAllowedOrigin($origin) {
    return in_array($origin, ALLOWED_ORIGINS);
}

// Función para sanitizar input
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Función para validar token JWT
function validateJWT($token) {
    try {
        $decoded = JWT::decode($token, JWT_SECRET, array('HS256'));
        return $decoded;
    } catch (Exception $e) {
        return false;
    }
}

// Función para generar token JWT
function generateJWT($data) {
    $issuedAt = time();
    $expiration = $issuedAt + TOKEN_EXPIRATION;
    
    $payload = array(
        'iat' => $issuedAt,
        'exp' => $expiration,
        'data' => $data
    );
    
    return JWT::encode($payload, JWT_SECRET);
}

// Función para verificar rate limit
// Función para verificar rate limit
function checkRateLimit($ip) {
    $redis = new Predis\Client();
    try {
        $redis->connect();
    } catch (\Predis\Connection\ConnectionException $e) {
        error_log("Error al conectar a Redis en checkRateLimit: " . $e->getMessage());
        return false; // O manejar el error como prefieras
    }

    $key = "rate_limit:$ip";
    $current = $redis->get($key);

    if (!$current) {
        $redis->setex($key, RATE_LIMIT_WINDOW, 1);
        return true;
    }

    if ($current >= RATE_LIMIT_MAX_REQUESTS) {
        return false;
    }

    $redis->incr($key);
    return true;
}

// Función para registrar actividad
function logActivity($user_id, $action, $details = '') {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    $stmt = $pdo->prepare("
        INSERT INTO actividad_log (user_id, action, details, ip_address, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR']
    ]);
} 