<?php
require_once 'config.php';

class SecurityUtils {
    private $db;
    private $config;

    public function __construct($db) {
        $this->db = $db;
        $this->config = [
            'max_login_attempts' => 5,
            'lockout_duration' => 15, // minutos
            'password_min_length' => 8,
            'session_lifetime' => 3600, // 1 hora
            'token_lifetime' => 3600, // 1 hora
        ];
    }

    public function validatePassword($password) {
        if (strlen($password) < $this->config['password_min_length']) {
            return false;
        }

        // Requerir al menos una letra mayúscula, una minúscula, un número y un carácter especial
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
            return false;
        }

        return true;
    }

    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    public function checkLoginAttempts($username, $ip) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE username = ? AND ip_address = ? 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$username, $ip]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['attempts'] < $this->config['max_login_attempts'];
    }

    public function recordLoginAttempt($username, $ip, $success) {
        if (!$success) {
            $stmt = $this->db->prepare("
                INSERT INTO login_attempts (username, ip_address) 
                VALUES (?, ?)
            ");
            $stmt->execute([$username, $ip]);
        }
    }

    public function isIPBlacklisted($ip) {
        $stmt = $this->db->prepare("
            SELECT 1 FROM ip_blacklist 
            WHERE ip_address = ? 
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([$ip]);
        return $stmt->fetch() !== false;
    }

    public function createSession($userId, $ip, $userAgent) {
        $sessionId = bin2hex(random_bytes(64));
        $expiresAt = date('Y-m-d H:i:s', time() + $this->config['session_lifetime']);

        $stmt = $this->db->prepare("
            INSERT INTO sesiones (id, user_id, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$sessionId, $userId, $ip, $userAgent, $expiresAt]);

        return $sessionId;
    }

    public function validateSession($sessionId, $ip) {
        $stmt = $this->db->prepare("
            SELECT user_id 
            FROM sesiones 
            WHERE id = ? 
            AND ip_address = ? 
            AND expires_at > NOW()
        ");
        $stmt->execute([$sessionId, $ip]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function invalidateSession($sessionId) {
        $stmt = $this->db->prepare("DELETE FROM sesiones WHERE id = ?");
        $stmt->execute([$sessionId]);
    }

    public function logActivity($userId, $action, $details = null) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $this->db->prepare("
            INSERT INTO actividad_log (user_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $action, $details, $ip]);
    }

    public function generatePasswordResetToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $this->config['token_lifetime']);

        $stmt = $this->db->prepare("
            INSERT INTO password_reset_tokens (user_id, token, expires_at)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $token, $expiresAt]);

        return $token;
    }

    public function validatePasswordResetToken($token) {
        $stmt = $this->db->prepare("
            SELECT user_id 
            FROM password_reset_tokens 
            WHERE token = ? 
            AND expires_at > NOW() 
            AND used = FALSE
        ");
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function markPasswordResetTokenAsUsed($token) {
        $stmt = $this->db->prepare("
            UPDATE password_reset_tokens 
            SET used = TRUE 
            WHERE token = ?
        ");
        $stmt->execute([$token]);
    }

    public function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            return false;
        }
        return true;
    }

    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
} 