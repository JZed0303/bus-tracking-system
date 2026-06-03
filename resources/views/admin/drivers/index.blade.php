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

        .driver-list-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #e9edf4;
            flex-shrink: 0;
        }

        .driver-card {
            border: 1px solid #e9ecef;
            box-shadow: 0 0.125rem 0.5rem rgba(22, 28, 45, .04);
        }

        .driver-grid-search {
            min-width: 260px;
        }

        .driver-grid-card {
            transition: all .2s ease;
            border-radius: 14px;
            overflow: hidden;
        }

        .driver-grid-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,.08);
        }

        .driver-grid-avatar-wrap {
            position: relative;
            width: 84px;
            margin: 0 auto 1rem;
        }

        .driver-grid-avatar {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f1f3f5;
        }

        .driver-grid-link,
        .driver-grid-name-link {
            color: inherit;
            text-decoration: none;
        }

        .driver-grid-name-link:hover {
            color: #1d6fdc;
        }

        .driver-grid-status-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #22c55e;
            position: absolute;
            right: 4px;
            bottom: 4px;
            border: 3px solid #ffffff;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.14);
        }

        .driver-grid-card .driver-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.6rem;
            border-radius: 999px;
            background: #f8fbff;
            border: 1px solid #e2eaf4;
        }

        .driver-grid-card .driver-actions .btn {
            width: 2.25rem;
            height: 2.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 999px !important;
            border: 1px solid #d9e3ef;
            background: #ffffff;
            color: #1f2a37;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.06);
            transition: background-color .18s ease, border-color .18s ease, color .18s ease, transform .18s ease, box-shadow .18s ease;
        }

        .driver-grid-card .driver-actions .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
        }

        .driver-grid-card .driver-actions a[title="View Profile"]:hover,
        .driver-grid-card .driver-actions a[aria-label="View Profile"]:hover {
            background: #e8f1ff;
            border-color: #bfd6ff;
            color: #1d6fdc;
        }

        .driver-grid-card .driver-actions button[title="Delete Driver"]:hover,
        .driver-grid-card .driver-actions button[aria-label="Delete Driver"]:hover,
        .driver-grid-card .driver-actions button[title="Restore Driver"]:hover,
        .driver-grid-card .driver-actions button[aria-label="Restore Driver"]:hover {
            background: #ffe8ec;
            border-color: #f5b8c3;
            color: #d63348;
        }

        .drivers-table td {
            vertical-align: middle;
        }

    </style>
@endsection

@section('page-title')
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
@php
    $isArchive = $isArchive ?? false;
@endphp
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
                        <option value="on_leave" @selected(request('status') === 'on_leave')>On Leave</option>
                        <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
                    </select>
                </div>

                <div class="col-12 text-end">
                    <button class="btn btn-primary">Apply Filters</button>
                    <a href="{{ $isArchive ? route('admin.drivers.archive') : route('admin.drivers.index') }}" class="btn btn-light">Reset</a>
                </div>

            </form>
        </div>
    </div>

    <!-- DRIVER TABLE -->
    <div class="card driver-card">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-end gap-2 flex-wrap mb-3">
                <div class="driver-grid-search d-none flex-grow-1" id="driverGridSearchWrap">
                    <input type="search"
                           id="driverGridSearch"
                           class="form-control"
                           placeholder="Search drivers in grid view">
                </div>
                <div class="text-end d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-primary" id="driverTableViewBtn">
                        <i class="mdi mdi-table"></i> Table
                    </button>
                    <button type="button" class="btn btn-outline-secondary" id="driverGridViewBtn">
                        <i class="mdi mdi-view-grid"></i> Grid
                    </button>
                    <a href="{{ $isArchive ? route('admin.drivers.index') : route('admin.drivers.archive') }}"
                       class="btn btn-light">
                        <i class="mdi mdi-archive-outline"></i> {{ $isArchive ? 'Back to Active' : 'Archive' }}
                    </a>
                    @unless($isArchive)
                        <button type="button"
                                class="btn btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#createDriverModal">
                            <i class="mdi mdi-account-plus"></i> Add Driver
                        </button>
                    @endunless
                </div>
            </div>

            <table id="drivers-table" class="table table-bordered table-hover dt-responsive nowrap drivers-table" style="width:100%">
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
                                    @if($isArchive)
                                        <form method="POST"
                                              action="{{ route('admin.drivers.restore', $driver->id) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('Restore this driver?')">
                                            @csrf
                                            <button type="submit"
                                                    class="btn"
                                                    data-bs-toggle="tooltip"
                                                    title="Restore Driver"
                                                    aria-label="Restore Driver">
                                                <i class="mdi mdi-restore"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST"
                                              action="{{ route('admin.drivers.destroy', $driver) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('Delete this driver?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn"
                                                    data-bs-toggle="tooltip"
                                                    title="Delete Driver"
                                                    aria-label="Delete Driver">
                                                <i class="mdi mdi-trash-can-outline"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div id="driver-grid-view" class="row mt-3 d-none"></div>

        </div>
    </div>

