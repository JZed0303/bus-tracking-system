@extends('layouts.master')

@section('title', 'Employee Detail')
@section('page-title', 'Employee Profile')

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .profile-page {
        --border-soft: #e9edf4;
        --text-muted-soft: #6c757d;
    }

    .card-soft { border: 1px solid rgba(0,0,0,.06); box-shadow: 0 1px 2px rgba(0,0,0,.04); }
    .label { font-size: .78rem; color: #6c757d; text-transform: uppercase; letter-spacing: .03em; }
    .value { font-weight: 600; }

    .employee-cover {
        height: 150px;
        border-radius: 12px 12px 0 0;
        background-size: cover;
        background-position: center;
        position: relative;
        overflow: hidden;
    }

    .employee-cover::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, rgba(16, 24, 40, .2), rgba(13, 110, 253, .18));
    }

    .profile-head-title {
        font-weight: 700;
        letter-spacing: .01em;
    }

    .profile-sub {
        color: var(--text-muted-soft);
        font-size: .92rem;
    }

    .employee-avatar {
        width: 108px;
        height: 108px;
        object-fit: cover;
    }

    #employee-scan-map {
        height: 420px;
        width: 100%;
    }

    .map-shell {
        position: relative;
        border-top: 1px solid var(--border-soft);
    }

    .map-legend-overlay {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 600;
        background: rgba(255, 255, 255, 0.96);
        border: 1px solid var(--border-soft);
        border-radius: 10px;
        box-shadow: 0 6px 18px rgba(16, 24, 40, 0.12);
        padding: .6rem .65rem;
        min-width: 165px;
    }

    .map-legend-title {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #6c757d;
        margin-bottom: .45rem;
    }

    .map-legend-item {
        display: flex;
        align-items: center;
        gap: .45rem;
        font-size: .78rem;
        color: #495057;
        margin-bottom: .35rem;
    }

    .map-legend-item:last-child { margin-bottom: 0; }

    .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }

    .line {
        width: 18px;
        height: 0;
        border-top: 2px solid #0d6efd;
        display: inline-block;
    }

    .qr-box svg {
        width: 100%;
        max-width: 220px;
        height: auto;
        display: block;
        margin: 0 auto;
    }

    .card-soft .card-header {
        border-bottom: 1px solid var(--border-soft);
    }

    .info-grid .info-item {
        border: 1px solid var(--border-soft);
        border-radius: 10px;
        padding: .8rem .9rem;
        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
        height: 100%;
    }

    .stats-list .stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .5rem 0;
        border-bottom: 1px dashed var(--border-soft);
    }

    .stats-list .stat-row:last-child {
        border-bottom: 0;
    }

    .stats-list .stat-value {
        font-weight: 700;
        font-size: 1rem;
    }
</style>
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
@php
    $statusClass = match ($employee->status) {
        'active' => 'success',
        'suspended' => 'warning',
        'resigned' => 'secondary',
        default => 'secondary',
    };

    $avatarUrl = $employee->photo_path
        ? asset('storage/' . $employee->photo_path)
        : asset('build/images/users/avatar-2.jpg');

    $coverUrl = asset('build/images/pattern-bg.jpg');

    $allCheckins = $employee->checkins;
    $checkinOnlyCount = $allCheckins->where('scan_type', 'checkin')->count();
    $checkoutOnlyCount = $allCheckins->where('scan_type', 'checkout')->count();
    $tripCount = $allCheckins->pluck('trip_id')->filter()->unique()->count();
@endphp

