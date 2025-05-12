
import React, { createContext, useContext, useEffect, useRef, useState } from 'react';

const WebSocketContext = createContext(null);

export const WebSocketProvider = ({ 
    children, 
    socketUrl = "ws://localhost:8081", 
    maxReconnectAttempts = 5, 
    reconnectDelay = 3000 
}) => {
    const [isConnected, setIsConnected] = useState(false);
    const [reconnectAttempts, setReconnectAttempts] = useState(0);
    const socketRef = useRef(null);
    const reconnectTimeoutRef = useRef(null);
    const isUnmounted = useRef(false);

    const connect = () => {
        if (isUnmounted.current) return;

        const ws = new WebSocket(socketUrl);

        ws.onopen = () => {
            if (isUnmounted.current) {
                ws.close();
                return;
            }
            console.log('✅ WebSocket connection established');
            setIsConnected(true);
            setReconnectAttempts(0);
        };

        ws.onclose = () => {
            console.warn('⚠️ WebSocket connection closed');
            setIsConnected(false);

            if (isUnmounted.current) return;
            if (reconnectAttempts < maxReconnectAttempts) {
                const nextAttempt = reconnectAttempts + 1;
                console.log(`🔁 Retrying connection in ${reconnectDelay / 1000} seconds... (Attempt ${nextAttempt}/${maxReconnectAttempts})`);
                reconnectTimeoutRef.current = setTimeout(() => {
                    setReconnectAttempts(nextAttempt);
                    connect();
                }, reconnectDelay);
            } else {
                console.error('❌ Maximum reconnection attempts reached');
            }
        };

        ws.onerror = (error) => {
            if (isUnmounted.current) return;
            console.error('❌ WebSocket error occurred:', error);
            // Enhanced error handling can be added here
        };

        ws.onmessage = (event) => {
            if (isUnmounted.current) return;
            try {
                const data = JSON.parse(event.data);
                console.log('📩 Message received:', data);

                switch (data.type) {
                    case 'connection_established':
                        console.log('🔗 Connection confirmed by server');
                        break;
                    case 'error':
                        console.error('⚠️ Server error:', data.message);
                        break;
                    default:
                        console.log('📦 Other message:', data);
                }
            } catch (err) {
                console.error('🚫 Error processing message:', err);
            }
        };

        socketRef.current = ws;
    };

    useEffect(() => {
        isUnmounted.current = false;
        connect();

        return () => {
            isUnmounted.current = true;
            if (socketRef.current) {
                socketRef.current.close();
            }
            if (reconnectTimeoutRef.current) {
                clearTimeout(reconnectTimeoutRef.current);
            }
        };
    }, [socketUrl, maxReconnectAttempts, reconnectDelay]);

    const sendMessage = (message) => {
        if (socketRef.current && isConnected) {
            try {
                socketRef.current.send(JSON.stringify(message));
            } catch (err) {
                console.error('🚫 Error sending message:', err);
            }
        } else {
            console.warn('⛔ WebSocket is not connected. Cannot send message.');
        }
    };

    return (
        <WebSocketContext.Provider value={{ get socket() { return socketRef.current }, isConnected, sendMessage }}>
            {children}
        </WebSocketContext.Provider>
    );
};

export const useWebSocket = () => {
    const context = useContext(WebSocketContext);
    if (!context) {
        throw new Error('useWebSocket must be used within a WebSocketProvider');
    }
    return context;
};
