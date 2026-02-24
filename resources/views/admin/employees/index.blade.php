@extends('layouts.master')

@section('title')
    Employee Management
@endsection

@section('css')
    <link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}"
        rel="stylesheet" />

    <link rel="stylesheet" href="https://unpkg.com/filepond/dist/filepond.min.css">
    <link rel="stylesheet" href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css">
@endsection

<style>
    /* Center FilePond */
    .filepond-avatar-wrapper {
        max-width: 160px;
        margin: 0 auto;
    }

    /* Make FilePond circular */
    .filepond--item-panel,
    .filepond--image-preview-wrapper,
    .filepond--image-preview {
        border-radius: 50% !important;
    }

    .filepond--credits {
        display: none !important
    }

    /* Ensure image fills the circle */
    .filepond--image-preview img {
        object-fit: cover;
    }

    /* Remove rectangular layout */
    .filepond--panel-root {
        border-radius: 50%;
        background-color: #f5f6f8;
    }

    /* Fixed size avatar */
    .filepond--root {
        width: 160px;
        height: 160px;
        margin: 0 auto;
    }

    /* Hide file info text inside preview */
    .filepond--file-info {
        display: none;
    }

    /* Avatar container */
    .avatar-pond {
        width: 160px;
        height: 160px;
    }

    /* Ensure FilePond fits the avatar container */
    .avatar-pond .filepond--root {
        width: 160px;
        height: 160px;
        margin: 0;
    }

    /* Make the FilePond panel circular */
    .avatar-pond .filepond--panel-root {
        border-radius: 999px !important;
        background: #f5f6f8;
    }

    /* Make the image preview circular */
    .avatar-pond .filepond--item-panel,
    .avatar-pond .filepond--image-preview-wrapper,
    .avatar-pond .filepond--image-preview {
        border-radius: 999px !important;
    }

    /* Crop image nicely inside the circle */
    .avatar-pond .filepond--image-preview img {
        object-fit: cover !important;
    }

    /* Hide file meta text inside the avatar (clean look) */
    .avatar-pond .filepond--file-info,
    .avatar-pond .filepond--file-status {
        display: none !important;
    }

    /* Center the idle label text inside circle */
    .avatar-pond .filepond--drop-label {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center;
        padding: 0 12px;
    }

    /* Slightly reduce padding so it looks like an avatar uploader */
    .avatar-pond .filepond--drop-label label {
        margin: 0;
        font-size: 12px;
        line-height: 1.2;
    }
</style>


@section('body')

    <body data-sidebar="colored">
    @endsection

    @section('content')
        <div class="container-fluid">


            <!-- FILTERS -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">Company</label>
                            <select name="company_id" class="form-select">
                                <option value="">All Companies</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" value="{{ request('department') }}" class="form-control"
                                placeholder="e.g. Production">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" @selected(request('status') === 'active')>Active</option>
                                <option value="resigned" @selected(request('status') === 'resigned')>Resigned</option>
                                <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
                            </select>
                        </div>

                        <div class="col-12 text-end">
                            <button class="btn btn-primary">Apply Filters</button>
                            <a href="{{ route('admin.employees.index') }}" class="btn btn-light">Reset</a>
                        </div>

                    </form>
                </div>
            </div>

            <!-- EMPLOYEE TABLE -->
            <div class="card">
                <div class="card-body">


                    <!-- Page Actions -->
                    <div class="row mb-3">
                        <div class="col">
                            <h4 class="card-title mb-2">Employee List</h4>
                            <p class="card-title-desc">
                                View employee profiles, QR codes, attendance, and transport history.
                            </p>
                        </div>
                        <div class="col text-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#createEmployeeModal">
                                <i class="mdi mdi-account-plus"></i> Add Employee
                            </button>

                        </div>
                    </div>


                    <table id="employees-table" class="table table-bordered table-striped dt-responsive nowrap"
                        style="width:100%">

                        <thead>
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th>Employee</th>
                                <th>Company</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>QR Code</th>
                                <th width="200">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($employees as $employee)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="row-checkbox" value="{{ $employee->id }}">
                                    </td>

                                    <td>
                                        <strong>{{ $employee->user->full_name }}</strong><br>
                                        <small class="text-muted">{{ $employee->employee_code }}</small>
                                    </td>

                                    <td>{{ $employee->company->name }}</td>

                                    <td>{{ $employee->department ?? '—' }}</td>

                                    <td>
                                        <span class="badge bg-{{ $employee->status === 'active' ? 'success' : 'danger' }}">
                                            {{ ucfirst($employee->status) }}
                                        </span>
                                    </td>

                                    <td>
                                        @if ($employee->qrcode)
                                            <span class="badge bg-success">
                                                <i class="mdi mdi-qrcode"></i> Generated
                                            </span>
                                        @else
                                            <span class="badge bg-warning">
                                                <i class="mdi mdi-alert-circle"></i> None
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.employees.show', $employee->id) }}"
                                                class="btn btn-info">Profile</a>

                                          <button class="btn btn-warning btn-edit-employee"
    data-id="{{ $employee->id }}"
    data-first-name="{{ $employee->user->first_name }}"
    data-middle-name="{{ $employee->user->middle_name }}"
    data-last-name="{{ $employee->user->last_name }}"
    data-email="{{ $employee->user->email }}"
    data-company-id="{{ $employee->company_id }}"
    data-employee-code="{{ $employee->employee_code }}"
    data-department="{{ $employee->department }}"
    data-status="{{ $employee->status }}"
    data-photo-url="{{ $employee->photo_path ? asset('storage/'.$employee->photo_path) : '' }}">
    Edit
