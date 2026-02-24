<!-- EDIT COMPANY MODAL -->
<div id="editCompanyModal"
     class="modal fade"
     tabindex="-1"
     aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <form id="editCompanyForm"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <!-- HEADER -->
                <div class="modal-header bg-primary bg-gradient text-white">
                    <h5 class="modal-title">
                        Edit Company
                    </h5>
                    <button type="button"
                            class="btn-close btn-close-white"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <!-- AVATAR / LOGO -->
                <div class="modal-body border-bottom text-center pb-4">
                    <img id="editLogoPreview"
                         src=""
                         alt="Company Logo"
                         class="rounded-circle border mb-2"
                         width="96"
                         height="96">

                    <div class="company-logo-uploader mx-auto mt-2">
                        <input type="file"
                               name="logo"
                               class="filepond-edit"
                               accept="image/png,image/jpeg">
                    </div>

                    <small class="text-muted d-block mt-2">
                        Drag & drop or click to replace logo
                    </small>
                </div>

                <!-- BODY -->
                <div class="modal-body">

                    <!-- ================= COMPANY INFORMATION ================= -->
                    <div class="mb-4">
                        <h6 class="text-uppercase text-muted mb-3">
                            Company Information
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Company Name</label>
                                <input type="text"
                                       name="name"
                                       id="editName"
                                       class="form-control form-control-sm"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text"
                                       name="contact_person"
                                       id="editContactPerson"
                                       class="form-control form-control-sm">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Contact Number</label>
                                <input type="text"
                                       name="contact_number"
                                       id="editContactNumber"
                                       class="form-control form-control-sm">
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Address</label>
                                <textarea name="address"
                                          id="editAddress"
                                          class="form-control form-control-sm"
                                          rows="2"></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status"
                                        id="editStatus"
                                        class="form-select form-select-sm">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ================= ADMIN ACCOUNT ================= -->
                    <div class="pt-3 border-top">
                        <h6 class="text-uppercase text-muted mb-3">
                            Company Admin Account
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Admin Email</label>
                                <input type="email"
                                       name="admin_email"
                                       id="editAdminEmail"
                                       class="form-control form-control-sm">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                Password
                                    <small class="text-muted">(optional)</small>
                                </label>
                                <input type="password"
                                       name="admin_password"
                                       class="form-control form-control-sm"
                                       placeholder="Leave blank to keep current">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- FOOTER -->
                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-light btn-sm"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit"
                            class="btn btn-primary btn-sm px-4">
                        Update Company
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
