@extends('layouts.master')

@section('title', 'User Permissions')
@section('page-title', 'User Permissions')

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    {{-- HEADER --}}
    <div class="row mb-3">
        <div class="col">
            <h4 class="mb-0">
                Manage Permissions — {{ $user->full_name }}
            </h4>
            <small class="text-muted">
                User-level permissions override role-based access
            </small>
        </div>
    </div>

    {{-- USER INFO --}}
    <div class="card mb-3">
        <div class="card-body d-flex justify-content-between">
            <div>
                <strong>Email:</strong> {{ $user->email }} <br>
                <strong>Role:</strong> {{ ucfirst(str_replace('_',' ', $user->role)) }}
            </div>
            <span class="badge bg-warning text-dark">
                Explicit Override
            </span>
        </div>
    </div>

    <form method="POST"
          action="{{ route('admin.users.permissions.update', $user) }}">
        @csrf

        <div class="card shadow-sm">

            {{-- HEADER --}}
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Permission Override Matrix</span>

                {{-- BULK --}}
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-primary" id="bulk-view">
                        View Only
                    </button>
                    <button type="button" class="btn btn-outline-success" id="bulk-full">
                        Full Access
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="bulk-deny">
                        Deny All
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="bulk-clear">
                        Clear
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width:35%">Module</th>
                            <th class="text-center">View</th>
                            <th class="text-center">Create</th>
                            <th class="text-center">Update</th>
                            <th class="text-center">Delete</th>
                            <th class="text-center text-danger">Deny</th>
                        </tr>
                    </thead>

                   <tbody>
@foreach($modules as $moduleKey => $module)
@php
    $view   = "view_{$moduleKey}";
    $create = "create_{$moduleKey}";
    $update = "update_{$moduleKey}";
    $delete = "delete_{$moduleKey}";
@endphp

<tr>
    <td class="fw-semibold">{{ $module['label'] }}</td>

    {{-- VIEW --}}
    <td class="text-center">
        <input type="checkbox"
               class="form-check-input perm-view"
               name="permissions[]"
               value="{{ $view }}"
               {{ $user->hasDirectPermission($view) ? 'checked' : '' }}>
    </td>

    {{-- CREATE --}}
    <td class="text-center">
        <input type="checkbox"
               class="form-check-input perm-create"
               name="permissions[]"
               value="{{ $create }}"
               {{ $user->hasDirectPermission($create) ? 'checked' : '' }}>
    </td>

    {{-- UPDATE --}}
    <td class="text-center">
        <input type="checkbox"
               class="form-check-input perm-update"
               name="permissions[]"
               value="{{ $update }}"
               {{ $user->hasDirectPermission($update) ? 'checked' : '' }}>
    </td>

    {{-- DELETE --}}
    <td class="text-center">
        <input type="checkbox"
               class="form-check-input perm-delete"
               name="permissions[]"
               value="{{ $delete }}"
               {{ $user->hasDirectPermission($delete) ? 'checked' : '' }}>
    </td>

    {{-- DENY --}}
    <td class="text-center">
        <input type="checkbox"
               class="form-check-input perm-deny"
               name="denied[]"
               value="{{ $view }}"
               {{ $user->isPermissionDenied($view) ? 'checked' : '' }}>
    </td>
</tr>
@endforeach
</tbody>

                </table>
                {{-- ================= SPECIAL PERMISSIONS ================= --}}
@if(isset($specialPermissions) && $specialPermissions->count())
<div class="card shadow-sm mt-4">
    <div class="card-header bg-light fw-semibold">
        Special Permissions
    </div>

    <div class="card-body">
        <div class="row">
            @foreach ($specialPermissions as $permission)
                <div class="col-md-4 mb-2">
                    <div class="form-check">
                        <input type="checkbox"
                               class="form-check-input"
                               name="permissions[]"
                               value="{{ $permission->name }}"
                               id="perm_{{ $permission->id }}"
                               {{ $user->hasDirectPermission($permission->name) ? 'checked' : '' }}>

                        <label class="form-check-label"
                               for="perm_{{ $permission->id }}">
                            {{ Str::of($permission->name)->replace('_',' ')->title() }}
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

            </div>

            {{-- ACTIONS --}}
            <div class="card-footer text-end">
                <a href="{{ route('admin.users.permissions.index') }}"
                   class="btn btn-light me-2">
                    Back
                </a>
                <button class="btn btn-primary">
                    Save Permissions
                </button>
            </div>
        </div>
    </form>

</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

    const rows = document.querySelectorAll('tbody tr');

    const syncRow = row => {
        const view   = row.querySelector('.perm-view');
        const create = row.querySelector('.perm-create');
        const update = row.querySelector('.perm-update');
        const del    = row.querySelector('.perm-delete');
        const deny   = row.querySelector('.perm-deny');

        // DENY overrides everything
        if (deny.checked) {
            view.checked = create.checked = update.checked = del.checked = false;
            row.classList.add('table-danger');
            row.classList.remove('table-success');
            return;
        }

        // Update/Delete require View
        if ((update.checked || del.checked) && !view.checked) {
            view.checked = true;
        }

        // Remove write perms if view is unchecked
        if (!view.checked) {
            create.checked = update.checked = del.checked = false;
        }

        row.classList.toggle(
            'table-success',
            view.checked || create.checked || update.checked || del.checked
        );

        row.classList.remove('table-danger');
    };

    rows.forEach(row => {
        row.querySelectorAll('input').forEach(cb => {
            cb.addEventListener('change', () => syncRow(row));
        });
        syncRow(row);
    });

    // BULK ACTIONS
    document.getElementById('bulk-view').onclick = () => {
        rows.forEach(row => {
            row.querySelector('.perm-view').checked = true;
            row.querySelector('.perm-create').checked = false;
            row.querySelector('.perm-update').checked = false;
            row.querySelector('.perm-delete').checked = false;
            row.querySelector('.perm-deny').checked = false;
            syncRow(row);
        });
    };

    document.getElementById('bulk-full').onclick = () => {
        rows.forEach(row => {
            row.querySelector('.perm-view').checked = true;
            row.querySelector('.perm-create').checked = true;
            row.querySelector('.perm-update').checked = true;
            row.querySelector('.perm-delete').checked = true;
            row.querySelector('.perm-deny').checked = false;
            syncRow(row);
        });
    };

    document.getElementById('bulk-deny').onclick = () => {
        rows.forEach(row => {
            row.querySelector('.perm-deny').checked = true;
            syncRow(row);
        });
    };

    document.getElementById('bulk-clear').onclick = () => {
        rows.forEach(row => {
            row.querySelectorAll('input').forEach(cb => cb.checked = false);
            row.classList.remove('table-success','table-danger');
        });
    };

});
</script>

@endsection
