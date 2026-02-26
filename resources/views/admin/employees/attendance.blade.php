@extends('layouts.master')

@section('title', 'Employee Attendance')
@section('page-title', 'Attendance History')

@section('css')
<style>
    .attendance-shell {
        --soft-border: #e9edf4;
        --soft-bg: #f8fafc;
    }

    .card-soft {
        border: 1px solid var(--soft-border);
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
    }

    .kpi-card {
        border: 1px solid var(--soft-border);
        border-radius: 12px;
        padding: .9rem 1rem;
        background: linear-gradient(180deg, #fff, #fbfdff);
        height: 100%;
    }

    .kpi-label {
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #6c757d;
        margin-bottom: .3rem;
    }

    .kpi-value {
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: 0;
        line-height: 1.1;
    }

    .attendance-table thead th {
        background: #f8fafc;
        border-bottom: 1px solid var(--soft-border);
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #6c757d;
        white-space: nowrap;
    }

    .attendance-table td {
        vertical-align: middle;
    }

    .status-pill {
        border-radius: 999px;
        padding: .26rem .65rem;
        font-size: .73rem;
        font-weight: 700;
        letter-spacing: .02em;
    }

    .status-in {
        background: #e8f8ef;
        color: #198754;
        border: 1px solid #c7ecd8;
    }

    .status-out {
        background: #e7f1ff;
        color: #0d6efd;
        border: 1px solid #cfe2ff;
    }
</style>
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
@php
    $rows = $checkins->getCollection();
    $totalLogs = $rows->count();
    $totalIn = $rows->where('scan_type', 'checkin')->count();
    $totalOut = $rows->where('scan_type', 'checkout')->count();
    $latest = $rows->first();
@endphp

<div class="container-fluid attendance-shell">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h4 class="mb-1">{{ $employee->user->full_name }}</h4>
            <p class="text-muted mb-0">{{ $employee->company->name }} | {{ $employee->employee_code }}</p>
        </div>
        <div class="col text-end">
            <a href="{{ route('admin.employees.show', $employee->id) }}" class="btn btn-secondary btn-sm">
                Back to Profile
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card">
                <div class="kpi-label">Attendance Logs</div>
                <p class="kpi-value">{{ $totalLogs }}</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card">
                <div class="kpi-label">Check-ins</div>
                <p class="kpi-value text-success">{{ $totalIn }}</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card">
                <div class="kpi-label">Check-outs</div>
                <p class="kpi-value text-primary">{{ $totalOut }}</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card">
                <div class="kpi-label">Latest Attendance</div>
                <p class="kpi-value" style="font-size:1rem;">
                    {{ $latest?->scan_time ? $latest->scan_time->timezone('Asia/Manila')->format('M d, Y h:i A') : 'N/A' }}
                </p>
            </div>
        </div>
    </div>

    <div class="card card-soft mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                </div>
                <div class="col-md-4 d-flex align-items-end justify-content-end">
                    <button class="btn btn-primary me-2">Apply Filters</button>
                    <a href="{{ route('admin.employees.attendance', $employee->id) }}" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-soft">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1"> Attendance Journal</h5>
                    <p class="text-muted mb-0">Daily attendance logs with exact in/out times.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover attendance-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time (PH)</th>
                            <th>Attendance</th>
                            <th>Route</th>
                            <th>Trip Direction</th>
                            <th>Driver</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($checkins as $log)
                            @php
                                $scanAt = $log->scan_time?->timezone('Asia/Manila');
                                $isIn = $log->scan_type === 'checkin';
                            @endphp
                            <tr>
                                <td>{{ $scanAt?->format('M d, Y') ?? '—' }}</td>
                                <td class="fw-semibold">{{ $scanAt?->format('h:i A') ?? '—' }}</td>
                                <td>
                                    <span class="status-pill {{ $isIn ? 'status-in' : 'status-out' }}">
                                        {{ $isIn ? 'IN' : 'OUT' }}
                                    </span>
                                </td>
                                <td>{{ optional($log->trip->assignment->route)->name ?? '—' }}</td>
                                <td>{{ ucfirst($log->trip->direction ?? '—') }}</td>
                                <td>{{ optional($log->trip->assignment->driver->user)->full_name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No attendance records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $checkins->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
