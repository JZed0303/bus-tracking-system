import React from 'react';
import { createRoot } from 'react-dom/client';
import BusChatApp from './pages/Admin/BusChat/BusChatApp';
import './bootstrap';

const el = document.getElementById('admin-bus-chat-root');
if (el) createRoot(el).render(<BusChatApp />);
