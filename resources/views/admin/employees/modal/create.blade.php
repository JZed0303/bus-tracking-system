<!-- CREATE EMPLOYEE MODAL -->
<div class="modal fade" id="createEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <form method="POST" action="{{ route('admin.employees.store') }}" enctype="multipart/form-data">
                @csrf


                <div class="modal-header">
                    <h5 class="modal-title">Add Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

              <!-- Profile Photo (Avatar - FilePond) -->
<div class="mb-4">
    <label class="form-label d-block text-center mb-2">Profile Photo</label>

    <div class="d-flex justify-content-center">
        <div class="avatar-pond">
            <input type="file"
                   name="photo"
                   id="create_employee_photo"
                   class="filepond"
                   accept="image/png, image/jpeg, image/jpg, image/webp"
                   data-max-file-size="2MB"
                   data-max-files="1">
        </div>
    </div>

    @error('photo')
        <div class="text-danger text-center mt-2">{{ $message }}</div>
    @enderror

    <small class="text-muted d-block text-center mt-2">
        JPG/PNG/WEBP • Max 2MB • 1 file
    </small>
</div>



                    <!-- Company -->
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <select name="company_id" class="form-select @error('company_id') is-invalid @enderror"
                            required>
                            <option value="">Select Company</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name"
                                class="form-control @error('first_name') is-invalid @enderror"
                                value="{{ old('first_name') }}" required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middle_name"
                                class="form-control @error('middle_name') is-invalid @enderror"
                                value="{{ old('middle_name') }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name"
                                class="form-control @error('last_name') is-invalid @enderror"
                                value="{{ old('last_name') }}" required>
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email"
                                class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}"
                                required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee Code</label>
                            <input type="text" name="employee_code"
                                class="form-control @error('employee_code') is-invalid @enderror"
                                value="{{ old('employee_code') }}" required>
                            @error('employee_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" name="department"
                                class="form-control @error('department') is-invalid @enderror"
                                value="{{ old('department') }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" @selected(old('status') === 'active')>Active</option>
                                <option value="suspended" @selected(old('status') === 'suspended')>Suspended</option>
                            </select>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Save Employee
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
