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
     * ROAD ROUTING (OSRM)
     * ============================= */
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

function renderRoadRoute(autoFit = true) {
    if (!L || !L.Routing || !L.Routing.control) {
        console.error('Leaflet Routing Machine is not loaded (L.Routing.control is undefined)');
        return;
    }

    const waypoints = buildWaypoints();
    if (!waypoints) return;

        clearRoadRoute();

        routingControl = L.Routing.control({
            waypoints: waypoints,

            // prevent adding/dragging route waypoints via the route line
            addWaypoints: false,
            draggableWaypoints: false,
            routeWhileDragging: false,

            // hide the instructions panel
            show: false,

            // you can style the route line here
            lineOptions: {
                styles: [{ color: '#0d6efd', weight: 4, opacity: 0.9 }]
            },

            // OSRM public demo router (OK for dev; don’t rely for heavy production)
            router: L.Routing.osrmv1({
                serviceUrl: 'https://router.project-osrm.org/route/v1'
            })
        })
        .on('routesfound', function(e) {
            const r = e.routes?.[0];
            if (!r) return;

            // Fit bounds once (or when forced)
            if (autoFit && !fitted) {
                const bounds = L.latLngBounds(r.coordinates);
                map.fitBounds(bounds, { padding: [40, 40] });
                fitted = true;
            }

            // OPTIONAL: If you want to store road geometry for backend saving
            // $('#route_geometry').val(JSON.stringify(r.coordinates));
            // $('#route_distance_m').val(r.summary.totalDistance);
            // $('#route_duration_s').val(r.summary.totalTime);
        })
        .on('routingerror', function(err) {
            console.error('Routing error:', err);
            // If the route fails, you can decide to keep old route or clear
            clearRoadRoute();
        })
        .addTo(map);
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
            const address = await reverseGeocode(pos.lat, pos.lng);
            $startLat.val(pos.lat.toFixed(7));
            $startLng.val(pos.lng.toFixed(7));
            $startLocation.val(address);
            $startSearch.val(address);
            redraw(false);
        });

        const address = await reverseGeocode(ll.lat, ll.lng);
        $startLat.val(ll.lat.toFixed(7));
        $startLng.val(ll.lng.toFixed(7));
        $startLocation.val(address);
        $startSearch.val(address);

        redraw(false);
    }

    async function setEnd(ll) {
        if (endMarker) map.removeLayer(endMarker);

        endMarker = L.marker(ll, { draggable: true, icon: endIcon }).addTo(map);

        endMarker.on('dragend', async () => {
            fitted = false;
            const pos = endMarker.getLatLng();
            const address = await reverseGeocode(pos.lat, pos.lng);
            $endLat.val(pos.lat.toFixed(7));
            $endLng.val(pos.lng.toFixed(7));
            $endLocation.val(address);
            $endSearch.val(address);
            redraw(false);
        });

        const address = await reverseGeocode(ll.lat, ll.lng);
        $endLat.val(ll.lat.toFixed(7));
        $endLng.val(ll.lng.toFixed(7));
        $endLocation.val(address);
        $endSearch.val(address);

        redraw(false);
    }

    /* =============================
     * STOPS
     * ============================= */
    function addStop(ll) {
        const marker = L.marker(ll, {
            draggable: true,
            icon: stopIcon(stopMarkers.length + 1)
        }).addTo(map);

        marker.on('dragend', () => {
            fitted = false;
            redraw(false);
        });

        stopMarkers.push(marker);
        fitted = false;
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
