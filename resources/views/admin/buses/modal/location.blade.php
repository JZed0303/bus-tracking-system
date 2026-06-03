<style>
    #busLocationModal .modal-dialog {
        max-width: 820px;
    }

    #busLocationModal .modal-content {
        overflow: hidden;
    }

    #busLocationModal .modal-header {
        padding: 1.15rem 1.25rem 1rem;
        border-bottom: 1px solid #e9edf4;
    }

    #busLocationModal .modal-title {
        font-size: 1.7rem;
        font-weight: 700;
        line-height: 1.15;
        color: #253041;
    }

    #busLocationModal .modal-header small {
        display: inline-block;
        margin-top: 0.35rem;
        font-size: 0.92rem;
    }

    #busLocationModal .modal-body {
        padding: 1rem 1.25rem 1.1rem;
        background: #f8fafc;
    }

    #busLocationModal .bus-location-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-bottom: 0.9rem;
    }

    #busLocationModal .bus-location-coords {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.9rem;
        border-radius: 999px;
        background: #cfe3fb;
        color: #28466d;
        font-weight: 600;
        font-size: 0.92rem;
    }

    #busLocationModal .bus-location-updated {
        color: #6b7280;
        font-size: 0.88rem;
        text-align: right;
    }

    #busLocationModal .bus-location-map-shell {
        border: 1px solid #dbe4ef;
        border-radius: 16px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
    }

    #busLocationModal #bus-location-map {
        height: 395px;
        border: 0 !important;
        border-radius: 0 !important;
    }

    #busLocationModal .leaflet-popup-content-wrapper {
        padding: 0;
        border-radius: 18px;
        background: transparent;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.18);
    }

    #busLocationModal .leaflet-popup-content {
        margin: 0;
    }

    #busLocationModal .leaflet-popup-tip-container {
        margin-top: -1px;
    }

    #busLocationModal .leaflet-popup-tip {
        background: #ffffff;
        box-shadow: none;
    }

    #busLocationModal .bus-location-popup {
        min-width: 220px;
        padding: 1rem 1rem 0.95rem;
        border: 1px solid rgba(207, 226, 255, 0.95);
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        color: #1f2937;
    }

    #busLocationModal .bus-location-popup__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-bottom: 0.45rem;
        padding: 0.22rem 0.55rem;
        border-radius: 999px;
        background: #dbeafe;
        color: #29507d;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    #busLocationModal .bus-location-popup__title {
        margin: 0;
        font-size: 1.28rem;
        font-weight: 800;
        line-height: 1.15;
        color: #1f2937;
    }

    #busLocationModal .bus-location-popup__label {
        margin-top: 0.2rem;
        font-size: 0.84rem;
        color: #64748b;
    }

    #busLocationModal .bus-location-popup__coords {
        margin-top: 0.75rem;
        padding-top: 0.7rem;
        border-top: 1px solid #e5edf6;
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.4;
    }

    #busLocationModal .modal-footer {
        padding: 0.95rem 1.25rem 1.15rem;
        border-top: 1px solid #e9edf4;
        background: #ffffff;
    }

    @media (max-width: 576px) {
        #busLocationModal .modal-header,
        #busLocationModal .modal-body,
        #busLocationModal .modal-footer {
            padding-left: 1rem;
            padding-right: 1rem;
        }

        #busLocationModal .modal-title {
            font-size: 1.4rem;
        }

        #busLocationModal .bus-location-updated {
            width: 100%;
            text-align: left;
        }

        #busLocationModal #bus-location-map {
            height: 330px;
        }
    }
</style>

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
                <div class="bus-location-meta">
                    <span id="bus-location-coords" class="bus-location-coords">
                        <i class="mdi mdi-crosshairs-gps"></i>
                        Coordinates: —
                    </span>
                    <span id="bus-location-time" class="bus-location-updated">Updated: —</span>
                </div>

                <div id="bus-location-empty" class="alert alert-warning d-none mb-2">
                    No GPS location found for this bus yet.
                </div>

                <div class="bus-location-map-shell">
                    <div id="bus-location-map"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>
