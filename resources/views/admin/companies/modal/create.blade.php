<!-- CREATE COMPANY MODAL -->
<style>
    .company-logo-uploader {
    width: 120px;
}

.company-logo-uploader .filepond--panel-root,
.company-logo-uploader .filepond--item-panel {
    border-radius: 50%;
}

.company-logo-uploader .filepond--drop-label {
    min-height: 120px;
}

</style>
<div id="createCompanyModal"
     class="modal fade"
     tabindex="-1"
     aria-labelledby="createCompanyModalLabel"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <form method="POST"
                  action="{{ route('admin.companies.store') }}"
                  enctype="multipart/form-data">
                @csrf

                <!-- HEADER -->
                <div class="modal-header bg-primary bg-gradient text-white">
                    <h5 class="modal-title fw-semibold" id="createCompanyModalLabel">
                        Add New Company
                    </h5>
                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <!-- AVATAR / LOGO -->
                <div class="bg-light text-center py-4 border-bottom">
                    <div class="company-logo-uploader mx-auto mb-2">
                        <input type="file"
                               name="logo"
                               class="filepond-create"
                               accept="image/png,image/jpeg">
                    </div>
                    <small class="text-muted d-block">
                        Company Logo
                    </small>
                </div>

                <!-- BODY -->
                <div class="modal-body bg-light">

                    <!-- ================= COMPANY INFORMATION ================= -->
                    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
                        <h6 class="text-uppercase text-muted mb-3 fw-semibold">
                            Company Information
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-medium">
                                    Company Name
                                </label>
                                <input type="text"
                                       name="name"
                                       class="form-control form-control-sm"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium">
                                    Contact Person
                                </label>
                                <input type="text"
                                       name="contact_person"
                                       class="form-control form-control-sm">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium">
                                    Contact Number
                                </label>
                                <input type="text"
                                       name="contact_number"
                                       class="form-control form-control-sm">
                            </div>

                            <div class="col-md-8">
                                <label class="form-label fw-medium">
                                    Address
                                </label>
                                <textarea name="address"
                                          class="form-control form-control-sm"
                                          rows="2"></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-medium">
                                    Status
                                </label>
                                <select name="status"
                                        class="form-select form-select-sm">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ================= ADMIN ACCOUNT ================= -->
                    <div class="bg-white rounded-3 p-3 shadow-sm">
                        <h6 class="text-uppercase text-muted mb-3 fw-semibold">
                            Company Admin Account
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">
                                    Admin Email
                                </label>
                                <input type="email"
                                       name="admin_email"
                                       class="form-control form-control-sm"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium">
                                    Admin Password
                                </label>
                                <input type="password"
                                       name="admin_password"
                                       class="form-control form-control-sm"
                                       required>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer bg-white border-top">
                    <button type="button"
                            class="btn btn-light btn-sm"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit"
                            class="btn btn-primary btn-sm px-4">
                        Save Company
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
