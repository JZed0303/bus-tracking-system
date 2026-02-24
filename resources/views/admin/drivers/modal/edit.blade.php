{{-- ======================================================================
   FILE: resources/views/admin/drivers/modal/edit.blade.php
   Edit Driver Modal (FilePond circular)
   ====================================================================== --}}

<div class="modal fade" id="editDriverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-sm border-0 rounded-4">

            <form method="POST"
                  id="editDriverForm"
                  action=""
                  enctype="multipart/form-data"
                  class="needs-validation"
                  novalidate>
                @csrf
                @method('PUT')

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title mb-1">Edit Driver</h5>
                        <small class="text-muted">Update driver profile information and photo.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-3">

                    <div class="text-center mb-4">
                        <input type="file"
                               name="photo"
                               id="edit_driver_photo_pond"
                               class="filepond"
                               accept="image/png,image/jpeg,image/webp">
                        <div class="form-text mt-2">
                            PNG / JPG / WEBP • Max 2MB (leave empty to keep current photo)
                        </div>
                    </div>

                    <h6 class="fw-semibold mb-3">Driver Information</h6>

                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-3 col-form-label">Company <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <select name="company_id" id="edit_company_id" class="form-select" required>
                                <option value="" disabled>Select company</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Company is required.</div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-sm-3 col-form-label">Full Name <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="text" name="first_name" id="edit_first_name"
                                           class="form-control" placeholder="First name" required>
                                    <div class="invalid-feedback">First name is required.</div>
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="middle_name" id="edit_middle_name"
                                           class="form-control" placeholder="Middle (optional)">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="last_name" id="edit_last_name"
                                           class="form-control" placeholder="Last name" required>
                                    <div class="invalid-feedback">Last name is required.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-3 col-form-label">Email <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                            <div class="invalid-feedback">Valid email is required.</div>
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-3 col-form-label">License No. <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="text" name="license_number" id="edit_license_number"
                                   class="form-control" required>
                            <div class="invalid-feedback">License number is required.</div>
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-3 col-form-label">Phone</label>
                        <div class="col-sm-9">
                            <input type="text" name="phone" id="edit_phone" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-0 align-items-center">
                        <label class="col-sm-3 col-form-label">Status <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="invalid-feedback">Status is required.</div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Driver</button>
                </div>

            </form>
        </div>
    </div>
</div>
