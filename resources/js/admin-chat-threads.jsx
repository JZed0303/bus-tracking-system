import React from 'react';
import { createRoot } from 'react-dom/client';
import ChatThreadPage from './pages/Admin/Chat/ChatThreadPage';
import './bootstrap';

const el = document.getElementById('admin-chat-thread-root');
if (el) createRoot(el).render(<ChatThreadPage threadId={window.chatThreadId} />);
