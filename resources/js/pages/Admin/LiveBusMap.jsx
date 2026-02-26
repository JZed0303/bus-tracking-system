import React, { useEffect, useMemo, useRef, useState } from 'react';
import { MapContainer, TileLayer, Marker, Popup, useMap } from 'react-leaflet';
import L from 'leaflet';
import axios from 'axios';

/* =========================================
   Normalize backend payload
========================================= */
function normalizeBusPayload(payload) {
  if (!payload) return null;

  const lat = payload.lat ?? payload.latitude ?? null;
  const lng = payload.lng ?? payload.longitude ?? null;
  const plateNumber = payload.plate_number ?? payload.bus ?? null;

  const busId =
    payload.bus_id !== undefined && payload.bus_id !== null
      ? Number(payload.bus_id)
      : payload.id !== undefined && payload.id !== null
      ? Number(payload.id)
      : null;

  if (!Number.isFinite(busId)) return null;

  return {
    ...payload,
    bus_id: busId,
    plate_number: plateNumber,
    trip_id: payload.trip_id ?? null,
    lat: lat !== null && lat !== '' ? Number(lat) : null,
    lng: lng !== null && lng !== '' ? Number(lng) : null,
    speed:
      payload.speed !== null && payload.speed !== undefined
        ? Number(payload.speed)
        : null,
    last_seen_ms: payload.last_seen_ms ?? null,
  };
}

/* =========================================
   Merge logic (prevents marker vanish)
========================================= */
function mergeBus(prevBus, incomingBus) {
  const now = Date.now();

  if (!prevBus) return { ...incomingBus, last_seen_ms: now };

  return {
    ...prevBus,
    ...incomingBus,
    lat: incomingBus.lat ?? prevBus.lat ?? null,
    lng: incomingBus.lng ?? prevBus.lng ?? null,
    speed: incomingBus.speed ?? prevBus.speed ?? null,
    last_seen_ms: now,
  };
}

/* =========================================
   Map ref binder (GUARANTEED mapRef.current)
========================================= */
function MapRefBinder({ mapRef }) {
  const map = useMap();

  useEffect(() => {
    mapRef.current = map;
    setTimeout(() => map.invalidateSize(), 50);
  }, [map, mapRef]);

  return null;
}

/* =========================================
   Leaflet control: Home + Reset ABOVE zoom
========================================= */
function MapTopLeftControls({ onHome, onReset, onFit }) {
  const map = useMap();

  useEffect(() => {
    const Control = L.Control.extend({
      options: { position: 'topleft' },

      onAdd() {
        const container = L.DomUtil.create('div', 'leaflet-bar map-custom-controls');

        const home = L.DomUtil.create('div', 'map-control-btn', container);
        home.title = 'Home';
        home.innerHTML = '🏠';

        const reset = L.DomUtil.create('div', 'map-control-btn', container);
        reset.title = 'Reset View';
        reset.innerHTML = '⟳';

        const fit = L.DomUtil.create('div', 'map-control-btn', container);
        fit.title = 'Fit to Buses';
        fit.innerHTML = '⤢';

        L.DomEvent.disableClickPropagation(container);

        [home, reset, fit].forEach((el) => {
          L.DomEvent.on(el, 'click', L.DomEvent.stop);
        });

        L.DomEvent.on(home, 'click', () => onHome?.());
        L.DomEvent.on(reset, 'click', () => onReset?.());
        L.DomEvent.on(fit, 'click', () => onFit?.());

        return container;
      },
    });

    const control = new Control();
    map.addControl(control);

    return () => {
      map.removeControl(control);
    };
  }, [map, onHome, onReset, onFit]);

  return null;
}

