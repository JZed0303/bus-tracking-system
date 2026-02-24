@extends('layouts.master')

@section('title')
    Company Management
@endsection

@section('css')
    <link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
@endsection

@section('page-title')
    Companies
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')

<style>
    .table td { vertical-align: middle; }
    .badge { font-weight: 500; }
    .company-logo {
        width: 36px;
        height: 36px;
        object-fit: cover;
    }
</style>

<div class="container-fluid">



    <!-- COMPANIES TABLE -->
    <div class="card">
        <div class="card-body">

            <!-- PAGE ACTIONS -->
            <div class="row mb-3">
                <div class="col">
                    <h4 class="card-title mb-2">Company List</h4>
                    <p class="card-title-desc">
                        Manage registered companies, routes, buses, and employees.
                    </p>
                </div>

            </div>
            <!-- STATUS FILTER -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET"
              action="{{ route('admin.companies.index') }}"
              class="d-flex align-items-end gap-2 flex-wrap">

            <div style="min-width: 220px;">
                <label class="form-label mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Companies</option>
                    <option value="active" @selected(request('status') === 'active')>
                        Active
                    </option>
                    <option value="inactive" @selected(request('status') === 'inactive')>
                        Inactive
                    </option>
                </select>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary">
                    <i class="mdi mdi-filter-variant"></i> Apply
                </button>

                <a href="{{ route('admin.companies.index') }}"
                   class="btn btn-light">
                    Reset
                </a>
            </div>
  <div class="col text-end">
                    <a href="{{ route('admin.companies.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-plus"></i> Add Company
                    </a>
                </div>
        </form>
    </div>
</div>


            <table id="companies-table"
                   class="table table-bordered table-striped dt-responsive nowrap align-middle"
                   style="width:100%">

                <thead class="table-light">
                    <tr>
                        <th>Company</th>
                        <th>Contact Person</th>
                        <th>Contact Number</th>
                        <th class="text-center">Employees</th>
                        <th class="text-center">Routes</th>
                        <th>Status</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($companies as $company)
                    <tr>

                        <!-- COMPANY (Logo + Name) -->
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="{{ $company->logo_url }}"
                                     alt="{{ $company->name }}"
                                     class="rounded-circle border company-logo me-2">
                                <div>
                                    <div class="fw-semibold">{{ $company->name }}</div>
                                    <small class="text-muted">
                                        ID: {{ $company->id }}
                                    </small>
                                </div>
                            </div>
                        </td>

                        <!-- CONTACT PERSON -->
                        <td>{{ $company->contact_person ?? '—' }}</td>

                        <!-- CONTACT NUMBER -->
                        <td>{{ $company->contact_number ?? '—' }}</td>

                        <!-- EMPLOYEES -->
                        <td class="text-center">
                            <span class="badge bg-info">
                                {{ $company->employees_count }}
                            </span>
                        </td>

                        <!-- ROUTES -->
                        <td class="text-center">
                            <span class="badge bg-success">
                                {{ $company->routes_count }}
                            </span>
                        </td>

                        <!-- STATUS -->
                        <td>
                            <span class="badge bg-{{ $company->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($company->status) }}
                            </span>
                        </td>

                        <!-- ACTIONS -->
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('admin.companies.show', $company) }}"
                                   class="btn btn-sm btn-info">
                                    View
                                </a>

                                <a href="{{ route('admin.companies.edit', $company) }}"
                                   class="btn btn-sm btn-warning">
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
    $('#companies-table').DataTable({
    responsive: true,
    pageLength: 10,
    stateSave: true,
    order: [[0, 'asc']],
    columnDefs: [
        { targets: [6], orderable: false }
    ]
});

});
</script>
@endsection