</div>

{{-- CREATE MODAL --}}
@unless($isArchive)
    @include('admin.drivers.modal.create')
@endunless

{{-- EDIT MODAL --}}
@unless($isArchive)
    @include('admin.drivers.modal.edit')
@endunless

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
        const driverTable = $('#drivers-table').DataTable({
            responsive: true,
            pageLength: 10,
            stateSave: true,
            order: [[0, 'asc']],
            columnDefs: [
                { targets: [5], orderable: false, searchable: false } // actions
            ],
        });

        function renderDriverGrid() {
            const grid = $('#driver-grid-view');
            grid.html('');

            driverTable.rows({ search: 'applied' }).every(function () {
                const rowNode = $(this.node());
                const image = rowNode.find('.driver-avatar').attr('src') || rowNode.find('.driver-list-avatar').attr('src');
                const driverName = rowNode.find('strong').first().text().trim();
                const licenseText = rowNode.find('small').first().text().trim().replace(/^License:\s*/, '');
                const company = rowNode.find('td:eq(1)').text().trim();
                const bus = rowNode.find('td:eq(2)').text().trim();
                const route = rowNode.find('td:eq(3)').text().trim();
                const status = rowNode.find('td:eq(4)').text().trim();
                const actions = rowNode.find('td:eq(5)').html();
                const profileUrl = rowNode.find('td:eq(5) a[title="View Profile"]').attr('href') || '#';
                const busText = bus && bus !== 'Unassigned' ? `Bus: ${bus}` : 'No bus assigned';
                const routeText = route && route !== 'Unassigned' ? `Route: ${route}` : 'No route assigned';

                grid.append(`
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card driver-grid-card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <a href="${profileUrl}" class="driver-grid-link">
                                    <div class="driver-grid-avatar-wrap">
                                        <img src="${image}" class="driver-grid-avatar" alt="${driverName}">
                                        ${status.toLowerCase() === 'active'
                                            ? '<span class="driver-grid-status-dot" title="Active"></span>'
                                            : ''}
                                    </div>
                                </a>
                                <h5 class="mb-1">
                                    <a href="${profileUrl}" class="driver-grid-name-link">${driverName}</a>
                                </h5>
                                <div class="text-muted small mb-3">
                                    ${licenseText || 'No license'} | ${company || 'No company'}
                                </div>
                             
                                <div class="d-flex justify-content-center">
                                    ${actions}
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            });

            grid.find('[data-bs-toggle="tooltip"]').tooltip();
        }

        $('#driverGridViewBtn').on('click', function () {
            $('#drivers-table_wrapper').hide();
            $('#driver-grid-view').removeClass('d-none');
            $('#driverGridSearchWrap').removeClass('d-none');
            renderDriverGrid();
        });

        $('#driverTableViewBtn').on('click', function () {
            $('#driver-grid-view').addClass('d-none');
            $('#drivers-table_wrapper').show();
            $('#driverGridSearchWrap').addClass('d-none');
        });

        $('#driverGridSearch').on('input', function () {
            const value = $(this).val();
            driverTable.search(value).draw();

            if (!$('#driver-grid-view').hasClass('d-none')) {
                renderDriverGrid();
            }
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
            const normalizedStatus = (driver.status === 'inactive') ? 'suspended' : (driver.status ?? 'active');
            $('#edit_status').val(normalizedStatus);

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
