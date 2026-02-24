@extends('layouts.master')

@section('title', 'Assignments')

@section('page-title', 'Assignments')

@section('css')
    <link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />

    <style>
        .table td,
        .table th {
            vertical-align: middle;
        }

        .badge {
            font-weight: 500;
        }

        .filter-actions {
            display: flex;
            gap: .5rem;
            justify-content: flex-end;
        }
    </style>
@endsection

@section('body')
    <body data-sidebar="colored">
@endsection

@section('content')
    <div class="container-fluid">

        {{-- FILTERS (NO company select) --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('company.assignments.index') }}" class="row g-3">

                    {{-- ROUTE --}}
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Route</label>
                        <select name="route_id" class="form-select">
                            <option value="">All Routes</option>
                            @foreach($routes as $route)
                                <option value="{{ $route->id }}" @selected(request('route_id') == $route->id)>
                                    {{ $route->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- DRIVER --}}
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Driver</label>
                        <select name="driver_id" class="form-select">
                            <option value="">All Drivers</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" @selected(request('driver_id') == $driver->id)>
                                    {{ $driver->user->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- STATUS --}}
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="active" @selected(request('status') === 'active')>Active</option>
                            <option value="upcoming" @selected(request('status') === 'upcoming')>Upcoming</option>
                            <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                        </select>
                    </div>

                    {{-- EFFECTIVE FROM (DATE RANGE) --}}
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Effective From (Start)</label>
                        <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Effective From (End)</label>
                        <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                    </div>

                    <div class="col-12">
                        <div class="filter-actions">
                            <button class="btn btn-primary">
                                <i class="mdi mdi-filter-variant"></i> Apply Filters
                            </button>
                            <a href="{{ route('company.assignments.index') }}" class="btn btn-light">
                                Reset
                            </a>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="card">
            <div class="card-body">

                <div class="row mb-3 align-items-center">
                    <div class="col">
                        <h4 class="card-title mb-1">Assignment List</h4>
                        <p class="card-title-desc mb-0">
                            Driver, bus, route, and assignment effective dates.
                        </p>
                    </div>
                    {{-- NO Create button for company --}}
                </div>

                <table id="assignments-table"
                       class="table table-bordered table-striped dt-responsive nowrap align-middle w-100">
                    <thead class="table-light">
                    <tr>
                        <th>Driver</th>
                        <th>Bus</th>
                        <th>Route</th>
                        <th>Effective Period</th>
                        <th>Status</th>
                        <th width="120" class="text-center">Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    @forelse($assignments as $assignment)
                        <tr>
                            {{-- DRIVER --}}
                            <td>
                                <div class="fw-semibold">{{ $assignment->driver->user->full_name }}</div>
                                <small class="text-muted">Driver ID: {{ $assignment->driver_id }}</small>
                            </td>

                            {{-- BUS --}}
                            <td>
                                <div class="fw-semibold">{{ $assignment->bus->plate_number }}</div>
                                <small class="text-muted">Bus ID: {{ $assignment->bus_id }}</small>
                            </td>

                            {{-- ROUTE --}}
                            <td>
                                <div class="fw-semibold">{{ $assignment->route->name }}</div>
                                <small class="text-muted">Route ID: {{ $assignment->route_id }}</small>
                            </td>

                            {{-- EFFECTIVE PERIOD --}}
                            <td>
                                <div class="small">
                                    <div>
                                        <strong>From:</strong>
                                        {{ optional($assignment->effective_from)->format('M d, Y') ?? '—' }}
                                    </div>
                                    <div>
                                        <strong>To:</strong>
                                        {{ optional($assignment->effective_to)->format('M d, Y') ?? 'Open-ended' }}
                                    </div>
                                </div>
                            </td>

                            {{-- STATUS --}}
                            <td>
                                @if($assignment->isActive())
                                    <span class="badge bg-success">Active</span>
                                @elseif($assignment->isUpcoming())
                                    <span class="badge bg-info">Upcoming</span>
                                @elseif($assignment->isExpired())
                                    <span class="badge bg-danger">Expired</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($assignment->status) }}</span>
                                @endif
                            </td>

                            {{-- ACTIONS: VIEW + TIMELINE only --}}
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('company.assignments.show', $assignment->id) }}"
                                       class="btn btn-secondary" title="View">
                                        <i class="mdi mdi-eye"></i>
                                    </a>

                                    <a href="{{ route('company.assignments.timeline', $assignment->id) }}"
                                       class="btn btn-primary" title="Timeline">
                                        <i class="mdi mdi-timeline"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No assignments found.
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
            $('#assignments-table').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[3, 'desc']], // Effective Period column
                columnDefs: [
                    { targets: [5], orderable: false, searchable: false } // Actions
                ]
            });
        });
    </script>
@endsection
