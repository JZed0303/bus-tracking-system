@extends('layouts.master')

@section('title', 'Assignment Timeline')
@section('page-title', 'Assignment Timeline')

@section('body')
    <body data-sidebar="colored">
@endsection

@section('css')
    <style>
        .timeline-shell {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 8px 22px rgba(20, 33, 61, 0.08);
        }

        .timeline-kpi {
            border: 1px solid #e9edf4;
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #fafcff 100%);
            padding: 14px;
            height: 100%;
        }

        .timeline-kpi-label {
            font-size: 11px;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #7c8799;
            margin-bottom: 4px;
        }

        .timeline-kpi-value {
            font-size: 1rem;
            font-weight: 700;
            color: #1f2a37;
            margin-bottom: 0;
        }

        .trip-timeline {
            position: relative;
            padding-left: 32px;
        }

        .trip-timeline::before {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: 9px;
            width: 2px;
            background: #dce3ef;
        }

        .trip-timeline-item {
            position: relative;
            margin-bottom: 16px;
        }

        .trip-timeline-item:last-child {
            margin-bottom: 0;
        }

        .trip-timeline-dot {
            position: absolute;
            left: -28px;
            top: 14px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px #dce3ef;
            background: #adb5bd;
        }

        .trip-timeline-dot.is-completed {
            background: #198754;
        }

        .trip-timeline-dot.is-ongoing {
            background: #0d6efd;
        }

        .trip-timeline-dot.is-pending {
            background: #fd7e14;
        }

        .trip-timeline-card {
            border: 1px solid #e9edf4;
            border-radius: 12px;
            padding: 12px;
            background: #fff;
        }

        .timeline-filter-bar {
            border: 1px solid #e9edf4;
            border-radius: 12px;
            background: #f8fafc;
            padding: 12px;
        }

        .timeline-section-title {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #6c757d;
            margin-bottom: .75rem;
        }

        .timeline-divider {
            border-top: 1px solid #e9edf4;
            margin: 1rem 0 1.25rem 0;
        }
    </style>
@endsection

