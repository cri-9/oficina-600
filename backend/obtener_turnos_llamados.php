<?php
// No debe haber ningún espacio antes de <?php
ini_set('display_errors', 0);
error_reporting(E_ALL);


header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'turnos' => [],
    'ultimosTurnos' => [],
    'message' => ''
];

try {
    require_once 'conexion.php';
    if (!isset($pdo)) {
        throw new Exception("No se encontró conexión a la base de datos");
    }

    // Obtener todos los turnos que están actualmente en estado "en_atencion"
    $sql = "SELECT t.numero_turno, t.tipo_atencion, t.id_modulo, t.id_usuario 
            FROM turnos t 
            WHERE t.estado = 'en_atencion' 
            ORDER BY t.id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener nombre del operador para cada turno
    foreach ($turnos as &$row) {
        if ($row['id_usuario']) {
            $sqlUsuario = "SELECT nombre FROM usuarios WHERE id = ?";
            $stmtUsuario = $pdo->prepare($sqlUsuario);
            $stmtUsuario->execute([$row['id_usuario']]);
            $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
            $row['nombre_operador'] = $usuario ? $usuario['nombre'] : 'Operador Desconocido';
        } else {
            $row['nombre_operador'] = 'Sin Asignar';
        }
    }

    // Últimos turnos finalizados
    $sqlUltimos = "SELECT numero_turno, id_modulo FROM turnos WHERE estado = 'finalizado' ORDER BY id DESC LIMIT 5";
    $stmtUltimos = $pdo->prepare($sqlUltimos);
    $stmtUltimos->execute();
    $ultimosTurnos = $stmtUltimos->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['turnos'] = $turnos;
    $response['ultimosTurnos'] = $ultimosTurnos;

} catch (Exception $e) {
    $response['message'] = "Error al consultar turnos: " . $e->getMessage();
} finally {
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
