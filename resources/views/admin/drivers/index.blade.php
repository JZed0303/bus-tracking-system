@extends('layouts.master')

@section('title')
    Driver Management
@endsection

@section('css')
    {{-- Datatables --}}
    <link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}"
          rel="stylesheet" />

    {{-- FilePond (Create + Edit modal) --}}
    <link rel="stylesheet" href="https://unpkg.com/filepond/dist/filepond.min.css">
    <link rel="stylesheet" href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css">

    {{-- FilePond circular avatar styling --}}
    <style>
        .filepond--root { width: 160px; margin: 0 auto; }
        .filepond--panel-root,
        .filepond--item-panel,
        .filepond--image-preview-wrapper,
        .filepond--image-preview,
        .filepond--drop-label { border-radius: 50%; }

        .filepond--image-preview canvas,
        .filepond--image-preview-wrapper img { border-radius: 50%; object-fit: cover; }

        .filepond--panel-root { border: 1px solid rgba(0,0,0,.15); background: #f8f9fa; }

        .driver-cell {
            display: flex;
            align-items: center;
            gap: .7rem;
            min-width: 220px;
        }

        .driver-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #e9edf4;
            background: #f8fafc;
            flex-shrink: 0;
        }

        .driver-actions .btn {
            border-radius: 0 !important;
        }
        .driver-actions .btn:first-child {
            border-top-left-radius: .25rem !important;
            border-bottom-left-radius: .25rem !important;
        }
        .driver-actions .btn:last-child {
            border-top-right-radius: .25rem !important;
            border-bottom-right-radius: .25rem !important;
        }
    </style>
@endsection

@section('page-title')
@endsection

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
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>

                <div class="col-12 text-end">
                    <button class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('admin.drivers.index') }}" class="btn btn-light">Reset</a>
                </div>

            </form>
        </div>
    </div>

    <!-- DRIVER TABLE -->
    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col">
                    <h4 class="card-title mb-2">Driver List</h4>
                    <p class="card-title-desc">
                        View driver profiles, bus assignments, routes, and activity.
                    </p>
                </div>
                <div class="col text-end">
                    <button type="button"
                            class="btn btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#createDriverModal">
                        <i class="mdi mdi-account-plus"></i> Add Driver
                    </button>
                </div>
            </div>

            <table id="drivers-table" class="table table-bordered table-striped dt-responsive nowrap align-middle w-100">
                <thead>
                    <tr>
                        <th>Driver</th>
                        <th>Company</th>
                        <th>Bus</th>
                        <th>Route</th>
                        <th class="text-center">Status</th>
                       
                        <th width="190">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($drivers as $driver)
                        @php
                            // Build payload in PHP to avoid Blade parsing issues inside attributes
                            $driverPayload = [
                                'id'             => $driver->id,
                                'company_id'      => $driver->company_id,
                                'first_name'      => $driver->user->first_name ?? '',
                                'middle_name'     => $driver->user->middle_name ?? '',
                                'last_name'       => $driver->user->last_name ?? '',
                                'email'           => $driver->user->email ?? '',
                                'license_number'  => $driver->license_number ?? '',
                                'phone'           => $driver->phone ?? '',
                                'status'          => $driver->status ?? 'active',
                                'photo_url'       => $driver->photo_url ?? null,
                            ];
                        @endphp

                        <tr>
                            <td>
                                <div class="driver-cell">
                                    <img
                                        src="{{ $driver->photo_url ?? asset('build/images/user-placeholder.png') }}"
                                        alt="{{ $driver->user->full_name }} profile"
                                        class="driver-avatar"
                                    >
                                    <div>
                                        <strong>{{ $driver->user->full_name }}</strong><br>
                                        <small class="text-muted">
                                            License: {{ $driver->license_number ?? '—' }}
                                        </small>
                                    </div>
                                </div>
                            </td>

                            <td>{{ $driver->company->name ?? '—' }}</td>

                            <td>{{ optional($driver->currentAssignment?->vehicle)->plate_number ?? 'Unassigned' }}</td>

                            <td>{{ optional($driver->currentAssignment?->route)->name ?? 'Unassigned' }}</td>

                            <td class="text-center">
                                <span class="badge bg-{{ $driver->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($driver->status) }}
                                </span>
                            </td>

                           
                            <td>
                                <div class="btn-group btn-group-sm driver-actions">
                                    <a href="{{ route('admin.drivers.show', $driver) }}"
                                       class="btn"
                                       data-bs-toggle="tooltip"
                                       title="View Profile"
                                       aria-label="View Profile">
                                        <i class="mdi mdi-account-circle-outline"></i>
                                    </a>

                                    {{-- EDIT (no onclick; jQuery will handle) --}}
                                   
                              
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>

