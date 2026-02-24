@extends('layouts.master')

@section('title')
    Add Company
@endsection

@section('page-title')
    Add Company
@endsection

@section('css')
    {{-- FilePond --}}
    <link href="https://unpkg.com/filepond@^4/dist/filepond.css" rel="stylesheet" />
    <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet" />

    <style>
        /* ===============================
           COMPANY LOGO (CENTERED)
           =============================== */
        .company-logo-uploader {
            width: 130px;
            margin: 0 auto;
        }

        .company-logo-uploader .filepond--root,
        .company-logo-uploader .filepond--panel-root,
        .company-logo-uploader .filepond--item-panel {
            border-radius: 50%;
        }

        .company-logo-uploader .filepond--drop-label {
            min-height: 130px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .company-logo-uploader .filepond--root {
            height: 130px;
        }
    </style>
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <!-- PAGE HEADER -->
    <div class="row mb-4">
        <div class="col">
            <h4 class="mb-1">New Company</h4>
            <small class="text-muted">
                Register a new factory or company into the transport system.
            </small>
        </div>
        <div class="col text-end">
            <a href="{{ route('admin.companies.index') }}" class="btn btn-light">
                Back
            </a>
        </div>
    </div>

    <!-- FORM CARD -->
    <div class="card">
        <div class="card-body">

            <h5 class="card-title mb-4">Company Information</h5>

            <form method="POST"
                  action="{{ route('admin.companies.store') }}"
                  enctype="multipart/form-data">
                @csrf

                {{-- COMPANY LOGO --}}
                <div class="row mb-4">
                    <div class="col-12 text-center">
                        <label class="form-label d-block mb-2">
                            Company Logo
                        </label>

                        <div class="company-logo-uploader mx-auto">
                            <input type="file"
                                   name="logo"
                                   class="filepond"
                                   accept="image/png,image/jpeg">
                        </div>

                        <small class="text-muted d-block mt-2">
                            Drag & drop or click to upload
                        </small>
                    </div>
                </div>

                <hr class="my-4">

                {{-- COMPANY DETAILS --}}
                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <label class="form-label">Company Name</label>
                        <input type="text"
                               name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Contact Person</label>
                        <input type="text"
                               name="contact_person"
                               class="form-control @error('contact_person') is-invalid @enderror"
                               value="{{ old('contact_person') }}">
                        @error('contact_person')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Contact Number</label>
                        <input type="text"
                               name="contact_number"
                               class="form-control @error('contact_number') is-invalid @enderror"
                               value="{{ old('contact_number') }}">
                        @error('contact_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status"
                                class="form-select @error('status') is-invalid @enderror">
                            <option value="active" @selected(old('status') === 'active')>
                                Active
                            </option>
                            <option value="inactive" @selected(old('status') === 'inactive')>
                                Inactive
                            </option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address"
                                  rows="3"
                                  class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                <hr class="my-4">

                {{-- COMPANY ADMIN ACCOUNT --}}
                <h5 class="mb-3">Company Admin Account</h5>

                <div class="row g-3 mb-4">

                    <div class="col-md-6">
                        <label class="form-label">Admin Email</label>
                        <input type="email"
                               name="admin_email"
                               class="form-control @error('admin_email') is-invalid @enderror"
                               value="{{ old('admin_email') }}"
                               required>
                        @error('admin_email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Admin Password</label>
                        <input type="password"
                               name="admin_password"
                               class="form-control @error('admin_password') is-invalid @enderror"
                               required>
                        @error('admin_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>

                {{-- ACTIONS --}}
                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.companies.index') }}"
                       class="btn btn-light px-4">
                        Cancel
                    </a>
                    <button type="submit"
                            class="btn btn-primary px-4">
                        Save Company
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>

{{-- FilePond --}}
<script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
<script src="https://unpkg.com/filepond@^4/dist/filepond.js"></script>

<script>
    FilePond.registerPlugin(FilePondPluginImagePreview);

    FilePond.create(document.querySelector('.filepond'), {
        name: 'logo',
        allowMultiple: false,
        instantUpload: false,
        storeAsFile: true,
        imagePreviewHeight: 130,
        imageCropAspectRatio: '1:1',
        imageResizeTargetWidth: 300,
        imageResizeTargetHeight: 300,
        stylePanelLayout: 'compact circle',
        labelIdle: 'Upload Logo',
    });
</script>
@endsection
