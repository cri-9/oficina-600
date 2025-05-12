
import React, { useEffect, useState } from 'react';
import { Box, Typography } from '@mui/material';
import logo from '../assets/logo.jpg';
import axios from 'axios';
import ListaTurnos from './ListaTurnos';
import { useWebSocket } from '../contexts/WebSocketContext';
import { toast } from 'react-hot-toast';

// Prefijo condicional para desarrollo (Vite) o producción (build/dist)
const API_PREFIX = import.meta.env.DEV ? '/api' : '';

const modulosPorPerfil = {
  1: [1, 2, 3, 4], // Atención General
  2: [1, 2, 3, 4], // Solicitud Certificados
  3: [1, 2, 3, 4], // Apostilla
  4: [5, 6, 7],    // SAE
  5: [1, 2, 3, 4], // Inscripción Básica
  6: [1, 2, 3, 4]  // Inscripción Media
};

const nombreModuloPorId = {
  1: 'Modulo 1 G,C,A,B,M',
  2: 'Modulo 2 G,C,A,B,M',
  3: 'Modulo 3 G,C,A,B,M',
  4: 'Modulo 4 G,C,A,B,M',
  5: 'Modulo 5 S',
  6: 'Modulo 6 S',
  7: 'Modulo 7 S'
};

const Operadores = () => {
  let usuario = JSON.parse(localStorage.getItem('usuario'));
  const { socket } = useWebSocket();

  if (!usuario) {
    usuario = {
      id: 1,
      nombre: 'Juan Pérez',
      perfil: 1 // Apostilla
    };
    localStorage.setItem('usuario', JSON.stringify(usuario));
  }

  const [turnosPorModulo, setTurnosPorModulo] = useState([]);
  const [mensaje, setMensaje] = useState('');

  const obtenerTurnos = async () => {
    try {
      const response = await axios.get('http://localhost/oficina-600/backend/obtener_turnos.php',{
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      });

      if (response.data.success) {
        console.log('Datos de turnos del backend:', response.data.turnosActuales); // <----- AÑADE ESTE LOG
        const turnosFiltrados = (response.data.turnosActuales || []).filter(turno =>
          turno.estado === 'pendiente' || turno.estado === 'en_atencion'
        );
        const turnosConNombreModulo = turnosFiltrados.map(turno => ({
          ...turno,
          modulo: turno.modulo_nombre || nombreModuloPorId[turno.id_modulo] || `Módulo ${turno.id_modulo}`
        }));
        setTurnosPorModulo(turnosConNombreModulo || []);
        setMensaje('');
      } else {
        setMensaje(response.data.error || 'Error al obtener turnos');
      }
    } catch (error) {
      console.error('Error al obtener turnos:', error);
      setMensaje(error.response?.data?.error || error.message || 'Error al conectar con el servidor');
    }
  };

  const finalizarTurno = async (idTurno) => {
    try {
      const response = await axios.post(
        `${API_PREFIX}/oficina-600/backend/finalizar_turno.php`,
        { id_turno: idTurno },
        {
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        }
      );
      if (response.data.success) {
        toast.success('Turno finalizado correctamente');
        await obtenerTurnos();
      } else {
        throw new Error(response.data.error || 'Error al finalizar turno');
      }
    } catch (error) {
      console.error('Error al finalizar turno:', error);
      toast.error(error.response?.data?.error || error.message || 'Error al finalizar turno');
      setMensaje(error.response?.data?.error || error.message);
    }
  };

  const llamarSiguiente = async (moduloId) => {
    if (!moduloId) {
      toast.error('ID de módulo no válido');
      return;
    }

    try {
      console.log('Llamando siguiente turno para módulo:', moduloId);

      const response = await axios.post(
        `${API_PREFIX}/oficina-600/backend/llamar_siguiente_turno.php`,
        { id_modulo: parseInt(moduloId, 10) },
        {
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        }
      );

      console.log('Respuesta del servidor:', response.data);

      if (response.data.success) {
        await obtenerTurnos();
        toast.success(`Turno ${response.data.turno.numero_turno} llamado`);
      } else {
        if (response.data.error?.includes('Ya existe un turno en atención')) {
          const resetResponse = await axios.post(`${API_PREFIX}/oficina-600/backend/resetear_turnos.php`);
          if (resetResponse.data.success) {
            toast.success('Turnos reseteados correctamente');
            await llamarSiguiente(moduloId);
          } else {
            throw new Error(resetResponse.data.error || 'Error al resetear turnos');
          }
        } else {
          throw new Error(response.data.error || 'Error al llamar siguiente turno');
        }
      }
    } catch (error) {
      console.error('Error al llamar siguiente turno:', error);
      const errorMessage = error.response?.data?.error || error.message || 'Error al llamar siguiente turno';
      toast.error(errorMessage);
      setMensaje(errorMessage);
    }
  };

  useEffect(() => {
    if (!socket) return;

    const handleMessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        console.log('Mensaje WebSocket recibido:', data);
        if (data.type === 'turno_llamado' || data.type === 'turno_generado') {
          obtenerTurnos();
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

  useEffect(() => {
    obtenerTurnos();
    const interval = setInterval(obtenerTurnos, 30000);
    return () => clearInterval(interval);
  }, []);

  return (
    <Box sx={{ minHeight: '100vh', backgroundColor: '#fff' }}>
      <Box sx={{ backgroundColor: '#1976d2', color: '#fff', p: 2, display: 'flex', alignItems: 'center' }}>
        <img src={logo} alt="Logo" style={{ height: 50, marginRight: 16 }} />
        <Typography variant="h6">
          Operario: {usuario?.nombre || 'Invitado'} {usuario?.apellido || 'Invitado'} | Perfil: {usuario?.perfil || 'Invitado'}
        </Typography>
      </Box>
      <Box sx={{ p: 4 }}>
        <Typography variant="h5" gutterBottom>
          Panel de Turnos
        </Typography>
        {mensaje && <Typography color="error" gutterBottom>{mensaje}</Typography>}
        <ListaTurnos
          turnos={turnosPorModulo}
          onLlamarSiguiente={llamarSiguiente}
          onFinalizarTurno={finalizarTurno}
        />
      </Box>
    </Box>
  );
};

export default Operadores;
