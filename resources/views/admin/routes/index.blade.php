@extends('layouts.master')

@section('title')
Route Management
@endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
@endsection

@section('page-title')

@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <!-- ROUTE TABLE -->
    <div class="card">
        <div class="card-body">
<!-- FILTERS -->
<form method="GET" action="{{ route('admin.routes.index') }}">
    <div class="card mb-4">
        <div class="card-body">

            <div class="row align-items-end g-3">

                {{-- COMPANY --}}
                <div class="col-xl-4 col-md-6">
                    <label class="form-label fw-semibold">Company</label>
                    <select name="company_id" class="form-select">
                        <option value="">All Companies</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}"
                                @selected(request('company_id') == $company->id)>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- STATUS --}}
                <div class="col-xl-3 col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" @selected(request('status') === 'active')>
                            Active
                        </option>
                        <option value="inactive" @selected(request('status') === 'inactive')>
                            Inactive
                        </option>
                    </select>
                </div>

                {{-- TRIPS --}}
                <div class="col-xl-3 col-md-6">
                    <label class="form-label fw-semibold">Trips</label>
                    <select name="has_trips" class="form-select">
                        <option value="">All</option>
                        <option value="1" @selected(request('has_trips') === '1')>
                            With Active Trips
                        </option>
                        <option value="0" @selected(request('has_trips') === '0')>
                            No Active Trips
                        </option>
                    </select>
                </div>

                {{-- ACTIONS --}}
                <div class="col-xl-2 col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        Apply
                    </button>

                    <a href="{{ route('admin.routes.index') }}"
                       class="btn btn-light w-100">
                        Reset
                    </a>
                </div>

            </div>

        </div>
    </div>
</form>

    <!-- Page Actions -->
    <div class="row mb-3">
        <div class="col">
     <h4 class="card-title mb-2">Route List</h4>
            <p class="card-title-desc">
                View and manage transport routes and their activity status.
            </p>
        </div>
        <div class="col text-end">
            <a href="{{ route('admin.routes.create') }}" class="btn btn-primary">
                <i class="mdi mdi-plus"></i> New Route
            </a>
        </div>
    </div>



            <table id="routes-table"
                   class="table table-bordered table-striped dt-responsive nowrap"
                   style="width:100%">

                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Stops</th>
                        <th>Active Trips</th>
                        <th>Status</th>
                        <th width="180">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($routes as $route)
                        <tr>
                            <td>
                                <strong>{{ $route->name }}</strong>
                            </td>

                            <td>
                                {{ $route->company?->name ?? 'Shared' }}
                            </td>

                            <td>
                                <span class="badge bg-info">
                                    {{ $route->stops_count }}
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-primary">
                                    {{ $route->active_trips_count }}
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-{{ $route->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($route->status) }}
                                </span>
                            </td>

                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.routes.show', $route->id) }}"
                                       class="btn btn-info">
                                        View
                                    </a>

                                    <a href="{{ route('admin.routes.edit', $route->id) }}"
                                       class="btn btn-secondary">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
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
    $('#routes-table').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'asc']]
    });
});
</script>
@endsection
