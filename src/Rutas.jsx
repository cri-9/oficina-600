import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import Login from './componentes/Login';
import Cliente from './componentes/Cliente';
import Operadores from './componentes/Operadores';
import Totem from './componentes/Totem';
import DashboardOperador from './componentes/DashboardOperador';

// Protección opcional de ruta por login básico
const ProtegidoOperador = ({ children }) => {
  // Busca datos en localStorage
  const datos = localStorage.getItem("operador");
  if (!datos) return <Navigate to="/atencion-600/login" replace />;
  return children;
};

const Rutas = () => {
  return (
    <Routes>
      <Route path="/atencion-600/login" element={<Login />} />
      <Route path="/atencion-600/cliente" element={<Cliente />} />
      <Route path="/atencion-600/operadores" element={<Operadores />} />
      <Route path="/atencion-600/totem" element={<Totem />} />

      {/* Dashboard principal para el operador */}
      <Route
        path="/atencion-600/dashboard"
        element={
          <ProtegidoOperador>
            <DashboardOperador />
          </ProtegidoOperador>
        }
      />

      {/* Ruta por defecto */}
      <Route path="*" element={<Navigate to="/atencion-600/login" replace />} />
    </Routes>
  );
};

export default Rutas;
