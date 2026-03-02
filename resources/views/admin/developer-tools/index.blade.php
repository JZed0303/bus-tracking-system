@extends('layouts.master')

@section('title', 'Developer Tools')
@section('page-title', 'Developer Tools')

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-danger mb-0">
                <strong>Danger Zone:</strong> These actions are destructive and intended for developer reset workflows only.
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-success mb-0">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-danger mb-0">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="mb-1">Assignments</h6>
                    <p class="text-muted mb-3">Current rows: <strong>{{ number_format($assignmentCount) }}</strong></p>
                    <form method="POST" action="{{ route('admin.developer-tools.run') }}" onsubmit="return confirmReset(this);">
                        @csrf
                        <input type="hidden" name="action" value="truncate_assignments">
                        <input type="hidden" name="confirm_text" value="RESET">
                        <button type="submit" class="btn btn-danger btn-sm w-100">Truncate Assignments</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="mb-1">Trips</h6>
                    <p class="text-muted mb-3">Current rows: <strong>{{ number_format($tripCount) }}</strong></p>
                    <form method="POST" action="{{ route('admin.developer-tools.run') }}" onsubmit="return confirmReset(this);">
                        @csrf
                        <input type="hidden" name="action" value="truncate_trips">
                        <input type="hidden" name="confirm_text" value="RESET">
                        <button type="submit" class="btn btn-danger btn-sm w-100">Truncate Trips</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="mb-1">Checkins</h6>
                    <p class="text-muted mb-3">Current rows: <strong>{{ number_format($checkinCount) }}</strong></p>
                    <form method="POST" action="{{ route('admin.developer-tools.run') }}" onsubmit="return confirmReset(this);">
                        @csrf
                        <input type="hidden" name="action" value="truncate_checkins">
                        <input type="hidden" name="confirm_text" value="RESET">
                        <button type="submit" class="btn btn-danger btn-sm w-100">Truncate Checkins</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="mb-3">Delete Checkins of a Trip</h6>
                    <form method="POST" action="{{ route('admin.developer-tools.run') }}" class="row g-2" onsubmit="return confirmReset(this);">
                        @csrf
                        <input type="hidden" name="action" value="delete_trip_checkins">
                        <input type="hidden" name="confirm_text" value="RESET">
                        <div class="col-md-8">
                            <input type="number" name="trip_id" class="form-control" min="1" placeholder="Trip ID (e.g., 41)" required>
                        </div>
                        <div class="col-md-4 d-grid">
                            <button type="submit" class="btn btn-warning">Delete Trip Checkins</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="mb-3">Delete a Trip (with related records)</h6>
                    <form method="POST" action="{{ route('admin.developer-tools.run') }}" class="row g-2" onsubmit="return confirmReset(this);">
                        @csrf
                        <input type="hidden" name="action" value="delete_trip">
                        <input type="hidden" name="confirm_text" value="RESET">
                        <div class="col-md-8">
                            <input type="number" name="trip_id" class="form-control" min="1" placeholder="Trip ID (e.g., 41)" required>
                        </div>
                        <div class="col-md-4 d-grid">
                            <button type="submit" class="btn btn-danger">Delete Trip</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Assignments (Latest 50)</h6></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Bus</th>
                                <th>Driver</th>
                                <th>Route</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->id }}</td>
                                    <td>{{ $assignment->bus?->plate_number ?? '—' }}</td>
                                    <td>{{ $assignment->driver?->user?->full_name ?? '—' }}</td>
                                    <td>{{ $assignment->route?->name ?? '—' }}</td>
                                    <td>{{ $assignment->status }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center">No assignment records.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Trips (Latest 50)</h6></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Bus</th>
                                <th>Route</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trips as $trip)
                                <tr>
                                    <td>{{ $trip->id }}</td>
                                    <td>{{ $trip->trip_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td>{{ $trip->assignment?->bus?->plate_number ?? '—' }}</td>
                                    <td>{{ $trip->assignment?->route?->name ?? '—' }}</td>
                                    <td>{{ $trip->status }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center">No trip records.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">Checkins of a Trip</h6>
                        <small class="text-muted">Top checkin count is global. Use a valid Trip ID here to load the table below.</small>
                    </div>
                    <form method="GET" action="{{ route('admin.developer-tools.index') }}" class="d-flex gap-2">
                        <input type="number" name="trip_id" value="{{ $tripId ?? '' }}" min="1" class="form-control form-control-sm" placeholder="Trip ID">
                        <button type="submit" class="btn btn-sm btn-primary">Load</button>
                    </form>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Trip ID</th>
                                <th>Employee</th>
                                <th>Scan Type</th>
                                <th>Scan Time</th>
                                <th>Voided</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($checkins as $checkin)
                                <tr>
                                    <td>{{ $checkin->id }}</td>
                                    <td>{{ $checkin->trip_id }}</td>
                                    <td>{{ $checkin->employee?->user?->full_name ?? $checkin->employee_id }}</td>
                                    <td>{{ $checkin->scan_type }}</td>
                                    <td>{{ $checkin->scan_time?->format('Y-m-d H:i:s') ?? '—' }}</td>
                                    <td>{{ $checkin->voided_at ? 'Yes' : 'No' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted text-center">No checkin records loaded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
function confirmReset(form) {
    const typed = prompt('Type RESET to confirm this destructive action.');
    if (typed !== 'RESET') {
        return false;
    }
    return confirm('Are you sure you want to continue?');
}
</script>
@endsection
