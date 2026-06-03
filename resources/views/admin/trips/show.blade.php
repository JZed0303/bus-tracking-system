@extends('layouts.master')

@section('title', 'Trip Details')

@section('css')
    <link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />

    {{-- Leaflet CSS --}}
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin=""
    />
    <style>
        .trip-hero-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 8px 22px rgba(20, 33, 61, 0.08);
        }
        .trip-kpi-card {
            border: 1px solid #e9edf4;
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #fafcff 100%);
            padding: 14px;
            height: 100%;
        }
        .trip-kpi-label {
            font-size: 11px;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #7c8799;
            margin-bottom: 4px;
        }
        .trip-kpi-value {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2a37;
            margin-bottom: 0;
        }
        .trip-bus-photo {
            width: 100%;
            max-width: 320px;
            height: 180px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #e9edf4;
            background: #f8fafc;
            display: block;
            margin-top: 10px;
        }
        .trip-driver-photo {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 50%;
            border: 1px solid #e9edf4;
            background: #f8fafc;
            display: block;
            margin-bottom: 10px;
        }
        .timeline-detail-table th {
            width: 260px;
            background: #f8fafc;
            color: #5f6c80;
            font-weight: 600;
        }
    </style>
@endsection

@section('page-title', 'Trip Details')

@section('body')
    <body data-sidebar="colored">
@endsection

@section('content')
@php
    $tab = request()->query('tab', 'timeline');
    $tz = 'Asia/Manila';
    $statusColor = match ($trip->status) {
        'ongoing'   => 'success',
        'completed' => 'primary',
        'cancelled' => 'danger',
        default     => 'secondary',
    };

    $summaryScheduledStart = $trip->scheduled_start_time?->timezone($tz);
    $summaryActualStart = $trip->actual_start_time?->timezone($tz);
    $summaryActualEnd = $trip->actual_end_time?->timezone($tz);
@endphp

