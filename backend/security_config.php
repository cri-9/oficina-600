<?php
// Configuración de seguridad

// Configuración de sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600); // 1 hora
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);

// Configuración de headers de seguridad
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:; font-src \'self\'; connect-src \'self\' ws: wss:;');

// Configuración de contraseñas
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_MAX_LENGTH', 72);
define('PASSWORD_HASH_ALGO', PASSWORD_ARGON2ID);
define('PASSWORD_HASH_OPTIONS', [
    'memory_cost' => 65536,
    'time_cost' => 4,
    'threads' => 3
]);

// Configuración de JWT
define('JWT_SECRET', 'tu_clave_secreta_muy_segura'); // Cambiar en producción
define('JWT_ALGO', 'HS256');
define('JWT_EXPIRATION', 3600); // 1 hora

// Configuración de rate limiting
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_WINDOW', 60); // 1 minuto
define('RATE_LIMIT_MAX_REQUESTS', [
    'login' => 5,
    'register' => 3,
    'password_reset' => 3,
    'api' => 60
]);

// Configuración de CORS
define('ALLOWED_ORIGINS', [
    'http://localhost:3000',
    'https://tu-dominio.com'
]);

// Configuración de logging
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_FILE', __DIR__ . '/logs/app.log');
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('LOG_MAX_FILES', 5);

// Configuración de limpieza
define('CLEANUP_INTERVAL', 300); // 5 minutos
define('SESSION_LIFETIME', 3600); // 1 hora
define('PASSWORD_RESET_LIFETIME', 3600); // 1 hora
define('LOG_RETENTION_DAYS', 30);

// Configuración de bloqueo de cuenta
define('MAX_LOGIN_ATTEMPTS', 5);
define('ACCOUNT_LOCKOUT_DURATION', 900); // 15 minutos

// Configuración de WebSocket
define('WS_HOST', 'localhost');
define('WS_PORT', 8080);
define('WS_SSL', false);

// Configuración de email
define('SMTP_HOST', 'smtp.tu-servidor.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'tu-usuario');
define('SMTP_PASSWORD', 'tu-contraseña');
define('SMTP_FROM', 'noreply@tu-dominio.com');
define('SMTP_FROM_NAME', 'Oficina 600');

// Configuración de backup
define('BACKUP_ENABLED', true);
define('BACKUP_INTERVAL', 86400); // 24 horas
define('BACKUP_RETENTION_DAYS', 7);
define('BACKUP_PATH', __DIR__ . '/backups');

// Configuración de monitoreo
define('MONITORING_ENABLED', true);
define('MONITORING_INTERVAL', 300); // 5 minutos
define('ALERT_EMAIL', 'admin@tu-dominio.com');

// Configuración de caché
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hora
define('CACHE_PATH', __DIR__ . '/cache');

// Configuración de compresión
define('COMPRESSION_ENABLED', true);
define('COMPRESSION_LEVEL', 6);

// Configuración de timeout
define('REQUEST_TIMEOUT', 30); // segundos
define('DB_TIMEOUT', 5); // segundos
define('WS_TIMEOUT', 10); // segundos

// Configuración de debug
define('DEBUG_MODE', false);
define('SHOW_ERRORS', false);
define('LOG_ERRORS', true);

// Configuración de mantenimiento
define('MAINTENANCE_MODE', false);
define('MAINTENANCE_IP_WHITELIST', [
    '127.0.0.1',
    '::1'
]);

// Configuración de validación
define('VALIDATE_INPUT', true);
define('SANITIZE_OUTPUT', true);
define('ESCAPE_HTML', true);

// Configuración de sesión
define('SESSION_NAME', 'OFICINA600_SESSID');
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', '');
define('SESSION_SECURE', true);
define('SESSION_HTTPONLY', true);

// Configuración de cookies
define('COOKIE_PREFIX', 'OFICINA600_');
define('COOKIE_PATH', '/');
define('COOKIE_DOMAIN', '');
define('COOKIE_SECURE', true);
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Strict');

// Configuración de headers
define('SECURITY_HEADERS', [
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'X-Content-Type-Options' => 'nosniff',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self' ws: wss:;"
]); 