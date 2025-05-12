import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import { ThemeProvider } from '@mui/material/styles';
import CssBaseline from '@mui/material/CssBaseline';
import theme from './theme';

// Componentes
import Totem from './componentes/Totem';
import Operadores from './componentes/Operadores';
import Login from './componentes/Login';
import DashboardOperador from './componentes/DashboardOperador';
import Cliente from './componentes/Cliente';

function App() {
  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <Routes>
        <Route path="/" element={<Navigate to="/cliente" replace />} />
        <Route path="/cliente" element={<Cliente />} />
        <Route path="/totem" element={<Totem />} />
        <Route path="/operadores" element={<Operadores />} />
        <Route path="/login" element={<Login />} />
        <Route path="/dashboard" element={<DashboardOperador />} />
      </Routes>
    </ThemeProvider>
  );
}

export default App;
