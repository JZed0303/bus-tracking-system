{{-- ======================================================
   FILEPOND + MODAL : CREATE DRIVER (ONE PAGE)
   ====================================================== --}}

{{-- ===========================
   FILEPOND CSS
   =========================== --}}
<link rel="stylesheet" href="https://unpkg.com/filepond/dist/filepond.min.css">
<link rel="stylesheet" href="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css">

{{-- ===========================
   CREATE DRIVER MODAL
   =========================== --}}
<div class="modal fade" id="createDriverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-sm border-0 rounded-4">

            <form method="POST"
                  action="{{ route('admin.drivers.store') }}"
                  enctype="multipart/form-data"
                  class="needs-validation"
                  novalidate>
                @csrf

                {{-- HEADER --}}
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title mb-1">Add Driver</h5>
                        <small class="text-muted">
                            Create a driver profile and assign it to a company.
                        </small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                {{-- BODY --}}
                <div class="modal-body pt-3">

                    {{-- AVATAR --}}
                    <div class="text-center mb-4">
                        <input type="file"
                               name="photo"
                               id="driver_photo_pond"
                               class="filepond"
                               accept="image/png,image/jpeg,image/webp">

                        @error('photo')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror

                        <div class="form-text mt-2">
                            PNG / JPG / WEBP • Max 2MB
                        </div>
                    </div>

                    <h6 class="fw-semibold mb-3">Driver Information</h6>

                    {{-- Company --}}
                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-3 col-form-label">
                            Company <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-9">
                            <select name="company_id"
                                    class="form-select @error('company_id') is-invalid @enderror"
                                    required>
                                <option value="" disabled selected>Select company</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}"
                                        @selected(old('company_id') == $company->id)>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Company is required.</div>
                        </div>
                    </div>

                    {{-- Name --}}
                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">
                            Full Name <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-9">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="text" name="first_name"
                                           class="form-control"
                                           placeholder="First name"
                                           value="{{ old('first_name') }}"
                                           required>
                                    <div class="invalid-feedback">First name is required.</div>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="middle_name"
                                           class="form-control"
                                           placeholder="Middle (optional)"
                                           value="{{ old('middle_name') }}">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="last_name"
                                           class="form-control"
                                           placeholder="Last name"
                                           value="{{ old('last_name') }}"
                                           required>
                                    <div class="invalid-feedback">Last name is required.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Email --}}
                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-3 col-form-label">
                            Email <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-9">
                            <input type="email" name="email"
                                   class="form-control"
                                   value="{{ old('email') }}"
                                   required>
                            <div class="invalid-feedback">Valid email is required.</div>
                        </div>
                    </div>

                    {{-- License --}}
                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-3 col-form-label">
                            License No. <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-9">
                            <input type="text" name="license_number"
                                   class="form-control"
                                   value="{{ old('license_number') }}"
                                   required>
                            <div class="invalid-feedback">License number is required.</div>
                        </div>
                    </div>

                    {{-- Phone --}}
                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-3 col-form-label">Phone</label>
                        <div class="col-sm-9">
                            <input type="text" name="phone"
                                   class="form-control"
                                   value="{{ old('phone') }}">
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="row mb-0 align-items-center">
                        <label class="col-sm-3 col-form-label">
                            Status <span class="text-danger">*</span>
                        </label>
                        <div class="col-sm-9">
                            <select name="status" class="form-select" required>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="invalid-feedback">Status is required.</div>
                        </div>
                    </div>

                </div>

                {{-- FOOTER --}}
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Save Driver
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

{{-- ===========================
   FILEPOND JS
   =========================== --}}
<script src="https://unpkg.com/filepond/dist/filepond.min.js"></script>
<script src="https://unpkg.com/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.js"></script>
<script src="https://unpkg.com/filepond-plugin-file-validate-type/dist/filepond-plugin-file-validate-type.min.js"></script>
<script src="https://unpkg.com/filepond-plugin-file-validate-size/dist/filepond-plugin-file-validate-size.min.js"></script>

<script>
FilePond.registerPlugin(
    FilePondPluginImagePreview,
    FilePondPluginFileValidateType,
    FilePondPluginFileValidateSize
);

FilePond.create(document.querySelector('#driver_photo_pond'), {
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
</script>

{{-- ===========================
   FILEPOND CIRCULAR AVATAR CSS
   =========================== --}}
<style>
.filepond--root {
    width: 160px;
    margin: 0 auto;
}

.filepond--panel-root,
.filepond--item-panel,
.filepond--image-preview-wrapper,
.filepond--image-preview,
.filepond--drop-label {
    border-radius: 50%;
}

.filepond--image-preview canvas,
.filepond--image-preview-wrapper img {
    border-radius: 50%;
    object-fit: cover;
}

.filepond--panel-root {
    border: 1px solid rgba(0,0,0,.15);
    background: #f8f9fa;
}
</style>
