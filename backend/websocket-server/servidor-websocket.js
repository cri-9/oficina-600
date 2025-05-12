// servidor-websocket.js
/**
 * Servidor WebSocket Node.js para integración bidireccional.
 * ALTA DISPONIBILIDAD: Notificación entre clientes y soporte para mensajes desde PHP.
 * 
 * Mejoras:
 * - Mensaje de conexión 'connection_established' para frontend moderno
 * - Soporte a OPTIONS CORS para llamadas HTTP
 * - Logging robusto
 * 
 * Cómo usar:
 *   1. En terminal:
 *      cd backend/websocket-server
 *      node servidor-websocket.js
 *   2. El frontend se conecta a ws://localhost:8081
 *   3. El backend PHP puede enviar mensajes vía HTTP POST a http://localhost:8080
 */

const WebSocket = require('ws');
const http = require('http');
const Redis = require('redis');

// --- WebSocket Server ---
const wss = new WebSocket.Server({ port: 8081 });
let clientCount = 0;

wss.on('connection', (ws) => {
    clientCount++;
    console.log(`✅ Cliente WebSocket conectado (${clientCount} activos)`);

    ws.send(JSON.stringify({
        type: 'connection_established',
        message: 'Conectado al servidor WebSocket'
    }));

    ws.on('message', (message) => {
        try {
            const parsedMessage = JSON.parse(message);
            console.log('📩 [WS Recibido]:', JSON.stringify(parsedMessage, null, 2));
        } catch (e) {
            console.log('📩 [WS Recibido (string)]:', message.toString());
        }

        wss.clients.forEach((client) => {
            if (client !== ws && client.readyState === WebSocket.OPEN) {
                client.send(message);
            }
        });
    });

    ws.on('close', () => {
        clientCount--;
        console.log(`❌ Cliente WebSocket desconectado (${clientCount} activos)`);
    });

    ws.on('error', (error) => {
        console.error('⚠️ [WS Error]:', error);
    });
});

console.log('🚀 WebSocket server funcionando en ws://localhost:8081');

// --- Redis Client for WebSocket Server ---
const redisClient = Redis.createClient({
    database: 0 // O la base de datos que estés utilizando
});

redisClient.on('error', err => console.log('Redis Client Error', err));

redisClient.connect().then(() => {
    const subscriber = redisClient.duplicate();
    subscriber.connect().then(() => {
        subscriber.subscribe('turnos', (message) => {
            console.log('Raw Redis Message:', message); // <--- AÑADE ESTE LOG
            try {
                const turnoLlamado = JSON.parse(message);
                console.log('📢 [Redis PubSub]:', JSON.stringify(turnoLlamado, null, 2));
                console.log('🔍 [Redis Message Type]:', turnoLlamado.type);
                wss.clients.forEach(client => {
                    if (client.readyState === WebSocket.OPEN) {
                        client.send(JSON.stringify(turnoLlamado));
                    }
                });
            } catch (e) {
                console.error('⚠️ Error al procesar mensaje de Redis:', e);
            }
        });
        console.log('👂 WebSocket server suscrito al canal "turnos" de Redis');
    });
});

// --- HTTP Server for PHP Backend ---
const httpServer = http.createServer((req, res) => {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
    res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');

    if (req.method === 'OPTIONS') {
        res.writeHead(204);
        res.end();
        return;
    }

    if (req.method === 'POST') {
        let body = '';
        req.on('data', (chunk) => {
            body += chunk;
        });

        req.on('end', () => {
            try {
                const data = JSON.parse(body);
                console.log('🔄 [HTTP Recibido]:', JSON.stringify(data, null, 2), 'desde PHP');

                // Publicar el mensaje recibido de PHP a Redis
                redisClient.publish('turnos', JSON.stringify(data));

                res.writeHead(200, {'Content-Type': 'application/json'});
                res.end(JSON.stringify({
                    success: true,
                    message: 'Mensaje publicado a Redis',
                    clientCount
                }));
            } catch (e) {
                console.error('⚠️ [HTTP Error]:', e);
                res.writeHead(400, {'Content-Type': 'application/json'});
                res.end(JSON.stringify({
                    success: false,
                    error: 'Formato JSON inválido'
                }));
            }
        });
    } else {
        res.writeHead(405, {
            'Allow': 'POST, OPTIONS',
            'Content-Type': 'application/json'
        });
        res.end(JSON.stringify({
            success: false,
            error: 'Método no permitido. Use POST para enviar mensajes.'
        }));
    }
});

httpServer.listen(8080, () => {
    console.log('🔗 HTTP server aceptando POST en http://localhost:8080');
});

// --- Manejo de cierre ---
process.on('SIGINT', () => {
    console.log('\n🛑 Cerrando servidores...');
    httpServer.close(() => {
        console.log('🔒 Servidor HTTP cerrado');
    });

    wss.close(() => {
        console.log('🔒 Servidor WebSocket cerrado');
        redisClient.quit().then(() => {
            console.log('🔒 Cliente Redis desconectado');
            process.exit(0);
        });
    });
});
