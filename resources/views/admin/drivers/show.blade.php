@extends('layouts.master')

@section('title')
    Driver Management
@endsection

@section('css')
    {{-- FilePond --}}
    <link rel="stylesheet" href="https://unpkg.com/filepond/dist/filepond.min.css">
    <link rel="stylesheet" href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css">

    <style>
        /* Minimal, clean */
        .card-soft { border: 1px solid rgba(0,0,0,.06); box-shadow: 0 1px 2px rgba(0,0,0,.04); }
        .muted { color:#6c757d; font-size:.9rem; }
        .label { font-size:.78rem; color:#6c757d; text-transform:uppercase; letter-spacing:.03em; }
        .value { font-weight:600; }
        .avatar { width:96px; height:96px; object-fit:cover; }

        /* Circular FilePond */
        .filepond--root { width: 160px; margin: 0; }
        .filepond--panel-root,
        .filepond--item-panel,
        .filepond--image-preview-wrapper,
        .filepond--image-preview,
        .filepond--drop-label { border-radius: 50%; }
        .filepond--image-preview canvas,
        .filepond--image-preview-wrapper img { border-radius: 50%; object-fit: cover; }
        .filepond--panel-root { border: 1px solid rgba(0,0,0,.12); background: #f8f9fa; }
    </style>
@endsection

@section('page-title')
    Driver Profile
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <div class="row g-3">


        {{-- RIGHT: Edit Form + Collapsible Sections --}}
        <div class="col-lg-12">
            <div class="card card-soft">
                <div class="card-body">

                    <form method="POST"
                          action="{{ route('admin.drivers.update', $driver->id) }}"
                          enctype="multipart/form-data"
                          class="needs-validation"
                          novalidate>
                        @csrf
                        @method('PUT')
                    <div class="row">
                        <div class="col-12 justify-content-center text-center mb-4">
  {{-- Photo Upload (compact) --}}
                        <div class="d-flex align-items-start justify-content-between mb-2">
                            <div class="fw-semibold">Photo</div>
                            <small class="text-muted">PNG/JPG/WEBP • Max 2MB</small>
                        </div>

                        <div class="mb-4 d-flex justify-content-center">
                            <input type="file"
                                   name="photo"
                                   id="driver_photo_pond_profile"
                                   class="filepond"
                                   accept="image/png,image/jpeg,image/webp">
                            @error('photo')
                                <div class="text-danger small mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        </div>
                    </div>

                        {{-- Fields (clean grouping) --}}
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name"
                                       class="form-control @error('first_name') is-invalid @enderror"
                                       value="{{ old('first_name', $driver->user->first_name) }}"
                                       required>
                                <div class="invalid-feedback">First name is required.</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name"
                                       class="form-control @error('middle_name') is-invalid @enderror"
                                       value="{{ old('middle_name', $driver->user->middle_name) }}">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name"
                                       class="form-control @error('last_name') is-invalid @enderror"
                                       value="{{ old('last_name', $driver->user->last_name) }}"
                                       required>
                                <div class="invalid-feedback">Last name is required.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $driver->user->email) }}"
                                       required>
                                <div class="invalid-feedback">Valid email is required.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $driver->phone) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">License Number <span class="text-danger">*</span></label>
                                <input type="text" name="license_number"
                                       class="form-control @error('license_number') is-invalid @enderror"
                                       value="{{ old('license_number', $driver->license_number) }}"
                                       required>
                                <div class="invalid-feedback">License number is required.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Company <span class="text-danger">*</span></label>
                                <select name="company_id"
                                        class="form-select @error('company_id') is-invalid @enderror"
                                        required>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}"
                                            @selected(old('company_id', $driver->company_id) == $company->id)>
                                            {{ $company->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Company is required.</div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="active" @selected(old('status', $driver->status) === 'active')>Active</option>
                                    <option value="inactive" @selected(old('status', $driver->status) === 'inactive')>Inactive</option>
                                </select>
                                <div class="invalid-feedback">Status is required.</div>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Created</label>
                                <input type="text" class="form-control" disabled value="{{ $driver->created_at->format('M d, Y H:i') }}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.drivers.index') }}" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>

                    {{-- Collapsible extra info (hidden by default) --}}
                    <div class="accordion mt-4" id="driverExtraAccordion">

                        {{-- Current Assignment --}}
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingAssignment">
                                <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#collapseAssignment">
                                    Current Assignment
                                </button>
                            </h2>
                            <div id="collapseAssignment" class="accordion-collapse collapse" data-bs-parent="#driverExtraAccordion">
                                <div class="accordion-body">
                                    @if($driver->currentAssignment)
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="label">Bus</div>
                                                <div class="value">{{ $driver->currentAssignment->bus->plate_number ?? '—' }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="label">Route</div>
                                                <div class="value">{{ $driver->currentAssignment->route->name ?? '—' }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="label">Effective From</div>
                                                <div class="value">{{ optional($driver->currentAssignment->effective_from)->format('M d, Y') ?? '—' }}</div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="label">Effective To</div>
                                                <div class="value">{{ $driver->currentAssignment->effective_to ? $driver->currentAssignment->effective_to->format('M d, Y') : 'Ongoing' }}</div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <a href="{{ route('admin.drivers.assignment', $driver->id) }}" class="btn btn-light btn-sm">
                                                Open Assignment Page
                                            </a>
                                        </div>
                                    @else
                                        <div class="text-muted">No active assignment.</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Recent Trips --}}
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTrips">
                                <button class="accordion-button collapsed" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#collapseTrips">
                                    Recent Trips
                                </button>
                            </h2>
                            <div id="collapseTrips" class="accordion-collapse collapse" data-bs-parent="#driverExtraAccordion">
                                <div class="accordion-body">
                                    @if($recentTrips->isEmpty())
                                        <div class="text-muted">No trips found.</div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Route</th>
                                                        <th class="text-end">Bus</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recentTrips as $trip)
                                                        <tr>
                                                            <td class="fw-semibold">
                                                                {{ optional($trip->actual_start_time)->format('M d, Y') ?? optional($trip->created_at)->format('M d, Y') }}
                                                            </td>
                                                            <td class="text-muted">
                                                                {{ optional($trip->assignment?->route)->name ?? '—' }}
                                                            </td>
                                                            <td class="text-end">
                                                                {{ optional($trip->assignment?->bus)->plate_number ?? '—' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="mt-3">
                                            <a href="{{ route('admin.drivers.trips', $driver->id) }}" class="btn btn-light btn-sm">
                                                Open Trips Page
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>

    {{-- FilePond --}}
    <script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>
    <script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.js"></script>
    <script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.min.js"></script>
    <script src="https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.min.js"></script>

    <script>
        // Bootstrap validation
        (function () {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach((form) => {
                form.addEventListener('submit', (event) => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        // FilePond init
        FilePond.registerPlugin(
            FilePondPluginImagePreview,
            FilePondPluginFileValidateType,
            FilePondPluginFileValidateSize
        );

        const input = document.querySelector('#driver_photo_pond_profile');
        if (input) {
            const pond = FilePond.create(input, {
                allowMultiple: false,
                storeAsFile: true,
                imageCropAspectRatio: '1:1',
                imageResizeTargetWidth: 240,
                imageResizeTargetHeight: 240,
                acceptedFileTypes: ['image/png','image/jpeg','image/webp'],
                maxFileSize: '2MB',
                stylePanelLayout: 'compact circle',
                labelIdle: 'Upload photo',
            });

            // Preload current photo
            const currentPhoto = @json($driver->photo_url);
            if (currentPhoto) {
                pond.addFile(currentPhoto).catch(() => {});
            }
        }
    </script>
@endsection
