@extends('layouts.master')

@section('title')
User Management
@endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
@endsection

@section('')
Users
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')

<style>
    .table td {
    vertical-align: middle;
}

.badge {
    font-weight: 500;
}
</style>
<div class="container-fluid">


    <!-- FILTERS -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">

                <!-- COMPANY -->
                <div class="col-md-4">
                    <label class="form-label">Company</label>
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

                <!-- ROLE -->
                <div class="col-md-4">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}"
                                @selected(request('role') == $role->name)>
                                {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- STATUS -->
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" @selected(request('status')==='active')>Active</option>
                        <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
                    </select>
                </div>

                <div class="col-12 text-end">
                    <button class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light">Reset</a>
                </div>

            </form>
        </div>
    </div>

    <!-- USERS TABLE -->
    <div class="card">
        <div class="card-body">


              <!-- PAGE ACTIONS -->
    <div class="row mb-3">
        <div class="col">

            <h4 class="card-title mb-2">User List</h4>
            <p class="card-title-desc">
                Manage system and company users, roles, and access status.
            </p>
        </div>
        <div class="col text-end">
            @can('manage_users')
                <a href="{{ route('admin.users.create') }}"
                   class="btn btn-primary">
                    <i class="mdi mdi-account-plus"></i> Add User
                </a>
            @endcan
        </div>
    </div>


           <table id="users-table"
       class="table table-bordered table-striped dt-responsive nowrap align-middle"
       style="width:100%">

    <thead class="table-light">
        <tr>
            <th>User</th>
            <th>Email</th>
            <th>Roles</th>
            <th>Company</th>
            <th>Status</th>
            <th width="160" class="text-center">Actions</th>
        </tr>
    </thead>

    <tbody>
        @foreach($users as $user)
            <tr>

                <!-- USER -->
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-xs rounded-circle bg-primary text-white
                                    d-flex align-items-center justify-content-center fw-semibold me-2">
                            {{ strtoupper(substr($user->full_name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-semibold">
                                {{ $user->full_name }}
                            </div>
                            <small class="text-muted">
                                {{ $user->email }}
                            </small>
                        </div>
                    </div>
                </td>

                <!-- EMAIL (DESKTOP VISIBILITY) -->
                <td class="d-none d-md-table-cell">
                    {{ $user->email }}
                </td>

                <!-- ROLES -->
                <td>
                    @forelse($user->roles as $role)
                        <span class="badge bg-light text-dark border me-1 mb-1">
                            {{ ucfirst(str_replace('_',' ', $role->name)) }}
                        </span>
                    @empty
                        <span class="text-muted">—</span>
                    @endforelse
                </td>

                <!-- COMPANY -->
                <td>
                    {{ $user->company?->name ?? '—' }}
                </td>

                <!-- STATUS -->
                <td>
                    <span class="badge bg-{{ $user->status === 'active' ? 'success' : 'secondary' }}">
                        {{ ucfirst($user->status) }}
                    </span>
                </td>

                <!-- ACTIONS -->
                <td class="text-center">
                    @can('manage_users')
                        <div class="d-inline-flex gap-1">

                            <a href="{{ route('admin.users.edit', $user) }}"
                               class="btn btn-sm btn-warning">
                                Edit
                            </a>

                            <form method="POST"
                                  action="{{ route('admin.users.destroy', $user) }}"
                                  onsubmit="return confirm('Deactivate this user?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-sm btn-danger">
                                    Disable
                                </button>
                            </form>

                        </div>
                    @endcan
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
<script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></cript>
<script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>

<script>
$(function () {

    $('#users-table').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'asc']]
    });

});
</script>
@endsection
