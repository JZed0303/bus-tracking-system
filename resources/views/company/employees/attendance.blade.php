@extends('layouts.master')

@section('title')
Employee Attendance
@endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
@endsection

@section('page-title')
Attendance History
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <!-- Page Actions / Header -->
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h4 class="mb-0">{{ $employee->user->full_name }}</h4>
            <small class="text-muted">
                {{ $employee->company->name }} • {{ $employee->employee_code }}
            </small>
        </div>
        <div class="col text-end">
            <a href="{{ route('admin.employees.show', $employee->id) }}"
               class="btn btn-secondary btn-sm">
                Back to Profile
            </a>
        </div>
    </div>

    <!-- FILTERS -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">From Date</label>
                    <input type="date"
                           name="from"
                           value="{{ request('from') }}"
                           class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">To Date</label>
                    <input type="date"
                           name="to"
                           value="{{ request('to') }}"
                           class="form-control">
                </div>

                <div class="col-md-4 d-flex align-items-end justify-content-end">
                    <button class="btn btn-primary me-2">Apply Filters</button>
                    <a href="{{ route('admin.employees.attendance', $employee->id) }}"
                       class="btn btn-light">Reset</a>
                </div>

            </form>
        </div>
    </div>

    <!-- ATTENDANCE TABLE -->
    <div class="card">
        <div class="card-body">

            <h4 class="card-title mb-2">Attendance Records</h4>
            <p class="card-title-desc">
                View check-in and check-out history including route, trip, and driver details.
            </p>

            <table id="attendance-table"
                   class="table table-bordered table-striped dt-responsive nowrap"
                   style="width:100%">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Route</th>
                        <th>Trip</th>
                        <th>Driver</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($checkins as $log)
                        <tr>
                            <td>{{ $log->scan_time->format('M d, Y') }}</td>
                            <td>{{ $log->scan_time->format('H:i') }}</td>
                            <td>
                                <span class="badge bg-{{ $log->scan_type === 'checkin' ? 'success' : 'info' }}">
                                    {{ ucfirst($log->scan_type) }}
                                </span>
                            </td>
                            <td>
                                {{ optional($log->trip->assignment->route)->name ?? '—' }}
                            </td>
                            <td>
                                {{ ucfirst($log->trip->direction) }}
                            </td>
                            <td>
                                {{ optional($log->trip->assignment->driver->user)->full_name ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No attendance records found
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>

            <div class="mt-3">
                {{ $checkins->withQueryString()->links() }}
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
    $('#attendance-table').DataTable({
        responsive: true,
        paging: false,
        ordering: false,
        info: false
    });
});
</script>
@endsection
