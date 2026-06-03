@extends('layouts.master')

@section('title', 'Assignment Details')

@section('page-title', 'Assignment Details')

@section('body')
    <body data-sidebar="colored">
@endsection

@section('css')
    <link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />

    <style>
        .assignment-hero {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 14px 36px rgba(20, 33, 61, 0.08);
        }
        .assignment-kpi {
            border: 1px solid #e9edf4;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #fafcff 100%);
            padding: 18px;
            height: 100%;
        }
        .assignment-column {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .assignment-kpi-compact {
            height: auto;
        }
        .assignment-kpi-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .assignment-kpi-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1f2a37;
            margin: 0;
        }
        .assignment-kpi-label {
            font-size: 11px;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #7c8799;
            margin-bottom: 4px;
        }
        .assignment-kpi-value {
            font-size: 1.05rem;
            font-weight: 600;
            color: #1f2a37;
            margin-bottom: 0;
        }
        .assignment-divider {
            margin: 16px 0 18px;
            border-color: #e9edf4;
            opacity: 1;
        }
        .assignment-summary-group + .assignment-summary-group {
            margin-top: 16px;
        }
        .assignment-table th {
            width: 190px;
            background: #f5f8fc;
            color: #526176;
            font-weight: 700;
            font-size: 0.78rem;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .assignment-kpi .table-responsive,
        .assignment-kpi .assignment-table {
            border-radius: 0 !important;
            overflow: visible !important;
        }
        .assignment-kpi .assignment-table thead th:first-child,
        .assignment-kpi .assignment-table thead th:last-child,
        .assignment-kpi .assignment-table tbody tr:last-child th:first-child,
        .assignment-kpi .assignment-table tbody tr:last-child td:last-child {
            border-radius: 0 !important;
        }
        .assignment-table {
            border-collapse: separate !important;
            border-spacing: 0 !important;
            margin-top: 4px;
        }
        .assignment-table > :not(caption) > * > * {
            padding: 14px 16px !important;
            vertical-align: middle;
            border-color: #e4ebf3 !important;
        }
        .assignment-table td {
            color: #182433;
            font-weight: 500;
            background: #ffffff;
        }
        .assignment-table tbody tr:hover td,
        .assignment-table tbody tr:hover th {
            background: #f9fbfe;
        }
        .assignment-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 32px;
            border-radius: 999px;
            padding: 0.45rem 0.9rem;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: .03em;
        }
        .assignment-media {
            margin-bottom: 14px;
        }
        .assignment-mini-card {
            min-height: 220px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .assignment-map-card {
            min-height: 280px;
        }
        .assignment-map-shell {
            position: relative;
            margin-top: 4px;
        }
        .assignment-map-shell.map-fullscreen {
            position: fixed;
            inset: 16px;
            margin: 0;
            padding: 16px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 22px 60px rgba(15, 23, 42, 0.24);
            z-index: 2000;
        }
        .assignment-map {
            height: 220px;
            border: 1px solid #e4ebf3;
            border-radius: 14px;
            overflow: hidden;
            background: #f5f8fc;
        }
        .assignment-map-shell.map-fullscreen .assignment-map {
            height: calc(100vh - 110px);
            border-radius: 16px;
        }
        .assignment-map.route-loading {
            filter: blur(2px);
            transition: filter 0.15s ease;
        }
        .assignment-map-loader {
            position: absolute;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.55);
            border-radius: 14px;
            z-index: 900;
            font-size: 13px;
            font-weight: 600;
            color: #495057;
        }
        .assignment-map-shell.loading .assignment-map-loader {
            display: flex;
        }
        .assignment-map-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }
        .assignment-map-shell:not(.map-fullscreen) .assignment-map-toolbar {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 950;
            margin-bottom: 0;
        }
        .assignment-map-shell:not(.map-fullscreen) .assignment-map-toolbar-label {
            display: none;
        }
        .assignment-map-fullscreen-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid #d6dfeb;
            background: rgba(255, 255, 255, 0.95);
            color: #1f2a37;
            border-radius: 999px;
            padding: 0.45rem 0.85rem;
            font-size: 0.78rem;
            font-weight: 700;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
        }
        .assignment-map-fullscreen-btn:hover {
            background: #ffffff;
        }
        .assignment-map-alert {
            margin-bottom: 12px;
        }
        .assignment-stop-badge {
            background: #ffc107;
            color: #000;
            padding: 4px 7px;
            border-radius: 50%;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .assignment-map-note {
            margin-top: 10px;
            color: #6b7788;
            font-size: 0.84rem;
        }
        body.assignment-map-open {
            overflow: hidden;
        }
        .assignment-bus-photo {
            width: 100%;
            max-width: 360px;
            height: 190px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #e9edf4;
            background: #f8fafc;
            display: block;
            margin: 0 auto;
        }
        .assignment-driver-photo {
            width: 172px;
            height: 172px;
            object-fit: cover;
            border-radius: 50%;
            border: 1px solid #e9edf4;
            background: #f8fafc;
            display: block;
            margin: 0 auto;
        }
        .assignment-card-note {
            margin-top: 6px;
            color: #6b7788;
        }
        @media (max-width: 991.98px) {
            .assignment-hero .card-body {
                padding: 1rem;
            }
            .assignment-kpi {
                padding: 16px;
            }
            .assignment-column {
                gap: 1rem;
            }
            .assignment-kpi-header {
                align-items: flex-start;
                flex-direction: column;
            }
            .assignment-table th {
                width: 42%;
            }
            .assignment-bus-photo {
                height: 180px;
            }
            .assignment-driver-photo {
                width: 160px;
                height: 160px;
            }
            .assignment-mini-card {
                min-height: 0;
            }
            .assignment-map {
                height: 200px;
            }
            .assignment-map-shell.map-fullscreen {
                inset: 8px;
                padding: 10px;
                border-radius: 14px;
            }
            .assignment-map-shell.map-fullscreen .assignment-map {
                height: calc(100vh - 92px);
            }
        }
        @media (max-width: 575.98px) {
            .assignment-table > :not(caption) > * > * {
                padding: 12px !important;
            }
            .assignment-table th,
            .assignment-table td {
                display: block;
                width: 100%;
            }
            .assignment-table th {
                border-bottom: 0 !important;
            }
            .assignment-table td {
                border-top: 0 !important;
            }
        }
    </style>
@endsection

@section('content')
@php
    $statusColor = $assignment->status === 'active' ? 'success' : 'secondary';
    $today = now('Asia/Manila')->startOfDay();
    $lifecycle = $assignment->isActive() ? 'Active' : ($assignment->isUpcoming() ? 'Upcoming' : ($assignment->isExpired() ? 'Expired' : 'Inactive'));
    $lifecycleColor = $assignment->isActive() ? 'success' : ($assignment->isUpcoming() ? 'info' : ($assignment->isExpired() ? 'danger' : 'secondary'));
    $assignmentTrips = $assignment->trips
        ->sortByDesc(fn ($trip) => $trip->actual_start_time ?? $trip->scheduled_start_time ?? $trip->trip_date)
        ->values();

    $effectiveFrom = $assignment->effective_from?->timezone('Asia/Manila');
    $effectiveTo = $assignment->effective_to?->timezone('Asia/Manila');
    $durationText = $effectiveTo
        ? $effectiveFrom?->diffInDays($effectiveTo) . ' days'
        : 'Indefinite';
    $routeModel = $assignment->route;
    $routeHasMapPoints = $routeModel && (
        (!is_null($routeModel->start_lat) && !is_null($routeModel->start_lng)) ||
        (!is_null($routeModel->end_lat) && !is_null($routeModel->end_lng)) ||
        $routeModel->stops->contains(fn ($stop) => !is_null($stop->latitude) && !is_null($stop->longitude))
    );
@endphp

<div class="container-fluid">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h4 class="mb-1">Assignment Details</h4>
            <p class="text-muted mb-0">Comprehensive assignment profile for operations and monitoring.</p>
        </div>
        <div class="col text-end d-flex justify-content-end gap-2">
            <a href="{{ route('admin.assignments.timeline', $assignment->id) }}" class="btn btn-outline-primary btn-sm">
                <i class="mdi mdi-timeline me-1"></i> Timeline
            </a>
            <a href="{{ route('admin.assignments.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="mdi mdi-arrow-left me-1"></i> Back to Assignments
            </a>
        </div>
    </div>

    <div class="card mb-4 assignment-hero">
        <div class="card-body">
            <div class="row g-3 align-items-start">
                <div class="col-lg-6 assignment-column">
                    <div class="assignment-kpi assignment-kpi-compact">
                        <div class="assignment-kpi-header">
                            <div>
                                <p class="assignment-kpi-label">Assignment Reference</p>
                                <h5 class="mb-1">#{{ $assignment->id }}</h5>
                            </div>
                            <span class="badge bg-{{ $statusColor }} assignment-badge text-uppercase">
                                {{ strtoupper($assignment->status) }}
                            </span>
                        </div>

                        <hr class="assignment-divider">
                        <div class="assignment-summary-group">
                            <div class="assignment-kpi-header">
                                <div>
                                    <p class="assignment-kpi-label">Overview</p>
                                    <h6 class="assignment-kpi-title">Core Assignment Information</h6>
                                </div>
                            </div>
                            <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0 assignment-table">
                                <tbody>
                                <tr>
                                    <th>Company</th>
                                    <td>{{ optional($assignment->company)->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Lifecycle State</th>
                                    <td><span class="badge bg-{{ $lifecycleColor }} assignment-badge">{{ $lifecycle }}</span></td>
                                </tr>
                                <tr>
                                    <th>Assignment Leg</th>
                                    <td>
                                        @php($leg = $assignment->leg ?? 'both')
                                        <span class="badge bg-{{ $leg === 'both' ? 'dark' : ($leg === 'pickup' ? 'info' : 'primary') }} assignment-badge">
                                            {{ strtoupper($leg) }}
                                        </span>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>

                    <div class="assignment-kpi">
                        <div class="assignment-kpi-header">
                            <div>
                                <p class="assignment-kpi-label">Timeline</p>
                                <h6 class="assignment-kpi-title">Schedule and Audit</h6>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0 assignment-table">
                                <tbody>
                                <tr>
                                    <th>Effective From</th>
                                    <td>{{ $effectiveFrom?->format('M d, Y') ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Effective To</th>
                                    <td>{{ $effectiveTo?->format('M d, Y') ?? 'Ongoing' }}</td>
                                </tr>
                                <tr>
                                    <th>Duration</th>
                                    <td>{{ $durationText }}</td>
                                </tr>
                                <tr>
                                    <th>Created At</th>
                                    <td>{{ $assignment->created_at?->timezone('Asia/Manila')->format('M d, Y h:iA') ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Last Updated</th>
                                    <td>{{ $assignment->updated_at?->timezone('Asia/Manila')->format('M d, Y h:iA') ?? '—' }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <div class="col-lg-6 assignment-column">
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <div class="assignment-kpi assignment-mini-card">
                                <div class="assignment-kpi-header">
                                    <div>
                                        <p class="assignment-kpi-label">Personnel</p>
                                        <h6 class="assignment-kpi-title">Driver</h6>
                                    </div>
                                </div>
                                <div class="assignment-media">
                                    <img
                                        src="{{ optional($assignment->driver)->photo_url ?? asset('build/images/user-placeholder.png') }}"
                                        alt="Driver Photo"
                                        class="assignment-driver-photo"
                                    >
                                </div>
                                <p class="assignment-kpi-value">{{ optional($assignment->driver?->user)->full_name ?? '—' }}</p>
                                <p class="mb-0 text-muted small assignment-card-note">Assigned operator</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="assignment-kpi assignment-mini-card">
                                <div class="assignment-kpi-header">
                                    <div>
                                        <p class="assignment-kpi-label">Vehicle</p>
                                        <h6 class="assignment-kpi-title">Bus</h6>
                                    </div>
                                </div>
                                <div class="assignment-media">
                                    <img
                                        src="{{ optional($assignment->bus)->photo_url ?? asset('build/images/bus-placeholder.png') }}"
                                        alt="Bus Photo"
                                        class="assignment-bus-photo"
                                    >
                                </div>
                                <p class="assignment-kpi-value">{{ optional($assignment->bus)->plate_number ?? '—' }}</p>
                                <p class="mb-0 text-muted small assignment-card-note">{{ optional($assignment->bus)->model ?? 'No model data' }}</p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="assignment-kpi assignment-map-card">
                                <div class="assignment-kpi-header">
                                    <div>
                                        <p class="assignment-kpi-label">Navigation</p>
                                        <h6 class="assignment-kpi-title">Saved Route Map</h6>
                                    </div>
                                </div>
                                @if($routeModel && $routeHasMapPoints)
                                    <div id="assignment-route-map-alert" class="alert alert-warning assignment-map-alert d-none"></div>
                                    <div class="assignment-map-shell" id="assignment-route-map-shell">
                                        <div class="assignment-map-toolbar">
                                            <span class="assignment-map-toolbar-label text-muted small">Saved route preview</span>
                                            <button type="button" class="assignment-map-fullscreen-btn" id="assignment-route-map-toggle">
                                                <i class="mdi mdi-arrow-expand-all"></i>
                                                <span>Full size</span>
                                            </button>
                                        </div>
                                        <div id="assignment-route-map" class="assignment-map"></div>
                                        <div class="assignment-map-loader" id="assignment-route-map-loader">Loading route...</div>
                                    </div>
                                    <p class="mb-0 assignment-map-note">
                                        {{ $routeModel->name ?? 'Assigned route' }}
                                    </p>
                                @else
                                    <div class="d-flex flex-column justify-content-center h-100">
                                        <p class="assignment-kpi-value mb-2">No saved route map</p>
                                        <p class="mb-0 text-muted small assignment-card-note">
                                            Add route coordinates and stops to display the map here.
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="assignment-kpi mt-4">
                <div class="assignment-kpi-header">
                    <div>
                        <p class="assignment-kpi-label">Recent Activity</p>
                        <h6 class="assignment-kpi-title">Operations Snapshot</h6>
                    </div>
                </div>
                <div class="table-responsive">
                    @if($assignmentTrips->isEmpty())
                        <div class="text-muted small">No trips recorded for this assignment.</div>
                    @else
                        <table id="operations-snapshot-table" class="table table-bordered table-striped dt-responsive nowrap align-middle w-100 mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Route</th>
                                <th>Start</th>
                                <th>End</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($assignmentTrips as $trip)
                                <tr>
                                    <td>{{ optional($assignment->route)->name ?? '—' }}</td>
                                    <td>{{ $trip->actual_start_time?->timezone('Asia/Manila')->format('M d, Y h:iA') ?? '—' }}</td>
                                    <td>{{ $trip->actual_end_time?->timezone('Asia/Manila')->format('M d, Y h:iA') ?? '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
    <script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
    <script>
        $(function () {
            if ($('#operations-snapshot-table').length) {
                $('#operations-snapshot-table').DataTable({
                    responsive: true,
                    pageLength: 10,
                    order: [[1, 'desc']]
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            const mapEl = document.getElementById('assignment-route-map');
            if (!mapEl) {
                return;
            }

            const directionsEndpoint = @json(route('admin.api.routes.directions'));
            const routeMapAlert = document.getElementById('assignment-route-map-alert');
            const routeMapShell = document.getElementById('assignment-route-map-shell');
            const routeMapLoader = document.getElementById('assignment-route-map-loader');
            const routeMapToggle = document.getElementById('assignment-route-map-toggle');
            const routeMapToggleLabel = routeMapToggle?.querySelector('span');
            const routeMapToggleIcon = routeMapToggle?.querySelector('i');
            const routeId = @json($routeModel?->id);
            const routeUpdatedAt = @json(optional($routeModel?->updated_at)?->toIso8601String());
            const persistedRouteGeometry = @json($persistedRouteGeometry);
            const geometryCacheKey = routeId && routeUpdatedAt
                ? `assignment-route-geometry:${routeId}:${routeUpdatedAt}`
                : null;
            let routeLine = null;

            function showRouteAlert(message, type = 'warning') {
                if (!routeMapAlert) return;
                routeMapAlert.classList.remove('d-none', 'alert-warning', 'alert-danger', 'alert-info');
                routeMapAlert.classList.add(`alert-${type}`);
                routeMapAlert.textContent = message;
            }

            function hideRouteAlert() {
                if (!routeMapAlert) return;
                routeMapAlert.classList.add('d-none');
                routeMapAlert.textContent = '';
            }

            function setRouteLoading(loading, text = 'Loading route...') {
                if (!routeMapShell || !routeMapLoader) return;
                routeMapLoader.textContent = text;
                routeMapShell.classList.toggle('loading', loading);
                mapEl.classList.toggle('route-loading', loading);
            }

            function getCsrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            }

            function readGeometryCache() {
                if (!geometryCacheKey) return null;
                try {
                    const raw = window.localStorage.getItem(geometryCacheKey);
                    if (!raw) return null;
                    const parsed = JSON.parse(raw);
                    if (!Array.isArray(parsed?.geometry) || parsed.geometry.length < 2) {
                        return null;
                    }
                    return parsed;
                } catch (error) {
                    return null;
                }
            }

            function writeGeometryCache(payload) {
                if (!geometryCacheKey) return;
                try {
                    window.localStorage.setItem(geometryCacheKey, JSON.stringify(payload));
                } catch (error) {
                    // Ignore quota/storage errors; map can still render from network.
                }
            }

            function renderGeometry(geometry) {
                if (!Array.isArray(geometry) || geometry.length < 2) {
                    return false;
                }

                if (routeLine) {
                    map.removeLayer(routeLine);
                }

                routeLine = L.polyline(
                    geometry.map(([lng, lat]) => [lat, lng]),
                    {
                        color: '#0d6efd',
                        weight: 4,
                        opacity: 0.85,
                    }
                ).addTo(map);

                return true;
            }

            function toggleFullscreen(forceOpen = null) {
                if (!routeMapShell) return;
                const shouldOpen = forceOpen === null
                    ? !routeMapShell.classList.contains('map-fullscreen')
                    : forceOpen;

                routeMapShell.classList.toggle('map-fullscreen', shouldOpen);
                document.body.classList.toggle('assignment-map-open', shouldOpen);

                if (routeMapToggleLabel) {
                    routeMapToggleLabel.textContent = shouldOpen ? 'Exit full size' : 'Full size';
                }
                if (routeMapToggleIcon) {
                    routeMapToggleIcon.className = shouldOpen ? 'mdi mdi-arrow-collapse-all' : 'mdi mdi-arrow-expand-all';
                }

                window.setTimeout(() => map.invalidateSize(), 180);
            }

            const map = L.map('assignment-route-map', {
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
            const points = [];

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

            function stopIcon(n) {
                return L.divIcon({
                    className: '',
                    html: `<span class="assignment-stop-badge">${n}</span>`,
                    iconSize: [26, 26],
                    iconAnchor: [13, 26]
                });
            }

            @if($assignment->route && !is_null($assignment->route->start_lat) && !is_null($assignment->route->start_lng))
                const startLat = parseFloat(@json($assignment->route->start_lat));
                const startLng = parseFloat(@json($assignment->route->start_lng));

                if (!isNaN(startLat) && !isNaN(startLng)) {
                    const startMarker = L.marker([startLat, startLng], {
                        icon: startIcon,
                        zIndexOffset: 1000
                    }).addTo(markerLayer).bindPopup(@json('<strong>Start</strong><br>' . ($assignment->route->start_location ?? '')));
                    points.push(startMarker.getLatLng());
                }
            @endif

            @if($assignment->route)
                @foreach($assignment->route->stops->sortBy('stop_order') as $stop)
                    @if(!is_null($stop->latitude) && !is_null($stop->longitude))
                        const stopMarker{{ $stop->id }} = L.marker([
                            {{ $stop->latitude }},
                            {{ $stop->longitude }}
                        ], {
                            icon: stopIcon({{ $stop->stop_order }}),
                            zIndexOffset: 900
                        }).addTo(markerLayer).bindPopup(@json('<strong>Stop ' . $stop->stop_order . '</strong><br>' . ($stop->address ?? 'Pickup Stop')));
                        points.push(stopMarker{{ $stop->id }}.getLatLng());
                    @endif
                @endforeach
            @endif

            @if($assignment->route && !is_null($assignment->route->end_lat) && !is_null($assignment->route->end_lng))
                const endLat = parseFloat(@json($assignment->route->end_lat));
                const endLng = parseFloat(@json($assignment->route->end_lng));

                if (!isNaN(endLat) && !isNaN(endLng)) {
                    const endMarker = L.marker([endLat, endLng], {
                        icon: endIcon,
                        zIndexOffset: 1000
                    }).addTo(markerLayer).bindPopup(@json('<strong>End</strong><br>' . ($assignment->route->end_location ?? '')));
                    points.push(endMarker.getLatLng());
                }
            @endif

            if (routeMapToggle) {
                routeMapToggle.addEventListener('click', function () {
                    toggleFullscreen();
                });
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && routeMapShell?.classList.contains('map-fullscreen')) {
                    toggleFullscreen(false);
                }
            });

            const cachedGeometry = readGeometryCache();
            if (Array.isArray(persistedRouteGeometry?.coordinates) && persistedRouteGeometry.coordinates.length > 1) {
                renderGeometry(persistedRouteGeometry.coordinates);
                writeGeometryCache({
                    geometry: persistedRouteGeometry.coordinates,
                    distance_meters: null,
                    duration_seconds: null,
                });
                hideRouteAlert();
            } else if (cachedGeometry?.geometry) {
                hideRouteAlert();
                renderGeometry(cachedGeometry.geometry);
            } else if (points.length > 1) {
                setRouteLoading(true);
                fetch(directionsEndpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                    body: JSON.stringify({
                        coordinates: points.map((wp) => [wp.lng, wp.lat]),
                    }),
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
                    if (!renderGeometry(geometry)) {
                        throw new Error('ORS_EMPTY_ROUTE');
                    }

                    writeGeometryCache({
                        geometry,
                        distance_meters: body.data?.distance_meters || null,
                        duration_seconds: body.data?.duration_seconds || null,
                    });
                    hideRouteAlert();
                    setRouteLoading(false);
                })
                .catch((err) => {
                    setRouteLoading(false);
                    showRouteAlert(`Routing failed: ${err?.message || 'ORS proxy error'}`, 'danger');
                });
            }

            if (points.length > 0) {
                map.fitBounds(L.latLngBounds(points), {
                    padding: [30, 30],
                    maxZoom: 15
                });
            } else {
                map.setView([14.5995, 120.9842], 11);
            }

            window.setTimeout(() => map.invalidateSize(), 150);
        });
    </script>
@endsection
