<?php

header('Content-Type: application/json');
//Conexión a la base de datos
include 'conexion.php';

// Conexión a la base de datos
$datos = json_decode(file_get_contents("php://input"), true);
$id_usuario = $datos['id_usuario'];
$perfil = $datos['perfil'];

// Obtener los módulos asociados a este perfil
$sqlMod = "SELECT modulo FROM perfiles_modulos WHERE perfil = ?";
$stmt = $conn->prepare($sqlMod);
$stmt->bind_param("s", $perfil);
$stmt->execute();
$resultMod = $stmt->get_result();

$modulos = [];
while ($row = $resultMod->fetch_assoc()) {
    $modulos[] = $row['id_modulo'];
}

if (count($modulos) === 0) {
    echo json_encode(["success" => false, "message" => "No hay módulos asociados al perfil"]);
    exit;
}

// Buscar el siguiente turno 'pendiente' según módulos
$placeholders = implode(',', array_fill(0, count($modulos), '?'));
$params = array_merge(['pendiente'], $modulos);
$types = str_repeat('s', count($params));

$sqlTurno = "SELECT  numero_turno, id_modulo FROM turnos WHERE estado = ? AND id_modulo IN ($placeholders) ORDER BY id ASC LIMIT 1";
$stmt = $conn->prepare($sqlTurno);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($turno = $result->fetch_assoc()) {
    // Actualizar el turno a 'en_atencion'
    $sqlUpdate = "UPDATE turnos SET estado = 'en_atencion', id_usuario = ? WHERE id = ?";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ii", $id_usuario, $turno['id']);
    $stmtUpdate->execute();

    echo json_encode([
        "success" => true,
        "turno" => [
            "numero_turno" => $turno['numero_turno'],
            "id_modulo" => $turno['id_modulo']
        ]
    ]);
} else {
    echo json_encode(["success" => false, "message" => "No hay turnos disponibles"]);
}
