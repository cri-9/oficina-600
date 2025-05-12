<?php
/**
 * Enviar datos a tu servidor WebSocket (Ratchet) vía HTTP POST local, para notificar eventos.
 * Compatible con el método usado en llamar_siguiente_turno.php, finalizar_turno.php, etc.
 */

// Ruta del log
define('WS_CLIENT_LOG', __DIR__ . '/websocket.log');

/**
 * Envia un mensaje JSON al WebSocket Server.
 *
 * @param array $wsData Los datos a enviar como mensaje (array asociativo).
 * @param string $wsUrl La url del WebSocket en modo HTTP POST (por defecto localhost:8080).
 * @return array ["success"=>bool, "response"=>string, "http_code"=>int]
 */
function enviar_websocket_evento($wsData, $wsUrl = 'http://localhost:8080') {
    $dataJson = json_encode($wsData);
    $ch = curl_init($wsUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($dataJson)
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Loggear el envío
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp][WS-CLIENT] Sent: $dataJson | Response: $result | HTTP: $httpCode\n";
    file_put_contents(WS_CLIENT_LOG, $logMessage, FILE_APPEND);

    return [
        "success"   => $httpCode === 200,
        "response"  => $result,
        "http_code" => $httpCode
    ];
}
?>