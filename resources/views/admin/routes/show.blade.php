@extends('layouts.master')

@section('title', 'Route Details')
@section('page-title', 'Route Details')

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
<style>
    #route-map {
        height: 420px;
        border-radius: 6px;
    }

    .stop-badge {
        background: #ffc107;
        color: #000;
        padding: 4px 7px;
        border-radius: 50%;
        font-size: 12px;
        font-weight: 600;
        display: inline-block;
    }
</style>
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    {{-- HEADER --}}
    <div class="row mb-3">
        <div class="col-sm-6">
            <h4 class="mb-0">{{ $route->name }}</h4>
            <small class="text-muted">
                {{ $route->company?->name ?? 'Shared' }}
            </small>
        </div>
        <div class="col-sm-6 text-end">
            <a href="{{ route('admin.routes.edit', $route->id) }}"
               class="btn btn-secondary btn-sm">
                <i class="mdi mdi-pencil-outline"></i> Edit Route
            </a>
            <a href="{{ route('admin.routes.index') }}"
               class="btn btn-light btn-sm">
                Back
            </a>
        </div>
    </div>

    {{-- DETAILS --}}
    <div class="card mb-3">
        <div class="card-body">

            <div class="row mb-2">
                <div class="col-md-6">
                    <strong>Status:</strong>
                    <span class="badge bg-{{ $route->status === 'active' ? 'success' : 'secondary' }}">
                        {{ ucfirst($route->status) }}
                    </span>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <strong>Start Location</strong>
                    <p class="text-muted mb-2">{{ $route->start_location }}</p>
                </div>
                <div class="col-md-6">
                    <strong>End Location</strong>
                    <p class="text-muted mb-2">{{ $route->end_location }}</p>
                </div>
            </div>

            @if($route->description)
                <hr>
                <strong>Description</strong>
                <p class="text-muted">{{ $route->description }}</p>
            @endif
        </div>
    </div>

    {{-- MAP --}}
    <div class="card mb-3">
        <div class="card-header">
            <strong>Route Map</strong>
            <small class="text-muted d-block">
                Real road route showing start, stops, and destination
            </small>
        </div>
        <div class="card-body p-0">
            <div id="route-map"></div>
        </div>
    </div>

    {{-- STOPS LIST --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">Route Stops</h5>

            @if($route->stops->isEmpty())
                <p class="text-muted mb-0">No stops defined for this route.</p>
            @else
                <ul class="list-group list-group-flush">
                    @foreach($route->stops->sortBy('stop_order') as $stop)
                        <li class="list-group-item">
                            <span class="stop-badge me-2">{{ $stop->stop_order }}</span>
                            {{ $stop->address ?? 'Pickup Stop' }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const map = L.map('route-map', {
        zoomControl: true,
        dragging: true,
        scrollWheelZoom: true,
        doubleClickZoom: true,
        boxZoom: true,
        keyboard: true
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
    }).addTo(map);

    const points = [];
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

    function stopIcon(n) {
        return L.divIcon({
            className: '',
            html: `<span class="stop-badge">${n}</span>`,
            iconSize: [26, 26],
            iconAnchor: [13, 26]
        });
    }

    /* ================= START ================= */
 @if(!is_null($route->start_lat) && !is_null($route->start_lng))
    const startLat = parseFloat('{{ $route->start_lat }}');
    const startLng = parseFloat('{{ $route->start_lng }}');

    if (!isNaN(startLat) && !isNaN(startLng)) {
        const startMarker = L.marker(
            [startLat, startLng],
            { icon: startIcon, zIndexOffset: 1000 }
        ).addTo(markerLayer)
         .bindPopup('<strong>Start</strong><br>{{ $route->start_location }}');

        points.push(startMarker.getLatLng());
    }
@endif


    /* ================= STOPS ================= */
    @foreach($route->stops->sortBy('stop_order') as $stop)
        @if($stop->latitude && $stop->longitude)
            const stopMarker{{ $stop->id }} = L.marker(
                [{{ $stop->latitude }}, {{ $stop->longitude }}],
                {
                    icon: stopIcon({{ $stop->stop_order }}),
                    zIndexOffset: 900
                }
            ).addTo(markerLayer)
             .bindPopup(
                '<strong>Stop {{ $stop->stop_order }}</strong><br>{{ $stop->address ?? "Pickup Stop" }}'
             );

            points.push(stopMarker{{ $stop->id }}.getLatLng());
        @endif
    @endforeach

    /* ================= END ================= */
  @if(!is_null($route->end_lat) && !is_null($route->end_lng))
    const endLat = parseFloat('{{ $route->end_lat }}');
    const endLng = parseFloat('{{ $route->end_lng }}');

    if (!isNaN(endLat) && !isNaN(endLng)) {
        const endMarker = L.marker(
            [endLat, endLng],
            { icon: endIcon, zIndexOffset: 1000 }
        ).addTo(markerLayer)
         .bindPopup('<strong>End</strong><br>{{ $route->end_location }}');

        points.push(endMarker.getLatLng());
    }
@endif

    /* ================= ROUTE LINE ================= */
    if (points.length > 1) {
        L.Routing.control({
            waypoints: points,
            addWaypoints: false,
            draggableWaypoints: false,
            createMarker: () => null,
            fitSelectedRoutes: false,
            show: false,
            lineOptions: {
                styles: [{ color: '#0d6efd', weight: 4, opacity: 0.85 }]
            },
            router: L.Routing.osrmv1({
                serviceUrl: 'https://router.project-osrm.org/route/v1'
            })
        }).addTo(map).getContainer().style.display = 'none';
    }

    /* ================= AUTO FIT ================= */
    if (points.length > 0) {
        map.fitBounds(L.latLngBounds(points), {
            padding: [60, 60],
            maxZoom: 16
        });
    }
});
</script>

@endsection
