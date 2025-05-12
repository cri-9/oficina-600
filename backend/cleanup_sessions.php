<?php
require_once 'config.php';
require_once 'security_utils.php';

// Este script debe ejecutarse periódicamente mediante un cron job
// Ejemplo: */5 * * * * php /ruta/al/script/cleanup_sessions.php

try {
    // Limpiar sesiones expiradas
    $stmt = $db->prepare("
        DELETE FROM sesiones 
        WHERE expires_at < NOW()
    ");
    $stmt->execute();
    $sessionsDeleted = $stmt->rowCount();

    // Limpiar tokens de recuperación expirados
    $stmt = $db->prepare("
        DELETE FROM password_reset_tokens 
        WHERE expires_at < NOW() 
        OR used = TRUE
    ");
    $stmt->execute();
    $tokensDeleted = $stmt->rowCount();

    // Limpiar intentos de login antiguos
    $stmt = $db->prepare("
        DELETE FROM login_attempts 
        WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $attemptsDeleted = $stmt->rowCount();

    // Limpiar IPs de la lista negra expiradas
    $stmt = $db->prepare("
        DELETE FROM ip_blacklist 
        WHERE expires_at IS NOT NULL 
        AND expires_at < NOW()
    ");
    $stmt->execute();
    $blacklistDeleted = $stmt->rowCount();

    // Limpiar logs de actividad antiguos (mantener solo los últimos 30 días)
    $stmt = $db->prepare("
        DELETE FROM actividad_log 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $logsDeleted = $stmt->rowCount();

    // Registrar resultados
    error_log(sprintf(
        "Limpieza completada: %d sesiones, %d tokens, %d intentos, %d IPs, %d logs eliminados",
        $sessionsDeleted,
        $tokensDeleted,
        $attemptsDeleted,
        $blacklistDeleted,
        $logsDeleted
    ));

} catch (Exception $e) {
    error_log("Error en limpieza: " . $e->getMessage());
} 