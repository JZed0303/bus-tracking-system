@extends('layouts.master')

@section('title')
    Profile
@endsection

@section('page-title')
    Profile
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col">
            <h4 class="mb-1">Super Admin Profile</h4>
            <small class="text-muted">
                Manage your account information and password.
            </small>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-4">Profile Information</h5>

            <form method="POST" action="{{ route('admin.profile.update') }}">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">First Name</label>
                        <input type="text"
                               name="first_name"
                               class="form-control @error('first_name') is-invalid @enderror"
                               value="{{ old('first_name', $user->first_name) }}"
                               required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input type="text"
                               name="middle_name"
                               class="form-control @error('middle_name') is-invalid @enderror"
                               value="{{ old('middle_name', $user->middle_name) }}">
                        @error('middle_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Last Name</label>
                        <input type="text"
                               name="last_name"
                               class="form-control @error('last_name') is-invalid @enderror"
                               value="{{ old('last_name', $user->last_name) }}"
                               required>
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email"
                               name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}"
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <input type="text"
                               name="address"
                               class="form-control @error('address') is-invalid @enderror"
                               value="{{ old('address', $user->address) }}">
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4">
                        Update Profile
                    </button>
                </div>
            </form>

            <hr class="my-4">

            <h5 class="mb-3">Change Password</h5>

            <form method="POST" action="{{ route('admin.profile.password.update') }}">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password"
                                    id="current_password"
                                    name="current_password"
                                    class="form-control @error('current_password') is-invalid @enderror"
                                    required>
                                <button class="btn btn-outline-secondary password-toggle"
                                        type="button"
                                        data-target="#current_password"
                                        aria-label="Toggle current password visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('current_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password"
                                    id="new_password"
                                    name="new_password"
                                    class="form-control @error('new_password') is-invalid @enderror"
                                    required>
                                <button class="btn btn-outline-secondary password-toggle"
                                        type="button"
                                        data-target="#new_password"
                                        aria-label="Toggle new password visibility">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('new_password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                <div class="col-md-4">
                    <label class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <input type="password"
                            id="new_password_confirmation"
                            name="new_password_confirmation"
                            class="form-control"
                            required>
                        <button class="btn btn-outline-secondary password-toggle"
                                type="button"
                                data-target="#new_password_confirmation"
                                aria-label="Toggle confirm new password visibility">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
</div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.password-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const targetSelector = btn.getAttribute('data-target');
                    const input = document.querySelector(targetSelector);
                    if (!input) return;

                    const icon = btn.querySelector('i');
                    const isHidden = input.getAttribute('type') === 'password';

                    input.setAttribute('type', isHidden ? 'text' : 'password');

                    if (icon) {
                        icon.classList.toggle('bi-eye', !isHidden);
                        icon.classList.toggle('bi-eye-slash', isHidden);
                    }
                });
            });
        });
        </script>
@endsection
