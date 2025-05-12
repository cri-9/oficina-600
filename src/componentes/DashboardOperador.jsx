import React, { useState, useEffect } from "react";
import OperadorTurnosPendientes from "./OperadorTurnosPendientes";
import { useNavigate } from "react-router-dom";

/**
 * Botón de cerrar sesión profesional.
 */
const CerrarSesion = () => {
  const navigate = useNavigate();

  const salir = () => {
    localStorage.removeItem("operador");
    navigate("/atencion-600/login");
  };

  return (
    <button
      onClick={salir}
      style={{
        background: "#8a1c1c",
        color: "#fff",
        border: "none",
        borderRadius: 7,
        padding: "0.4em 1.2em",
        fontWeight: 600,
        fontSize: "1rem",
        cursor: "pointer",
        boxShadow: "0 1px 6px #0002"
      }}
      title="Cerrar sesión"
    >
      Cerrar sesión
    </button>
  );
};

/**
 * Diálogo simple de confirmación.
 */
function ConfirmDialog({ open, mensaje, onConfirm, onCancel }) {
  if (!open) return null;
  return (
    <div
      style={{
        position: "fixed",
        left: 0,
        top: 0,
        width: "100vw",
        height: "100vh",
        background: "#222b",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        zIndex: 1300,
      }}
    >
      <div
        style={{
          background: "#fff",
          borderRadius: 12,
          padding: "2rem 2.5rem",
          minWidth: 260,
          boxShadow: "0 8px 28px #0003",
          textAlign: "center",
        }}
      >
        <div style={{ marginBottom: 20, fontSize: "1.1rem" }}>{mensaje}</div>
        <div style={{ display: "flex", gap: 16, justifyContent: "center" }}>
          <button
            onClick={onCancel}
            style={{
              background: "#eee",
              color: "#222",
              border: "none",
              borderRadius: 5,
              padding: "0.5em 1.5em",
              fontWeight: 600,
              cursor: "pointer",
            }}
          >
            Cancelar
          </button>
          <button
            onClick={onConfirm}
            style={{
              background: "#b62b2b",
              color: "#fff",
              border: "none",
              borderRadius: 5,
              padding: "0.5em 1.5em",
              fontWeight: 600,
              cursor: "pointer",
            }}
          >
            Finalizar Jornada
          </button>
        </div>
      </div>
    </div>
  );
}

/**
 * Snackbar simple para mostrar alertas rápidas.
 */
function Snackbar({ mensaje, onClose }) {
  useEffect(() => {
    if (mensaje) {
      const timeout = setTimeout(() => onClose(), 3000);
      return () => clearTimeout(timeout);
    }
  }, [mensaje, onClose]);
  if (!mensaje) return null;
  return (
    <div
      style={{
        position: "fixed",
        bottom: 30,
        left: "50%",
        transform: "translateX(-50%)",
        background: "#315bb6",
        color: "#fff",
        padding: "1rem 2rem",
        borderRadius: "10px",
        boxShadow: "0 2px 16px #2225",
        zIndex: 1000,
        fontSize: "1rem",
        minWidth: "180px",
        textAlign: "center"
      }}
    >
      {mensaje}
    </div>
  );
}

/**
 * Hook para manejar los datos del operador.
 */
function useDatosOperador() {
  // Busca en localStorage o usa datos de prueba
  const [datos] = useState(() => {
    const saved = localStorage.getItem("operador");
    if (saved) return JSON.parse(saved);
    // fallback de prueba
    return {
      perfil_id: 1,
      operario_id: 12,
      modulo_id: 5,
      nombre: "Operador Demo"
    };
  });
  return datos;
}

/**
 * Dashboard operador principal.
 */