<div class="container-fluid profile-page">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h4 class="mb-0 profile-head-title">Employee Profile</h4>
            <small class="profile-sub">Profile overview, scan map activity, and QR credential.</small>
        </div>
        <div class="col text-end">
            <a href="{{ route('admin.employees.index') }}" class="btn btn-light">Back</a>
        </div>
    </div>

    <div class="card card-soft overflow-hidden mb-3">
        <div class="employee-cover" style="background-image: url('{{ $coverUrl }}');"></div>
        <div class="card-body pt-5">
            <div class="d-flex align-items-end justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-end gap-3">
                    <img src="{{ $avatarUrl }}" class="employee-avatar rounded-circle img-thumbnail mt-n5" alt="Employee Photo">
                    <div class="pb-2">
                        <h5 class="mb-1">{{ $employee->user->full_name }}</h5>
                        <div class="text-muted">{{ $employee->department ?? 'No department' }}</div>
                        <span class="badge bg-{{ $statusClass }} mt-2">{{ ucfirst($employee->status) }}</span>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap pb-2">
                    <a href="{{ route('admin.employees.trips', $employee->id) }}" class="btn btn-outline-primary btn-sm">
                        <i class="mdi mdi-bus me-1"></i> Transport History
                    </a>
                    <a href="{{ route('admin.employees.attendance', $employee->id) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="mdi mdi-calendar-check me-1"></i> Attendance
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card card-soft mb-3">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Profile Information</h5>
                </div>
                <div class="card-body info-grid">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="info-item"><div class="label">First Name</div><div class="value">{{ $employee->user->first_name ?? 'N/A' }}</div></div></div>
                        <div class="col-md-6"><div class="info-item"><div class="label">Middle Name</div><div class="value">{{ $employee->user->middle_name ?? 'N/A' }}</div></div></div>
                        <div class="col-md-6"><div class="info-item"><div class="label">Last Name</div><div class="value">{{ $employee->user->last_name ?? 'N/A' }}</div></div></div>
                        <div class="col-md-6"><div class="info-item"><div class="label">Email</div><div class="value text-break">{{ $employee->user->email ?? 'N/A' }}</div></div></div>
                        <div class="col-md-6"><div class="info-item"><div class="label">Employee Code</div><div class="value">{{ $employee->employee_code ?? 'N/A' }}</div></div></div>
                        <div class="col-md-6"><div class="info-item"><div class="label">Company</div><div class="value">{{ $employee->company->name ?? 'N/A' }}</div></div></div>
                        <div class="col-md-6"><div class="info-item"><div class="label">Department</div><div class="value">{{ $employee->department ?? 'N/A' }}</div></div></div>
                        <div class="col-md-6"><div class="info-item"><div class="label">Status</div><div class="value"><span class="badge bg-{{ $statusClass }}">{{ ucfirst($employee->status) }}</span></div></div></div>
                    </div>
                </div>
            </div>

            <div class="card card-soft mt-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Scan Map</h5>
                    <small class="text-muted">Check-in and check-out GPS timeline</small>
                </div>
                <div class="map-shell">
                    <div id="employee-scan-map"></div>
                    <div class="map-legend-overlay">
                        <div class="map-legend-title">Map Legend</div>
                        <div class="map-legend-item"><span class="dot bg-success"></span> Check-in</div>
                        <div class="map-legend-item"><span class="dot bg-danger"></span> Check-out</div>
                        <div class="map-legend-item"><span class="dot bg-secondary"></span> Other Scan</div>
                        <div class="map-legend-item"><span class="line"></span> Travel Path</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card card-soft mb-3">
                <div class="card-header bg-white"><h5 class="card-title mb-0">Quick Stats</h5></div>
                <div class="card-body stats-list">
                    <div class="stat-row"><span class="label">Total Scans</span><span class="stat-value">{{ $checkinCount }}</span></div>
                    <div class="stat-row"><span class="label">Check-ins</span><span class="stat-value text-success">{{ $checkinOnlyCount }}</span></div>
                    <div class="stat-row"><span class="label">Check-outs</span><span class="stat-value text-danger">{{ $checkoutOnlyCount }}</span></div>
                    <div class="stat-row"><span class="label">Total Trips</span><span class="stat-value">{{ $tripCount }}</span></div>
                </div>
            </div>

            <div class="card card-soft mb-3">
                <div class="card-header bg-white"><h5 class="card-title mb-0">Employee QR Code</h5></div>
                <div class="card-body text-center">
                    @if($employee->qrcode)
                        <div class="qr-box mb-3">
                            {!! QrCode::format('svg')->size(220)->margin(1)->generate($employee->qrcode->qr_token) !!}
                        </div>
                        <span class="badge bg-success mb-2">QR Active</span>
                        <div class="small text-muted">
                            Generated: {{ optional($employee->qrcode->generated_at)->format('M d, Y h:i A') ?? 'N/A' }}
                        </div>
                    @else
                        <div class="alert alert-warning mb-3">No QR code found for this employee.</div>
                        <form method="POST" action="{{ route('admin.employees.qr.generate', $employee->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm">Generate QR</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card card-soft">
                <div class="card-header bg-white"><h5 class="card-title mb-0">Recent Activity</h5></div>
                <div class="card-body">
                    <div class="label">Last Scan</div>
                    <div class="value mb-1">{{ optional($lastCheckin)->scan_time?->format('M d, Y h:i A') ?? 'N/A' }}</div>
                    <div class="text-muted small">
                        Type: {{ optional($lastCheckin)->scan_type ? ucfirst($lastCheckin->scan_type) : 'N/A' }}
                    </div>
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

        const points = rawPoints.filter(p =>
            Number.isFinite(p.lat) &&
            Number.isFinite(p.lng) &&
            !(p.lat === 0 && p.lng === 0)
        );

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
            checkin:  { radius: 7, color: '#0f5132', weight: 1.5, fillColor: '#198754', fillOpacity: 0.95 },
            checkout: { radius: 7, color: '#842029', weight: 1.5, fillColor: '#dc3545', fillOpacity: 0.95 },
            other:    { radius: 7, color: '#495057', weight: 1.5, fillColor: '#6c757d', fillOpacity: 0.95 },
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

        const routeLine = L.polyline(
            points.map(p => [p.lat, p.lng]),
            { weight: 4, color: '#0d6efd', opacity: 0.8, lineCap: 'round', lineJoin: 'round' }
        ).addTo(map);

        routeLine.bringToBack();
        map.fitBounds(bounds, { padding: [25, 25] });
        setTimeout(() => map.invalidateSize(), 200);
    })();
</script>
@endsection
