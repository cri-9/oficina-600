import React, { useEffect, useState } from "react";

/**
 * Componente que muestra hasta 6 turnos pendientes en dos filas de 3,
 * permitiendo llamar (enviar a atención) al pinchar uno.
 * Requiere que el operador esté autenticado o al menos tener operario_id, perfil_id y modulo_id.
 */
const OperadorTurnosPendientes = ({ perfil_id, operario_id, modulo_id, onFeedback }) => {
  const [pendientes, setPendientes] = useState([]);
  const [llamando, setLlamando] = useState(null);
  const [mensaje, setMensaje] = useState(null);
  const [turnoActual, setTurnoActual] = useState(null);

  useEffect(() => {
    fetchPendientes();
    checkTurnoActual();

    // Refrescar cada 5 segundos
    const intervalPendientes = setInterval(fetchPendientes, 5000);
    const intervalTurnoActual = setInterval(checkTurnoActual, 5000);

    // Escuchar evento global para forzar refresco en Cliente.jsx
    const handler = () => {
      fetchPendientes();
      checkTurnoActual();
    };
    window.addEventListener('turno-actualizado', handler);

    return () => {
      clearInterval(intervalPendientes);
      clearInterval(intervalTurnoActual);
      window.removeEventListener('turno-actualizado', handler);
    };
    // eslint-disable-next-line
  }, [operario_id]);

  // Verificar si ya hay un turno en atención para este operador
  const checkTurnoActual = async () => {
    if (!operario_id) return;
    
    try {
      const res = await fetch(`/backend/turno_operador.php?id_usuario=${operario_id}`);
      const data = await res.json();
      
      if (data && data.turno) {
        setTurnoActual(data.turno);
      } else {
        setTurnoActual(null);
      }
    } catch (error) {
      console.error("Error al verificar turno actual:", error);
    }
  };

  const fetchPendientes = async () => {
    try {
      const res = await fetch("/backend/obtener_turnos.php");
      const data = await res.json();
      // Cambiado: usar data.turnosActuales en vez de data.pendientes
      if (data && data.success) {
        setPendientes((data.turnosActuales || []).filter(t => t.estado === 'pendiente').slice(0, 6)); // Solo 6 turnos máximo
      }
    } catch (error) {
      console.error("Error al obtener pendientes:", error);
      if (onFeedback) onFeedback("Error al obtener pendientes");
    }
  };

  // Manejador de llamada de turno
  const llamarTurno = async (turno) => {
    if (!perfil_id || !operario_id) {
      const msg = "Faltan datos del operador para llamar el turno.";
      setMensaje(msg);
      if (onFeedback) onFeedback(msg);
      return;
    }
    
    // Si ya hay un turno en atención, informar al operador
    if (turnoActual) {
      const msg = `Ya tienes el turno ${turnoActual.numero_turno} en atención. Finalízalo primero.`;
      setMensaje(msg);
      if (onFeedback) onFeedback(msg);
      return;
    }
    
    setLlamando(turno.id);
    setMensaje(null);

    try {
      // Cambiado: enviar id_turno al backend
      const res = await fetch("/backend/llamar_siguiente.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          perfil_id,
          operario_id,
          modulo_id,
          id_turno: turno.id
        }),
      });
      
      const data = await res.json();
      
      if (data.success) {
        const msg = `Turno ${data.numero_turno} ahora está en atención`;
        setMensaje(msg);
        if (onFeedback) onFeedback(msg);
        
        // Actualizar localmente
        setTurnoActual({
          numero_turno: data.numero_turno,
          tipo_atencion: data.tipo_atencion
        });
        
        // Refrescar la lista de pendientes y forzar refresco global
        fetchPendientes();
        if (window && window.dispatchEvent) {
          window.dispatchEvent(new Event('turno-actualizado'));
        }
      } else {
        const msg = data.message || "Error al llamar turno";
        setMensaje(msg);
        if (onFeedback) onFeedback(msg);
      }
    } catch (error) {
      console.error("Error de red:", error);
      const msg = "Error de red al llamar al turno";
      setMensaje(msg);
      if (onFeedback) onFeedback(msg);
    } finally {
      setLlamando(null);
    }
  };

  // Divide en 2 filas (máximo 3 por fila)
  const filas = [pendientes.slice(0, 3), pendientes.slice(3, 6)];

  return (
    <div style={{ maxWidth: 500, margin: "0 auto" }}>
      <h2 style={{ textAlign: "center", marginBottom: 24 }}>Turnos Pendientes</h2>
      
      {turnoActual && (
        <div style={{
          padding: "12px",
          background: "#e3f2fd",
          borderRadius: "8px",
          marginBottom: "20px",
          boxShadow: "0 2px 4px rgba(0,0,0,0.1)",
          border: "1px solid #90caf9"
        }}>
          <h3 style={{ margin: "0 0 8px 0", color: "#1976d2" }}>En atención actualmente:</h3>
          <div style={{ 
            fontSize: "22px", 
            fontWeight: "bold",
            display: "flex",
            alignItems: "center",
            justifyContent: "space-between",
            gap: 10
          }}>
            <span>Turno: {turnoActual.numero_turno}</span>
            {/* SOLO el botón combinado */}
            <button
              onClick={async () => {
                try {
                  // 1. Finalizar el turno actual
                  const resFin = await fetch("/backend/finalizar_turno.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ 
                      id_usuario: operario_id,
                      numero_turno: turnoActual.numero_turno
                    }),
                  });
                  const dataFin = await resFin.json();
                  if (!dataFin.success) {
                    if (onFeedback) onFeedback(dataFin.message || "No se pudo finalizar el turno actual");
                    return;
                  }
                  // 2. Buscar siguiente pendiente en la lista local (refrescar por si acaso)
                  await fetchPendientes();
                  // refrescamos pendientes para estar seguros
                  // 3. Llamar al siguiente turno pendiente
                  const pendientesRefresh = pendientes.filter(t => t.estado === 'pendiente');
                  const siguiente = pendientesRefresh[0];
                  if (!siguiente) {
                    setTurnoActual(null);
                    if (onFeedback) onFeedback("No quedan turnos pendientes.");
                    if (window && window.dispatchEvent) {
                      window.dispatchEvent(new Event('turno-actualizado'));
                    }
                    return;
                  }
                  // Llama al siguiente
                  const resLlamar = await fetch("/backend/llamar_siguiente.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({
                      perfil_id,
                      operario_id,
                      modulo_id,
                      id_turno: siguiente.id
                    }),
                  });
                  const dataLlamar = await resLlamar.json();
                  if (dataLlamar.success) {
                    setTurnoActual({
                      numero_turno: dataLlamar.numero_turno,
                      tipo_atencion: dataLlamar.tipo_atencion
                    });
                    if (onFeedback) onFeedback(`Turno ${dataLlamar.numero_turno} está ahora en atención`);
                    fetchPendientes();
                    if (window && window.dispatchEvent) {
                      window.dispatchEvent(new Event('turno-actualizado'));
                    }
                  } else {
                    setTurnoActual(null);
                    if (onFeedback) onFeedback(dataLlamar.message || "No se pudo llamar al siguiente turno.");
                    fetchPendientes();
                    if (window && window.dispatchEvent) {
                      window.dispatchEvent(new Event('turno-actualizado'));
                    }
                  }
                } catch (error) {
                  console.error("Error Finalizar y Llamar Siguiente:", error);
                  if (onFeedback) onFeedback("Ocurrió un error en la operación combinada");
                }
              }}
              style={{
                backgroundColor: "#1976d2",
                color: "white",
                border: "none",
                borderRadius: "4px",
                padding: "8px 16px",
                cursor: "pointer",
                fontWeight: "bold"
              }}
            >
              Finalizar y Llamar Siguiente
            </button>
          </div>
        </div>
      )}
      
      {mensaje && (
        <div
          style={{
            marginBottom: "1rem",
            color: mensaje.includes("ahora") ? "green" : "red",
          }}
        >
          {mensaje}
        </div>
      )}
      <div
        style={{
          display: "grid",
          gridTemplateColumns: "repeat(3, 1fr)",
          gridTemplateRows: "repeat(2, 1fr)",
          gap: "22px",
          margin: "24px 0",
          minHeight: 220,
        }}
      >
        {Array(6)
          .fill(0)
          .map((_, idx) => {
            const turno = pendientes[idx];
            if (turno) {
              return (
                <button
                  key={turno.id}
                  disabled={llamando === turno.id || !!turnoActual}
                  onClick={() => llamarTurno(turno)}
                  style={{
                    border: "2px solid #2196F3",
                    borderRadius: "12px",
                    padding: "1.2em .5em",
                    minWidth: 110,
                    minHeight: 80,
                    textAlign: "center",
                    background: llamando === turno.id ? "#e6e6e6" : turnoActual ? "#f5f5f5" : "#fafdff",
                    cursor: llamando === turno.id ? "wait" : turnoActual ? "not-allowed" : "pointer",
                    boxShadow: llamando === turno.id ? "0 0 13px #2196F3 inset" : "0 2px 6px #0001",
                    fontFamily: "inherit",
                    fontSize: "1.07em",
                    opacity: turnoActual ? 0.7 : 1,
                    transition: "box-shadow 0.2s, background 0.2s",
                    display: "flex",
                    flexDirection: "column",
                    alignItems: "center",
                    justifyContent: "center",
                  }}
                  title={turnoActual ? "Ya tienes un turno en atención" : "Pincha para llamar este turno"}
                >
                  <div>
                    <strong style={{ fontSize: 28 }}>{turno.numero_turno}</strong>
                  </div>
                  <div style={{ fontSize: "1.05rem", color: "#2196F3", fontWeight: 600 }}>
                    {turno.tipo_atencion}
                  </div>
                </button>
              );
            } else {
              // Espacio vacío para mantener la cuadrícula
              return <div key={"vacio" + idx} style={{ minWidth: 110, minHeight: 80 }}></div>;
            }
          })}
      </div>
    </div>
  );
};

export default OperadorTurnosPendientes;
