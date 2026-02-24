@extends('layouts.master')

@section('title')
    Employee Schedules
@endsection

@section('page-title')
    Employee Schedules
@endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <!-- ACTION BAR -->
    <div class="row mb-3 align-items-center">
        <div class="col-md-6">
            <h4 class="mb-0">Daily Employee Transport Schedule</h4>
            <small class="text-muted">
                Defines who is expected to be transported on a given day
            </small>
        </div>

        <div class="col-md-6 text-end">
            <a href="{{ route('company.schedules.create') }}"
               class="btn btn-primary">
                <i class="mdi mdi-calendar-plus"></i>
                Create Schedule
            </a>
        </div>
    </div>

    <!-- FILTERS -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">

                <div class="col-md-4">
                    <label class="form-label">Schedule Date</label>
                    <input type="date"
                           name="date"
                           class="form-control"
                           value="{{ request('date', now()->toDateString()) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="missed">Missed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="col-md-4 text-end">
                    <button class="btn btn-primary">
                        <i class="mdi mdi-filter"></i> Apply
                    </button>
                    <a href="{{ route('company.schedules.index') }}"
                       class="btn btn-light">
                        Reset
                    </a>
                </div>

            </form>
        </div>
    </div>

    <!-- SCHEDULE TABLE -->
    <div class="card">
        <div class="card-body">

            <table id="schedule-table"
                   class="table table-bordered table-striped dt-responsive nowrap"
                   style="width:100%">

                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Route</th>
                        <th>Bus</th>
                        <th>Shift</th>
                        <th>Pickup</th>
                        <th>Drop-off</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($schedules as $schedule)
                        <tr>
                            <td>
                                <strong>{{ $schedule->employee->user->full_name }}</strong><br>
                                <small class="text-muted">
                                    {{ $schedule->employee->employee_code }}
                                </small>
                            </td>

                            <td>
                                {{ $schedule->employee->department ?? '—' }}
                            </td>

                            <td>
                                {{ $schedule->route->name }}
                            </td>

                            <td>
                                {{ $schedule->bus?->plate_number ?? 'Unassigned' }}
                            </td>

                            <td>
                                {{ $schedule->shift_name ?? '—' }}
                            </td>

                            <td>
                                {{ $schedule->expected_pickup_time ?? '—' }}
                            </td>

                            <td>
                                {{ $schedule->expected_dropoff_time ?? '—' }}
                            </td>

                            <td>
                                @php
                                    $badge = match($schedule->status) {
                                        'completed' => 'success',
                                        'missed'    => 'danger',
                                        'cancelled' => 'secondary',
                                        default     => 'primary'
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge }}">
                                    {{ ucfirst($schedule->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8"
                                class="text-center text-muted py-4">
                                No schedules found for the selected date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>

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
    $('#schedule-table').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'asc']]
    });
});
</script>
@endsection
