@extends('layouts.master')

@section('title')
Employee Transport History
@endsection

@section('page-title')
Transport History
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <!-- HEADER -->
    <div class="row mb-3">
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
                    <label class="form-label">From</label>
                    <input type="date" name="from"
                           value="{{ request('from') }}"
                           class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">To</label>
                    <input type="date" name="to"
                           value="{{ request('to') }}"
                           class="form-control">
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('admin.employees.trips', $employee->id) }}"
                       class="btn btn-secondary">Reset</a>
                </div>

            </form>
        </div>
    </div>

    <!-- TRIPS TABLE -->
    <div class="card">
        <div class="card-body">

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Route</th>
                        <th>Direction</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Driver</th>
                        <th>Bus</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($trips as $row)
                        @php
                            $trip = \App\Models\Trip::find($row->trip_id);
                        @endphp
                        <tr>
                            <td>{{ optional($row->checkin_time)->format('M d, Y') }}</td>
                            <td>{{ optional($trip->assignment->route)->name ?? '—' }}</td>
                            <td>{{ ucfirst($trip->direction) }}</td>
                            <td>{{ optional($row->checkin_time)->format('H:i') ?? '—' }}</td>
                            <td>{{ optional($row->checkout_time)->format('H:i') ?? '—' }}</td>
                            <td>{{ optional($trip->assignment->driver->user)->full_name ?? '—' }}</td>
                            <td>{{ optional($trip->assignment->bus)->plate_number ?? '—' }}</td>
                            <td>
                                @if($row->checkin_time && $row->checkout_time)
                                    <span class="badge bg-success">Complete</span>
                                @else
                                    <span class="badge bg-warning">Incomplete</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                No transport history found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $trips->withQueryString()->links() }}

        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
