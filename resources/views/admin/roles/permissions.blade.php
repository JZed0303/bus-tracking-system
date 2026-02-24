@extends('layouts.master')

@section('title')
    Role Permissions
@endsection

@section('page-title')
    Role Permissions
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    {{-- HEADER --}}
    <div class="row mb-4">
        <div class="col">
            <h4 class="mb-1">
                Manage Permissions —
                <span class="text-primary">
                    {{ Str::of($role->name)->replace('_',' ')->title() }}
                </span>
            </h4>
        <small class="text-muted">
    Assign granular CRUD access per module
</small>

    </div>

    <form method="POST" action="{{ route('admin.roles.permissions.update', $role) }}">
        @csrf

        {{-- ================= MODULE PERMISSION MATRIX ================= --}}
        <div class="card shadow-sm">
            <div class="card-header bg-light fw-semibold">
                Module Permissions
            </div>

            <div class="card-body p-0">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 35%">Module</th>

                            @foreach ($actions as $action)
                                <th class="text-center">
                                    <div class="form-check d-inline-block">
                                        <input type="checkbox"
                                               class="form-check-input toggle-column"
                                               data-action="{{ $action }}"
                                               {{ $role->name === 'super_admin' ? 'disabled' : '' }}>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        {{ ucfirst($action) }}
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($modules as $moduleKey => $module)
                            <tr>
                                <td class="fw-semibold">
                                    <div class="d-flex align-items-center">
                                        <input type="checkbox"
                                               class="form-check-input toggle-row me-2"
                                               data-module="{{ $moduleKey }}"
                                               {{ $role->name === 'super_admin' ? 'disabled' : '' }}>
                                        {{ $module['label'] }}
                                    </div>
                                </td>

                                @foreach ($actions as $action)
                              @php
    $permission = "{$action}_{$moduleKey}";
    $exists = true; // permission exists because CRUD is explicit
@endphp

                                    <td class="text-center">
                                        @if ($exists)
                                          <input type="checkbox"
       class="form-check-input permission-checkbox"
       name="permissions[]"
       value="{{ $permission }}"
       data-module="{{ $moduleKey }}"
       data-action="{{ $action }}"
       {{ $role->hasPermissionTo($permission) ? 'checked' : '' }}
       {{ $role->name === 'super_admin' ? 'disabled' : '' }}>

                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ================= SPECIAL PERMISSIONS ================= --}}
        @if($specialPermissions->count())
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
                                           {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}
                                           {{ $role->name === 'super_admin' ? 'disabled' : '' }}>

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

        {{-- ACTIONS --}}
        <div class="mt-4 d-flex justify-content-end">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-light me-2">
                Cancel
            </a>

            <button class="btn btn-primary"
                    {{ $role->name === 'super_admin' ? 'disabled' : '' }}>
                Save Changes
            </button>
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
        const view   = row.querySelector('[data-action="view"]');
        const create = row.querySelector('[data-action="create"]');
        const update = row.querySelector('[data-action="update"]');
        const del    = row.querySelector('[data-action="delete"]');

        // Update/Delete require View
        if ((update.checked || del.checked) && !view.checked) {
            view.checked = true;
        }

        // Remove write permissions if view is unchecked
        if (!view.checked) {
            create.checked = update.checked = del.checked = false;
        }
    };

    // Individual permission change
    document.querySelectorAll('.permission-checkbox').forEach(cb => {
        cb.addEventListener('change', function () {
            syncRow(this.closest('tr'));
        });
    });

    // Toggle entire module row
    document.querySelectorAll('.toggle-row').forEach(rowToggle => {
        rowToggle.addEventListener('change', function () {
            const row = this.closest('tr');
            row.querySelectorAll('.permission-checkbox:not(:disabled)')
               .forEach(cb => cb.checked = this.checked);
            syncRow(row);
        });
    });

    // Toggle entire action column
    document.querySelectorAll('.toggle-column').forEach(colToggle => {
        colToggle.addEventListener('change', function () {
            document
                .querySelectorAll(
                    `.permission-checkbox[data-action="${this.dataset.action}"]:not(:disabled)`
                )
                .forEach(cb => {
                    cb.checked = this.checked;
                    syncRow(cb.closest('tr'));
                });
        });
    });

});
</script>

@endsection
