<?php

// Desactivar la salida de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);


// Incluir archivos necesarios
require_once 'config.php';
require_once 'cors.php';
require_once 'conexion.php';
require_once 'security_utils.php';
require_once 'auth_middleware.php';

// Asegurar que no haya salida antes del JSON
ob_clean();

// Establecer headers
header('Content-Type: application/json; charset=utf-8');

try {
    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Obtener y validar datos de entrada
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('No se recibieron datos');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }

    // Validar campos requeridos
    if (empty($data['tipo_atencion']) || empty($data['id_modulo'])) {
        throw new Exception('Tipo de atención y módulo son requeridos');
    }

    // Sanitizar entrada
    $tipoAtencion = filter_var($data['tipo_atencion'], FILTER_SANITIZE_STRING);
    $idModulo = filter_var($data['id_modulo'], FILTER_SANITIZE_NUMBER_INT);

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        // Buscar el último numero_turno generado HOY para este módulo y tipo
        $stmt = $pdo->prepare("
    SELECT MAX(CAST(SUBSTRING(numero_turno, LENGTH(:tipo_len)+1) AS UNSIGNED)) as ultimo_numero
    FROM turnos
    WHERE id_modulo = :modulo
      AND tipo_atencion = :tipo_value
      AND DATE(creado_en) = CURDATE()
");
$stmt->execute([
    ':tipo_len' => $tipoAtencion,
    ':tipo_value' => $tipoAtencion,
    ':modulo' => $idModulo
]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $ultimoNumero = $result && $result['ultimo_numero'] ? intval($result['ultimo_numero']) : 0;
        $nuevoNumero = $ultimoNumero + 1;

        // Armar el valor de numero_turno (ejemplo: "SAE023")
        $numeroTurno = $tipoAtencion . str_pad($nuevoNumero, 3, '0', STR_PAD_LEFT);

        // Insertar nuevo turno
        $stmt = $pdo->prepare("
            INSERT INTO turnos (
                numero_turno,
                tipo_atencion,
                id_modulo,
                estado,
                creado_en,
                fecha_actualizacion,
                llamado
            ) VALUES (?, ?, ?, 'pendiente', NOW(), NOW(), 0)
        ");
        $stmt->execute([$numeroTurno, $tipoAtencion, $idModulo]);
        $idTurno = $pdo->lastInsertId();

        // Obtener toda la info del turno recién creado
        $stmt = $pdo->prepare("
            SELECT t.id, t.numero_turno, t.tipo_atencion, t.estado,
                   t.creado_en, m.nombre as modulo_nombre
            FROM turnos t
            JOIN modulos m ON t.id_modulo = m.id
            WHERE t.id = ?
        ");
        $stmt->execute([$idTurno]);
        $turno = $stmt->fetch(PDO::FETCH_ASSOC);

        // Confirmar transacción
        $pdo->commit();

        // Ajustar la respuesta a lo que espera el frontend:
        $response = [
            'success' => true,
            'turno' => [
                'id' => $turno['id'],
                'numero_turno' => $turno['numero_turno'],
                'tipo_atencion' => $turno['tipo_atencion'],
                'modulo' => $turno['modulo_nombre'],
                'estado' => $turno['estado'],
                'creado_en' => $turno['creado_en']
            ]
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}

