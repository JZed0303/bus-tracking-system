@extends('layouts.master')

@section('title')
Reports
@endsection

@section('page-title')
Reports
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h4 class="mb-1">Management Reports</h4>
            <p class="text-muted mb-0">Important operational and admin tables with export and print support.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="{{ route('company.reports.export', array_merge(['type' => 'excel'], request()->query())) }}" class="btn btn-success btn-sm">
                <i class="ri-file-excel-2-line"></i> Export Excel (XLSX)
            </a>
            <a href="{{ route('company.reports.export', array_merge(['type' => 'pdf', 'preview' => 1], request()->query())) }}" target="_blank" class="btn btn-warning btn-sm">
                <i class="ri-eye-line"></i> Preview PDF
            </a>
            <a href="{{ route('company.reports.export', array_merge(['type' => 'pdf'], request()->query())) }}" class="btn btn-danger btn-sm">
                <i class="ri-file-pdf-line"></i> Download PDF
            </a>
            <a href="{{ route('company.reports.print', request()->query()) }}" target="_blank" class="btn btn-primary btn-sm">
                <i class="ri-printer-line"></i> Print
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('company.reports.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Report Type</label>
                    <select name="report_type" class="form-select" required>
                        @foreach($reportTypes as $value => $label)
                            <option value="{{ $value }}" @selected($filters['report_type'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from']->toDateString() }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to']->toDateString() }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Trip</label>
                    <select name="trip_id" class="form-select">
                        <option value="0">Select trip for detailed report</option>
                        @foreach($filterOptions['trips'] as $tripOption)
                            <option value="{{ $tripOption->id }}" @selected($filters['trip_id'] === (int) $tripOption->id)>
                                #{{ $tripOption->id }} | {{ $tripOption->trip_date?->format('M d') }} | {{ $tripOption->assignment?->bus?->plate_number ?? 'Bus' }} | {{ $tripOption->assignment?->route?->name ?? 'Route' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($filters['is_super_admin'])
                    <div class="col-md-2">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select">
                            <option value="0">All Companies</option>
                            @foreach($filterOptions['companies'] as $company)
                                <option value="{{ $company->id }}" @selected($filters['company_id'] === (int) $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-2">
                    <label class="form-label">Route</label>
                    <select name="route_id" class="form-select">
                        <option value="0">All Routes</option>
                        @foreach($filterOptions['routes'] as $route)
                            <option value="{{ $route->id }}" @selected($filters['route_id'] === (int) $route->id)>{{ $route->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Bus</label>
                    <select name="bus_id" class="form-select">
                        <option value="0">All Buses</option>
                        @foreach($filterOptions['buses'] as $bus)
                            <option value="{{ $bus->id }}" @selected($filters['bus_id'] === (int) $bus->id)>{{ $bus->plate_number }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Driver</label>
                    <select name="driver_id" class="form-select">
                        <option value="0">All Drivers</option>
                        @foreach($filterOptions['drivers'] as $driver)
                            <option value="{{ $driver->id }}" @selected($filters['driver_id'] === (int) $driver->id)>{{ $driver->user?->full_name ?? ('Driver #' . $driver->id) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Trip Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach($filterOptions['statuses'] as $status)
                            <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Apply Filters</button>
                    <a href="{{ route('company.reports.index') }}" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">{{ $report['title'] }}</h5>

            @if(($report['type'] ?? null) === 'trip_detail' && !empty($report['trip_report']))
                <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <strong>Detailed trip analytics are available.</strong>
                        Open the full trip report page for charts, stop analytics, scan audit, and rider manifest.
                    </div>
                    <a href="{{ route('company.trips.report', $report['trip_report']['trip']) }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-open-in-new me-1"></i> Open Full Trip Report
                    </a>
                </div>
            @endif

            <div class="row mb-3">
                @foreach($report['summary'] as $item)
                    <div class="col-md-3 col-sm-6 mb-2">
                        <div class="border rounded p-2 h-100">
                            <div class="text-muted small">{{ $item['label'] }}</div>
                            <div class="fw-bold">{{ $item['value'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @foreach($report['columns'] as $column)
                                <th>{{ $column['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['rows'] as $row)
                            <tr>
                                @foreach($report['columns'] as $column)
                                    <td>{{ $row[$column['key']] ?? '' }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($report['columns']) }}" class="text-center text-muted py-4">No data found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
