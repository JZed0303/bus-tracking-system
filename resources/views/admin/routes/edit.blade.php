@extends('layouts.master')

@section('title', 'Edit Route')

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<style>
/* ============================
   LAYOUT & SPACING REFINEMENTS
   ============================ */

.equal-height-row {
    display: flex;
    align-items: stretch;
}

.equal-height-row > div {
    display: flex;
}

.equal-height-row .card {
    flex: 1;
    display: flex;
    flex-direction: column;
}

/* Page rhythm */
.container-fluid {
    padding-top: 1.25rem;
    padding-bottom: 1.5rem;
}

/* Card consistency */
.card {
    border-radius: 8px;
}

.card-body {
    padding: 1rem 1.25rem;
}

.card-header {
    padding: 0.6rem 1.25rem;
    background-color: #f8f9fa;
}

/* Map */
#route-map {
    height: 520px;
    border-radius: 8px;
}

/* Section blocks */
.form-section {
    margin-bottom: 1.25rem;
}

.form-section:last-child {
    margin-bottom: 0;
}

.form-section h6 {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #6c757d;
    margin-bottom: 0.5rem;
    letter-spacing: 0.04em;
}

/* Inputs */
.form-control-sm,
.form-select-sm {
    padding: 0.35rem 0.6rem;
    font-size: 13px;
}

textarea.form-control-sm {
    resize: none;
}

/* Buttons */
.btn-sm {
    padding: 0.35rem 0.65rem;
}

/* Table */
.table th {
    font-size: 12px;
    font-weight: 600;
}

.table td {
    font-size: 12px;
    padding: 0.55rem;
    vertical-align: middle;
}

/* Stop badge */
.stop-badge {
    background: #ffc107;
    color: #000;
    padding: 4px 7px;
    border-radius: 50%;
    font-size: 11px;
    font-weight: 600;
}
</style>
@endsection

@section('page-title', 'Routes')

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

<!-- HEADER -->
<div class="row mb-4 align-items-center">
    <div class="col">
        <h4 class="mb-1">Edit Route</h4>
        <small class="text-muted">
            Update route definition and pickup planning
        </small>
    </div>
    <div class="col-auto">
        <a href="{{ route('admin.routes.show', $route->id) }}"
           class="btn btn-outline-secondary btn-sm">
            ← Back to Route
        </a>
    </div>
</div>

<!-- FORM + MAP -->
<div class="row g-3 equal-height-row">

    <!-- FORM -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.routes.update', $route->id) }}">
                    @csrf
                    @method('PUT')

                    <!-- ROUTE INFO -->
                    <div class="form-section">
                        <h6>Route Information</h6>

                        <input name="name"
                               class="form-control form-control-sm mb-2"
                               value="{{ old('name', $route->name) }}"
                               required>

                        <select name="company_id" class="form-select form-select-sm">
                            <option value="">Shared Route</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}"
                                    @selected($route->company_id == $company->id)>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- START POINT -->
                    <div class="form-section">
                        <h6>Start Point</h6>

                        <input id="start_search"
                               class="form-control form-control-sm mb-1"
                               placeholder="Search start location">

                        <div id="start_suggestions"
                             class="list-group position-absolute w-100"
                             style="z-index:1000"></div>

                        <input id="start_location"
                               name="start_location"
                               class="form-control form-control-sm mb-1"
                               value="{{ $route->start_location }}"
                               required>

                        <div class="row g-1 mb-1">
                            <div class="col-6">
                                <input id="start_lat"
                                       name="start_lat"
                                       class="form-control form-control-sm"
                                       value="{{ $route->start_lat }}">
                            </div>
                            <div class="col-6">
                                <input id="start_lng"
                                       name="start_lng"
                                       class="form-control form-control-sm"
                                       value="{{ $route->start_lng }}">
                            </div>
                        </div>

                        <button type="button"
                                id="clear-start"
                                class="btn btn-outline-danger btn-sm w-100">
                            Clear Start
                        </button>
                    </div>

                    <!-- END POINT -->
                    <div class="form-section">
                        <h6>End Point</h6>

                        <input id="end_search"
                               class="form-control form-control-sm mb-1"
                               placeholder="Search end location">

                        <div id="end_suggestions"
                             class="list-group position-absolute w-100"
                             style="z-index:1000"></div>

                        <input id="end_location"
                               name="end_location"
                               class="form-control form-control-sm mb-1"
                               value="{{ $route->end_location }}"
                               required>

                        <div class="row g-1 mb-1">
                            <div class="col-6">
                                <input id="end_lat"
                                       name="end_lat"
                                       class="form-control form-control-sm"
                                       value="{{ $route->end_lat }}">
                            </div>
                            <div class="col-6">
                                <input id="end_lng"
                                       name="end_lng"
                                       class="form-control form-control-sm"
                                       value="{{ $route->end_lng }}">
                            </div>
                        </div>

                        <button type="button"
                                id="clear-end"
                                class="btn btn-outline-danger btn-sm w-100">
                            Clear End
                        </button>
                    </div>

                    <!-- DETAILS -->
                    <div class="form-section">
                        <h6>Details</h6>

                        <textarea name="description"
                                  class="form-control form-control-sm mb-2"
                                  rows="2">{{ $route->description }}</textarea>

                        <select name="status" class="form-select form-select-sm mb-3">
                            <option value="active" @selected($route->status === 'active')>
                                Active
                            </option>
                            <option value="inactive" @selected($route->status === 'inactive')>
                                Inactive
                            </option>
                        </select>

                        <input type="hidden" name="stops_json" id="stops_json">

                        <button class="btn btn-primary btn-sm w-100">
                            Update Route
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MAP -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header py-2">
                <strong>Route Map</strong>
                <small class="text-muted d-block">
                    Drag markers to update route
                </small>
            </div>
            <div class="card-body p-1">
                <div id="route-map"></div>
            </div>
        </div>
    </div>
</div>

<!-- STOPS TABLE -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header py-2">
                <strong>Pickup Stops</strong>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40">#</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Place</th>
                            <th width="60">Action</th>
                        </tr>
                    </thead>
                    <tbody id="stops-list"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</div>
@endsection

@section('scripts')
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>

<script>
window.EXISTING_ROUTE = {
    start: {
        lat: {{ $route->start_lat }},
        lng: {{ $route->start_lng }},
        address: @json($route->start_location),
    },
    end: {
        lat: {{ $route->end_lat }},
        lng: {{ $route->end_lng }},
        address: @json($route->end_location),
    },
    stops: @json($stopsForJs)
};
</script>

@include('admin.routes.route-map-script')
@endsection