</div>

{{-- CREATE MODAL --}}
@include('admin.drivers.modal.create')

{{-- EDIT MODAL --}}
@include('admin.drivers.modal.edit')

@endsection

@section('scripts')
    {{-- Datatables --}}
    <script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>

    {{-- FilePond --}}
    <script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>
    <script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.js"></script>
    <script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.min.js"></script>
    <script src="https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.min.js"></script>

    <script src="{{ URL::asset('build/js/app.js') }}"></script>

    <script>
    $(function () {

        // DataTable
        $('#drivers-table').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'asc']],
            columnDefs: [
                { targets: [5], orderable: false, searchable: false } // actions
            ],
        });

        $('[data-bs-toggle="tooltip"]').tooltip();

        // Bootstrap validation (Create + Edit forms)
        $(document).on('submit', '.needs-validation', function (e) {
            const form = this;
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            $(form).addClass('was-validated');
        });

        // FilePond plugins
        FilePond.registerPlugin(
            FilePondPluginImagePreview,
            FilePondPluginFileValidateType,
            FilePondPluginFileValidateSize
        );

        // Create pond (if exists)
        const createInput = document.querySelector('#driver_photo_pond');
        if (createInput) {
            FilePond.create(createInput, {
                allowMultiple: false,
                storeAsFile: true,
                imageCropAspectRatio: '1:1',
                imageResizeTargetWidth: 160,
                imageResizeTargetHeight: 160,
                acceptedFileTypes: ['image/png','image/jpeg','image/webp'],
                maxFileSize: '2MB',
                stylePanelLayout: 'compact circle',
                labelIdle: 'Upload photo',
            });
        }

        // Edit pond
        let editDriverPond = null;

        function initEditDriverPond() {
            const input = document.querySelector('#edit_driver_photo_pond');
            if (!input) return;

            if (!editDriverPond) {
                editDriverPond = FilePond.create(input, {
                    allowMultiple: false,
                    storeAsFile: true,
                    imageCropAspectRatio: '1:1',
                    imageResizeTargetWidth: 160,
                    imageResizeTargetHeight: 160,
                    acceptedFileTypes: ['image/png','image/jpeg','image/webp'],
                    maxFileSize: '2MB',
                    stylePanelLayout: 'compact circle',
                    labelIdle: 'Upload photo',
                });
            }
        }

        // Update route template
        const updateRouteTemplate = @json(route('admin.drivers.update', ':id'));

        // jQuery click handler (works with DataTables redraw)
        $(document).on('click', '.btn-edit-driver', function () {
            const raw = $(this).attr('data-driver');
            if (!raw) return;

            let driver;
            try {
                driver = JSON.parse(raw);
            } catch (e) {
                console.error('Invalid driver JSON:', e, raw);
                return;
            }

            // Init pond
            initEditDriverPond();

            // Set form action
            $('#editDriverForm')
                .attr('action', updateRouteTemplate.replace(':id', driver.id))
                .removeClass('was-validated');

            // Fill fields
            $('#edit_company_id').val(driver.company_id ?? '');
            $('#edit_first_name').val(driver.first_name ?? '');
            $('#edit_middle_name').val(driver.middle_name ?? '');
            $('#edit_last_name').val(driver.last_name ?? '');
            $('#edit_email').val(driver.email ?? '');
            $('#edit_license_number').val(driver.license_number ?? '');
            $('#edit_phone').val(driver.phone ?? '');
            $('#edit_status').val(driver.status ?? 'active');

            // Photo preview (requires photo_url to be publicly accessible)
            if (editDriverPond) {
                editDriverPond.removeFiles();
                if (driver.photo_url) {
                    editDriverPond.addFile(driver.photo_url).catch(() => {});
                }
            }

            // Show modal
            new bootstrap.Modal(document.getElementById('editDriverModal')).show();
        });

    });
    </script>
@endsection
