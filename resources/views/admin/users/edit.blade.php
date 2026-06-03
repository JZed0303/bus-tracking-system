@extends('layouts.master')

@section('title')
    Edit User
@endsection

@section('page-title')
    Edit User
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-1">Edit User</h4>
            <small class="text-muted">Update account details, email, and password.</small>
        </div>
        <div class="col text-end">
            <a href="{{ route('admin.users.index') }}" class="btn btn-light">Back</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $user->middle_name) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" autocomplete="new-password">
                        <small class="text-muted">Leave blank to keep current password.</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="">Select role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" @selected(old('role', $user->roles->first()?->name ?? $user->role) === $role->name)>
                                    {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected((string) old('company_id', $user->company_id) === (string) $company->id)>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active" @selected(old('status', $user->status) === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactive</option>
                            <option value="suspended" @selected(old('status', $user->status) === 'suspended')>Suspended</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection

