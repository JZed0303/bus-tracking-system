import React from 'react';
import { createRoot } from 'react-dom/client';
import './bootstrap'; // IMPORTANT: load first so axios has CSRF headers, etc.
import '../css/chat-list-light-red.css';
import GroupChatsPage from './pages/Admin/GroupChats/GroupChatsPage.jsx';

const el = document.getElementById('admin-group-chats-root');

if (el) {
  createRoot(el).render(<GroupChatsPage />);
}
