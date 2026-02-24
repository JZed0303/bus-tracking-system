@extends('layouts.master')

@section('title')
    Edit Company
@endsection

@section('page-title')
    Edit Company
@endsection

@section('css')
    {{-- FilePond --}}
    <link href="https://unpkg.com/filepond@^4/dist/filepond.css" rel="stylesheet" />
    <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet" />

    <style>
        /* Centered avatar uploader */
        .company-logo-uploader {
            width: 120px;
            margin: 0 auto;
        }

        .company-logo-uploader .filepond--panel-root,
        .company-logo-uploader .filepond--item-panel {
            border-radius: 50%;
        }

        .company-logo-uploader .filepond--drop-label {
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
    </style>
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <!-- Page Header -->
    <div class="row mb-3">
        <div class="col">
            <h4 class="mb-0">Edit Company</h4>
            <small class="text-muted">
                Update company details and branding.
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

            <h4 class="card-title mb-3">Company Information</h4>

            <form method="POST"
                  action="{{ route('admin.companies.update', $company->id) }}"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')

              {{-- COMPANY LOGO --}}
<div class="row mb-4 align-items-center">
    <label class="col-sm-3 col-form-label">
        Company Logo
    </label>

    <div class="col-sm-9">
        <div class="company-logo-uploader text-center">
            <input type="file"
                   name="logo"
                   id="companyLogo"
                   class="filepond"
                   accept="image/png,image/jpeg">

            <small class="text-muted d-block mt-2">
                Drag & drop or click to replace
            </small>

            @error('logo')
                <div class="text-danger mt-1">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

                <!-- Company Name -->
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Company Name</label>
                    <div class="col-sm-9">
                        <input type="text"
                               name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $company->name) }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Contact Person -->
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Contact Person</label>
                    <div class="col-sm-9">
                        <input type="text"
                               name="contact_person"
                               class="form-control @error('contact_person') is-invalid @enderror"
                               value="{{ old('contact_person', $company->contact_person) }}">
                        @error('contact_person')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Contact Number -->
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Contact Number</label>
                    <div class="col-sm-9">
                        <input type="text"
                               name="contact_number"
                               class="form-control @error('contact_number') is-invalid @enderror"
                               value="{{ old('contact_number', $company->contact_number) }}">
                        @error('contact_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Address -->
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label">Address</label>
                    <div class="col-sm-9">
                        <textarea name="address"
                                  class="form-control @error('address') is-invalid @enderror"
                                  rows="3">{{ old('address', $company->address) }}</textarea>
                        @error('address')
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
                            <option value="active" @selected(old('status', $company->status) === 'active')>
                                Active
                            </option>
                            <option value="inactive" @selected(old('status', $company->status) === 'inactive')>
                                Inactive
                            </option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="row">
                    <div class="col-sm-9 offset-sm-3">
                        <button type="submit" class="btn btn-primary">
                            Update Company
                        </button>
                        <a href="{{ route('admin.companies.index') }}"
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

{{-- FilePond --}}
<script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js"></script>
<script src="https://unpkg.com/filepond@^4/dist/filepond.js"></script>
<script>
FilePond.registerPlugin(FilePondPluginImagePreview);

FilePond.create(document.querySelector('#companyLogo'), {
    allowMultiple: false,
    instantUpload: false,
    storeAsFile: true,
    imagePreviewHeight: 120,
    imageCropAspectRatio: '1:1',
    imageResizeTargetWidth: 300,
    imageResizeTargetHeight: 300,
    stylePanelLayout: 'compact circle',
    labelIdle: 'Drop logo<br><span class="filepond--label-action">Browse</span>',

    files: [
        {
            source: "{{ $company->logo_url }}",
            options: {
                type: 'remote', // ✅ IMPORTANT
            }
        }
    ]
});
</script>

@endsection
