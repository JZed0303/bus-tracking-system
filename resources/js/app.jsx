import React from 'react';
import { createRoot } from 'react-dom/client';
import LiveBusMap from './pages/Admin/LiveBusMap';

import './bootstrap';
import './chat-global-listener';
import { initGlobalPresence } from './presence-global';
initGlobalPresence();

// ✅ Leaflet core + CSS via Vite
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// ✅ Leaflet Routing Machine (JS + CSS) via Vite
import 'leaflet-routing-machine';
import 'leaflet-routing-machine/dist/leaflet-routing-machine.css';

// ✅ Fix marker icons for Vite/Webpack builds
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({
  iconRetinaUrl,
  iconUrl,
  shadowUrl,
});

// ✅ Expose Leaflet (with Routing) to window for Blade/jQuery scripts
if (typeof window !== 'undefined') {
  window.L = L;
}

const el = document.getElementById('admin-live-map');

if (el) {
  createRoot(el).render(<LiveBusMap />);
}
