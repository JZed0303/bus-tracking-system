@extends('layouts.master')
@section('title')
    User Permissions
@endsection
@section('page-title')
    User Permissions
@endsection
@section('body')
    <body data-sidebar="colored">
@endsection
@section('content')
<div class="container-fluid">

    <!-- PAGE HEADER -->
    <div class="row mb-3">
        <div class="col">
            <h4 class="mb-0">User Permissions</h4>
            <small class="text-muted">
                Select a user and manage their permissions.
            </small>
        </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">Select User</h5>
                    <small class="text-muted">Filter by name or email</small>
                </div>
                <div class="col-md-6">
                    <form method="GET" class="row g-2 justify-content-md-end mt-2 mt-md-0">
                        <div class="col-md-5 col-12">
                            <input type="text"
                                   name="search"
                                   value="{{ request('search') }}"
                                   class="form-control"
                                   placeholder="Search name or email">
                        </div>
                        <div class="col-md-4 col-8">
                            <select name="role" class="form-select">
                                <option value="">All roles</option>
                                @foreach(($roles ?? collect()) as $role)
                                    <option value="{{ $role }}" @selected(request('role') === $role)>
                                        {{ ucfirst(str_replace('_',' ', $role)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-4 d-grid d-md-flex gap-2 justify-content-md-end">
                            <button class="btn btn-primary" type="submit">
                                Filter
                            </button>
                            <a href="{{ route('admin.users.permissions.index') }}" class="btn btn-light">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- USERS TABLE -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($users as $u)
                            <tr>
                                <td>{{ $u->full_name }}</td>
                                <td>{{ $u->email }}</td>
                                <td>{{ ucfirst(str_replace('_',' ', $u->role)) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.users.permissions.edit', $u) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        Manage Permissions
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($users->hasPages())
            <div class="card-footer d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} results
                </small>
                <div>
                    {{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
@section('scripts')
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