@section('content')
    @php
        $sortedTrips = $assignment->trips
            ->sortByDesc(fn ($trip) => $trip->actual_start_time ?? $trip->scheduled_start_time ?? $trip->trip_date)
            ->values();

        $totalTrips = $sortedTrips->count();
        $completedTrips = $sortedTrips->where('status', 'completed')->count();
        $ongoingTrips = $sortedTrips->where('status', 'ongoing')->count();
        $pendingTrips = max($totalTrips - $completedTrips - $ongoingTrips, 0);
        $latestTrip = $sortedTrips->first();
        $latestTripMoment = $latestTrip?->actual_start_time ?? $latestTrip?->scheduled_start_time ?? $latestTrip?->trip_date;
    @endphp

    <div class="container-fluid">
        <div class="row mb-3 align-items-center">
            <div class="col">
                <h4 class="mb-1">Trip Timeline</h4>
                <p class="text-muted mb-0">Operational history for Assignment #{{ $assignment->id }}.</p>
            </div>
            <div class="col text-end d-flex justify-content-end gap-2">
                <a href="{{ route('admin.assignments.show', $assignment->id) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="mdi mdi-arrow-left me-1"></i> Back to Assignment
                </a>
            </div>
        </div>

        <div class="card timeline-shell">
            <div class="card-body">
                <p class="timeline-section-title">Assignment Summary</p>
                <div class="row g-3 mb-3">
                    <div class="col-xl-3 col-md-6">
                        <div class="timeline-kpi">
                            <p class="timeline-kpi-label">Driver</p>
                            <p class="timeline-kpi-value">{{ optional($assignment->driver?->user)->full_name ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="timeline-kpi">
                            <p class="timeline-kpi-label">Route</p>
                            <p class="timeline-kpi-value">{{ optional($assignment->route)->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="timeline-kpi">
                            <p class="timeline-kpi-label">Bus</p>
                            <p class="timeline-kpi-value">{{ optional($assignment->bus)->plate_number ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="timeline-kpi">
                            <p class="timeline-kpi-label">Latest Trip Event</p>
                            <p class="timeline-kpi-value">{{ $latestTripMoment ? $latestTripMoment->timezone('Asia/Manila')->format('M d, Y h:i A') : 'No trips yet' }}</p>
                        </div>
                    </div>
                </div>

                <hr class="timeline-divider">
                <p class="timeline-section-title">Trip Metrics</p>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="timeline-kpi">
                            <p class="timeline-kpi-label">Total Trips</p>
                            <p class="timeline-kpi-value">{{ $totalTrips }}</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="timeline-kpi">
                            <p class="timeline-kpi-label">Completed</p>
                            <p class="timeline-kpi-value text-success">{{ $completedTrips }}</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="timeline-kpi">
                            <p class="timeline-kpi-label">Ongoing / Pending</p>
                            <p class="timeline-kpi-value text-primary">{{ $ongoingTrips }} / {{ $pendingTrips }}</p>
                        </div>
                    </div>
                </div>

                @if($sortedTrips->isEmpty())
                    <div class="border rounded-3 p-4 text-center text-muted">
                        No trips recorded for this assignment.
                    </div>
                @else
                    <hr class="timeline-divider">
                    <p class="timeline-section-title">Timeline Filters</p>
                    <div class="timeline-filter-bar mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label mb-1">From (Date & Time)</label>
                                <input type="datetime-local" id="timeline-filter-from" class="form-control">
                            </div>
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label mb-1">To (Date & Time)</label>
                                <input type="datetime-local" id="timeline-filter-to" class="form-control">
                            </div>
                            <div class="col-lg-4 col-md-12 d-flex gap-2">
                                <button type="button" id="timeline-filter-apply" class="btn btn-primary">
                                    <i class="mdi mdi-filter-variant me-1"></i> Apply
                                </button>
                                <button type="button" id="timeline-filter-reset" class="btn btn-light">
                                    Reset
                                </button>
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle align-self-center" id="timeline-filter-count">
                                    Showing {{ $totalTrips }} trip{{ $totalTrips === 1 ? '' : 's' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div id="timeline-filter-empty" class="alert alert-light border text-center text-muted d-none mb-3">
                        No trips matched the selected date and time range.
                    </div>

                    <hr class="timeline-divider">
                    <p class="timeline-section-title">Trip Events</p>
                    <div class="trip-timeline">
                        @foreach($sortedTrips as $trip)
                            @php
                                $tripMoment = $trip->actual_start_time ?? $trip->scheduled_start_time ?? $trip->trip_date;
                                $status = strtolower((string) $trip->status);
                                $badgeClass = $status === 'completed' ? 'success' : ($status === 'ongoing' ? 'primary' : 'secondary');
                                $dotClass = $status === 'completed' ? 'is-completed' : ($status === 'ongoing' ? 'is-ongoing' : 'is-pending');
                            @endphp

                            <div class="trip-timeline-item"
                                data-trip-moment="{{ $tripMoment ? $tripMoment->timezone('Asia/Manila')->format('Y-m-d\TH:i') : '' }}">
                                <span class="trip-timeline-dot {{ $dotClass }}"></span>

                                <div class="trip-timeline-card">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                        <div>
                                            <h6 class="mb-1">
                                                {{ $tripMoment ? $tripMoment->timezone('Asia/Manila')->format('M d, Y h:i A') : 'No start time available' }}
                                            </h6>
                                            <p class="text-muted mb-0 small">
                                                Trip #{{ $trip->id }} | {{ $trip->direction_label }}
                                            </p>
                                        </div>
                                        <span class="badge bg-{{ $badgeClass }} px-3 py-2">{{ ucfirst($trip->status ?? 'Unknown') }}</span>
                                    </div>

                                    <hr class="my-2">
                                    <div class="row g-2 mt-2">
                                        <div class="col-md-4">
                                            <div class="small text-muted">Start</div>
                                            <div class="small fw-medium">{{ $trip->actual_start_time ? $trip->actual_start_time->timezone('Asia/Manila')->format('M d, Y h:i A') : 'N/A' }}</div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="small text-muted">End</div>
                                            <div class="small fw-medium">{{ $trip->actual_end_time ? $trip->actual_end_time->timezone('Asia/Manila')->format('M d, Y h:i A') : 'N/A' }}</div>
                                        </div>
                                        <div class="col-md-4 text-md-end">
                                            <a href="{{ route('admin.trips.show', $trip->id) }}" class="btn btn-sm btn-outline-secondary mt-md-3">
                                                View Trip Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        $(function () {
            const $from = $('#timeline-filter-from');
            const $to = $('#timeline-filter-to');
            const $items = $('.trip-timeline-item');
            const $empty = $('#timeline-filter-empty');
            const $count = $('#timeline-filter-count');

            function setCount(visibleCount) {
                $count.text(`Showing ${visibleCount} trip${visibleCount === 1 ? '' : 's'}`);
            }

            function applyTimelineFilter() {
                const fromValue = $from.val();
                const toValue = $to.val();
                const fromTs = fromValue ? new Date(fromValue).getTime() : null;
                const toTs = toValue ? new Date(toValue).getTime() : null;
                let visibleCount = 0;

                $items.each(function () {
                    const momentValue = $(this).data('trip-moment');
                    if (!momentValue) {
                        $(this).addClass('d-none');
                        return;
                    }

                    const tripTs = new Date(momentValue).getTime();
                    if (isNaN(tripTs)) {
                        $(this).addClass('d-none');
                        return;
                    }
                    const matchFrom = fromTs === null || tripTs >= fromTs;
                    const matchTo = toTs === null || tripTs <= toTs;
                    const visible = matchFrom && matchTo;

                    $(this).toggleClass('d-none', !visible);
                    if (visible) {
                        visibleCount++;
                    }
                });

                $empty.toggleClass('d-none', visibleCount !== 0);
                setCount(visibleCount);
            }

            $('#timeline-filter-apply').on('click', applyTimelineFilter);

            $('#timeline-filter-reset').on('click', function () {
                $from.val('');
                $to.val('');
                $items.removeClass('d-none');
                $empty.addClass('d-none');
                setCount($items.length);
            });

            setCount($items.length);
        });
    </script>
@endsection
