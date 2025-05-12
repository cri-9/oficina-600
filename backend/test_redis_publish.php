<?php
require 'vendor/autoload.php';

try {
    $redis = new Predis\Client();
    $redis->connect();
    echo "Conectado a Redis\n";

    $message = [
        "type" => "turno_llamado",
        "numero_turno" => "TEST",
        "tipo_atencion" => "PRUEBA",
        "modulo" => "TEST_MODULO",
        "id_modulo" => 99
    ];
    $redis->publish('turnos', json_encode($message));
    echo "Mensaje 'turno_llamado' publicado a Redis\n";

    $testMessage = ['type' => 'test_message', 'data' => 'hello from test script'];
    $redis->publish('turnos', json_encode($testMessage));
    echo "Mensaje de prueba publicado a Redis\n";

} catch (\Predis\Connection\ConnectionException $e) {
    echo "Error al conectar a Redis: " . $e->getMessage() . "\n";
}
?>