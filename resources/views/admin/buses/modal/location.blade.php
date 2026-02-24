<div class="modal fade" id="busLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Last Bus Location</h5>
                    <small id="bus-location-subtitle" class="text-muted">—</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <span id="bus-location-coords" class="badge bg-info-subtle text-dark">Coordinates: —</span>
                    <span id="bus-location-time" class="text-muted small">Updated: —</span>
                </div>

                <div id="bus-location-empty" class="alert alert-warning d-none mb-2">
                    No GPS location found for this bus yet.
                </div>

                <div id="bus-location-map" class="rounded border" style="height: 380px;"></div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>
