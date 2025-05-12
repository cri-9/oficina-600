import React, { useEffect, useState } from 'react';
import { Box, Typography, Paper } from '@mui/material';
import { useWebSocket } from '../contexts/WebSocketContext';
import { toast } from 'react-hot-toast';
import '../css/Cliente.css';

const Cliente = () => {
  const { socket, isConnected } = useWebSocket();
  const [turnoActual, setTurnoActual] = useState(null);
  const [nuevoTurno, setNuevoTurno] = useState(false); // Para manejar la animación

  useEffect(() => {
    if (!socket || socket.readyState !== 1) return;

    const handleMessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        console.log('Mensaje WebSocket recibido:', data);
        console.log('➡️ Data parseada:', data); // <--- AÑADE ESTE LOG

        if (data.type === 'turno_llamado' && data.numero_turno && data.tipo_atencion && data.modulo) {
          console.log('[CLIENTE] Recibido evento turno_llamado:', data); // <---- AÑADE ESTE LOG

          setTurnoActual({
            numero: data.numero_turno,
            tipo: data.tipo_atencion,
            modulo: data.modulo
          });

          // Activar animación
          setNuevoTurno(true);
          setTimeout(() => setNuevoTurno(false), 1800); // Dura poco más que la animación CSS

          // Mostrar notificación
          toast.success(`Turno ${data.numero_turno} llamado al módulo ${data.modulo}`);
        } else {
          console.log('📦 Other message:', data);
        }
      } catch (error) {
        console.error('Error al procesar mensaje WebSocket:', error);
      }
    };

    const handleError = (error) => {
      console.error('Error en WebSocket:', error);
      toast.error('Error de conexión con el servidor');
    };

    socket.addEventListener('message', handleMessage);
    socket.addEventListener('error', handleError);

    return () => {
      socket.removeEventListener('message', handleMessage);
      socket.removeEventListener('error', handleError);
    };
  }, [socket]);
  return (
    <Box
      sx={{
        minHeight: '100vh',
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: '#f5f5f5',
        padding: 4
      }}
    >
      <Typography
        variant="h3"
        component="h1"
        gutterBottom
        sx={{
          color: '#1976d2',
          fontWeight: 'bold',
          marginBottom: 4
        }}
      >
        Pantalla de Información
      </Typography>

      {!isConnected && (
        <Typography
          variant="h5"
          color="error"
          sx={{ marginBottom: 2 }}
        >
          Conectando al servidor...
        </Typography>
      )}

      {turnoActual ? (
        <Paper
          elevation={3}
          className={`turno-card${nuevoTurno ? ' nuevo' : ''}`} // Aplica clase de animación cuando hay nuevo turno
          sx={{
            padding: 4,
            textAlign: 'center',
            backgroundColor: '#fff',
            borderRadius: 2,
            maxWidth: 600,
            width: '100%'
          }}
        >
          <Typography
            variant="h1"
            component="div"
            sx={{
              color: '#1976d2',
              fontWeight: 'bold',
              marginBottom: 2
            }}
          >
            {turnoActual.numero}
          </Typography>
          <Typography
            variant="h4"
            sx={{
              color: '#666',
              marginBottom: 2
            }}
          >
            {turnoActual.tipo}
          </Typography>
          <Typography
            variant="h3"
            sx={{
              color: '#1976d2',
              fontWeight: 'bold'
            }}
          >
            Módulo {turnoActual.modulo}
          </Typography>
        </Paper>
      ) : (
        <Typography
          variant="h4"
          sx={{
            color: '#666',
            marginTop: 4
          }}
        >
          Esperando turno...
        </Typography>
      )}
    </Box>
  );
};

export default Cliente;
