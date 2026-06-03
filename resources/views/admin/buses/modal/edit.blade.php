<div class="modal fade" id="editBusModal{{ $bus->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <form method="POST"
                  action="{{ request()->routeIs('company.*') ? route('company.buses.update', $bus) : route('admin.buses.update', $bus) }}"
                  enctype="multipart/form-data"
                  class="needs-validation bus-edit-form"
                  style="display: block; width: 100%;"
                  novalidate>
                @csrf
                @method('PUT')

                {{-- Header --}}
                <div class="modal-header d-flex align-items-start justify-content-between">
                    <div class="pe-3">
                        <h5 class="modal-title mb-0">Edit Bus</h5>
                        <small class="text-muted">Update bus details and photo.</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                {{-- Body --}}
                <div class="modal-body">

                    {{-- PHOTO --}}
                    <div class="border rounded-3 p-3 mb-3 bg-light">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-semibold">Bus Photo</div>
                            <small class="text-muted">PNG/JPG/WEBP • Max 2MB</small>
                        </div>

                        <div class="row g-3 align-items-center">
                            <div class="col-12 col-md-auto text-center text-md-start">
                                <img src="{{ $bus->photo_url }}"
                                     alt="Bus Photo"
                                     class="rounded border bus-photo-preview"
                                     data-bus-id="{{ $bus->id }}"
                                     width="140"
                                     height="90"
                                     style="object-fit: cover;">
                            </div>

                            <div class="col-12 col-md">
                                <input type="file"
                                       name="photo"
                                       class="form-control bus-photo-input @error('photo') is-invalid @enderror"
                                       data-bus-id="{{ $bus->id }}"
                                       accept="image/png,image/jpeg,image/jpg,image/webp">
                                @error('photo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Leave empty to keep the current photo.</div>
                            </div>
                        </div>
                    </div>

                    {{-- DETAILS --}}
                    <div class="border rounded-3 p-3">
                        <div class="fw-semibold mb-2">Bus Information</div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Plate Number</label>
                                <input type="text"
                                       name="plate_number"
                                       class="form-control"
                                       value="{{ $bus->plate_number }}"
                                       required>
                                <div class="invalid-feedback">Plate number is required.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Capacity</label>
                                <input type="number"
                                       name="capacity"
                                       class="form-control"
                                       value="{{ $bus->capacity }}"
                                       min="1"
                                       required>
                                <div class="invalid-feedback">Capacity must be at least 1.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="active" @selected($bus->status === 'active')>Active</option>
                                    <option value="maintenance" @selected($bus->status === 'maintenance')>Maintenance</option>
                                    <option value="inactive" @selected($bus->status === 'inactive')>Inactive</option>
                                </select>
                                <div class="invalid-feedback">Status is required.</div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Brand / Model</label>
                                <input type="text"
                                       name="brand_model"
                                       class="form-control"
                                       value="{{ $bus->brand_model }}">
                                <div class="form-text">Optional (e.g., Isuzu NQR).</div>
                            </div>
                        </div>

                        {{-- OPTIONAL: Credential update --}}
                        <hr class="my-3">

                        <div class="fw-semibold mb-1">Bus Code (Optional)</div>
                        <div class="text-muted small mb-2">
                            Leave blank to keep the current bus code.
                        </div>

                        <input type="password"
                               name="bus_code"
                               class="form-control"
                               placeholder="Enter new bus code (optional)">
                    </div>

                </div>

                {{-- Footer --}}
                <div class="modal-footer d-flex justify-content-end gap-2 flex-wrap">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
