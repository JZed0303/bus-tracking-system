@extends('layouts.master')

@section('title')
Employee Detail
@endsection

@section('page-title')
Employee Profile
@endsection

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #employee-scan-map { height: 420px; width: 100%; }
</style>
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <!-- PAGE HEADER -->
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h4 class="mb-0">Employee Profile</h4>
            <small class="text-muted">View employee details, attendance, and QR status.</small>
        </div>
        <div class="col text-end">
            <a href="{{ route('admin.employees.index') }}" class="btn btn-light">
                Back
            </a>
        </div>
    </div>

    <div class="row g-4">

        <!-- MAIN COLUMN -->
        <div class="col-12">

            <!-- PROFILE CARD -->
            <div class="card overflow-hidden">
                <div class="position-relative">

                    <!-- Banner -->
                    <div class="bg-primary" style="height: 110px;"></div>

                    <!-- Content -->
                    <div class="card-body pt-0">
                        <div class="d-flex align-items-end justify-content-between flex-wrap gap-3">

                            <!-- Avatar + Name -->
                            <div class="d-flex align-items-end gap-3">
                                <div class="mt-n5">
                                    <img
                                        src="{{ $employee->photo_path
                                            ? asset('storage/'.$employee->photo_path)
                                            : asset('build/images/users/avatar-2.jpg') }}"
                                        class="avatar-xl rounded-circle img-thumbnail"
                                        alt="Employee Photo"
                                        style="width: 110px; height: 110px; object-fit: cover;"
                                    >
                                </div>

                                <div class="pb-2">
                                    <h5 class="mb-1">{{ $employee->user->full_name }}</h5>
                                    <div class="text-muted">
                                        {{ $employee->position ?? '—' }}
                                        <span class="mx-2">•</span>
                                        {{ $employee->department ?? '—' }}
                                    </div>

                                    <div class="mt-2">
                                        @php
                                            $statusClass = match($employee->status) {
                                                'active' => 'success',
                                                'suspended' => 'warning',
                                                'resigned' => 'secondary',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ ucfirst($employee->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="pb-2">
                                <div class="d-flex gap-2 flex-wrap justify-content-end">
                                    <a href="{{ route('admin.employees.trips', $employee->id) }}"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="mdi mdi-bus"></i> Transport History
                                    </a>

                                    <a href="{{ route('admin.employees.attendance', $employee->id) }}"
                                       class="btn btn-outline-secondary btn-sm">
                                        <i class="mdi mdi-calendar-check"></i> Attendance
                                    </a>
                                </div>
                            </div>

                        </div>

                        <hr class="my-4">

                        <!-- Details Grid -->
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 border rounded h-100">
                                    <div class="text-muted small">Company</div>
                                    <div class="fw-semibold">{{ $employee->company->name ?? '—' }}</div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 border rounded h-100">
                                    <div class="text-muted small">Employee Code</div>
                                    <div class="fw-semibold">{{ $employee->employee_code ?? '—' }}</div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 border rounded h-100">
                                    <div class="text-muted small">Email</div>
                                    <div class="fw-semibold text-break">{{ $employee->user->email ?? '—' }}</div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 border rounded h-100">
                                    <div class="text-muted small">Address</div>
                                    <div class="fw-semibold">{{ $employee->user->address ?? '—' }}</div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- SUMMARY METRICS -->
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card mb-0">
                        <div class="card-body text-center">
                            <div class="text-muted small">Total Scans</div>
                            <h3 class="mb-0">{{ $checkinCount }}</h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-0">
                        <div class="card-body text-center">
                            <div class="text-muted small">Check-ins</div>
                            <h3 class="mb-0">{{ $employee->checkins()->where('scan_type','checkin')->count() }}</h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-0">
                        <div class="card-body text-center">
                            <div class="text-muted small">Check-outs</div>
                            <h3 class="mb-0">{{ $employee->checkins()->where('scan_type','checkout')->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LOWER SECTION -->
            <div class="row g-4 mt-1">

                <!-- LEFT -->
                <div class="col-xl-12">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">Recent Activity</h5>

                        </div>

                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="p-3 border rounded">
                                        <div class="text-muted small">Last Scan</div>
                                        <div class="fw-semibold">
                                            {{ optional($lastCheckin)->scan_time?->format('M d, Y h:i A') ?? '—' }}
                                        </div>
                                        <div class="text-muted small mt-1">
                                            Type: {{ optional($lastCheckin)->scan_type ? ucfirst($lastCheckin->scan_type) : '—' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="p-3 border rounded">
                                        <div class="text-muted small">Total Trips</div>
                                        <div class="fw-semibold">
                                            {{ $employee->checkins()->distinct('trip_id')->count('trip_id') }}
                                        </div>
                                        <div class="text-muted small mt-1">
                                            Based on scanned trip IDs
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
    <div class="p-4 border rounded d-flex align-items-center justify-content-between">
        <div>
            <div class="text-muted small">QR Code</div>

            @if($employee->qrcode)
                <span class="badge bg-success">QR Generated</span>
            @else
                <span class="badge bg-warning text-dark">No QR Code</span>
            @endif
        </div>

        <div>
            <a href="{{ route('admin.employees.qr', $employee->id) }}"
               class="btn {{ $employee->qrcode ? 'btn-warning' : 'btn-primary' }} btn-sm">
                {{ $employee->qrcode ? 'View / Print' : 'Generate' }}
            </a>
        </div>
    </div>
</div>

                            </div>

                            <!-- MAP -->
                            <div class="mt-3">
                                <div class="card mb-0">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">Scan Map</h5>
                                        <small class="text-muted">Check-in / Check-out locations</small>
                                    </div>
                                    <div class="card-body p-0">
                                        <div id="employee-scan-map"></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- RIGHT -->



                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
(function () {
    const rawPoints = {!! json_encode(
        $checkinsForMap->map(function ($c) {
            return [
                'id'      => $c->id,
                'type'    => $c->scan_type,
                'time'    => optional($c->scan_time)->format('M d, Y h:i A'),
                'lat'     => $c->scan_lat !== null ? (float) $c->scan_lat : null,
                'lng'     => $c->scan_lng !== null ? (float) $c->scan_lng : null,
                'trip_id' => $c->trip_id,
            ];
        })
    ) !!};

    const mapEl = document.getElementById('employee-scan-map');
    if (!mapEl) return;

    // Filter invalid coords (null, NaN, 0,0)
    const points = rawPoints.filter(p =>
        Number.isFinite(p.lat) &&
        Number.isFinite(p.lng) &&
        !(p.lat === 0 && p.lng === 0)
    );

    // Default if no points
    const fallbackCenter = [14.312, 121.047];

    const map = L.map('employee-scan-map').setView(fallbackCenter, 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    if (!points.length) {
        L.marker(fallbackCenter).addTo(map).bindPopup('No scan coordinates yet.');
        setTimeout(() => map.invalidateSize(), 200);
        return;
    }

    const styles = {
        checkin:  { radius: 7, color: '#198754', fillColor: '#198754', fillOpacity: 0.85 },
        checkout: { radius: 7, color: '#dc3545', fillColor: '#dc3545', fillOpacity: 0.85 },
        other:    { radius: 7, color: '#6c757d', fillColor: '#6c757d', fillOpacity: 0.85 },
    };

    const bounds = [];

    points.forEach(p => {
        const style = styles[p.type] ?? styles.other;

        L.circleMarker([p.lat, p.lng], style)
            .addTo(map)
            .bindPopup(`
                <div style="min-width:180px">
                    <div><strong>${(p.type || 'scan').toUpperCase()}</strong></div>
                    <div>${p.time || ''}</div>
                    ${p.trip_id ? `<div class="mt-1"><small>Trip ID: ${p.trip_id}</small></div>` : ''}
                    <div class="mt-1"><small>${p.lat.toFixed(6)}, ${p.lng.toFixed(6)}</small></div>
                </div>
            `);

        bounds.push([p.lat, p.lng]);
    });

    // Polyline route
    L.polyline(points.map(p => [p.lat, p.lng]), { weight: 3, opacity: 0.7 }).addTo(map);

    map.fitBounds(bounds, { padding: [25, 25] });

    // Fix render sizing inside cards
    setTimeout(() => map.invalidateSize(), 200);
})();
</script>
@endsection
