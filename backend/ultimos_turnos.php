<?php

// No debe haber ningún espacio antes de <?php
ini_set('display_errors', 0);
error_reporting(E_ALL);


$response = [
    'success' => false,
    'turnos_finalizados' => [],
    'turnos_anteriores' => [],
    'turno_actual' => null,
    'message' => ''
];

try {
    require_once 'conexion.php';
    if (!isset($pdo)) {
        throw new Exception("No se encontró conexión a la base de datos");
    }

    // Obtener los últimos 10 turnos finalizados
    $sql_finalizados = "SELECT numero_turno, tipo_atencion, id_modulo AS id_modulo, 'finalizado' AS estado, finalizado_en AS finalizado_en FROM turnos WHERE estado = 'finalizado' ORDER BY finalizado_en DESC LIMIT 10";
    $stmt_finalizados = $pdo->prepare($sql_finalizados);
    $stmt_finalizados->execute();
    $response['turnos_finalizados'] = $stmt_finalizados->fetchAll(PDO::FETCH_ASSOC);

    
    // Agregar después de obtener los finalizados:
    $sql_pendientes = "SELECT numero_turno, tipo_atencion, id_modulo AS id_modulo, 'pendiente' AS estado, creado_en FROM turnos WHERE estado = 'pendiente' ORDER BY creado_en ASC";
    $stmt_pendientes = $pdo->prepare($sql_pendientes);
    $stmt_pendientes->execute();
    $response['turnos_pendientes'] = $stmt_pendientes->fetchAll(PDO::FETCH_ASSOC);

    // Últimos 5 turnos llamados (ordenados del más reciente al más antiguo)
    $sql_anteriores = "SELECT numero_turno, tipo_atencion, id_modulo AS id_modulo, 'llamado' AS estado, creado_en AS fecha_llamado FROM turnos WHERE estado = 'en_atencion' ORDER BY creado_en DESC LIMIT 5";
    $stmt_anteriores = $pdo->prepare($sql_anteriores);
    $stmt_anteriores->execute();
    $response['turnos_anteriores'] = $stmt_anteriores->fetchAll(PDO::FETCH_ASSOC);

    // Turno actual: el último turno llamado
    $sql_actual = "SELECT numero_turno, tipo_atencion, id_modulo AS id_modulo, 'llamado' AS estado, creado_en AS fecha_llamado FROM turnos WHERE estado = 'en_atencion' ORDER BY creado_en DESC LIMIT 1";
    $stmt_actual = $pdo->prepare($sql_actual);
    $stmt_actual->execute();
    $turno_actual = $stmt_actual->fetch(PDO::FETCH_ASSOC);
    if ($turno_actual) {
        $response['turno_actual'] = $turno_actual;
    }

    $response['success'] = true;
} catch (Exception $e) {
    $response['message'] = 'Error al obtener turnos: ' . $e->getMessage();
} finally {
    $json = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    // Guardar la salida en un archivo de depuración
    file_put_contents(__DIR__ . '/debug_ultimos_turnos.log', $json . "\n");
    echo $json;
}
