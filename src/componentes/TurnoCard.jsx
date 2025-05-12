import React from 'react';
import { Paper, Typography, Box } from '@mui/material';

const TurnoCard = ({ numero, modulo, actual }) => {
  // Colores: azul fuerte para actual, gris claro para antiguos
  const cardColor = actual ? '#1976d2' : '#f5f5f5';
  const textColor = actual ? '#fff' : '#000';
  const moduloBgColor = actual ? '#1565c0' : '#e3e3e3';
  const moduloTextColor = actual ? '#ffeb3b' : '#1976d2';

  return (
    <Paper
      elevation={actual ? 6 : 3}
      sx={{
        p: 0,
        mb: 4,
        maxWidth: 340,
        mx: 'auto',
        borderRadius: 3,
        overflow: 'hidden',
        boxShadow: actual
          ? '0 6px 24px 0 rgba(25,118,210,0.18)'
          : '0 2px 8px 0 rgba(96,125,139,0.1)',
      }}
    >
      {/* Banda superior de módulo */}
      <Box
        sx={{
          background: moduloBgColor,
          p: 1,
          textAlign: 'center',
        }}
      >
        <Typography
          variant="subtitle1"
          sx={{
            color: moduloTextColor,
            fontWeight: 'bold',
            letterSpacing: 1,
            fontSize: 18,
          }}
        >
          {modulo}
        </Typography>
      </Box>
      {/* Número de turno grande */}
      <Box
        sx={{
          background: cardColor,
          color: textColor,
          p: 4,
          textAlign: 'center',
        }}
      >
        <Typography
          variant="h1"
          sx={{
            fontWeight: 'bold',
            fontSize: '3.2rem',
            letterSpacing: 3,
            lineHeight: 1.1,
          }}
        >
          {numero}
        </Typography>
        <Typography
          variant="caption"
          sx={{ color: actual ? '#fff' : '#999', mt: 1, fontSize: '1.1rem', display: 'block' }}
        >
          {actual ? 'ATENDIENDO AHORA' : 'Llamado'}
        </Typography>
      </Box>
    </Paper>
  );
};

export default TurnoCard;