<div class="container-fluid">

    {{-- PAGE HEADER --}}
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h4 class="mb-1">Trip Details</h4>
            <p class="text-muted mb-0">
                Monitor trip schedule, employee movements, and GPS playback for operational insight.
            </p>
        </div>
        <div class="col text-end">
            @if($trip->status === 'ongoing')
                <button type="button" class="btn btn-danger btn-sm me-2" data-bs-toggle="modal" data-bs-target="#incidentModal">
                    <i class="mdi mdi-alert-circle-outline"></i> Report Incident
                </button>
            @endif
            <a href="{{ route('admin.trips.report', $trip) }}" class="btn btn-primary btn-sm me-2">
                <i class="mdi mdi-file-chart-outline"></i> Trip Report
            </a>
            <a href="{{ route('admin.trips.today') }}" class="btn btn-outline-secondary btn-sm">
                <i class="mdi mdi-arrow-left"></i> Back to Today’s Trips
            </a>
        </div>
    </div>

    {{-- TRIP SUMMARY HEADER --}}
    <div class="card mb-3 trip-hero-card">
        <div class="card-body">
            <div class="row g-3 align-items-stretch">
                <div class="col-lg-5">
                    <div class="trip-kpi-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <p class="trip-kpi-label">Trip Reference</p>
                                <h5 class="mb-1">
                                    {{ optional($trip->assignment->bus)->plate_number ?? 'Unassigned Bus' }}
                                </h5>
                                <p class="mb-0 text-muted">
                                    {{ optional($trip->assignment->route)->name ?? 'No route linked' }}
                                </p>
                            </div>
                            <span class="badge bg-{{ $statusColor }} text-uppercase px-3 py-2">
                                {{ ucfirst($trip->status) }}
                            </span>
                        </div>
                        <img
                            src="{{ optional($trip->assignment->bus)->photo_url ?? asset('build/images/bus-placeholder.png') }}"
                            alt="Bus Photo"
                            class="trip-bus-photo"
                        >
                        <div class="small text-muted mt-3">
                            <span class="fw-semibold text-dark">Direction:</span> {{ $trip->direction_label }}
                        </div>
                        @if($trip->transfer_from_trip_id)
                            <div class="small text-muted mt-1">
                                <span class="fw-semibold text-dark">Replacement Of Trip:</span> #{{ $trip->transfer_from_trip_id }}
                            </div>
                        @endif
                        @if($trip->ended_reason)
                            <div class="small text-muted mt-1">
                                <span class="fw-semibold text-dark">Ended Reason:</span> {{ ucfirst($trip->ended_reason) }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="trip-kpi-card">
                                <p class="trip-kpi-label">Driver</p>
                                <img
                                    src="{{ optional($trip->assignment->driver)->photo_url ?? asset('build/images/user-placeholder.png') }}"
                                    alt="Driver Photo"
                                    class="trip-driver-photo"
                                >
                                <p class="trip-kpi-value">
                                    {{ optional($trip->assignment->driver?->user)->full_name ?? 'Unassigned Driver' }}
                                </p>
                                <p class="mb-0 text-muted small">Assigned operator</p>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="trip-kpi-card">
                                <p class="trip-kpi-label">Trip Date</p>
                                <p class="trip-kpi-value">
                                    {{ $trip->trip_date?->timezone($tz)->format('M d, Y') ?? '—' }}
                                </p>
                                <p class="mb-0 text-muted small">Philippines time (PHT)</p>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="trip-kpi-card">
                                <p class="trip-kpi-label">Trip Start</p>
                                <p class="trip-kpi-value">
                                    {{ $summaryActualStart?->format('h:iA') ?? '—' }}
                                </p>
                                <p class="mb-0 text-muted small">{{ $summaryActualStart?->format('M d, Y') ?? '' }}</p>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="trip-kpi-card">
                                <p class="trip-kpi-label">Trip End</p>
                                <p class="trip-kpi-value">
                                    {{ $summaryActualEnd?->format('h:iA') ?? '—' }}
                                </p>
                                <p class="mb-0 text-muted small">{{ $summaryActualEnd?->format('M d, Y') ?? '' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($pendingTransferEmployees) && $pendingTransferEmployees->isNotEmpty())
        <div class="alert alert-warning d-flex align-items-start mb-3">
            <i class="mdi mdi-transfer-right me-2 mt-1"></i>
            <div>
                <strong>Transferred Pending Confirmation:</strong>
                {{ $pendingTransferEmployees->count() }} employee(s) are transferred to this trip but not yet re-scanned.
            </div>
        </div>
    @endif

    {{-- TABS --}}
    <ul class="nav nav-tabs nav-tabs-custom mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'timeline' ? 'active' : '' }}"
               href="{{ route('admin.trips.show', $trip) }}?tab=timeline">
                <i class="mdi mdi-timeline-outline me-1"></i>
                <span class="d-none d-sm-inline">Timeline</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ $tab === 'checkins' ? 'active' : '' }}"
               href="{{ route('admin.trips.show', $trip) }}?tab=checkins">
                <i class="mdi mdi-account-clock-outline me-1"></i>
                <span class="d-none d-sm-inline">Employee Check-ins</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ $tab === 'gps' ? 'active' : '' }}"
               href="{{ route('admin.trips.show', $trip) }}?tab=gps">
                <i class="mdi mdi-map-marker-path me-1"></i>
                <span class="d-none d-sm-inline">GPS Playback</span>
            </a>
        </li>
    </ul>

    {{-- TAB CONTENT --}}
    <div class="card shadow-sm border-0">
        <div class="card-body">

            {{-- TIMELINE TAB --}}
            @if($tab === 'timeline')
                @php
                    $scheduledStart = $trip->scheduled_start_time?->timezone($tz);
                    $scheduledEnd = $trip->scheduled_end_time?->timezone($tz);
                    $actualStart = $trip->actual_start_time?->timezone($tz);
                    $actualEnd = $trip->actual_end_time?->timezone($tz);

                    $durationMinutes = ($actualStart && $actualEnd)
                        ? $actualStart->diffInMinutes($actualEnd)
                        : null;

                    $gpsPointsCount = $trip->locations()->count();
                    $latestScanByEmployee = $trip->checkins
                        // VOID FLOW: timeline counters should ignore voided scans.
                        ->whereNull('voided_at')
                        ->sortBy('scan_time')
                        ->groupBy('employee_id')
                        ->map(fn ($rows) => $rows->last());
                    $totalEmployeesCount = $latestScanByEmployee->count();
                    $onboardNowCount = $latestScanByEmployee
                        ->filter(fn ($scan) => $scan && $scan->scan_type === 'checkin')
                        ->count();
                    $completedRideCount = $latestScanByEmployee
                        ->filter(fn ($scan) => $scan && $scan->scan_type === 'checkout')
                        ->count();
                @endphp

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="card-title mb-1">Trip Timeline</h5>
                        <p class="card-subtitle text-muted mb-0">
                            Detailed breakdown of this trip in Philippines time (PHT).
                        </p>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    @if($scheduledStart || $scheduledEnd)
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="trip-kpi-card">
                                <p class="trip-kpi-label">Planned Time</p>
                                <p class="trip-kpi-value">{{ $scheduledStart?->format('h:iA') ?? '—' }}</p>
                                <p class="mb-0 text-muted small">to {{ $scheduledEnd?->format('h:iA') ?? '—' }}</p>
                            </div>
                        </div>
                    @endif
                    @if($actualStart || $actualEnd)
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <div class="trip-kpi-card">
                                <p class="trip-kpi-label">Actual Time</p>
                                <p class="trip-kpi-value">{{ $actualStart?->format('h:iA') ?? '—' }}</p>
                                <p class="mb-0 text-muted small">to {{ $actualEnd?->format('h:iA') ?? '—' }}</p>
                            </div>
                        </div>
                    @endif
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="trip-kpi-card">
                            <p class="trip-kpi-label">Trip Duration</p>
                            <p class="trip-kpi-value">
                                @if(is_null($durationMinutes))
                                    —
                                @else
                                    {{ intdiv($durationMinutes, 60) }}h {{ $durationMinutes % 60 }}m
                                @endif
                            </p>
                            <p class="mb-0 text-muted small">Based on actual start and end</p>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 col-sm-6">
                        <div class="trip-kpi-card">
                            <p class="trip-kpi-label">Total Employees</p>
                            <p class="trip-kpi-value">{{ $totalEmployeesCount }}</p>
                            <p class="mb-0 text-muted small">Unique employees with scans</p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="trip-kpi-card">
                            <p class="trip-kpi-label">Employee Boarding</p>
                            <p class="trip-kpi-value">
                                {{ $onboardNowCount }} On Board
                            </p>
                            <p class="mb-0 text-muted small">
                                {{ $completedRideCount }} Completed Ride
                            </p>
                        </div>
                    </div>
                </div>

           
            @endif

            {{-- CHECK-INS TAB --}}
            @if($tab === 'checkins')
                @php
                    $expectedEmployees = collect($expectedEmployees ?? []);
                    $expectedPendingCount = $expectedEmployees->where('status', 'pending')->count();
                    $expectedCheckedInCount = $expectedEmployees->where('status', 'checked_in')->count();
                    $expectedCheckedOutCount = $expectedEmployees->where('status', 'checked_out')->count();
                    $expectedMissedCount = $expectedEmployees->where('status', 'missed')->count();
                @endphp
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="card-title mb-1">Employee Check-ins</h5>
                        <p class="card-subtitle text-muted mb-0">
                            Expected riders and scan history for this trip.
                        </p>
                    </div>
                </div>

                @if($expectedEmployees->isNotEmpty())
                    <div class="row g-3 mb-3">
                        <div class="col-lg-3 col-sm-6">
                            <div class="trip-kpi-card">
                                <p class="trip-kpi-label">Expected Employees</p>
                                <p class="trip-kpi-value">{{ $expectedEmployees->count() }}</p>
                                <p class="mb-0 text-muted small">Scheduled or route-assigned riders</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-6">
                            <div class="trip-kpi-card">
                                <p class="trip-kpi-label">Checked In</p>
                                <p class="trip-kpi-value">{{ $expectedCheckedInCount }}</p>
                                <p class="mb-0 text-muted small">Currently on board</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-6">
                            <div class="trip-kpi-card">
                                <p class="trip-kpi-label">Checked Out</p>
                                <p class="trip-kpi-value">{{ $expectedCheckedOutCount }}</p>
                                <p class="mb-0 text-muted small">Ride completed</p>
                            </div>
                        </div>
                        <div class="col-lg-3 col-sm-6">
                            <div class="trip-kpi-card">
                                <p class="trip-kpi-label">Pending / Missed</p>
                                <p class="trip-kpi-value">{{ $expectedPendingCount + $expectedMissedCount }}</p>
                                <p class="mb-0 text-muted small">{{ $expectedMissedCount }} marked missed</p>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table id="expected-checkins-table"
                               class="table table-bordered table-striped table-hover table-sm dt-responsive mb-0"
                               style="width:100%">
                            <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Employee</th>
                                <th style="width: 160px;">Expected Pickup</th>
                                <th>Pickup Stop</th>
                                <th style="width: 150px;">Status</th>
                                <th style="width: 220px;">Latest Activity</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($expectedEmployees as $index => $expected)
                                @php
                                    $statusBadge = match ($expected['status']) {
                                        'checked_in' => 'success',
                                        'checked_out' => 'secondary',
                                        'missed' => 'danger',
                                        default => 'warning',
                                    };
                                    $statusLabel = match ($expected['status']) {
                                        'checked_in' => 'Checked In',
                                        'checked_out' => 'Checked Out',
                                        'missed' => 'Missed',
                                        default => 'Pending',
                                    };
                                    $latestActivity = $expected['checkout']?->scan_time ?? $expected['checkin']?->scan_time;
                                    $latestLabel = $expected['checkout']
                                        ? 'Checked out'
                                        : ($expected['checkin'] ? 'Checked in' : 'No scan yet');
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $expected['employee']->user?->full_name ?? '—' }}</div>
                                        <div class="small text-muted">{{ $expected['employee']->employee_code ?? '—' }}</div>
                                    </td>
                                    <td>
                                        {{ $expected['expected_pickup_time']?->format('h:iA') ?? '—' }}
                                    </td>
                                    <td>{{ $expected['pickup_stop'] ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $statusBadge }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td>
                                        <div>{{ $latestLabel }}</div>
                                        <div class="small text-muted">
                                            {{ $latestActivity ? $latestActivity->timezone('Asia/Manila')->format('M d, Y h:iA') : '—' }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if($trip->checkins->isEmpty())
                    <div class="alert alert-light border d-flex align-items-center mb-0">
                        <i class="mdi mdi-information-outline text-muted me-2"></i>
                        <span class="text-muted">No scan records have been recorded for this trip yet.</span>
                    </div>
                @else
                    @php
                        $checkinRows = $trip->checkins->sortByDesc('scan_time')->values();
                        $checkinOnlyRows = $checkinRows->where('scan_type', 'checkin')->values();
                        $checkoutOnlyRows = $checkinRows->where('scan_type', 'checkout')->values();
                    @endphp
                    <ul class="nav nav-pills mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active"
                               id="checkins-in-tab"
                               data-bs-toggle="pill"
                               href="#checkins-in-pane"
                               role="tab"
                               aria-controls="checkins-in-pane"
                               aria-selected="true">
                                <i class="mdi mdi-login me-1"></i>
                                Check-ins ({{ $checkinOnlyRows->count() }})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link"
                               id="checkins-out-tab"
                               data-bs-toggle="pill"
                               href="#checkins-out-pane"
                               role="tab"
                               aria-controls="checkins-out-pane"
                               aria-selected="false">
                                <i class="mdi mdi-logout me-1"></i>
                                Check-outs ({{ $checkoutOnlyRows->count() }})
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="checkins-in-pane" role="tabpanel" aria-labelledby="checkins-in-tab">
                            @if($checkinOnlyRows->isEmpty())
                                <div class="alert alert-light border d-flex align-items-center mb-0">
                                    <i class="mdi mdi-information-outline text-muted me-2"></i>
                                    <span class="text-muted">No check-in records for this trip.</span>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table id="checkins-in-table"
                                           class="table table-bordered table-striped table-hover table-sm dt-responsive mb-0"
                                           style="width:100%">
                                        <thead class="table-light">
                                        <tr>
                                            <th style="width: 60px;">#</th>
                                            <th>Employee</th>
                                            <th style="width: 140px;">Type</th>
                                            <th style="width: 140px;">Record Status</th>
                                            <th style="width: 240px;">Scanned At (PHT)</th>
                                            <th style="width: 180px;">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($checkinOnlyRows as $index => $checkin)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ optional($checkin->employee?->user)->full_name ?? '—' }}</td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <i class="mdi mdi-login me-1"></i> Check-in
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($checkin->voided_at)
                                                        <span class="badge bg-danger">Voided</span>
                                                        <div class="small text-muted mt-1">
                                                            {{ $checkin->voidedByUser?->full_name ?? $checkin->voidedByUser?->email ?? 'System' }}
                                                        </div>
                                                    @else
                                                        <span class="badge bg-primary">Active</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $checkin->scan_time->timezone('Asia/Manila')->format('M d, Y h:iA') }}
                                                    @if($checkin->voided_at && $checkin->void_reason)
                                                        <div class="small text-danger mt-1">
                                                            Reason: {{ $checkin->void_reason }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{-- VOID FLOW: admin can void incorrect scan records with required reason. --}}
                                                    @if(!$checkin->voided_at)
                                                        <form method="POST"
                                                              action="{{ route('admin.trips.checkins.void', [$trip, $checkin]) }}"
                                                              onsubmit="const r=prompt('Void reason (required):'); if(!r){return false;} this.querySelector('input[name=reason]').value=r.trim(); if(!this.querySelector('input[name=reason]').value){return false;} return confirm('Confirm void this scan record?');">
                                                            @csrf
                                                            <input type="hidden" name="reason" value="">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="mdi mdi-close-circle-outline me-1"></i>Void
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="text-muted small">No action</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <div class="tab-pane fade" id="checkins-out-pane" role="tabpanel" aria-labelledby="checkins-out-tab">
                            @if($checkoutOnlyRows->isEmpty())
                                <div class="alert alert-light border d-flex align-items-center mb-0">
                                    <i class="mdi mdi-information-outline text-muted me-2"></i>
                                    <span class="text-muted">No check-out records for this trip.</span>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table id="checkins-out-table"
                                           class="table table-bordered table-striped table-hover table-sm dt-responsive mb-0"
                                           style="width:100%">
                                        <thead class="table-light">
                                        <tr>
                                            <th style="width: 60px;">#</th>
                                            <th>Employee</th>
                                            <th style="width: 140px;">Type</th>
                                            <th style="width: 140px;">Record Status</th>
                                            <th style="width: 240px;">Scanned At (PHT)</th>
                                            <th style="width: 180px;">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($checkoutOnlyRows as $index => $checkin)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ optional($checkin->employee?->user)->full_name ?? '—' }}</td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <i class="mdi mdi-logout me-1"></i> Check-out
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($checkin->voided_at)
                                                        <span class="badge bg-danger">Voided</span>
                                                        <div class="small text-muted mt-1">
                                                            {{ $checkin->voidedByUser?->full_name ?? $checkin->voidedByUser?->email ?? 'System' }}
                                                        </div>
                                                    @else
                                                        <span class="badge bg-primary">Active</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $checkin->scan_time->timezone('Asia/Manila')->format('M d, Y h:iA') }}
                                                    @if($checkin->voided_at && $checkin->void_reason)
                                                        <div class="small text-danger mt-1">
                                                            Reason: {{ $checkin->void_reason }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{-- VOID FLOW: admin can void incorrect scan records with required reason. --}}
                                                    @if(!$checkin->voided_at)
                                                        <form method="POST"
                                                              action="{{ route('admin.trips.checkins.void', [$trip, $checkin]) }}"
                                                              onsubmit="const r=prompt('Void reason (required):'); if(!r){return false;} this.querySelector('input[name=reason]').value=r.trim(); if(!this.querySelector('input[name=reason]').value){return false;} return confirm('Confirm void this scan record?');">
                                                            @csrf
                                                            <input type="hidden" name="reason" value="">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="mdi mdi-close-circle-outline me-1"></i>Void
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="text-muted small">No action</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endif

            {{-- GPS TAB --}}
            @if($tab === 'gps')
                @php
                    $gpsPoints = $trip->locations()
                        ->orderBy('tracked_at')
                        ->get(['latitude as lat', 'longitude as lng', 'tracked_at']);
                    $firstPoint = $gpsPoints->first();
                    $lastPoint  = $gpsPoints->last();
                @endphp

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h5 class="card-title mb-1">GPS Playback</h5>
                        <p class="card-subtitle text-muted mb-0">
                            Replay the bus movement for this trip for auditing and reporting.
                        </p>
                    </div>
                </div>

                {{-- Trip date / time window --}}
                @if($firstPoint && $lastPoint)
                    <div class="mb-3 small">
                        <span class="text-muted">Trip Date:</span>
                        <span class="fw-semibold">
                            {{ $firstPoint->tracked_at->timezone(config('app.timezone'))->format('M d, Y') }}
                        </span>
                        <span class="mx-2 text-muted">|</span>
                        <span class="text-muted">Window:</span>
                        <span class="fw-semibold">
                            {{ $firstPoint->tracked_at->timezone(config('app.timezone'))->format('H:i') }}
                        </span>
                        <span class="text-muted">to</span>
                        <span class="fw-semibold">
                            {{ $lastPoint->tracked_at->timezone(config('app.timezone'))->format('H:i') }}
                        </span>
                    </div>
                @endif

                @if($gpsPoints->isEmpty())
                    <div class="alert alert-light border d-flex align-items-center mb-0">
                        <i class="mdi mdi-map-marker-off text-muted me-2"></i>
                        <span class="text-muted">No GPS data has been captured for this trip.</span>
                    </div>
                @else
                    {{-- Controls --}}
                    <div class="mb-3 d-flex flex-column flex-md-row align-items-md-center gap-2">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Playback controls">
                            <button id="gps-play"  class="btn btn-primary" title="Play">
                                <i class="mdi mdi-play"></i>
                            </button>
                            <button id="gps-pause" class="btn btn-outline-secondary" title="Pause">
                                <i class="mdi mdi-pause"></i>
                            </button>
                            <button id="gps-reset" class="btn btn-outline-secondary" title="Reset">
                                <i class="mdi mdi-reload"></i>
                            </button>
                            <button id="gps-step-back" class="btn btn-outline-secondary" title="Step backward">
                                <i class="mdi mdi-skip-previous"></i>
                            </button>
                            <button id="gps-step-forward" class="btn btn-outline-secondary" title="Step forward">
                                <i class="mdi mdi-skip-next"></i>
                            </button>
                        </div>

                        <div class="ms-md-3 small text-muted d-flex flex-column flex-lg-row gap-2">
                            <div>
                                <span class="fw-semibold">Current Position:</span>
                                <span id="gps-current-time">—</span>
                            </div>
                            <div class="ms-lg-3">
                                <span class="fw-semibold">From:</span>
                                <span id="gps-start-time">—</span>
                                <span class="mx-1">to</span>
                                <span id="gps-end-time">—</span>
                            </div>
                            <div class="ms-lg-3">
                                <span class="fw-semibold">Total Duration:</span>
                                <span id="gps-duration">—</span>
                            </div>
                        </div>
                    </div>

                    {{-- Timeline slider --}}
                    <div class="mb-3">
                        <input
                            type="range"
                            id="gps-slider"
                            class="form-range"
                            min="0"
                            max="{{ max($gpsPoints->count() - 1, 0) }}"
                            step="1"
                            value="0"
                        >
                        <div class="d-flex justify-content-between small text-muted">
                            <span id="gps-slider-start-label">Start</span>
                            <span>
                                <span class="fw-semibold">At:</span>
                                <span id="gps-slider-time">—</span>
                            </span>
                            <span id="gps-slider-end-label">End</span>
                        </div>
                    </div>

                    <div id="gps-map"
                         class="border rounded bg-light"
                         style="height: 420px;"></div>

                    {{-- expose GPS data to JS --}}
                    <script>
                        window.TRIP_GPS_POINTS = @json($gpsPoints);
                    </script>
                @endif
            @endif

        </div>
    </div>

    @if($trip->status === 'ongoing')
        <div class="modal fade" id="incidentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form method="POST" action="{{ route('admin.trips.incident', $trip) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Report Trip Incident</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Incident Type <span class="text-danger">*</span></label>
                                <select name="incident_type" class="form-select" required>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="breakdown">Breakdown</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Replacement Bus (Optional)</label>
                                <select name="replacement_bus_id" class="form-select">
                                    <option value="">No Replacement</option>
                                    @foreach($replacementBuses as $bus)
                                        <option value="{{ $bus->id }}">
                                            {{ $bus->plate_number }} (Cap: {{ $bus->capacity ?? 'N/A' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Reason / Notes</label>
                                <input type="text" name="reason" class="form-control" maxlength="255" placeholder="Required reason" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Employees To Transfer</label>
                                <small class="d-block text-muted mb-2">Leave all unchecked to auto-transfer all currently onboard employees.</small>
                                <div class="border rounded p-2" style="max-height: 220px; overflow: auto;">
                                    @forelse($onboardEmployees as $employee)
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" id="emp-{{ $employee->id }}">
                                            <label class="form-check-label" for="emp-{{ $employee->id }}">
                                                {{ $employee->user?->full_name ?? ('Employee #'.$employee->id) }}
                                            </label>
                                        </div>
                                    @empty
                                        <div class="text-muted small">No onboard employees detected.</div>
                                    @endforelse
                                </div>
                            </div>

                            @if(auth()->user()?->can('update_trips') || auth()->user()?->hasRole('super_admin'))
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="override_capacity" value="1" id="override-capacity">
                                        <label class="form-check-label" for="override-capacity">
                                            Override capacity limit (requires trip update permission)
                                        </label>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="mdi mdi-alert"></i> Submit Incident
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection

@section('scripts')
    <script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>

    {{-- Leaflet JS --}}
    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""
    ></script>

    <script>
        $(function () {
            // Datatables for check-ins tab
            @if($tab === 'checkins')
                if ($('#expected-checkins-table').length) {
                    $('#expected-checkins-table').DataTable({
                        responsive: true,
                        pageLength: 10,
                        order: [[2, 'asc'], [1, 'asc']]
                    });
                }

                if ($('#checkins-in-table').length) {
                    $('#checkins-in-table').DataTable({
                        responsive: true,
                        pageLength: 10,
                        order: [[4, 'desc']],
                        columnDefs: [
                            { targets: [0, 5], orderable: false, searchable: false }
                        ]
                    });
                }

                if ($('#checkins-out-table').length) {
                    $('#checkins-out-table').DataTable({
                        responsive: true,
                        pageLength: 10,
                        order: [[4, 'desc']],
                        columnDefs: [
                            { targets: [0, 5], orderable: false, searchable: false }
                        ]
                    });
                }
            @endif

            // GPS playback initialization
            @if($tab === 'gps')
                if (window.TRIP_GPS_POINTS && window.TRIP_GPS_POINTS.length) {
                    initGpsPlayback(window.TRIP_GPS_POINTS);
                }
            @endif
        });

        /**
         * Initialize Leaflet map and video-style playback.
         *
         * @param {Array} points - [{ lat, lng, tracked_at }, ...]
         */
        function initGpsPlayback(points) {
            if (!points || !points.length) return;

            // Normalize data
            const latLngs    = points.map(p => [parseFloat(p.lat), parseFloat(p.lng)]);
            const timestamps = points.map(p => p.tracked_at);

            // DOM references
            const $currentTime = $('#gps-current-time');
            const $startTime   = $('#gps-start-time');
            const $endTime     = $('#gps-end-time');
            const $duration    = $('#gps-duration');

            const $slider      = $('#gps-slider');
            const $sliderTime  = $('#gps-slider-time');
            const $sliderStart = $('#gps-slider-start-label');
            const $sliderEnd   = $('#gps-slider-end-label');

            const $btnPlay     = $('#gps-play');
            const $btnPause    = $('#gps-pause');
            const $btnReset    = $('#gps-reset');
            const $btnStepBack = $('#gps-step-back');
            const $btnStepFwd  = $('#gps-step-forward');

            // Time window
            const startDate = new Date(timestamps[0]);
            const endDate   = new Date(timestamps[timestamps.length - 1]);
            const totalMs   = endDate - startDate;

            if (!isNaN(startDate.getTime())) {
                $startTime.text(formatTimestamp(startDate));
                $sliderStart.text(startDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }));
            }
            if (!isNaN(endDate.getTime())) {
                $endTime.text(formatTimestamp(endDate));
                $sliderEnd.text(endDate.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }));
            }
            if (!isNaN(totalMs) && totalMs > 0) {
                $duration.text(formatDuration(totalMs));
            }

            // Slider range
            $slider.attr('min', 0);
            $slider.attr('max', latLngs.length - 1);
            $slider.val(0);

            // Leaflet map setup
            const map = L.map('gps-map');
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const bounds = L.latLngBounds(latLngs);
            map.fitBounds(bounds, { padding: [30, 30] });

            L.polyline(latLngs, {
                color: '#007bff',
                weight: 4
            }).addTo(map);

            const startLatLng = latLngs[0];
            const endLatLng   = latLngs[latLngs.length - 1];

            L.circleMarker(startLatLng, {
                radius: 8,
                color: 'green',
                fillColor: 'green',
                fillOpacity: 0.9
            })
            .addTo(map)
            .bindPopup('Start: ' + formatTimestamp(startDate));

            L.circleMarker(endLatLng, {
                radius: 8,
                color: 'red',
                fillColor: 'red',
                fillOpacity: 0.9
            })
            .addTo(map)
            .bindPopup('End: ' + formatTimestamp(endDate));

            const movingMarker = L.circleMarker(startLatLng, {
                radius: 9,
                color: '#0056b3',
                fillColor: '#0056b3',
                fillOpacity: 1,
                weight: 2
            }).addTo(map);

            // Playback state
            let currentIndex  = 0;
            let playbackTimer = null;
            const playbackMs  = 250;  // interval per step
            const stepSize    = 10;   // number of points to jump on step

            updateUIForIndex(0);

            function updateUIForIndex(idx) {
                currentIndex = idx;

                const pos = latLngs[currentIndex];
                movingMarker.setLatLng(pos);
                map.panTo(pos, { animate: true });

                const t = new Date(timestamps[currentIndex]);
                if (!isNaN(t.getTime())) {
                    $currentTime.text(formatTimestamp(t));
                    $sliderTime.text(
                        t.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
                    );
                }

                $slider.val(currentIndex);
            }

            function stepForward() {
                if (currentIndex >= latLngs.length - 1) {
                    stopPlayback();
                    return;
                }
                updateUIForIndex(currentIndex + 1);
            }

            function startPlayback() {
                if (playbackTimer) return;
                playbackTimer = setInterval(stepForward, playbackMs);
            }

            function stopPlayback() {
                if (playbackTimer) {
                    clearInterval(playbackTimer);
                    playbackTimer = null;
                }
            }

            function resetPlayback() {
                stopPlayback();
                updateUIForIndex(0);
                map.fitBounds(bounds, { padding: [30, 30] });
            }

            function stepBackwardBy(n) {
                stopPlayback();
                const target = Math.max(currentIndex - n, 0);
                updateUIForIndex(target);
            }

            function stepForwardBy(n) {
                stopPlayback();
                const target = Math.min(currentIndex + n, latLngs.length - 1);
                updateUIForIndex(target);
            }

            // Events
            $btnPlay.on('click', startPlayback);
            $btnPause.on('click', stopPlayback);
            $btnReset.on('click', resetPlayback);

            $btnStepBack.on('click', function () {
                stepBackwardBy(stepSize);
            });

            $btnStepFwd.on('click', function () {
                stepForwardBy(stepSize);
            });

            $slider.on('input change', function () {
                const idx = parseInt($(this).val(), 10) || 0;
                stopPlayback();
                updateUIForIndex(idx);
            });
        }

        /**
         * Format a Date into a readable timestamp.
         */
        function formatTimestamp(dateOrString) {
            const d = dateOrString instanceof Date ? dateOrString : new Date(dateOrString);
            if (isNaN(d.getTime())) return '';

            const options = {
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            };

            return d.toLocaleString('en-US', options);
        }

        /**
         * Format duration in ms to "HH:MM hrs" or "MM min".
         */
        function formatDuration(ms) {
            const totalSeconds = Math.floor(ms / 1000);
            const hours   = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);

            if (hours > 0) {
                return `${hours.toString().padStart(2, '0')}:${minutes
                    .toString()
                    .padStart(2, '0')} hrs`;
            }

            return `${minutes} min`;
        }
    </script>
@endsection
