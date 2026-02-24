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
@endsection

@section('page-title', 'Trip Details')

@section('body')
    <body data-sidebar="colored">
@endsection

@section('content')
@php
    $tab = request()->query('tab', 'timeline');
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
            <a href="{{ route('admin.trips.today') }}" class="btn btn-outline-secondary btn-sm">
                <i class="mdi mdi-arrow-left"></i> Back to Today’s Trips
            </a>
        </div>
    </div>

    {{-- TRIP SUMMARY HEADER --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body">
            <div class="row gy-3 align-items-center">
                <div class="col-md-4">
                    <h6 class="text-uppercase text-muted mb-1">Trip Reference</h6>
                    <div class="d-flex align-items-center">
                        <div class="me-2 text-primary">
                            <i class="mdi mdi-bus font-size-24"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">
                                {{ optional($trip->assignment->bus)->plate_number ?? 'Unassigned Bus' }}
                            </div>
                            <small class="text-muted">
                                {{ optional($trip->assignment->route)->name ?? 'No route linked' }}
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 border-start-md">
                    <h6 class="text-uppercase text-muted mb-1">Driver</h6>
                    <div class="d-flex align-items-center">
                        <div class="me-2 text-success">
                            <i class="mdi mdi-account-tie font-size-24"></i>
                        </div>
                        <div>
                            <div class="fw-semibold">
                                {{ optional($trip->assignment->driver?->user)->full_name ?? 'Unassigned Driver' }}
                            </div>
                            <small class="text-muted">
                                {{ $trip->scheduled_start_time?->format('M d, Y') ?? '—' }}
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 border-start-md">
                    <h6 class="text-uppercase text-muted mb-1">Status & Schedule</h6>
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-1">
                        <div>
                            <span class="badge bg-success text-uppercase px-3">
                                {{ ucfirst($trip->status) }}
                            </span>
                        </div>
                        <div class="small text-muted">
                            <div>
                                <span class="fw-semibold">Scheduled:</span>
                                {{ $trip->scheduled_start_time?->format('M d, Y H:i') ?? '—' }}
                            </div>
                            <div>
                                <span class="fw-semibold">Actual:</span>
                                {{ $trip->actual_start_time?->format('M d, Y H:i') ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="card-title mb-1">Trip Timeline</h5>
                        <p class="card-subtitle text-muted mb-0">
                            High-level view of the planned and actual execution of this trip.
                        </p>
                    </div>
                </div>

                <table class="table table-sm align-middle mb-0">
                    <tbody>
                    <tr>
                        <th scope="row" class="text-muted" style="width: 220px;">Scheduled Start</th>
                        <td>{{ $trip->scheduled_start_time?->format('M d, Y H:i') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-muted">Actual Start</th>
                        <td>{{ $trip->actual_start_time?->format('M d, Y H:i') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th scope="row" class="text-muted">Status</th>
                        <td>
                            <span class="badge bg-success px-3">
                                {{ ucfirst($trip->status) }}
                            </span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            @endif

            {{-- CHECK-INS TAB --}}
            @if($tab === 'checkins')
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="card-title mb-1">Employee Check-ins</h5>
                        <p class="card-subtitle text-muted mb-0">
                            Scan history of employees boarding and alighting this trip.
                        </p>
                    </div>
                </div>

                @if($trip->checkins->isEmpty())
                    <div class="alert alert-light border d-flex align-items-center mb-0">
                        <i class="mdi mdi-information-outline text-muted me-2"></i>
                        <span class="text-muted">No check-in data has been recorded for this trip.</span>
                    </div>
                @else
                    <div class="table-responsive">
                        <table id="checkins-table"
                               class="table table-bordered table-striped table-hover table-sm dt-responsive nowrap mb-0"
                               style="width:100%">
                            <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Type</th>
                                <th style="width: 220px;">Scanned At</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($trip->checkins as $checkin)
                                <tr>
                                    <td>
                                        {{ optional($checkin->employee?->user)->full_name ?? '—' }}
                                    </td>
                                    <td>
                                        @if($checkin->scan_type === 'checkin')
                                            <span class="badge bg-success">
                                                <i class="mdi mdi-login me-1"></i> Check-in
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                <i class="mdi mdi-logout me-1"></i> Check-out
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $checkin->scan_time
                                            ->timezone(config('app.timezone'))
                                            ->format('M d, Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
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
                $('#checkins-table').DataTable({
                    responsive: true,
                    pageLength: 10,
                    order: [[2, 'asc']]
                });
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
