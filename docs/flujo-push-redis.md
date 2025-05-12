# Push desde backend PHP a WebSocketServer usando Redis (pub/sub desacoplado)

## 1. Cuando el backend procesa el turno:

```php
$wsData = [
    "type" => "turno_llamado",
    "numero_turno" => $turno['numero_turno'],
    "tipo_atencion" => $turno['tipo_atencion'],
    "modulo" => $turno['modulo_nombre']
];
$redis = new Predis\Client();
$redis->rpush('turnos', json_encode($wsData));
```

## 2. El WebSocket server (`websocket_server.php`):

```php
$loop->addPeriodicTimer(0.5, function () use ($webSocket) {
    if ($webSocket->redis) {
        $messages = $webSocket->redis->blpop(['turnos'], 0);
        if ($messages && isset($messages[1])) {
            $data = json_decode($messages[1], true);
            if ($data) {
                $webSocket->broadcastTurnoUpdate($data);
            }
        }
    }
});
```

- Así, tu sistema puede emitir cualquier tipo de evento a todos los clientes WebSocket en tiempo real, ¡sin depender de HTTP POST al puerto del WS server!
- Puedes publicar cualquier mensaje estructurado que tu frontend necesite.