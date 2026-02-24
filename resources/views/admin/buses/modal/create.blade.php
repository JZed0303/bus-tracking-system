<div class="modal fade" id="createBusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

           <form method="POST"
      action="{{ route('admin.buses.store') }}"
      enctype="multipart/form-data"
      class="needs-validation"
      novalidate>

                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Bus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Plate Number</label>
                            <input type="text"
                                   name="plate_number"
                                   class="form-control @error('plate_number') is-invalid @enderror"
                                   value="{{ old('plate_number') }}"
                                   required>
                            <div class="invalid-feedback">Plate number is required.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bus Code</label>
                            <input type="text"
                                   name="bus_code"
                                   class="form-control @error('bus_code') is-invalid @enderror"
                                   value="{{ old('bus_code') }}"
                                   required>
                            <div class="invalid-feedback">Bus code is required.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number"
                                   name="capacity"
                                   class="form-control @error('capacity') is-invalid @enderror"
                                   value="{{ old('capacity') }}"
                                   min="1"
                                   required>
                            <div class="invalid-feedback">Capacity must be at least 1.</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status"
                                    class="form-select @error('status') is-invalid @enderror"
                                    required>
                                <option value="active">Active</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Brand / Model</label>
                        <input type="text"
                               name="brand_model"
                               class="form-control"
                               value="{{ old('brand_model') }}">
                    </div>
                    <div class="mb-3">
    <label class="form-label">Bus Photo</label>
    <input type="file"
           name="photo"
           class="form-control @error('photo') is-invalid @enderror"
           accept="image/png,image/jpeg,image/jpg,image/webp">
    @error('photo')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <small class="text-muted">Max 2MB. PNG/JPG/WEBP only.</small>
</div>


                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit"
                            class="btn btn-primary">
                        Save Bus
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
