import React from 'react';
import { createRoot } from 'react-dom/client';
import ChatIndex from './pages/Admin/ChatIndex';
import './bootstrap';

const el = document.getElementById('admin-chat-index');

if (el) {
  createRoot(el).render(<ChatIndex />);
}