const DashboardOperador = () => {
  const { perfil_id, operario_id, modulo_id, nombre } = useDatosOperador();
  const [mensajeSnackbar, setMensajeSnackbar] = useState(null);
  const [confirmarFinalizar, setConfirmarFinalizar] = useState(false);
  const [finalizando, setFinalizando] = useState(false);
  
  const navigate = useNavigate();

  /**
   * callback para mostrar el snackbar desde hijos.
   */
  const mostrarSnackbar = (msg) => setMensajeSnackbar(msg);
  
  /**
   * Limpia el estado del operador
   */
  const limpiarDatosOperador = () => {
    localStorage.removeItem("operador");
    // Si usas context/redux, limpia allí también
  };
  
  // Si no hay datos de operador, fuerza a login (por seguridad)
  useEffect(() => {
    if (!perfil_id || !operario_id || !modulo_id) {
      navigate("/atencion-600/login");
    }
  }, [perfil_id, operario_id, modulo_id, navigate]);

  /**
   * Finaliza la jornada luego de confirmación y redirige.
   */
  const finalizarJornada = async () => {
    setFinalizando(true);
    setConfirmarFinalizar(false);
    try {
      const resp = await fetch("/backend/finalizar_jornada.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" }
      });
      const data = await resp.json();
      if (data.success) {
        mostrarSnackbar("¡Jornada finalizada correctamente!");
        limpiarDatosOperador();
        setTimeout(() => {
          navigate("/atencion-600/login");
        }, 1200); // espera para que vean el mensaje
      } else {
        mostrarSnackbar(data.message || "Hubo un error al finalizar la jornada.");
      }
    } catch (e) {
      mostrarSnackbar("Error de red al finalizar jornada.");
    } finally {
      setFinalizando(false);
    }
  };

  return (
    <div style={{ background: "#f7fafd", minHeight: "100vh" }}>
      {/* Header profesional */}
      <header
        style={{
          background: "#1B4BBE",
          color: "#fff",
          display: "flex",
          justifyContent: "space-between",
          alignItems: "center",
          padding: "1rem 2.5rem",
          boxShadow: "0 2px 14px #0003",
          position: "sticky",
          top: 0,
          zIndex: 999,
        }}
      >
        <div style={{ fontSize: "1.45rem", fontWeight: 800, letterSpacing: "0.03em" }}>
          {/* Aquí coloca tu logo como <img ... /> si lo tienes */}
          Oficina 600
        </div>
        <div style={{ display: 'flex', alignItems: 'center', gap: 20 }}>
          <span style={{ fontWeight: 600, fontSize: "1.10rem" }}>
            {nombre ? nombre : `Operador #${operario_id}`}
          </span>
          <CerrarSesion />
        </div>
      </header>

      <main style={{ maxWidth: 700, margin: "2.5rem auto 0 auto", padding: 16 }}>
        <OperadorTurnosPendientes
          perfil_id={perfil_id}
          operario_id={operario_id}
          modulo_id={modulo_id}
          onFeedback={mostrarSnackbar}
        />

        {/* Botón para finalizar jornada */}
        <div style={{ margin: "2rem 0", textAlign: "center" }}>
          <button
            onClick={() => setConfirmarFinalizar(true)}
            disabled={finalizando}
            style={{
              background: "#b62b2b",
              color: "#fff",
              fontWeight: 700,
              border: "none",
              borderRadius: 8,
              padding: "0.6em 2em",
              fontSize: "1.15rem",
              cursor: finalizando ? "not-allowed" : "pointer",
              boxShadow: "0 1px 4px #0002",
              opacity: finalizando ? 0.7 : 1
            }}
          >
            {finalizando ? "Finalizando..." : "Finalizar Jornada"}
          </button>
        </div>
      </main>

      {/* Snackbar y confirmación */}
      <Snackbar mensaje={mensajeSnackbar} onClose={() => setMensajeSnackbar(null)} />
      <ConfirmDialog
        open={confirmarFinalizar}
        mensaje="¿Estás seguro que deseas finalizar la jornada? Esta acción archivará todos los turnos del día."
        onConfirm={finalizarJornada}
        onCancel={() => setConfirmarFinalizar(false)}
      />
    </div>
  );
};

export default DashboardOperador;
