import React, { useState } from 'react';
import { Grid, Typography, Box, Button, CircularProgress } from "@mui/material";
import TurnoCard from "./TurnoCard";
import { toast } from "react-hot-toast";
import axios from 'axios';

/**
 * @param {Object[]} turnos - Array de objetos turno, cada uno debe tener:
 * { id, numero, modulo, estado, id_modulo } donde estado es 'pendiente' o 'en_atencion'
 * @param {function} onLlamarSiguiente - Función llamada con id_modulo al presionar "Llamar"
 */
const ListaTurnos = ({ turnos, onLlamarSiguiente, obtenerTurnos }) => { // <-- Asegúrate de recibir obtenerTurnos como prop
  const [loading, setLoading] = useState(false);
  const [loadingTurnoId, setLoadingTurnoId] = useState(null);
  const [resetting, setResetting] = useState(false);

  // Filtrado por estado real usado en la base/backend
  // Aseguramos que los campos sean los que espera TurnoCard
  const turnosPendientes = turnos.filter((t) => t.estado === "pendiente").map(t => ({
    ...t,
    numero: t.numero || t.numero_turno,
    modulo: t.modulo || t.id_modulo
  }));

  // Agrupar turnos en atención por módulo y tomar solo el más reciente
  const turnosEnAtencionPorModulo = turnos
    .filter((t) => t.estado === "en_atencion")
    .reduce((acc, turno) => {
      if (!acc[turno.id_modulo] || new Date(turno.fecha_atencion) > new Date(acc[turno.id_modulo].fecha_atencion)) {
        acc[turno.id_modulo] = turno;
      }
      return acc;
    }, {});

  const turnosAtencion = Object.values(turnosEnAtencionPorModulo).map(t => ({
    ...t,
    numero: t.numero || t.numero_turno,
    modulo: t.modulo || t.id_modulo,
    ...t, // Incluimos todas las propiedades originales, incluido 'id'
  }));

  // Función robusta para resetear todos los turnos
  const handleResetearTurnos = async () => {
    if (resetting || loading) return; // Prevenir múltiples clicks o conflictos con otras operaciones
    setResetting(true);

    try {
      console.log('[Resetear] Iniciando reseteo de turnos...');
      const response = await axios.post('http://localhost/oficina-600/backend/resetear_turnos.php');

      console.log('[Resetear] Respuesta:', response.data);

      if (response.data.success) {
        toast.success(response.data.message || 'Turnos reseteados exitosamente');

        // Actualizar la lista una sola vez es suficiente si el backend es confiable
        if (obtenerTurnos) { // Usamos la prop obtenerTurnos
          console.log('[Resetear] Actualizando lista tras reseteo...');
          await obtenerTurnos();
        }
      } else {
        toast.error(response.data.error || 'No se pudieron resetear los turnos');
      }
    } catch (error) {
      console.error('[Resetear][Error]:', error);
      toast.error(error.response?.data?.error || error.message || 'Error al resetear turnos');
    } finally {
      setResetting(false);
    }
  };

  // Función para finalizar un turno
  const handleFinalizarTurno = async (turno) => {
    if (loading) return;

    // Validar robustamente el id_modulo
    const idModuloNum = Number(turno.id_modulo);
    if (!idModuloNum || isNaN(idModuloNum) || idModuloNum <= 0) {
      toast.error('No se puede finalizar: ID de módulo no válido');
      return;
    }
    // Validar que el estado sea correcto
    // Para id
    const idTurnoNum = Number(turno.id);
    if (!idTurnoNum || isNaN(idTurnoNum) || idTurnoNum <= 0) {
      toast.error('No se puede finalizar: ID de turno no válido');
      return;
    }

    setLoading(true);
    setLoadingTurnoId(turno.id);

    try {
      // Finalizar el turno actual
      console.log('[Finalizar] Datos a enviar:', { id: turno.id });
      const responseFinalizar = await axios.post('http://localhost/oficina-600/backend/finalizar_turno.php',  {
        id: turno.id,
      }, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      });

      console.log('[Finalizar] Respuesta:', responseFinalizar.data);


      if (responseFinalizar.data.success) {
        toast.success('Turno finalizado correctamente');
        if (obtenerTurnos) { // Usamos la prop obtenerTurnos
          console.log('[Finalizar] Actualizando lista tras finalizar...');
          await obtenerTurnos();
        }
      } else {
        toast.error(responseFinalizar.data.error || 'Error al finalizar el turno');
      }
    } catch (error) {
      console.error('[Finalizar][Error]:', error);
      if (error.response) {
        console.error('[Finalizar][Error response]:', error.response.data);
      }
      toast.error(error.response?.data?.error || error.message || 'Error al finalizar el turno');
    } finally {
      setLoading(false);
      setLoadingTurnoId(null);
    }
  };

  // Función para finalizar y llamar siguiente
  const handleFinalizarYLLamar = async (turno) => {
    if (loading) return;

    setLoading(true);
    setLoadingTurnoId(turno.id);

    try {
      // 1. FINALIZAR TURNO ACTUAL
      console.log('[Finalizar y llamar] Turno a finalizar:', turno); // <---- LOG AÑADIDO AQUÍ
      const responseFinalizar = await axios.post('http://localhost/oficina-600/backend/finalizar_turno.php', {
        id: turno.id
      }, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      });

      console.log('[Finalizar] Respuesta:', responseFinalizar.data);

      if (!responseFinalizar.data.success) {
        throw new Error(responseFinalizar.data.error || 'Error al finalizar el turno');
      }

      toast.success('Turno finalizado correctamente');

      // Refrescar lista tras finalizar
      if (obtenerTurnos) { // Usamos la prop obtenerTurnos
        console.log('[Finalizar] Actualizando lista tras finalizar...');
        await obtenerTurnos();
      }

      // Esperar para asegurar persistencia
      await new Promise(resolve => setTimeout(resolve, 800));

      // 2. LLAMAR SIGUIENTE TURNO
      console.log('[Llamar siguiente] Módulo:', turno.id_modulo, 'Tipo:', typeof turno.id_modulo);

      const responseLlamarSiguiente = await axios.post(
        'http://localhost/oficina-600/backend/llamar_siguiente_turno.php',
        { id_modulo: turno.id_modulo },
        {
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        }
      );
      console.log('[Llamar siguiente] Respuesta:', responseLlamarSiguiente.data);

      if (responseLlamarSiguiente.data?.success) { // Use optional chaining
        toast.success(`Turno ${responseLlamarSiguiente.data.turno.numero_turno} llamado`);
      } else {
        const errorMessage = responseLlamarSiguiente.data?.error || 'Error al llamar siguiente turno'; // Handle undefined
        toast.error(errorMessage);
        console.error('[Llamar siguiente][Error]:', responseLlamarSiguiente.data); // Log the entire response
      }

    } catch (error) {
      console.error('[Finalizar y llamar][Error]:', error);
      toast.error('Error al finalizar y/o llamar siguiente turno');
    } finally {
      setLoading(false);
      setLoadingTurnoId(null);
    }
  };

  const handleLlamarSiguienteUI = async (idModulo) => {
    if (loading) return;

    const parsedIdModulo = parseInt(idModulo, 10);
    if (!parsedIdModulo || isNaN(parsedIdModulo)) {
      toast.error('No se puede llamar: ID de módulo no válido');
      return;
    }

    setLoading(true);

    try {
      console.log('[Llamar] Llamando turno para módulo:', parsedIdModulo, 'Tipo:', typeof parsedIdModulo);

      const response = await axios.post('http://localhost/oficina-600/backend/llamar_siguiente_turno.php', {
        id_modulo: parsedIdModulo
      }, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      });

      console.log('[Llamar] Respuesta:', response.data);

      if (response.data.success) {
        toast.success('Turno llamado correctamente');
      } else if (response.data.error?.includes('Ya existe un turno en atención')) {
        // Caso especial: no es un error fatal
        toast('Ya hay un turno en atención para ese módulo. Refrescando la lista...');
      } else {
        toast.error(response.data.error || 'Error al llamar siguiente turno');
      }

      // Siempre refresca la lista tras la respuesta, exitosa o no
      if (obtenerTurnos) { // Usamos la prop obtenerTurnos
        await obtenerTurnos();
      }

    } catch (error) {
      console.error('[Llamar][Error]:', error);

      // Manejo diferenciado de errores
      if (error.response?.data?.error?.includes('Ya existe un turno en atención')) {
        // Caso especial: no es un error fatal
        toast('Ya hay un turno en atención para ese módulo. Refrescando la lista...');
        if (obtenerTurnos) await obtenerTurnos();
      } else if (error.response?.data?.error) {
        // Error específico del backend
        toast.error(error.response.data.error);
      } else if (error.response?.status === 404) {
        toast.error('No se pudo conectar con el servicio de turnos');
      } else if (error.response?.status >= 500) {
        toast.error('Error en el servidor. Intente nuevamente más tarde');
      } else {
        // Error genérico
        toast.error(error.message || 'Error al llamar siguiente turno');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <Box sx={{ maxWidth: 1200, mx: "auto", mt: 6 }}>
      {/* Botón de reseteo de turnos */}
      <Box sx={{ display: 'flex', justifyContent: 'flex-end', mb: 2 }}>
        <Button
          variant="outlined"
          color="warning"
          onClick={handleResetearTurnos}
          disabled={resetting || loading}
          startIcon={resetting ? <CircularProgress size={20} /> : null}
        >
          {resetting ? 'Reseteando...' : 'Resetear Todos los Turnos'}
        </Button>
      </Box>

      <Grid container spacing={6}>
        {/* Columna En Atención */}
        <Grid item xs={12} md={6}>
          <Typography variant="h4" align="center" sx={{ fontWeight: "bold", color: "#1976d2", mb: 3 }}>
            En Atención
          </Typography>
          {turnosAtencion.length === 0 ? (
            <Typography align="center" color="text.secondary">
              No hay turnos en atención.
            </Typography>
          ) : (
            turnosAtencion.map((turno) => (
              <Box key={turno.id || `${turno.numero}-${turno.modulo}`}
                sx={{ width: '100%' }}>
                <TurnoCard
                  numero={turno.numero}
                  modulo={turno.modulo}
                  actual={true}
                />
                <Box sx={{ textAlign: 'center', mt: -1, display: 'flex', justifyContent: 'center', gap: 2 }}>
                  <Button
                    variant="contained"
                    color="error"
                    sx={{ mt: 1 }}
                    onClick={() => handleFinalizarTurno(turno)}
                    disabled={loading && loadingTurnoId === turno.id}
                  >
                    {loading && loadingTurnoId === turno.id ? 'Procesando...' : 'Finalizar Turno'}
                  </Button>
                  <Button
                    variant="contained"
                    color="success"
                    sx={{ mt: 1 }}
                    onClick={() => handleFinalizarYLLamar(turno)}
                    disabled={loading && loadingTurnoId === turno.id}
                  >
                    {loading && loadingTurnoId === turno.id ? 'Procesando...' : 'Finalizar y Llamar Siguiente'}
                  </Button>
                </Box>
              </Box>
            ))
          )}
        </Grid>
        {/* Columna Pendientes */}
        <Grid item xs={12} md={6}>
          <Typography variant="h4" align="center" sx={{ fontWeight: "bold", color: "#888", mb: 3 }}>
            Pendientes
          </Typography>
          {turnosPendientes.length === 0 ? (
            <Typography align="center" color="text.secondary">
              No hay turnos pendientes.
            </Typography>
          ) : (
            <Box
              sx={{
                display: 'grid',
                gridTemplateColumns: 'repeat(3, 1fr)',
                gridTemplateRows: 'repeat(2, 1fr)',
                gap: 3,
                minHeight: 220,
                justifyItems: 'center',
                alignItems: 'center',
              }}
            >
              {Array(6)
                .fill(0)
                .map((_, idx) => {
                  const turno = turnosPendientes[idx];
                  if (turno) {
                    const moduloEnAtencion = turnosAtencion.some(t => t.id_modulo === turno.id_modulo);
                    return (
                      <Box key={turno.id || `${turno.numero}-${turno.modulo}`}
                        sx={{ width: '100%' }}>
                        <TurnoCard
                          numero={turno.numero}
                          modulo={turno.modulo}
                          actual={false}
                        />
                        {onLlamarSiguiente && (
                          <Box sx={{ textAlign: 'center', mt: -1 }}>
                            <Button
                              variant="contained"
                              color="primary"
                              sx={{ mt: 1 }}
                              onClick={() => {
                                console.log('[UI] Botón Llamar presionado para módulo:', turno.id_modulo);
                                handleLlamarSiguienteUI(turno.id_modulo);
                              }}
                              disabled={(loading && loadingTurnoId === null) || moduloEnAtencion}
                            >
                              {(loading && loadingTurnoId === null) ? 'Procesando...' : 'Llamar'}
                            </Button>
                          </Box>
                        )}
                      </Box>
                    );
                  } else {
                    // Espacio vacío para mantener la cuadrícula
                    return <Box key={"vacio" + idx} sx={{ minHeight: 80 }} />;
                  }
                })}
            </Box>
          )}
        </Grid>
      </Grid>
    </Box>
  );
};

export default ListaTurnos;