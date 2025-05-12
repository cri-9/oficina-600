import React from 'react';
import { AppBar, Toolbar, Typography, Box } from '@mui/material';
import logo from '../assets/logo.jpg';

const Header = ({ titulo }) => {
  return (
    <AppBar position="static" sx={{ backgroundColor: '#1976d2' }}>
      <Toolbar>
        <Box component="img" src={logo} alt="Logo" sx={{ height: 50, mr: 2 }} />
        <Typography variant="h6" sx={{ flexGrow: 1 }}>
          {titulo}
        </Typography>
      </Toolbar>
    </AppBar>
  );
};

export default Header;
