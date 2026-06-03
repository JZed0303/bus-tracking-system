<script>
$(function () {

    /* =============================
     * MAP INIT
     * ============================= */
    const map = L.map('route-map').setView([14.5995, 120.9842], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    let startMarker = null;
    let endMarker   = null;

    // REMOVE straight polyline; use router instead
    let routingControl = null;
    let syncingFromRoutingControl = false;

    let stopMarkers = [];
    let fitted      = false;

    const placeCache = {};

    /* =============================
     * ELEMENT REFERENCES
     * ============================= */
    const $startLat      = $('#start_lat');
    const $startLng      = $('#start_lng');
    const $startLocation = $('#start_location');
    const $startSearch   = $('#start_search');

    const $endLat        = $('#end_lat');
    const $endLng        = $('#end_lng');
    const $endLocation   = $('#end_location');
    const $endSearch     = $('#end_search');
    const $routeGeometry = $('#route_geometry_json');

    /* =============================
     * HELPERS
     * ============================= */
    const coordKey = (lat, lng) => lat.toFixed(5) + ',' + lng.toFixed(5);

    async function reverseGeocode(lat, lng) {
        const key = coordKey(lat, lng);
        if (placeCache[key]) return placeCache[key];

        try {
            const res = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`
            );
            const data = await res.json();
            placeCache[key] = data.display_name || 'Unknown location';
        } catch {
            placeCache[key] = 'Unknown location';
        }

        return placeCache[key];
    }

    function stopIcon(n) {
        return L.divIcon({
            html: `<span class="stop-badge">${n}</span>`,
            className: '',
            iconSize: [26, 26],
            iconAnchor: [13, 26]
        });
    }

    /* =============================
     * CUSTOM MARKER ICONS
     * ============================= */
    const startIcon = L.icon({
        iconUrl: 'https://maps.gstatic.com/mapfiles/ms2/micons/green-dot.png',
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32]
    });

    const endIcon = L.icon({
        iconUrl: 'https://maps.gstatic.com/mapfiles/ms2/micons/red-dot.png',
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -32]   
    });

    /* =============================
     * ROAD ROUTING (secure OpenRouteService)
     * ============================= */
    const directionsEndpoint = window.ROUTE_MAP_CONFIG?.directionsEndpoint || null;
    const STOP_MIN_DISTANCE_METERS = 8;

    function buildWaypoints() {
        if (!startMarker || !endMarker) return null;

        const pts = [
            startMarker.getLatLng(),
            ...stopMarkers.map(m => m.getLatLng()),
            endMarker.getLatLng()
        ];

        // Leaflet Routing Machine expects array of L.LatLng
        return pts.map(p => L.latLng(p.lat, p.lng));
    }

    function clearRoadRoute() {
        if (routingControl) {
            map.removeControl(routingControl);
            routingControl = null;
        }
    }

    function setStoredRouteGeometry(coordinates) {
        if (!$routeGeometry.length) return;

        if (!Array.isArray(coordinates) || coordinates.length < 2) {
            $routeGeometry.val('');
            return;
        }

        $routeGeometry.val(JSON.stringify({
            type: 'LineString',
            coordinates
        }));
    }

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function showRouteAlert(message, type = 'warning') {
        const $alert = $('#route-map-alert');
        if (!$alert.length) return;
        $alert.removeClass('d-none alert-warning alert-danger alert-info').addClass(`alert-${type}`).text(message);
    }

    function hideRouteAlert() {
        const $alert = $('#route-map-alert');
        if (!$alert.length) return;
        $alert.addClass('d-none').text('');
    }

    function isRoutingContextActive(context) {
        // Ignore stale async callbacks after the control/map was already removed.
        return !!(context && context._map);
    }

    function setRouteLoading(loading, text = 'Loading route...') {
        const shell = document.getElementById('route-map-shell');
        const mapEl = document.getElementById('route-map');
        const loader = document.getElementById('route-map-loader');
        if (!shell || !mapEl || !loader) return;
        loader.textContent = text;
        shell.classList.toggle('loading', loading);
        mapEl.classList.toggle('route-loading', loading);
    }

    function normalizeLrmWaypoints(waypoints) {
        return waypoints.map(wp => [wp.latLng.lng, wp.latLng.lat]);
    }

    function computeWaypointIndices(waypoints, geometry) {
        // LRM expects one index per waypoint; using only [start,end] breaks multi-stop routes.
        let minGeometryIndex = 0;
        return waypoints.map((wp) => {
            let bestIndex = 0;
            let bestScore = Number.POSITIVE_INFINITY;

            for (let i = minGeometryIndex; i < geometry.length; i += 1) {
                const [lng, lat] = geometry[i];
                const dLat = lat - wp.latLng.lat;
                const dLng = lng - wp.latLng.lng;
                const score = (dLat * dLat) + (dLng * dLng);
                if (score < bestScore) {
                    bestScore = score;
                    bestIndex = i;
                }
            }

            minGeometryIndex = bestIndex;
            return bestIndex;
        });
    }

    function buildLrmRouteFromGeometry(waypoints, geometry, summary, name) {
        return {
            name,
            coordinates: geometry.map(([lng, lat]) => L.latLng(lat, lng)),
            instructions: [],
            summary: {
                totalDistance: summary?.distance || 0,
                totalTime: summary?.duration || 0
            },
            inputWaypoints: waypoints,
            waypoints: waypoints.map(wp => ({ latLng: wp.latLng })),
            waypointIndices: computeWaypointIndices(waypoints, geometry)
        };
    }

    // Leaflet Routing Machine-compatible router backed by a server-side ORS proxy.
    const secureOrsRouter = {
        route(waypoints, callback, context) {
            if (!directionsEndpoint) {
                const err = new Error('DIRECTIONS_ENDPOINT_MISSING');
                showRouteAlert('Directions endpoint is missing.', 'danger');
                if (isRoutingContextActive(context)) {
                    callback.call(context, err);
                }
                return;
            }

            const payload = { coordinates: normalizeLrmWaypoints(waypoints) };
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            };

            const sendRequest = () => fetch(directionsEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers,
                body: JSON.stringify(payload)
            });

            // Retry once on 429 before falling back.
            sendRequest()
                .then(async (res) => {
                    if (res.status === 429) {
                        setRouteLoading(true, 'Retrying route request...');
                        await new Promise(resolve => setTimeout(resolve, 700));
                        return sendRequest();
                    }
                    return res;
                })
                .then(async (res) => {
                    const body = await res.json().catch(() => ({}));
                    if (!res.ok || body.status !== 'success') {
                        const errCode = body.error_code || `ORS_PROXY_HTTP_${res.status}`;
                        const providerStatus = body.provider_status ? ` (${body.provider_status})` : '';
                        const providerError = body.provider_error ? ` - ${body.provider_error}` : '';
                        throw new Error(`${errCode}${providerStatus}${providerError}`);
                    }

                    const geometry = body.data?.geometry || [];
                    if (!Array.isArray(geometry) || geometry.length < 2) {
                        throw new Error('ORS_EMPTY_ROUTE');
                    }

                    hideRouteAlert();
                    setRouteLoading(false);
                    if (isRoutingContextActive(context)) {
                        callback.call(context, null, [
                            buildLrmRouteFromGeometry(
                                waypoints,
                                geometry,
                                {
                                    distance: body.data?.distance_meters || 0,
                                    duration: body.data?.duration_seconds || 0
                                },
                                'OpenRouteService'
                            )
                        ]);
                    }
                })
                .catch((err) => {
                    setRouteLoading(false);
                    showRouteAlert(`Routing failed: ${err?.message || 'ORS proxy error'}`, 'danger');
                    if (isRoutingContextActive(context)) {
                        callback.call(context, err);
                    }
                });
        }
    };

    function renderRoadRoute(autoFit = true) {
    if (!L || !L.Routing || !L.Routing.control) {
        console.error('Leaflet Routing Machine is not loaded (L.Routing.control is undefined)');
        showRouteAlert('Routing engine is not loaded on this page.', 'danger');
        return;
    }

    const waypoints = buildWaypoints();
    if (!waypoints) return;

        clearRoadRoute();
        setStoredRouteGeometry(null);
        setRouteLoading(true);

        routingControl = L.Routing.control({
            waypoints: waypoints,

            // Allow Google-like reshaping by dragging the route line.
            addWaypoints: true,
            draggableWaypoints: true,
            routeWhileDragging: true,
            // Hide Leaflet Routing Machine waypoint pins.
            // We already render custom start/end markers and numbered stop circles.
            createMarker: () => null,

            // hide the instructions panel
            show: false,

            // you can style the route line here
            lineOptions: {
                styles: [{ color: '#0d6efd', weight: 4, opacity: 0.9 }]
            },

            // Active: secure ORS proxy.
            router: secureOrsRouter

            // Manual fallback option if you explicitly want OSRM again:
            // router: L.Routing.osrmv1({ serviceUrl: window.ROUTE_MAP_CONFIG?.osrmServiceUrl || 'https://router.project-osrm.org/route/v1' })
        })
        .on('waypointschanged', function() {
            if (syncingFromRoutingControl) return;
            syncRouteEditorToRoutingWaypoints();
        })
        .on('routesfound', function(e) {
            const r = e.routes?.[0];
            if (!r) return;
            setRouteLoading(false);
            setStoredRouteGeometry(
                (r.coordinates || []).map(point => [point.lng, point.lat])
            );

            // Fit bounds once (or when forced)
            if (autoFit && !fitted) {
                const bounds = L.latLngBounds(r.coordinates);
                map.fitBounds(bounds, { padding: [40, 40] });
                fitted = true;
            }
        })
        .on('routingerror', function(err) {
            console.error('Routing error:', err);
            setRouteLoading(false);
            setStoredRouteGeometry(null);
            showRouteAlert('Unable to draw route right now. Check coordinates or try again.', 'danger');
            // If the route fails, you can decide to keep old route or clear
            clearRoadRoute();
        })
        .addTo(map);
    }

    function createStopMarker(ll) {
        const marker = L.marker(ll, {
            draggable: true,
            icon: stopIcon(stopMarkers.length + 1)
        }).addTo(map);

        marker.on('dragend', () => {
            fitted = false;
            redraw(false);
        });

        stopMarkers.push(marker);
        return marker;
    }

    async function updateStartFieldsFromLatLng(ll) {
        $startLat.val(ll.lat.toFixed(7));
        $startLng.val(ll.lng.toFixed(7));
        const address = await reverseGeocode(ll.lat, ll.lng);
        $startLocation.val(address);
        $startSearch.val(address);
    }

    async function updateEndFieldsFromLatLng(ll) {
        $endLat.val(ll.lat.toFixed(7));
        $endLng.val(ll.lng.toFixed(7));
        const address = await reverseGeocode(ll.lat, ll.lng);
        $endLocation.val(address);
        $endSearch.val(address);
    }

    function syncRouteEditorToRoutingWaypoints() {
        if (!routingControl || !startMarker || !endMarker) return;

        const rawWaypoints = routingControl.getWaypoints()
            .map(wp => wp?.latLng || null)
            .filter(Boolean);

        if (rawWaypoints.length < 2) return;

        const newStart = rawWaypoints[0];
        const newEnd = rawWaypoints[rawWaypoints.length - 1];
        const newStops = rawWaypoints.slice(1, -1);

        syncingFromRoutingControl = true;
        fitted = false;

        startMarker.setLatLng(newStart);
        endMarker.setLatLng(newEnd);
        updateStartFieldsFromLatLng(newStart);
        updateEndFieldsFromLatLng(newEnd);

        stopMarkers.forEach(m => map.removeLayer(m));
        stopMarkers = [];
        newStops.forEach(ll => createStopMarker(ll));

        hideRouteAlert();
        renderStopsTable();

        syncingFromRoutingControl = false;
    }

    /* =============================
     * REDRAW
     * ============================= */
    function redraw(autoFit = true) {
        // update stop badges order
        stopMarkers.forEach((m, i) => m.setIcon(stopIcon(i + 1)));

        // draw the ROAD route (not straight line)
        renderRoadRoute(autoFit);

        // update table + hidden input
        renderStopsTable();
    }

    /* =============================
     * HIGH PERFORMANCE SEARCH
     * ============================= */
    function initSearch({ $input, $suggestions, onSelect }) {
        let controller = null;
        let debounceTimer = null;
        let cache = {};
        let activeIndex = -1;

        const DELAY = 300;
        const LIMIT = 6;

        function render(results) {
            $suggestions.empty();
            activeIndex = -1;

            if (!results.length) {
                $suggestions.html(`<div class="list-group-item text-muted">No results found</div>`);
                return;
            }

            results.forEach((place, i) => {
                const item = $(`
                    <button type="button"
                            class="list-group-item list-group-item-action"
                            data-index="${i}">
                        <strong>${place.display_name.split(',')[0]}</strong><br>
                        <small class="text-muted">${place.display_name}</small>
                    </button>
                `);

                item.on('click', () => select(place));
                $suggestions.append(item);
            });
        }

        function select(place) {
            $suggestions.empty();
            activeIndex = -1;
            onSelect({
                lat: parseFloat(place.lat),
                lng: parseFloat(place.lon),
                address: place.display_name
            });
        }

        function highlight() {
            $suggestions.children().removeClass('active');
            if (activeIndex >= 0) {
                $suggestions.children().eq(activeIndex).addClass('active');
            }
        }

        $input.on('keydown', e => {
            const items = $suggestions.children();
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                activeIndex = (activeIndex + 1) % items.length;
                highlight();
                e.preventDefault();
            }

            if (e.key === 'ArrowUp') {
                activeIndex = (activeIndex - 1 + items.length) % items.length;
                highlight();
                e.preventDefault();
            }

            if (e.key === 'Enter' && activeIndex >= 0) {
                items.eq(activeIndex).click();
                e.preventDefault();
            }
        });

        $input.on('input', function () {
            const q = this.value.trim();
            clearTimeout(debounceTimer);
            $suggestions.empty();

            if (q.length < 3) return;

            debounceTimer = setTimeout(async () => {
                if (cache[q]) {
                    render(cache[q]);
                    return;
                }

                if (controller) controller.abort();
                controller = new AbortController();

                try {
                    const res = await fetch(
                        `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=${LIMIT}&countrycodes=ph&q=${encodeURIComponent(q)}`,
                        { signal: controller.signal }
                    );

                    const results = await res.json();
                    cache[q] = results;
                    render(results);

                } catch (err) {
                    if (err.name !== 'AbortError') {
                        $suggestions.html(`<div class="list-group-item text-danger">Search unavailable</div>`);
                    }
                }
            }, DELAY);
        });

        $(document).on('click', () => {
            $suggestions.empty();
            activeIndex = -1;
        });
    }

    /* =============================
     * START / END
     * ============================= */
    async function setStart(ll) {
        if (startMarker) map.removeLayer(startMarker);

        startMarker = L.marker(ll, { draggable: true, icon: startIcon }).addTo(map);

        startMarker.on('dragend', async () => {
            fitted = false;
            const pos = startMarker.getLatLng();
            await updateStartFieldsFromLatLng(pos);
            redraw(false);
        });

        await updateStartFieldsFromLatLng(ll);

        redraw(false);
    }

    async function setEnd(ll) {
        if (endMarker) map.removeLayer(endMarker);

        endMarker = L.marker(ll, { draggable: true, icon: endIcon }).addTo(map);

        endMarker.on('dragend', async () => {
            fitted = false;
            const pos = endMarker.getLatLng();
            await updateEndFieldsFromLatLng(pos);
            redraw(false);
        });

        await updateEndFieldsFromLatLng(ll);

        redraw(false);
    }

    /* =============================
     * STOPS
     * ============================= */
    function distanceMeters(a, b) {
        const R = 6371000;
        const dLat = ((b.lat - a.lat) * Math.PI) / 180;
        const dLng = ((b.lng - a.lng) * Math.PI) / 180;
        const lat1 = (a.lat * Math.PI) / 180;
        const lat2 = (b.lat * Math.PI) / 180;
        const h = Math.sin(dLat / 2) ** 2
            + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) ** 2;
        return 2 * R * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
    }

    function isTooCloseToAnyStop(candidate) {
        return stopMarkers.some(marker => distanceMeters(marker.getLatLng(), candidate) < STOP_MIN_DISTANCE_METERS);
    }

    function isTooCloseToEndpoint(candidate) {
        if (startMarker && distanceMeters(startMarker.getLatLng(), candidate) < STOP_MIN_DISTANCE_METERS) return true;
        if (endMarker && distanceMeters(endMarker.getLatLng(), candidate) < STOP_MIN_DISTANCE_METERS) return true;
        return false;
    }

    function addStop(ll) {
        // Guard against accidental duplicates and endpoint-overlap stops.
        if (isTooCloseToAnyStop(ll) || isTooCloseToEndpoint(ll)) {
            showRouteAlert(`Stop ignored: too close to an existing point (<${STOP_MIN_DISTANCE_METERS}m).`, 'info');
            return;
        }

        createStopMarker(ll);
        fitted = false;
        hideRouteAlert();
        redraw(true);
    }

    window.removeStop = function (i) {
        map.removeLayer(stopMarkers[i]);
        stopMarkers.splice(i, 1);
        fitted = false;
        redraw(true);
    };

    /* =============================
     * MAP CLICK FLOW
     * ============================= */
    map.on('click', e => {
        if (!startMarker) return setStart(e.latlng);
        if (!endMarker) return setEnd(e.latlng);
        addStop(e.latlng);
    });

    /* =============================
     * STOPS TABLE + HIDDEN JSON
     * ============================= */
    function renderStopsTable() {
        const $tbody = $('#stops-list').empty();

        if (!stopMarkers.length) {
            $tbody.html(`<tr><td colspan="5" class="text-center text-muted">No pickup stops added</td></tr>`);
            $('#stops_json').val('[]');
            return;
        }

        stopMarkers.forEach((m, i) => {
            const ll = m.getLatLng();
            const key = coordKey(ll.lat, ll.lng);

            if (!placeCache[key]) reverseGeocode(ll.lat, ll.lng).then(renderStopsTable);

            $tbody.append(`
                <tr>
                    <td>${i + 1}</td>
                    <td>${ll.lat.toFixed(6)}</td>
                    <td>${ll.lng.toFixed(6)}</td>
                    <td>${placeCache[key] || 'Resolving…'}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-danger" onclick="removeStop(${i})">✕</button>
                    </td>
                </tr>
            `);
        });

        $('#stops_json').val(JSON.stringify(
            stopMarkers.map((m, i) => ({
                latitude: m.getLatLng().lat,
                longitude: m.getLatLng().lng,
                stop_order: i + 1,
                address: placeCache[coordKey(m.getLatLng().lat, m.getLatLng().lng)]
            }))
        ));
    }

    /* =============================
     * CLEAR BUTTONS
     * ============================= */
    $('#clear-start').on('click', function () {
        if (startMarker) { map.removeLayer(startMarker); startMarker = null; }

        // Remove all stops
        stopMarkers.forEach(m => map.removeLayer(m));
        stopMarkers = [];

        if (endMarker) { map.removeLayer(endMarker); endMarker = null; }

        clearRoadRoute();
        setStoredRouteGeometry(null);

        // Clear fields
        $startLat.val(''); $startLng.val(''); $startLocation.val(''); $startSearch.val('');
        $endLat.val(''); $endLng.val(''); $endLocation.val(''); $endSearch.val('');

        $('#stops_json').val('[]');
        fitted = false;

        renderStopsTable();
    });

    $('#clear-end').on('click', function () {
        if (endMarker) { map.removeLayer(endMarker); endMarker = null; }

        $endLat.val(''); $endLng.val(''); $endLocation.val(''); $endSearch.val('');

        fitted = false;
        clearRoadRoute();
        setStoredRouteGeometry(null);
        redraw(false);
    });

    /* =============================
     * SEARCH INIT
     * ============================= */
    initSearch({
        $input: $('#start_search'),
        $suggestions: $('#start_suggestions'),
        onSelect: async ({ lat, lng, address }) => {
            await setStart({ lat, lng });
            $startLocation.val(address);
            map.setView([lat, lng], 15);
        }
    });

    initSearch({
        $input: $('#end_search'),
        $suggestions: $('#end_suggestions'),
        onSelect: async ({ lat, lng, address }) => {
            await setEnd({ lat, lng });
            $endLocation.val(address);
            map.setView([lat, lng], 15);
        }
    });

    /* =============================
     * LOAD EXISTING ROUTE (EDIT)
     * ============================= */
    if (window.EXISTING_ROUTE) {
        const r = window.EXISTING_ROUTE;

        (async () => {
            await setStart({ lat: +r.start.lat, lng: +r.start.lng });
            await setEnd({ lat: +r.end.lat, lng: +r.end.lng });

            if (Array.isArray(r.stops)) {
                r.stops
                    .sort((a, b) => a.stop_order - b.stop_order)
                    .forEach(s => {
                        placeCache[coordKey(+s.latitude, +s.longitude)] = s.address;
                        addStop({ lat: +s.latitude, lng: +s.longitude });
                    });
            }

            fitted = false;
            redraw(true);
        })();
    }

});
</script>
