@extends('layouts.master')

@section('title')
    Employee Management
@endsection

@section('page-title')
    Add Employee
@endsection

@section('body')
    <body data-sidebar="colored">
@endsection

@section('content')
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="row mb-3">
            <div class="col">
                <h4 class="mb-0">New Employee</h4>
                <small class="text-muted">
                    Register a new employee for <strong>{{ $company->name }}</strong>.
                </small>
            </div>
            <div class="col text-end">
                <a href="{{ route('company.employees.index') }}" class="btn btn-light">
                    Back
                </a>
            </div>
        </div>

        <!-- FORM -->
        <div class="card">
            <div class="card-body">

                <h4 class="card-title mb-3">Employee Information</h4>

                <form method="POST" action="{{ route('company.employees.store') }}" enctype="multipart/form-data">
                    @csrf

                    {{-- Company (fixed, current company only) --}}
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Company</label>
                        <div class="col-sm-9 pt-2">
                            <strong>{{ $company->name }}</strong>
                            {{-- If your CompanyEmployeeController::store still expects company_id,
                                 you can keep a hidden input. Otherwise you can remove this. --}}
                            <input type="hidden" name="company_id" value="{{ $company->id }}">
                        </div>
                    </div>

                    <!-- First Name -->
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">First Name</label>
                        <div class="col-sm-9">
                            <input type="text"
                                   name="first_name"
                                   class="form-control @error('first_name') is-invalid @enderror"
                                   value="{{ old('first_name') }}"
                                   required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Middle Name -->
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Middle Name</label>
                        <div class="col-sm-9">
                            <input type="text"
                                   name="middle_name"
                                   class="form-control @error('middle_name') is-invalid @enderror"
                                   value="{{ old('middle_name') }}">
                            @error('middle_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Last Name -->
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Last Name</label>
                        <div class="col-sm-9">
                            <input type="text"
                                   name="last_name"
                                   class="form-control @error('last_name') is-invalid @enderror"
                                   value="{{ old('last_name') }}"
                                   required>
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Email</label>
                        <div class="col-sm-9">
                            <input type="email"
                                   name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Employee Code -->
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Employee Code</label>
                        <div class="col-sm-9">
                            <input type="text"
                                   name="employee_code"
                                   class="form-control @error('employee_code') is-invalid @enderror"
                                   value="{{ old('employee_code') }}"
                                   required>
                            @error('employee_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Department -->
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Department</label>
                        <div class="col-sm-9">
                            <input type="text"
                                   name="department"
                                   class="form-control @error('department') is-invalid @enderror"
                                   value="{{ old('department') }}">
                            @error('department')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="row mb-4">
                        <label class="col-sm-3 col-form-label">Status</label>
                        <div class="col-sm-9">
                            <select name="status"
                                    class="form-select @error('status') is-invalid @enderror">
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="suspended" @selected(old('status') === 'suspended')>Suspended</option>
                                <option value="resigned" @selected(old('status') === 'resigned')>Resigned</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Optional: profile photo (if you want upload on create page, not only modal) --}}
                    {{--
                    <div class="row mb-4">
                        <label class="col-sm-3 col-form-label">Profile Photo</label>
                        <div class="col-sm-9">
                            <input type="file"
                                   name="photo"
                                   id="create_employee_photo"
                                   class="form-control @error('photo') is-invalid @enderror"
                                   accept="image/*">
                            @error('photo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    --}}

                    <!-- ACTIONS -->
                    <div class="row">
                        <div class="col-sm-9 offset-sm-3">
                            <button type="submit" class="btn btn-primary">
                                Save Employee
                            </button>
                            <a href="{{ route('company.employees.index') }}"
                               class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </div>

                </form>

            </div>
        </div>

    </div>
@endsection

@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