export default function LiveBusMap() {
  const [buses, setBuses] = useState([]);

  // UI / filtering
  const [query, setQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all'); // all | live | offline
  const [companyFilter, setCompanyFilter] = useState('all');
  const [routeFilter, setRouteFilter] = useState('all');

  // Follow mode
  const [followBusId, setFollowBusId] = useState(null); // number | null
  const FOLLOW_FLY_ZOOM = 17;
  const FOLLOW_INTERVAL_MS = 1500;

  const mapRef = useRef(null);
  const markerRefs = useRef(new Map());
  const didAutoFollowRef = useRef(false);

  // Timers
  const OFFLINE_REMOVE_MS = 10 * 60 * 1000; // purge from state after 10 minutes
  const OFFLINE_LABEL_MS = 30 * 1000; // show "offline" label after 30 seconds
  const POLLING_INTERVAL_MS = 10_000;

  const DEFAULT_CENTER = [14.3290, 121.0450];
  const DEFAULT_ZOOM = 13;
  const initialTripIdParam =
    typeof window !== 'undefined'
      ? Number(new URLSearchParams(window.location.search).get('trip'))
      : null;

  const role = String(window.userRole || '').toLowerCase();
  const companyId = window.companyId ?? null;
  const isAdminRole = ['super_admin', 'system_admin', 'administrator', 'admin'].includes(role);
  const liveBusesEndpoint =
    !isAdminRole && (role === 'company_admin' || companyId)
      ? '/company/api/live-buses'
      : '/admin/api/live-buses';

  /* =========================================
     Helpers
  ========================================= */
  const formatSince = (ms) => {
    if (ms == null) return '—';
    const s = Math.max(0, Math.floor(ms / 1000));
    const m = Math.floor(s / 60);
    const r = s % 60;
    if (m <= 0) return `${r}s`;
    return `${m}m ${r}s`;
  };

  const getIsOffline = (bus) => {
    const now = Date.now();
    const msSince = bus?.last_seen_ms ? now - bus.last_seen_ms : null;
    return msSince !== null && msSince > OFFLINE_LABEL_MS;
  };

  const unique = (arr) => Array.from(new Set(arr.filter(Boolean)));

  const companyOptions = useMemo(() => unique(buses.map((b) => b.company)), [buses]);
  const routeOptions = useMemo(() => unique(buses.map((b) => b.route)), [buses]);

  /* =========================================
     Polling fallback
  ========================================= */
  const fetchLiveBuses = async () => {
    try {
      const res = await axios.get(liveBusesEndpoint);
      const list = res.data?.data ?? [];
      const normalized = list.map(normalizeBusPayload).filter(Boolean);

      // do not wipe existing markers if backend returns empty
      if (normalized.length === 0) return;

      setBuses((prev) => {
        const map = new Map(prev.map((b) => [b.bus_id, b]));
        normalized.forEach((b) => {
          const existing = map.get(b.bus_id);
          map.set(b.bus_id, mergeBus(existing, b));
        });
        return Array.from(map.values());
      });
    } catch (e) {
      console.error('[LiveBusMap] Live fetch failed', e);
    }
  };

  useEffect(() => {
    fetchLiveBuses();
    const interval = setInterval(fetchLiveBuses, POLLING_INTERVAL_MS);
    return () => clearInterval(interval);
  }, [POLLING_INTERVAL_MS]);

  /* =========================================
     REALTIME via Echo
  ========================================= */
  useEffect(() => {
    if (!window.Echo) return;
    const channelName = isAdminRole ? 'admin.buses' : companyId ? `company.${companyId}` : null;
    if (!channelName) return;

    const channel = window.Echo.private(channelName);

    const onLocation = (payload) => {
      const bus = normalizeBusPayload(payload);
      if (!bus) return;

      setBuses((prev) => {
        const map = new Map(prev.map((b) => [b.bus_id, b]));
        const existing = map.get(bus.bus_id);
        map.set(bus.bus_id, mergeBus(existing, bus));
        return Array.from(map.values());
      });
    };

    // Optional: backend can send logout/offline
    const onStatus = (payload) => {
      const busId = payload?.bus_id != null ? Number(payload.bus_id) : null;
      if (!Number.isFinite(busId)) return;

      // Keep the bus in list (soft offline) unless you truly want to remove immediately.
      if (payload?.type === 'logout' || payload?.status === 'offline') {
        setBuses((prev) =>
          prev.map((b) => (b.bus_id === busId ? { ...b, last_seen_ms: b.last_seen_ms ?? Date.now() } : b))
        );
      }
    };

    channel.listen('.bus.location.updated', onLocation);
    channel.listen('.bus.status.updated', onStatus);

    return () => {
      channel.stopListening('.bus.location.updated', onLocation);
      channel.stopListening('.bus.status.updated', onStatus);
      window.Echo.leave(`private-${channelName}`);
    };
  }, [companyId, isAdminRole]);

  /* =========================================
     Offline cleanup (purge very stale)
  ========================================= */
  useEffect(() => {
    const t = setInterval(() => {
      const now = Date.now();
      setBuses((prev) =>
        prev.filter((b) => {
          if (!b.last_seen_ms) return true;
          return now - b.last_seen_ms < OFFLINE_REMOVE_MS;
        })
      );
    }, 30_000);

    return () => clearInterval(t);
  }, []);

  /* =========================================
     Filtered & sorted list
  ========================================= */
  const filteredList = useMemo(() => {
    const q = query.trim().toLowerCase();

    const filtered = buses.filter((bus) => {
      const isOffline = getIsOffline(bus);

      // status filter
      if (statusFilter === 'live' && isOffline) return false;
      if (statusFilter === 'offline' && !isOffline) return false;

      // company filter
      if (companyFilter !== 'all' && (bus.company ?? '') !== companyFilter) return false;

      // route filter
      if (routeFilter !== 'all' && (bus.route ?? '') !== routeFilter) return false;

      // search query
      if (!q) return true;

      const hay = [bus.plate_number, bus.bus_id, bus.driver, bus.company, bus.route]
        .filter((v) => v !== null && v !== undefined)
        .join(' ')
        .toLowerCase();

      return hay.includes(q);
    });

    filtered.sort((a, b) => (b.last_seen_ms ?? 0) - (a.last_seen_ms ?? 0));
    return filtered;
  }, [buses, query, statusFilter, companyFilter, routeFilter]);

  /* =========================================
     Actions
  ========================================= */
  const goHome = () => {
    window.location.href = '/admin/dashboard';
  };

  const resetView = () => {
    const map = mapRef.current;
    if (!map) return;
    map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
  };

  const fitToBuses = () => {
    const map = mapRef.current;
    if (!map) return;

    const points = filteredList
      .map((b) => [Number(b.lat), Number(b.lng)])
      .filter(([lat, lng]) => Number.isFinite(lat) && Number.isFinite(lng));

    if (points.length === 0) return;

    const bounds = L.latLngBounds(points);
    map.fitBounds(bounds, { padding: [30, 30] });
  };

  const flyToBus = (bus, { follow = false } = {}) => {
    const map = mapRef.current;

    const lat = Number(bus?.lat);
    const lng = Number(bus?.lng);

    if (!map) return;
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

    map.invalidateSize(true);
    map.flyTo([lat, lng], 17, { animate: true, duration: 0.75 });

    if (follow) setFollowBusId(bus.bus_id);

    setTimeout(() => {
      const marker = markerRefs.current.get(bus.bus_id);
      marker?.openPopup?.();
    }, 250);
  };

  /* =========================================
     Follow loop
  ========================================= */
  useEffect(() => {
    if (followBusId == null) return;

    const t = setInterval(() => {
      const map = mapRef.current;
      if (!map) return;

      const bus = buses.find((b) => b.bus_id === followBusId);
      if (!bus) return;

      const lat = Number(bus.lat);
      const lng = Number(bus.lng);
      if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

      // smoother follow
      map.panTo([lat, lng], { animate: true });
      if (map.getZoom() < FOLLOW_FLY_ZOOM) map.setZoom(FOLLOW_FLY_ZOOM);
    }, FOLLOW_INTERVAL_MS);

    return () => clearInterval(t);
  }, [followBusId, buses]);

  /* =========================================
     Auto-follow trip from query (?trip=ID)
  ========================================= */
  useEffect(() => {
    if (didAutoFollowRef.current) return;
    if (!Number.isFinite(initialTripIdParam)) return;
    if (!buses.length) return;

    const target = buses.find((b) => Number(b.trip_id) === initialTripIdParam);
    if (!target) return;

    flyToBus(target, { follow: true });
    didAutoFollowRef.current = true;
  }, [buses, initialTripIdParam]);

  /* =========================================
     Render
  ========================================= */
  return (
    <div style={{ height: '100vh', width: '100%', position: 'relative' }}>
      {/* LIVE BUS LIST (RIGHT PANEL) */}
      <div
        style={{
          position: 'absolute',
          top: 12,
          right: 12,
          width: 360,
          maxHeight: 'calc(100vh - 24px)',
          zIndex: 1000,
          overflow: 'hidden',
          pointerEvents: 'auto',
        }}
        className="card shadow-sm"
      >
        <div className="card-header">
          <div className="d-flex align-items-center justify-content-between">
            <div>
              <strong>Live Buses</strong>
              <div className="text-muted" style={{ fontSize: 12 }}>
                Showing {filteredList.length} bus(es)
              </div>
            </div>

            <div className="d-flex gap-2">
              <button className="btn btn-sm btn-light" onClick={fetchLiveBuses}>
                Refresh
              </button>
              <button className="btn btn-sm btn-light" onClick={fitToBuses} title="Fit map to visible buses">
                Fit
              </button>
            </div>
          </div>

          <div className="mt-2 d-flex gap-2">
            <input
              className="form-control form-control-sm"
              placeholder="Search plate, driver, company, route..."
              value={query}
              onChange={(e) => setQuery(e.target.value)}
            />
          </div>

          <div className="mt-2 d-flex gap-2">
            <select
              className="form-select form-select-sm"
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
            >
              <option value="all">All</option>
              <option value="live">Live</option>
              <option value="offline">Offline</option>
            </select>

            <select
              className="form-select form-select-sm"
              value={companyFilter}
              onChange={(e) => setCompanyFilter(e.target.value)}
            >
              <option value="all">All companies</option>
              {companyOptions.map((c) => (
                <option key={`co-${c}`} value={c}>
                  {c}
                </option>
              ))}
            </select>

            <select className="form-select form-select-sm" value={routeFilter} onChange={(e) => setRouteFilter(e.target.value)}>
              <option value="all">All routes</option>
              {routeOptions.map((r) => (
                <option key={`rt-${r}`} value={r}>
                  {r}
                </option>
              ))}
            </select>
          </div>

          {followBusId != null && (
            <div className="mt-2 d-flex align-items-center justify-content-between">
              <div style={{ fontSize: 12 }} className="text-muted">
                Following Bus #{followBusId}
              </div>
              <button className="btn btn-sm btn-outline-secondary" onClick={() => setFollowBusId(null)}>
                Stop
              </button>
            </div>
          )}
        </div>

        <div style={{ overflowY: 'auto', maxHeight: 'calc(100vh - 200px)' }}>
          {filteredList.length === 0 ? (
            <div className="p-3 text-muted">No buses found.</div>
          ) : (
            <ul className="list-group list-group-flush">
              {filteredList.map((bus) => {
                const now = Date.now();
                const msSince = bus.last_seen_ms ? now - bus.last_seen_ms : null;
                const isOffline = msSince !== null && msSince > OFFLINE_LABEL_MS;

                return (
                  <li
                    key={`bus-row-${bus.bus_id}`}
                    title="Click to locate this bus on the map"
                    className="list-group-item bus-row"
                    style={{ cursor: 'pointer' }}
                    onClick={() => flyToBus(bus)}
                  >
                    <div className="d-flex justify-content-between align-items-start">
                      <div>
                        <div style={{ fontWeight: 600 }}>{bus.plate_number ?? `Bus #${bus.bus_id}`}</div>
                        <div className="text-muted" style={{ fontSize: 12 }}>
                          {bus.company ?? '—'} • {bus.route ?? '—'} • {bus.driver ?? '—'}
                        </div>
                        <div className="text-muted" style={{ fontSize: 12 }}>
                          Speed: {bus.speed ?? 0} km/h
                        </div>
                      </div>

                      <div className="d-flex align-items-center gap-2">
                        <span className={`badge ${isOffline ? 'bg-secondary' : 'bg-success'}`}>
                          {isOffline ? 'offline' : 'live'}
                        </span>

                        <button
                          className="btn btn-sm btn-outline-primary"
                          onClick={(e) => {
                            e.stopPropagation();
                            flyToBus(bus, { follow: true });
                          }}
                          title="Follow this bus"
                        >
                          Follow
                        </button>

                        <i className="mdi mdi-chevron-right text-muted" />
                      </div>
                    </div>

                    {msSince !== null && (
                      <div className="text-muted" style={{ fontSize: 12, marginTop: 4 }}>
                        Last seen: {formatSince(msSince)} ago
                      </div>
                    )}
                  </li>
                );
              })}
            </ul>
          )}
        </div>
      </div>

      {/* MAP */}
      <MapContainer center={DEFAULT_CENTER} zoom={DEFAULT_ZOOM} style={{ height: '100%', width: '100%' }}>
        <MapRefBinder mapRef={mapRef} />

        <MapTopLeftControls onHome={goHome} onReset={resetView} onFit={fitToBuses} />

        <TileLayer attribution="&copy; OpenStreetMap contributors" url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />

        {buses
          .filter(
            (b) =>
              b.bus_id !== null &&
              b.lat !== null &&
              b.lng !== null &&
              Number.isFinite(Number(b.lat)) &&
              Number.isFinite(Number(b.lng))
          )
          .map((bus) => {
            const now = Date.now();
            const msSince = bus.last_seen_ms ? now - bus.last_seen_ms : null;
            const isOffline = msSince !== null && msSince > OFFLINE_LABEL_MS;

            return (
              <Marker
                key={`bus-${bus.bus_id}`}
                position={[Number(bus.lat), Number(bus.lng)]}
                ref={(m) => {
                  if (m) markerRefs.current.set(bus.bus_id, m);
                }}
              >
                <Popup>
                  <strong>Bus:</strong> {bus.plate_number ?? `#${bus.bus_id}`}
                  <br />
                  <strong>Status:</strong> {bus.status ?? 'unknown'}
                  <br />
                  <strong>Driver:</strong> {bus.driver ?? '—'}
                  <br />
                  <strong>Company:</strong> {bus.company ?? '—'}
                  <br />
                  <strong>Route:</strong> {bus.route ?? '—'}
                  <br />
                  <strong>Speed:</strong> {bus.speed ?? 0} km/h
                  <br />
                  <strong>Signal:</strong> {isOffline ? 'offline' : 'live'}
                  <br />
                  {msSince !== null && <small>Last seen: {formatSince(msSince)} ago</small>}
                  <br />
                  <small>Updated: {bus.updated_at ?? '—'}</small>
                </Popup>
              </Marker>
            );
          })}
      </MapContainer>
    </div>
  );
}
