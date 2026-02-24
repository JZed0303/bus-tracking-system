<div class="modal fade" id="viewBusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            {{-- Header --}}
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Bus Details</h5>
                    <small class="text-muted">Bus information, status, and current assignment.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body">
                <div class="row g-3">

                    {{-- Photo + Status --}}
                    <div class="col-12 col-md-5">
                        <div class="border rounded-3 p-2 h-100 bg-white">

                            <div class="d-flex align-items-center justify-content-between px-1 pt-1">
                                <span class="fw-semibold">Bus Photo</span>
                                <span id="bus-status" class="badge">—</span>
                            </div>

                            <div class="ratio ratio-16x9 mt-2 rounded-3 overflow-hidden border bg-light">
                                <img id="bus-photo"
                                     src=""
                                     alt="Bus Photo"
                                     class="w-100 h-100"
                                     style="object-fit: cover;">
                            </div>

                        </div>
                    </div>

                    {{-- Details --}}
                    <div class="col-12 col-md-7">
                        <div class="border rounded-3 p-3 h-100 bg-white">

                            {{-- Top stats --}}
                            <div class="d-flex align-items-start justify-content-between">
                                <div>
                                    <div class="text-muted small">Plate Number</div>
                                    <div id="bus-plate" class="fs-4 fw-semibold">—</div>
                                </div>

                                <div class="text-end">
                                    <div class="text-muted small">Capacity</div>
                                    <div class="fs-5 fw-semibold">
                                        <span id="bus-capacity">—</span>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- Assignment --}}
                            <div class="bg-light border rounded-3 p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="fw-semibold">Current Assignment</div>
                                    <small class="text-muted">Live</small>
                                </div>

                                <div id="bus-assignment" class="mt-2">
                                    <span class="text-muted">—</span>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>
