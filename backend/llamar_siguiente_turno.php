<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'cors.php';
require_once 'conexion.php';
require_once 'security_utils.php';
require_once 'auth_middleware.php';
require_once __DIR__ . '/websocket_client.php';

// Asegurar que no haya salida antes del JSON
ob_clean();

// Establecer headers
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener y validar datos de entrada
    $input = file_get_contents('php://input');
    error_log("RAW BODY: " . $input);
    
    if (empty($input)) {
        throw new Exception('No se recibieron datos');
    }

    $data = json_decode($input, true);    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }

    error_log("Datos decodificados: " . print_r($data, true));

    // Validar campos requeridos
    if (!isset($data['id_modulo'])) {
        throw new Exception('ID del módulo es requerido');
    }

    // Sanitizar entrada
    $idModulo = filter_var($data['id_modulo'], FILTER_SANITIZE_NUMBER_INT);
    // Validar que venga el valor y que sea numérico válido
if (
    !isset($data['id_modulo']) ||
    !is_numeric($data['id_modulo']) ||
    intval($data['id_modulo']) <= 0
) {
    throw new Exception('ID del módulo es requerido y debe ser un número entero mayor a 0');
}
// Sanitizar entrada
$idModulo = intval($data['id_modulo']);

    error_log("ID Módulo sanitizado: " . $idModulo);

    // Iniciar transacción
    $pdo->beginTransaction();

    // Verificar si hay turnos en atención para este módulo
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as en_atencion
        FROM turnos
        WHERE id_modulo = ? AND estado = 'en_atencion'
    ");
    $stmt->execute([$idModulo]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Turnos en atención para módulo $idModulo: " . $result['en_atencion']);

    if ($result['en_atencion'] > 0) {
        throw new Exception('Ya existe un turno en atención para este módulo');
    }

    // Obtener información del módulo
    $stmt = $pdo->prepare("
        SELECT m.*, p.nombre as perfil_nombre
        FROM modulos m
        JOIN perfiles p ON m.id_perfil = p.id
        WHERE m.id = ?
    ");
    $stmt->execute([$idModulo]);
    $modulo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$modulo) {
        throw new Exception('Módulo no encontrado');
    }

    error_log("Información del módulo: " . print_r($modulo, true));

    // Obtener el siguiente turno pendiente para este módulo
    $stmt = $pdo->prepare("
        SELECT t.*, m.nombre as modulo_nombre
        FROM turnos t
        JOIN modulos m ON t.id_modulo = m.id
        WHERE t.estado = 'pendiente'
        AND t.id_modulo = ?
        ORDER BY t.fecha_creacion ASC
        LIMIT 1
    ");
    $stmt->execute([$idModulo]);
    $turno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$turno) {
        throw new Exception('No hay turnos pendientes para llamar en este módulo');
    }

    error_log("Turno encontrado: " . print_r($turno, true));

    // Actualizar el estado del turno a en_atencion
    $stmt = $pdo->prepare("
        UPDATE turnos 
        SET estado = 'en_atencion',
            fecha_atencion = NOW(),
            id_modulo = ?
        WHERE id = ?
    ");
    $stmt->execute([$idModulo, $turno['id']]);

    // Confirmar transacción
    $pdo->commit();
    error_log("[LLAMAR_SIGUIENTE] Punto antes de publicar a Redis");

    // Enviar datos al WebSocket (via Redis)
    require_once 'vendor/autoload.php';
    $redis = new Predis\Client(); // <--- MANTENER ESTA INSTANCIACIÓN

    error_log("[LLAMAR_SIGUIENTE] Cliente Redis instanciado.");

    // Intenta reconectar forzosamente
    try {
        $redis->connect();
        error_log("[LLAMAR_SIGUIENTE] Conexión Redis forzada.");
    } catch (\Predis\Connection\ConnectionException $e) {
        error_log("[LLAMAR_SIGUIENTE] Error al conectar a Redis: " . $e->getMessage());
    }
    // Verificar si la conexión está activa
    $wsData = [
        "type" => "turno_llamado",
        "numero_turno" => $turno['numero_turno'],
        "tipo_atencion" => $turno['tipo_atencion'],
        "modulo" => $modulo['nombre'] ?? $turno['modulo_nombre'],
        "id_modulo" => $idModulo
    ];
    // Publicar el mensaje en Redis
    $redis->publish('turnos', json_encode($wsData));
    // También puedes usar rpush si necesitas almacenar el mensaje en una lista
    //$redis->rpush('turnos', json_encode(['type' => 'test_message', 'data' => 'hello from php']));
    error_log("[LLAMAR_SIGUIENTE] Publicado mensaje de prueba.");

    error_log("📢📢📢 [LLAMAR_SIGUIENTE] Datos publicados a Redis: " . json_encode($wsData));

    error_log("Datos WebSocket: " . print_r($wsData, true));
    
    // Preparar respuesta exitosa
    $response = [
        'success' => true,
        'message' => 'Turno llamado exitosamente',
        'turno' => [
            'id' => $turno['id'],
            'numero_turno' => $turno['numero_turno'],
            'tipo_atencion' => $turno['tipo_atencion'],
            'modulo' => $modulo['nombre'],
            'estado' => 'en_atencion'
        ]
    ];

    error_log("Respuesta exitosa: " . print_r($response, true));
    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    $errorMsg = $e->getMessage();

    // Default/bad request
    $httpStatus = 400;

    if ($errorMsg === 'No hay turnos pendientes para llamar en este módulo') {
        $httpStatus = 204;
    } elseif (strpos($errorMsg, 'ID del módulo') !== false) {
        $httpStatus = 422;
    } elseif ($errorMsg === 'Método no permitido') {
        $httpStatus = 405;
    }

    http_response_code($httpStatus);
    echo json_encode([
        'success' => false,
        'error' => $errorMsg
    ], JSON_UNESCAPED_UNICODE);
}

