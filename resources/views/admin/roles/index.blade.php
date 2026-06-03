@extends('layouts.master')

@section('title')
    Role Management
@endsection

@section('page-title')
    Role Management
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- PAGE HEADER -->
    <div class="row mb-3">
        <div class="col">
            <h4 class="mb-0">Role Management</h4>
            <small class="text-muted">
                Manage system roles and their permissions
            </small>
        </div>
    </div>

    @can('manage_role_permissions')
        <div class="row mb-3">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.roles.store') }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-6 col-lg-4">
                                <label for="role_name" class="form-label mb-1">New Role Name</label>
                                <input
                                    id="role_name"
                                    type="text"
                                    name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    placeholder="e.g. operations_admin"
                                    value="{{ old('name') }}"
                                    required
                                >
                                <small class="text-muted">Use letters, numbers, spaces, or underscores.</small>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">Add Role</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            @if (auth()->user()->hasRole('super_admin'))
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.permissions.store') }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-md-8">
                                    <label for="permission_name" class="form-label mb-1">New Permission Name</label>
                                    <input
                                        id="permission_name"
                                        type="text"
                                        name="permission_name"
                                        class="form-control @error('permission_name') is-invalid @enderror"
                                        placeholder="e.g. view_finance_reports"
                                        value="{{ old('permission_name') }}"
                                        required
                                    >
                                    <small class="text-muted">Use letters, numbers, spaces, or underscores.</small>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-primary">Add Permission</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endcan

    <!-- ROLES TABLE -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Role</th>
                                <th>Permissions</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($roles as $role)
                                <tr>
                                    <td>
                                        <strong>
                                            {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                        </strong>
                                    </td>

                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $role->permissions->count() }} permissions
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        @can('manage_role_permissions')
                                            <a href="{{ route('admin.roles.permissions.edit', $role) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                Manage Permissions
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        No roles found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
    
