import React, { useState } from 'react';
import { TextField, Button, Box, Typography, Paper, CircularProgress } from '@mui/material';
import { useNavigate } from 'react-router-dom';
import logo from '../assets/logo.jpg';

/**
 * Componente de login, consulta el backend, guarda usuario y redirige.
 */
const Login = () => {
  const [usuario, setUsuario] = useState('');
  const [clave, setClave] = useState('');
  const [error, setError] = useState('');
  const [cargando, setCargando] = useState(false);
  const navigate = useNavigate();

  const handleLogin = async (e) => {
    e.preventDefault();
    setError('');
    setCargando(true);
    
    try {
      const response = await fetch('http://localhost/oficina-600/backend/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ usuario, clave })
      });

      const data = await response.json();

      if (data.success && data.usuario) {
        // Guardar usuario en localStorage
        localStorage.setItem('usuario', JSON.stringify(data.usuario));
        // Redirigir a dashboard
        navigate('/atencion-600/dashboard');
      } else {
        setError(data.message || 'Credenciales incorrectas');
      }
    } catch (error) {
      setError('Error de conexión. Intente nuevamente.');
      console.error('Error de login:', error);
    } finally {
      setCargando(false);
    }
  };

  return (
    <Box sx={{ minHeight: '100vh', backgroundColor: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
      <Paper elevation={3} sx={{ p: 4, maxWidth: 400, width: '100%' }}>
        <Box sx={{ display: 'flex', justifyContent: 'center', mb: 2 }}>
          <img src={logo} alt="Logo" style={{ height: 60 }} />
        </Box>
        <Typography variant="h5" align="center" gutterBottom>
          Ingreso de Operario
        </Typography>
        
        <form onSubmit={handleLogin} autoComplete="off">
          <TextField
            label="Usuario"
            variant="outlined"
            fullWidth
            sx={{ mb: 2 }}
            value={usuario}
            onChange={(e) => setUsuario(e.target.value)}
            required
          />
          <TextField
            label="Clave"
            variant="outlined"
            fullWidth
            type="password"
            sx={{ mb: 2 }}
            value={clave}
            onChange={(e) => setClave(e.target.value)}
            required
          />
          {error && <Typography color="error" sx={{ mb: 2 }}>{error}</Typography>}
          <Button 
            variant="contained" 
            fullWidth 
            type="submit"
            disabled={cargando}
            sx={{ py: 1.2 }}
          >
            {cargando ? <CircularProgress size={24} color="inherit" /> : 'Ingresar'}
          </Button>
        </form>
      </Paper>
    </Box>
  );
};

export default Login;
