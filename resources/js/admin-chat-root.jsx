import React from 'react';
import { createRoot } from 'react-dom/client';
import ChatShell from './pages/Admin/ChatShell';
import './bootstrap';
import '../css/chat-shell.css';


const el = document.getElementById('admin-chat-root');
if (el) createRoot(el).render(<ChatShell />);
