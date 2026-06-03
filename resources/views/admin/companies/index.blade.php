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
@php
    $isArchive = $isArchive ?? false;
@endphp

<style>
    .table td { vertical-align: middle; }
    .badge { font-weight: 500; }
    .company-logo {
        width: 40px;
        height: 40px;
        object-fit: cover;
    }
    .page-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: end;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .company-card {
        border: 1px solid #e9ecef;
        box-shadow: 0 0.125rem 0.5rem rgba(22, 28, 45, .04);
    }
    .count-badge {
        min-width: 2.2rem;
        display: inline-block;
        text-align: center;
    }
    .action-icons {
        display: inline-flex;
       
    }
    .action-icons .btn {
        width: 2rem;
        height: 2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-radius: 0 !important;
    }
    .action-icons .btn:first-child {
        border-top-left-radius: .25rem !important;
        border-bottom-left-radius: .25rem !important;
    }
    .action-icons .btn:last-child {
        border-top-right-radius: .25rem !important;
        border-bottom-right-radius: .25rem !important;
    }
</style>

<div class="container-fluid">



    <div class="card company-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ $isArchive ? route('admin.companies.archive') : route('admin.companies.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4 col-lg-3">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Companies</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary">
                        <i class="mdi mdi-filter-variant me-1"></i> Apply
                    </button>
                </div>
                <div class="col-auto">
                    <a href="{{ $isArchive ? route('admin.companies.archive') : route('admin.companies.index') }}" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- COMPANIES TABLE -->
    <div class="card company-card">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-3 flex-wrap">
                <div>
                    <h4 class="card-title mb-1">Company Management</h4>
                    <p class="text-muted mb-0">Manage registered companies, contact details, routes, and employees.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ $isArchive ? route('admin.companies.index') : route('admin.companies.archive') }}" class="btn btn-light btn-sm">
                        <i class="mdi mdi-archive-outline me-1"></i> {{ $isArchive ? 'Back to Active' : 'Archive' }}
                    </a>
                    @unless($isArchive)
                        <a href="{{ route('admin.companies.create') }}" class="btn btn-primary btn-sm">
                            <i class="mdi mdi-plus-circle-outline me-1"></i> Add Company
                        </a>
                    @endunless
                </div>
            </div>
            <table id="companies-table"
                   class="table table-bordered table-hover dt-responsive nowrap align-middle"
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
                            <span class="badge bg-info count-badge">
                                {{ $company->employees_count }}
                            </span>
                        </td>

                        <!-- ROUTES -->
                        <td class="text-center">
                            <span class="badge bg-success count-badge">
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
                            <div class="btn-group btn-group-sm action-icons">
                                <a href="{{ route('admin.companies.show', $company) }}"
                                   class="btn btn-sm"
                                   data-bs-toggle="tooltip"
                                   title="View Company">
                                    <i class="mdi mdi-eye-outline"></i>
                                </a>

                                @if($isArchive)
                                    <form method="POST"
                                          action="{{ route('admin.companies.restore', $company->id) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Restore this company?')">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm"
                                                data-bs-toggle="tooltip"
                                                title="Restore Company">
                                            <i class="mdi mdi-restore"></i>
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('admin.companies.edit', $company) }}"
                                       class="btn btn-sm"
                                       data-bs-toggle="tooltip"
                                       title="Edit Company">
                                        <i class="mdi mdi-pencil-outline"></i>
                                    </a>

                                    <form method="POST"
                                          action="{{ route('admin.companies.destroy', $company) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete this company?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-sm"
                                                data-bs-toggle="tooltip"
                                                title="Delete Company">
                                            <i class="mdi mdi-trash-can-outline"></i>
                                        </button>
                                    </form>
                                @endif
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
        columnDefs: [{ targets: [6], orderable: false }]
    });

    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
@endsection
