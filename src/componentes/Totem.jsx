import React, { useEffect, useState } from 'react';
import { Grid, Button, Typography, Paper, Box, CircularProgress } from '@mui/material';
import logo from '../assets/logo.jpg';
import '../css/Totem.css'; // Asegúrate de crear este archivo
import { useWebSocket } from '../contexts/WebSocketContext';

// Organización personalizada de botones en filas
const opcionesTotem = [
  [
    { label: "Solicitud Certificados" },
    { label: "Atención General" }
  ],
  [
    { label: "Atención Apostilla" },
    { label: "Atención SAE" }
  ],
  [
    { label: "Atención Inscripción (Educ.Básica)" },
    { label: "Atención Inscripción (Educ. Media)" }
  ]
];

const Totem = () => {
  const [numeroTurno, setNumeroTurno] = useState(null);
  const [tipoSeleccionado, setTipoSeleccionado] = useState('');
  const { emitTurnoUpdate } = useWebSocket();
  const [turnoActual, setTurnoActual] = useState(null);
  const [mensaje, setMensaje] = useState('');
  const [loading, setLoading] = useState(false); // Estado para controlar el loading

  // Función para solicitar turno
  const solicitarTurno = async (tipoAtencion, idModulo) => {
    if (loading) return; // Prevención adicional
    setLoading(true);
    setTipoSeleccionado(tipoAtencion);
    try {
      const response = await fetch('http://localhost/oficina-600/backend/generar_turno.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          tipo_atencion: tipoAtencion,
          id_modulo: idModulo
        })
      });

      let data;
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        const text = await response.text();
        try {
          data = JSON.parse(text);
        } catch (e) {
          console.error('Error parsing JSON:', text);
          throw new Error('La respuesta del servidor no es JSON válido');
        }
      } else {
        throw new Error('La respuesta del servidor no es JSON válido');
      }

      if (!response.ok) {
        throw new Error(data.error || 'Error en la respuesta del servidor');
      }

      if (!data.success) {
        throw new Error(data.error || 'Error al generar el turno');
      }

      if (!data.turno || !data.turno.numero_turno) {
        throw new Error('Datos del turno incompletos');
      }

      // Actualizar el estado con el nuevo turno
      setNumeroTurno(data.turno.numero_turno);
      setTurnoActual(data.turno);
      setMensaje(`Turno ${data.turno.numero_turno} generado exitosamente`);
      
      // Limpiar el mensaje después de 5 segundos
      setTimeout(() => {
        setMensaje('');
      }, 5000);

    } catch (error) {
      console.error('Error al solicitar turno:', error);
      setMensaje(`Error: ${error.message}`);
      
      // Limpiar el mensaje después de 5 segundos
      setTimeout(() => {
        setMensaje('');
      }, 5000);
    } finally {
      setLoading(false);
    }
  };
  

  // useEffect para imprimir automáticamente y ocultar el ticket
  useEffect(() => {
    if (numeroTurno) {
      setTimeout(() => {
        window.print(); // Imprimir el ticket
        setNumeroTurno(null); // Ocultar el ticket después de imprimir
        setTipoSeleccionado(''); // Limpiar tipo seleccionado después de imprimir
      }, 1000); // Espera un segundo para asegurar que el ticket se renderice correctamente
    }
  }, [numeroTurno]);

  return (
    <Box sx={{ minHeight: '100vh', backgroundColor: '#fff', pb: 6 }}>
      {/* Cabecera con logo a la izquierda */}
      <Box sx={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'flex-start',
        p: 3, mb: 3
      }} className="no-print">
        <img
          src={logo}
          alt="Logo"
          style={{
            height: 90,
            marginRight: 36,
            objectFit: 'contain',
            borderRadius: 8,
            boxShadow: "0 2px 12px 0 #1976d230"
          }}
        />
        <Typography
          variant="h3"
          sx={{
            fontWeight: 700,
            color: '#1976d2',
            letterSpacing: 1,
            fontSize: '2.5rem'
          }}
        >
          Bienvenido al Ministerio de Educación
        </Typography>
      </Box>
      
      {/* Mensaje selección */}
      <Typography 
        variant="h3" 
        align="center" 
        sx={{ mb: 5, color: '#1976d2', fontWeight: 700, fontSize: '2rem', letterSpacing: 2 }}
        className="no-print"
      >
        Seleccione una Atención
      </Typography>

      {/* Mensaje de loading o mensaje de éxito/error */}
      {loading && (
        <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', mb: 3 }}>
          <CircularProgress size={38} sx={{ color: '#1976d2', mr: 2 }} />
          <Typography sx={{ color: '#1976d2', fontWeight: 600, fontSize: 22 }}>
            Generando turno...
          </Typography>
        </Box>
      )}
      {!loading && mensaje && (
        <Box sx={{ display: 'flex', justifyContent: 'center', mb: 3 }}>
          <Typography sx={{ color: mensaje.startsWith('Error') ? 'red' : '#1976d2', fontWeight: 600, fontSize: 22 }}>
            {mensaje}
          </Typography>
        </Box>
      )}

      {/* Grid de botones ordenados */}
      <Box sx={{ maxWidth: 1060, mx: 'auto' }}>
        {opcionesTotem.map((fila, filaIdx) => (
          <Grid container spacing={5} justifyContent="center" sx={{ mb: 5 }} key={filaIdx}>
            {fila.map((btn, colIdx) => (
              <Grid 
                item 
                xs={12} sm={6} md={6} 
                key={colIdx} 
                className="no-print"
                sx={{ display: 'flex', justifyContent: 'center' }}
              >
                <Button
                  fullWidth
                  size="large"
                  variant="contained"
                  disabled={loading}
                  sx={{
                    py: { xs: 5, sm: 7 },
                    fontSize: { xs: '2.2rem', sm: '2.6rem' },
                    fontWeight: 700,
                    minWidth: 280,
                    borderRadius: 4,
                    boxShadow: 5,
                    letterSpacing: 2,
                    background: 'linear-gradient(90deg, #1976d2 70%, #125ea3 100%)',
                    color: '#fff',
                    transition: 'transform 0.2s, box-shadow 0.2s',
                    margin: 0,
                    '&:hover': {
                      transform: 'scale(1.055)',
                      background: 'linear-gradient(90deg, #1565c0 80%, #1976d2 100%)',
                      boxShadow: 8
                    }
                  }}
                  onClick={() => solicitarTurno(btn.label, 1)}
                >
                  {btn.label}
                </Button>
              </Grid>
            ))}
          </Grid>
        ))}
      </Box>

      {/* Ticket de turno-imprimible */}
      {numeroTurno && (
        <Grid id="ticket" className="ticket-impresion" sx={{ gridColumn: '1 / -1', mt: 6, display: 'flex', justifyContent: 'center' }}>
          <Paper elevation={10} sx={{
            borderRadius: 4,
            px: 4, py: 4,
            mx: 'auto',
            maxWidth: 400,
            minWidth: 320,
            background: 'linear-gradient(120deg, #1976d2 72%, #fff 100%)',
            boxShadow: '0 8px 32px 0 rgba(25,118,210,0.18)'
          }}>
            {/* Logo en el ticket */}
            <Box sx={{ display: 'flex', justifyContent: 'center', mb: 2 }}>
              <img
                src={logo}
                alt="Logo"
                className="logo-ticket"
                style={{ height: 54, marginBottom: 8 }}
              />
            </Box>
            <Typography variant="subtitle1" align="center" sx={{
              color: '#ffeb3b',
              fontWeight: 'bold',
              letterSpacing: 1.2,
              mb: 0.5,
              fontSize: '1.4rem'
            }}>
              {tipoSeleccionado}
            </Typography>
            <Typography variant="h1" align="center" sx={{
              color: '#fff',
              fontWeight: 800,
              letterSpacing: 5,
              lineHeight: 1.1,
              mb: 1,
              fontSize: '4rem'
            }}>
              {numeroTurno}
            </Typography>
            <Typography align="center" sx={{ color: '#fff', fontSize: 24, fontWeight: 600, mb: 1 }}>
              ¡Gracias por esperar su turno!
            </Typography>
            <Typography align="center" sx={{
              mt: 1, color: '#fff', fontSize: 16, letterSpacing: 0.8
            }}>
              {new Date().toLocaleString()}
            </Typography>
          </Paper>
        </Grid>
      )}
    </Box>
  );
};

export default Totem;
