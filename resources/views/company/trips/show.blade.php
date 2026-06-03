@extends('layouts.master')

@section('title')
Trip Details
@endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
@endsection

@section('page-title')
Trip Details
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
@php
    $tab = request()->query('tab', 'timeline');
@endphp

<div class="container-fluid">

    <!-- PAGE CONTEXT -->
    <div class="row mb-3">
        <div class="col">
            <h4 class="mb-0">Trip Details</h4>
            <p class="text-muted mb-0">
                View timeline, employee check-ins, and GPS playback for this trip.
            </p>
        </div>
        <div class="col text-end">
            @if($trip->status === 'ongoing')
                <button type="button" class="btn btn-danger btn-sm me-2" data-bs-toggle="modal" data-bs-target="#incidentModal">
                    <i class="mdi mdi-alert-circle-outline"></i> Report Incident
                </button>
            @endif
            <a href="{{ route('company.trips.report', $trip) }}" class="btn btn-primary btn-sm me-2">
                <i class="mdi mdi-file-chart-outline"></i> Trip Report
            </a>
            <a href="{{ route('company.trips.today') }}" class="btn btn-light btn-sm">
                <i class="mdi mdi-arrow-left"></i> Back to Trips
            </a>
        </div>
    </div>

    <!-- TRIP HEADER -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">

                <!-- Bus -->
                <div class="col-md-4 d-flex align-items-start">
                    <div class="me-2 text-primary">
                        <i class="mdi mdi-bus font-size-24"></i>
                    </div>
                    <div>
                        <strong>Bus</strong>
                        <div class="text-muted">
                            {{ optional($trip->assignment->bus)->plate_number ?? '—' }}
                        </div>
                    </div>
                </div>

                <!-- Driver -->
                <div class="col-md-4 d-flex align-items-start">
                    <div class="me-2 text-success">
                        <i class="mdi mdi-account-tie font-size-24"></i>
                    </div>
                    <div>
                        <strong>Driver</strong>
                        <div class="text-muted">
                            {{ optional($trip->assignment->driver?->user)->full_name ?? '—' }}
                        </div>
                    </div>
                </div>

                <!-- Route -->
                <div class="col-md-4 d-flex align-items-start">
                    <div class="me-2 text-info">
                        <i class="mdi mdi-map-marker-path font-size-24"></i>
                    </div>
                    <div>
                        <strong>Route</strong>
                        <div class="text-muted">
                            {{ optional($trip->assignment->route)->name ?? '—' }}
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

    <!-- TABS -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'timeline' ? 'active' : '' }}"
               href="{{ route('company.trips.show', $trip) }}?tab=timeline">
                <i class="mdi mdi-timeline-outline"></i> Timeline
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ $tab === 'checkins' ? 'active' : '' }}"
               href="{{ route('company.trips.show', $trip) }}?tab=checkins">
                <i class="mdi mdi-account-clock-outline"></i> Employee Check-ins
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ $tab === 'gps' ? 'active' : '' }}"
               href="{{ route('company.trips.show', $trip) }}?tab=gps">
                <i class="mdi mdi-map-marker-path"></i> GPS Playback
            </a>
        </li>
    </ul>

    <!-- TAB CONTENT -->
    <div class="card">
        <div class="card-body">

            {{-- TIMELINE --}}
            @if($tab === 'timeline')
                <h4 class="card-title mb-3">Trip Timeline</h4>

                <table class="table table-bordered table-sm">
                    <tr>
                        <th width="200">Scheduled Start</th>
                        <td>{{ $trip->scheduled_start_time?->format('M d, Y H:i') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Actual Start</th>
                        <td>{{ $trip->actual_start_time?->format('M d, Y H:i') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge bg-success">
                                {{ ucfirst($trip->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            @endif

            {{-- CHECKINS --}}
          @if($tab === 'checkins')
    @php
        $expectedEmployees = collect($expectedEmployees ?? []);
    @endphp
    <h4 class="card-title mb-3">Employee Check-ins</h4>

    @if($expectedEmployees->isNotEmpty())
        <div class="table-responsive mb-4">
            <table id="expected-checkins-table"
                   class="table table-bordered table-striped table-sm dt-responsive nowrap"
                   style="width:100%">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Expected Pickup</th>
                        <th>Pickup Stop</th>
                        <th>Status</th>
                        <th>Latest Activity</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($expectedEmployees as $expected)
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
                        <td>
                            {{ $expected['employee']->user?->full_name ?? '—' }}
                            <div class="small text-muted">{{ $expected['employee']->employee_code ?? '—' }}</div>
                        </td>
                        <td>{{ $expected['expected_pickup_time']?->format('h:iA') ?? '—' }}</td>
                        <td>{{ $expected['pickup_stop'] ?? '—' }}</td>
                        <td><span class="badge bg-{{ $statusBadge }}">{{ $statusLabel }}</span></td>
                        <td>
                            {{ $latestLabel }}
                            <div class="small text-muted">
                                {{ $latestActivity ? $latestActivity->timezone(config('app.timezone'))->format('M d, Y H:i') : '—' }}
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($trip->checkins->isEmpty())
        <p class="text-muted mb-0">No scan records recorded for this trip yet.</p>
    @else
        <table id="checkins-table"
               class="table table-bordered table-striped table-sm dt-responsive nowrap"
               style="width:100%">

            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Record Status</th>
                    <th width="220">Scanned At</th>
                    <th width="160">Action</th>
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
                                <i class="mdi mdi-login"></i> Check-in
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                <i class="mdi mdi-logout"></i> Check-out
                            </span>
                        @endif
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
                        {{ $checkin->scan_time
                            ->timezone(config('app.timezone'))
                            ->format('M d, Y H:i') }}
                        @if($checkin->voided_at && $checkin->void_reason)
                            <div class="small text-danger mt-1">
                                Reason: {{ $checkin->void_reason }}
                            </div>
                        @endif
                    </td>
                    <td>
                        {{-- VOID FLOW: company can void incorrect scan records with required reason. --}}
                        @if(!$checkin->voided_at)
                            <form method="POST"
                                  action="{{ route('company.trips.checkins.void', [$trip, $checkin]) }}"
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
    @endif
@endif


            {{-- GPS --}}
            @if($tab === 'gps')
                <h4 class="card-title mb-3">GPS Playback</h4>

                <div id="gps-map"
                     class="border rounded bg-light d-flex align-items-center justify-content-center text-muted"
                     style="height: 400px;">
                    GPS map will render here
                </div>
            @endif

        </div>
    </div>

    @if($trip->status === 'ongoing')
        <div class="modal fade" id="incidentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form method="POST" action="{{ route('company.trips.incident', $trip) }}" class="modal-content">
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
                                            <input class="form-check-input" type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" id="cmp-emp-{{ $employee->id }}">
                                            <label class="form-check-label" for="cmp-emp-{{ $employee->id }}">
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
                                        <input class="form-check-input" type="checkbox" name="override_capacity" value="1" id="company-override-capacity">
                                        <label class="form-check-label" for="company-override-capacity">
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

<script>
$(function () {
    @if($tab === 'checkins')
        if ($('#expected-checkins-table').length) {
            $('#expected-checkins-table').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[1, 'asc'], [0, 'asc']]
            });
        }

        if ($('#checkins-table').length) {
            $('#checkins-table').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[3, 'desc']],
                columnDefs: [
                    { targets: 4, orderable: false, searchable: false }
                ]
            });
        }
    @endif
});
</script>
@endsection
