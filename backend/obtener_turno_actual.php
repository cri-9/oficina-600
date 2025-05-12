<?php

require_once 'conexion.php';

try {
    // Turnos actuales (múltiples en estado 'en_atencion')
    $stmtActual = $pdo->prepare("SELECT numero_turno, id_modulo FROM turnos WHERE estado = 'en_atencion' ORDER BY id DESC");
    $stmtActual->execute();
    $turnosActuales = $stmtActual->fetchAll(PDO::FETCH_ASSOC); // Devuelve todos los turnos en 'en_atencion'

    // Si no hay turnos actuales, retornamos un array vacío
    if (!$turnosActuales) {
        $turnosActuales = [];
    }

    // Últimos 5 turnos finalizados
    $stmtUltimos = $pdo->prepare("SELECT numero_turno, id_modulo FROM turnos WHERE estado = 'finalizado' ORDER BY id DESC LIMIT 5");
    $stmtUltimos->execute();
    $ultimosTurnos = $stmtUltimos->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "turnosActuales" => $turnosActuales, // Cambiado a plural para reflejar múltiples turnos
        "ultimosTurnos" => $ultimosTurnos
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Error en la base de datos: " . $e->getMessage()
    ]);
}


