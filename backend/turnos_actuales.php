<?php


// Si la solicitud es un preflight (OPTIONS), retornar una respuesta vacía exitosa
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

header('Content-Type: application/json');
include 'conexion.php';

// Últimos 5 turnos llamados
$sqlUltimos = "SELECT numero_turno, id_modulo FROM turnos WHERE estado = 'llamado' ORDER BY id DESC LIMIT 5";
$resultUltimos = $conn->query($sqlUltimos);
$ultimos = [];

while ($row = $resultUltimos->fetch_assoc()) {
    $ultimos[] = $row;
}

// Último turno en atención
$sqlActual = "SELECT numero_turno, id_modulo FROM turnos WHERE estado = 'en_atencion' ORDER BY id DESC LIMIT 1";
$resultActual = $conn->query($sqlActual);
$actual = $resultActual->fetch_assoc();

echo json_encode([
    "ultimos" => $ultimos,
    "actual" => $actual
]);

$conn->close();
