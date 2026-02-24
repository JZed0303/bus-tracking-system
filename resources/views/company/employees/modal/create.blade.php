<!-- CREATE EMPLOYEE MODAL (COMPANY PANEL) -->
<div class="modal fade" id="createEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <form method="POST"
                  action="{{ route('company.employees.store') }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Employee</h5>
                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body">

                    {{-- PROFILE PHOTO (Avatar / FilePond) --}}
                    <div class="text-center mb-4">
                        <label class="form-label fw-semibold d-block mb-2">Profile Photo</label>

                        <div class="d-flex justify-content-center">
                            <div class="avatar-pond">
                                <input type="file"
                                       name="photo"
                                       id="create_employee_photo"
                                       class="filepond @error('photo') is-invalid @enderror"
                                       accept="image/png, image/jpeg, image/jpg, image/webp"
                                       data-max-file-size="2MB"
                                       data-max-files="1">
                            </div>
                        </div>

                        @error('photo')
                            <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror

                        <small class="text-muted d-block mt-2">
                            JPG/PNG/WEBP • Max 2MB • 1 file
                        </small>
                    </div>

                    {{-- NO COMPANY FIELD HERE (company comes from auth user) --}}

                    {{-- NAME ROW (3 columns) --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">First Name</label>
                            <input type="text"
                                   name="first_name"
                                   class="form-control @error('first_name') is-invalid @enderror"
                                   value="{{ old('first_name') }}"
                                   required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Middle Name</label>
                            <input type="text"
                                   name="middle_name"
                                   class="form-control @error('middle_name') is-invalid @enderror"
                                   value="{{ old('middle_name') }}">
                            @error('middle_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Last Name</label>
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

                    {{-- EMAIL + EMPLOYEE CODE (2 columns) --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email"
                                   name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Employee Code</label>
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

                    {{-- DEPARTMENT + STATUS (2 columns) --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department</label>
                            <input type="text"
                                   name="department"
                                   class="form-control @error('department') is-invalid @enderror"
                                   value="{{ old('department') }}">
                            @error('department')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status"
                                    class="form-select @error('status') is-invalid @enderror">
                                <option value="active" @selected(old('status') === 'active')>Active</option>
                                <option value="suspended" @selected(old('status') === 'suspended')>Suspended</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit"
                            class="btn btn-primary">
                        Save Employee
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