</button>


                                            <a href="{{ route('admin.employees.qr', $employee->id) }}"
                                                class="btn btn-secondary">QR</a>
                                        </div>

                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>

                </div>
            </div>

        </div>
        @include('admin.employees.modal.create')
        @include('admin.employees.modal.edit')
    @endsection

    @section('scripts')
        <script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
        <script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
        <script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
        <script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>

        <script src="{{ URL::asset('build/js/app.js') }}"></script>

        {{-- FilePond --}}
        <script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>
        <script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.min.js">
        </script>
        <script src="https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.min.js">
        </script>
        <script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.js"></script>

        <script>
            $(function() {

                // DataTable
                $('#employees-table').DataTable({
                    responsive: true,
                    pageLength: 10,
                    order: [
                        [1, 'asc']
                    ]
                });

                $('#select-all').on('change', function() {
                    $('.row-checkbox').prop('checked', this.checked);
                });

                // FilePond plugins
                FilePond.registerPlugin(
                    FilePondPluginFileValidateType,
                    FilePondPluginFileValidateSize,
                    FilePondPluginImagePreview
                );

                // Initialize FilePond ONLY when modal opens (best practice)
                let createPond = null;

                $('#createEmployeeModal').on('shown.bs.modal', function() {
                    const input = document.querySelector('#create_employee_photo');
                    if (!input || createPond) return;

                    createPond = FilePond.create(input, {
                        allowMultiple: false,
                        maxFiles: 1,
                        acceptedFileTypes: ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'],
                        storeAsFile: true, // ✅ submit as normal file input
                        labelIdle: 'Drag & Drop your photo or <span class="filepond--label-action">Browse</span>',
                    });
                });

                // Optional: clear file when modal closes
                $('#createEmployeeModal').on('hidden.bs.modal', function() {
                    if (createPond) createPond.removeFiles();
                });

            });
            let editPond = null;

$('#editEmployeeModal').on('shown.bs.modal', function () {
    const input = document.querySelector('#edit_employee_photo');
    if (!input || editPond) return;

    editPond = FilePond.create(input, {
        allowMultiple: false,
        maxFiles: 1,
        storeAsFile: true,
        acceptedFileTypes: ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'],
        labelIdle: 'Drop photo<br>or <span class="filepond--label-action">Browse</span>',
        credits: false
    });
});

$('#editEmployeeModal').on('hidden.bs.modal', function () {
    if (editPond) editPond.removeFiles();
});

$(document).on('click', '.btn-edit-employee', function () {
    const btn = $(this);

    const id = btn.data('id');
    const photoUrl = btn.data('photo-url') || '';

    // Set form action (use your update route pattern)
    // If you have a named route: admin.employees.update
    // we build it by replacing :id
    const updateUrlTemplate = "{{ route('admin.employees.update', ':id') }}";
    $('#editEmployeeForm').attr('action', updateUrlTemplate.replace(':id', id));

    // Fill fields
    $('#edit_employee_id').val(id);
    $('#edit_first_name').val(btn.data('first-name') || '');
    $('#edit_middle_name').val(btn.data('middle-name') || '');
    $('#edit_last_name').val(btn.data('last-name') || '');
    $('#edit_email').val(btn.data('email') || '');
    $('#edit_company_id').val(btn.data('company-id') || '');
    $('#edit_employee_code').val(btn.data('employee-code') || '');
    $('#edit_department').val(btn.data('department') || '');
    $('#edit_status').val(btn.data('status') || 'active');

    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
    modal.show();

    // Preload current photo (after pond is ready)
    setTimeout(() => {
        if (editPond) {
            editPond.removeFiles();
            if (photoUrl) {
                editPond.addFile(photoUrl);
            }
        }
    }, 150);
});

        </script>
    @endsection
