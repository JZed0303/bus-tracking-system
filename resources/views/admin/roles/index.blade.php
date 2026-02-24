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

    <!-- PAGE HEADER -->
    <div class="row mb-3">
        <div class="col">
            <h4 class="mb-0">Role Management</h4>
            <small class="text-muted">
                Manage system roles and their permissions
            </small>
        </div>
    </div>

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
                                        @can('manage_roles')
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
    