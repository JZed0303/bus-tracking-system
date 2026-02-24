@extends('layouts.master')

@section('title')
    Create User
@endsection

@section('page-title')
    Create User
@endsection

@section('css')
    {{-- FilePond --}}
    <link href="https://unpkg.com/filepond@^4/dist/filepond.css" rel="stylesheet" />
    <link href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css" rel="stylesheet" />

    <style>
        /* ===============================
           USER AVATAR (CENTERED)
           =============================== */
        .user-avatar-uploader {
            width: 130px;
            margin: 0 auto;
        }

        .user-avatar-uploader .filepond--root,
        .user-avatar-uploader .filepond--panel-root,
        .user-avatar-uploader .filepond--item-panel {
            border-radius: 50%;
        }

        .user-avatar-uploader .filepond--drop-label {
            min-height: 130px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .user-avatar-uploader .filepond--root {
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
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h4 class="mb-1">New User</h4>
            <small class="text-muted">
                Create a system or company user account.
            </small>
        </div>
        <div class="col text-end">
            <a href="{{ route('admin.users.index') }}"
               class="btn btn-light">
                Back
            </a>
        </div>
    </div>

    <!-- FORM CARD -->
    <div class="card">
        <div class="card-body">

            <h5 class="card-title mb-4">
                User Information
            </h5>

            <form method="POST"
                  action="{{ route('admin.users.store') }}"
                  enctype="multipart/form-data">
                @csrf

                {{-- AVATAR (CENTERED, INSIDE FORM) --}}
                <div class="row mb-4">
                    <div class="col-12 text-center">

                        <label class="form-label d-block mb-2">
                            Profile Avatar
                        </label>

                        <div class="user-avatar-uploader">
                            <input type="file"
                                   name="avatar"
                                   class="filepond-avatar"
                                   accept="image/png,image/jpeg">
                        </div>

                        <small class="text-muted d-block mt-2">
                            Drag & drop or click to upload
                        </small>

                    </div>
                </div>

                <hr class="my-4">

                {{-- BASIC DETAILS --}}
                <div class="row g-3 mb-4">

                    <div class="col-md-4">
                        <label class="form-label">First Name</label>
                        <input type="text"
                               name="first_name"
                               class="form-control"
                               required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input type="text"
                               name="middle_name"
                               class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Last Name</label>
                        <input type="text"
                               name="last_name"
                               class="form-control"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email"
                               name="email"
                               class="form-control"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password"
                               name="password"
                               class="form-control"
                               required>
                    </div>

                </div>

                <hr class="my-4">

                {{-- ROLE & COMPANY --}}
                <div class="row g-3 mb-4">

                    <div class="col-md-4">
                        <label class="form-label">Role</label>
                        <select name="role"
                                id="roleSelect"
                                class="form-select"
                                required>
                            <option value="">Select role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}">
                                    {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Company</label>
                        <select name="company_id"
                                id="companySelect"
                                class="form-select">
                            <option value="">— None —</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            Required for company roles
                        </small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status"
                                class="form-select"
                                required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                </div>

                {{-- ACTIONS --}}
                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('admin.users.index') }}"
                       class="btn btn-light px-4">
                        Cancel
                    </a>
                    <button type="submit"
                            class="btn btn-primary px-4">
                        Create User
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

FilePond.create(document.querySelector('.filepond-avatar'), {
    name: 'avatar',
    allowMultiple: false,
    instantUpload: false,
    storeAsFile: true,
    imagePreviewHeight: 130,
    imageCropAspectRatio: '1:1',
    imageResizeTargetWidth: 300,
    imageResizeTargetHeight: 300,
    stylePanelLayout: 'compact circle',
    labelIdle: 'Upload Avatar',
});
</script>

<script>
document.getElementById('roleSelect').addEventListener('change', function () {
    const companySelect = document.getElementById('companySelect');
    const companyRoles = ['company_admin', 'employee', 'driver'];

    companySelect.required = companyRoles.includes(this.value);
});
</script>
@endsection
