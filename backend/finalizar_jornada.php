<?php
// No debe haber ningún espacio antes de <?php
ini_set('display_errors', 0);
error_reporting(E_ALL);


$response = [
    'success' => false,
    'message' => ''
];

try {
    require_once 'conexion.php';
    if (!isset($pdo)) {
        throw new Exception("No se encontró conexión a la base de datos");
    }

    // 1. Seleccionar todos los turnos que no están finalizados
    $stmt = $pdo->query("SELECT * FROM turnos WHERE estado != 'finalizado'");
    $to_archive = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    if ($to_archive) {
        // 2. Archivar en historial_turnos
        foreach ($to_archive as $turno) {
            $sqlHist = "INSERT INTO historial_turnos (id_turno, numero_turno, id_modulo, id_usuario, perfil_id, fecha_creacion, fecha_finalizacion)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmtHist = $pdo->prepare($sqlHist);
            $stmtHist->execute([
    $turno['id'],
    $turno['numero_turno'],
    $turno['id_modulo'],
    $turno['id_usuario'],
    $turno['perfil_id'],
    $turno['fecha_creacion'],
    $turno['fecha_finalizado'] ?? date('Y-m-d H:i:s')
]);
        }
        // 3. Marcar todos los turnos como finalizados y setear fecha de finalización
        $pdo->query("UPDATE turnos SET estado = 'finalizado', finalizado_en = NOW() WHERE estado != 'finalizado'");
    }
    // 4. Reiniciar numeración (puedes variar la lógica según tu modelo)
    $pdo->query("ALTER TABLE turnos AUTO_INCREMENT = 1");

    $response['success'] = true;
    $response['message'] = "Jornada finalizada y turnos archivados.";
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
} finally {
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}