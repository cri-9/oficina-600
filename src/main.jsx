import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { WebSocketProvider } from './contexts/WebSocketContext';
import App from './App';
import './index.css';

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <BrowserRouter basename="/atencion-600">
      <WebSocketProvider>
        <App />
        <Toaster position="top-right" />
      </WebSocketProvider>
    </BrowserRouter>
  </React.StrictMode>
);
