<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $route->name }} Preview</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        html, body {
            margin: 0;
            height: 100%;
            background: #f5f8fc;
            font-family: system-ui, sans-serif;
        }
        .route-preview-shell {
            position: relative;
            height: 100%;
            width: 100%;
        }
        #route-preview-map {
            height: 100%;
            width: 100%;
        }
        .route-preview-empty {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #64748b;
            padding: 1rem;
        }
        .route-preview-alert {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            z-index: 1000;
            display: none;
            padding: 10px 12px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid #f1c40f;
            color: #7c5f00;
            font-size: 13px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
        }
        .route-preview-alert.is-visible {
            display: block;
        }
    </style>
</head>
<body>
    @php
        $hasPoints = (!is_null($route->start_lat) && !is_null($route->start_lng))
            || (!is_null($route->end_lat) && !is_null($route->end_lng))
            || $route->stops->contains(fn ($stop) => !is_null($stop->latitude) && !is_null($stop->longitude));
    @endphp

    @if(!$hasPoints)
        <div class="route-preview-empty">
            <div>
                <strong>No route coordinates available.</strong><br>
                Add start, stop, or end coordinates to preview this route.
            </div>
        </div>
    @else
        <div class="route-preview-shell">
            <div id="route-preview-alert" class="route-preview-alert"></div>
            <div id="route-preview-map"></div>
        </div>
    @endif

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    @if($hasPoints)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const routeGeometry = @json($routeGeometryJson ? json_decode($routeGeometryJson, true) : null);
            const directionsEndpoint = @json(route('admin.api.routes.directions'));
            const alertEl = document.getElementById('route-preview-alert');
            const points = [];

            function showAlert(message) {
                if (!alertEl) return;
                alertEl.textContent = message;
                alertEl.classList.add('is-visible');
            }

            function hideAlert() {
                if (!alertEl) return;
                alertEl.textContent = '';
                alertEl.classList.remove('is-visible');
            }

            const map = L.map('route-preview-map', {
                zoomControl: true,
                dragging: true,
                scrollWheelZoom: true,
                doubleClickZoom: true,
                boxZoom: false,
                keyboard: true
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            const markerLayer = L.layerGroup().addTo(map);

            const startIcon = L.icon({
                iconUrl: 'https://maps.gstatic.com/mapfiles/ms2/micons/green-dot.png',
                iconSize: [32, 32],
                iconAnchor: [16, 32]
            });

            const endIcon = L.icon({
                iconUrl: 'https://maps.gstatic.com/mapfiles/ms2/micons/red-dot.png',
                iconSize: [32, 32],
                iconAnchor: [16, 32]
            });

            @if(!is_null($route->start_lat) && !is_null($route->start_lng))
                const startPoint = [{{ (float) $route->start_lat }}, {{ (float) $route->start_lng }}];
                L.marker(startPoint, { icon: startIcon })
                    .bindPopup(@json($route->start_location ?: 'Start'))
                    .addTo(markerLayer);
                points.push(startPoint);
            @endif

            @foreach($route->stops as $stop)
                @if(!is_null($stop->latitude) && !is_null($stop->longitude))
                    const stopPoint{{ $stop->id }} = [{{ (float) $stop->latitude }}, {{ (float) $stop->longitude }}];
                    L.circleMarker(stopPoint{{ $stop->id }}, {
                        radius: 6,
                        color: '#f59e0b',
                        weight: 2,
                        fillColor: '#fbbf24',
                        fillOpacity: 1
                    }).bindPopup(@json($stop->address ?: 'Stop'))->addTo(markerLayer);
                    points.push(stopPoint{{ $stop->id }});
                @endif
            @endforeach

            @if(!is_null($route->end_lat) && !is_null($route->end_lng))
                const endPoint = [{{ (float) $route->end_lat }}, {{ (float) $route->end_lng }}];
                L.marker(endPoint, { icon: endIcon })
                    .bindPopup(@json($route->end_location ?: 'End'))
                    .addTo(markerLayer);
                points.push(endPoint);
            @endif

            function fitMap() {
                if (points.length) {
                    map.fitBounds(points, {
                        padding: [24, 24],
                        maxZoom: 15
                    });
                } else {
                    map.setView([14.5995, 120.9842], 11);
                }
            }

            if (routeGeometry && Array.isArray(routeGeometry.coordinates) && routeGeometry.coordinates.length > 1) {
                L.polyline(
                    routeGeometry.coordinates.map(function (coordinate) {
                        return [coordinate[1], coordinate[0]];
                    }),
                    {
                        color: '#1f7ae0',
                        weight: 4,
                        opacity: 0.9
                    }
                ).addTo(map);
                hideAlert();
                fitMap();
                return;
            }

            if (points.length > 1) {
                fetch(directionsEndpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')) || '',
                    },
                    body: JSON.stringify({
                        coordinates: points.map(function (point) {
                            return [point[1], point[0]];
                        }),
                    }),
                })
                .then(async function (res) {
                    const body = await res.json().catch(function () { return {}; });
                    if (!res.ok || body.status !== 'success') {
                        throw new Error(body.error_code || ('HTTP_' + res.status));
                    }

                    const geometry = (body.data && body.data.geometry) ? body.data.geometry : [];
                    if (!Array.isArray(geometry) || geometry.length < 2) {
                        throw new Error('EMPTY_ROUTE');
                    }

                    L.polyline(
                        geometry.map(function (coordinate) {
                            return [coordinate[1], coordinate[0]];
                        }),
                        {
                            color: '#1f7ae0',
                            weight: 4,
                            opacity: 0.9
                        }
                    ).addTo(map);
                    hideAlert();
                    fitMap();
                })
                .catch(function () {
                    showAlert('Using simplified preview because road routing is unavailable right now.');
                    L.polyline(points, {
                        color: '#94a3b8',
                        weight: 3,
                        opacity: 0.75,
                        dashArray: '6 6'
                    }).addTo(map);
                    fitMap();
                });
            } else {
                fitMap();
            }
        });
    </script>
    @endif
</body>
</html>
