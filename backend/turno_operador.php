<?php

// Si la solicitud es un preflight (OPTIONS), retornar una respuesta vacía exitosa
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

header('Content-Type: application/json');
include 'conexion.php';

$id_usuario = $_GET['id_usuario'];

$sql = "SELECT numero_turno, id_modulo FROM turnos 
        WHERE id_usuario = ? AND estado = 'en_atencion' 
        ORDER BY id DESC LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(["turno" => $row]);
} else {
    echo json_encode(["turno" => null]);
}

$stmt->close();
$conn->close();
