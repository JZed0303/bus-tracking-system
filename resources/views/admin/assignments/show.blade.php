@extends('layouts.master')

@section('title', 'Assignment Details')

@section('page-title', 'Assignment Details')

@section('body')
    <body data-sidebar="colored">
@endsection

@section('css')
    <link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />

    <style>
        .assignment-hero {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 8px 22px rgba(20, 33, 61, 0.08);
        }
        .assignment-kpi {
            border: 1px solid #e9edf4;
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #fafcff 100%);
            padding: 14px;
            height: 100%;
        }
        .assignment-kpi-compact {
            height: auto;
        }
        .assignment-kpi-label {
            font-size: 11px;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #7c8799;
            margin-bottom: 4px;
        }
        .assignment-kpi-value {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2a37;
            margin-bottom: 0;
        }
        .assignment-table th {
            width: 220px;
            background: #f8fafc;
            color: #5f6c80;
            font-weight: 600;
        }
        .assignment-bus-photo {
            width: 100%;
            max-width: 420px;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #e9edf4;
            background: #f8fafc;
            display: block;
            margin: 0 auto;
        }
        .assignment-driver-photo {
               width: 194px;
    height: 196px;
            object-fit: cover;
            border-radius: 50%;
            border: 1px solid #e9edf4;
            background: #f8fafc;
            display: block;
            margin: 0 auto;
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
                <div class="col-lg-5">
                    <div class="assignment-kpi assignment-kpi-compact">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <p class="assignment-kpi-label">Assignment Reference</p>
                                <h5 class="mb-1">#{{ $assignment->id }}</h5>
                            </div>
                            <span class="badge bg-{{ $statusColor }} px-3 py-2 text-uppercase">
                                {{ strtoupper($assignment->status) }}
                            </span>
                        </div>

                        <hr class="my-3">
                        <h6 class="mb-2">Core Assignment Information</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0 assignment-table">
                                <tbody>
                                <tr>
                                    <th>Company</th>
                                    <td>{{ optional($assignment->company)->name ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <th>Lifecycle State</th>
                                    <td><span class="badge bg-{{ $lifecycleColor }}">{{ $lifecycle }}</span></td>
                                </tr>
                                <tr>
                                    <th>Assignment Leg</th>
                                    <td>
                                        @php($leg = $assignment->leg ?? 'both')
                                        <span class="badge bg-{{ $leg === 'both' ? 'dark' : ($leg === 'pickup' ? 'info' : 'primary') }}">
                                            {{ strtoupper($leg) }}
                                        </span>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="assignment-kpi mt-3">
                        <p class="assignment-kpi-label">Schedule and Audit</p>
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

                <div class="col-lg-7">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="assignment-kpi">
                                <p class="assignment-kpi-label">Driver</p>
                                <div class="mb-2">
                                    <img
                                        src="{{ optional($assignment->driver)->photo_url ?? asset('build/images/user-placeholder.png') }}"
                                        alt="Driver Photo"
                                        class="assignment-driver-photo"
                                    >
                                </div>
                                <p class="assignment-kpi-value">{{ optional($assignment->driver?->user)->full_name ?? '—' }}</p>
                                <p class="mb-0 text-muted small">Assigned operator</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="assignment-kpi">
                                <p class="assignment-kpi-label">Bus</p>
                                <div class="mb-2">
                                    <img
                                        src="{{ optional($assignment->bus)->photo_url ?? asset('build/images/bus-placeholder.png') }}"
                                        alt="Bus Photo"
                                        class="assignment-bus-photo"
                                    >
                                </div>
                                <p class="assignment-kpi-value">{{ optional($assignment->bus)->plate_number ?? '—' }}</p>
                                <p class="mb-0 text-muted small">{{ optional($assignment->bus)->model ?? 'No model data' }}</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="assignment-kpi">
                                <p class="assignment-kpi-label">Validity Window</p>
                                <p class="assignment-kpi-value">{{ $effectiveFrom?->format('M d, Y') ?? '—' }}</p>
                                <p class="mb-0 text-muted small">to {{ $effectiveTo?->format('M d, Y') ?? 'Ongoing' }}</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="assignment-kpi">
                                <p class="assignment-kpi-label">Route</p>
                                <p class="assignment-kpi-value">{{ optional($assignment->route)->name ?? '—' }}</p>
                                <p class="mb-0 text-muted small">Operational route</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="assignment-kpi mt-3">
                <p class="assignment-kpi-label">Operations Snapshot</p>
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
    </script>
@endsection
