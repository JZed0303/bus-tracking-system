@extends('layouts.master')

@section('title')
    Bus Management
@endsection

@section('css')
    <link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endsection

@section('page-title')
    Buses
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <!-- PAGE HEADER -->
    <div class="row mb-3">
        <div class="col">
            <h4 class="card-title mb-1">Bus List</h4>
            <p class="card-title-desc">
                Manage buses, capacity, status, and current assignments.
            </p>
        </div>
        <div class="col text-end">
            <button class="btn btn-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#createBusModal">
                <i class="mdi mdi-plus"></i> Add Bus
            </button>
        </div>
    </div>

    <!-- BUS TABLE -->
    <div class="card">
        <div class="card-body">

            <table id="buses-table"
                   class="table table-bordered table-striped dt-responsive nowrap"
                   style="width:100%">

         <thead>
<tr>
    <th>Photo</th>
    <th>Plate Number</th>
    <th>Capacity</th>
    <th>Status</th>
    <th>Current Assignment</th>
    <th width="190">Actions</th>
</tr>
</thead>


                <tbody>
                @foreach($buses as $bus)
                    @php
                        $activeAssignment = $bus->assignments
                            ->where('status', 'active')
                            ->first();
                    @endphp
                    <tr>
                        <td>
        <img src="{{ $bus->photo_url }}"
             alt="Bus Photo"
             class="rounded"
             width="60"
             height="40"
             style="object-fit: cover;">
    </td>

                        <td class="fw-semibold">{{ $bus->plate_number }}</td>

                        <td>{{ $bus->capacity }}</td>

                        <td>
                            <span class="badge bg-{{ $bus->status === 'active'
                                ? 'success'
                                : ($bus->status === 'maintenance' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($bus->status) }}
                            </span>
                        </td>

                        <td>
                            @if($activeAssignment)
                                <strong>{{ $activeAssignment->driver->user->full_name }}</strong><br>
                                <small class="text-muted">
                                    {{ $activeAssignment->route->name }}
                                </small>
                            @else
                                <span class="text-muted">Unassigned</span>
                            @endif
                        </td>

                        <td>
                            <div class="btn-group btn-group-sm">

                                <!-- VIEW -->
                                <button class="btn btn-primary btn-view-bus"
                                        data-id="{{ $bus->id }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewBusModal">
                                    <i class="mdi mdi-eye-outline"></i>
                                </button>

                                <!-- LAST LOCATION -->
                                <button class="btn btn-info btn-view-bus-location"
                                        data-id="{{ $bus->id }}"
                                        data-plate="{{ $bus->plate_number }}"
                                        data-bs-toggle="modal"
                                        data-bs-target="#busLocationModal">
                                    <i class="mdi mdi-map-marker-radius-outline"></i>
                                </button>

                                <!-- EDIT -->
                                <button class="btn btn-warning"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editBusModal{{ $bus->id }}">
                                    <i class="mdi mdi-pencil-outline"></i>
                                </button>

                            </div>

                            {{-- Edit Modal --}}
                            @include('admin.buses.modal.edit')
                        </td>
                    </tr>
                @endforeach
                </tbody>

            </table>

        </div>
    </div>

</div>

{{-- Create + View Modals --}}
@include('admin.buses.modal.create')
@include('admin.buses.modal.show')
@include('admin.buses.modal.location')

@endsection

@section('scripts')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
$(function () {

    // ============================
    // CACHE VIEW MODAL ELEMENTS
    // ============================
    const $busPlate      = $('#bus-plate');
    const $busCapacity   = $('#bus-capacity');
    const $busCode       = $('#bus-code');
    const $busStatus     = $('#bus-status');
    const $busAssignment = $('#bus-assignment');
    const $busPhoto      = $('#bus-photo');
    const $busLocationSubtitle = $('#bus-location-subtitle');
    const $busLocationCoords = $('#bus-location-coords');
    const $busLocationTime = $('#bus-location-time');
    const $busLocationEmpty = $('#bus-location-empty');
    const $busLocationMap = $('#bus-location-map');

    let locationMap = null;
    let locationMarker = null;

    function ensureLocationMap() {
        if (locationMap) return locationMap;

        locationMap = L.map('bus-location-map', {
            zoomControl: true,
        }).setView([14.5995, 120.9842], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(locationMap);

        return locationMap;
    }

    function resetLocationModal(plate) {
        $busLocationSubtitle.text(plate ? `Bus: ${plate}` : 'Bus: —');
        $busLocationCoords.text('Coordinates: —');
        $busLocationTime.text('Updated: —');
        $busLocationEmpty.addClass('d-none');
        $busLocationMap.removeClass('d-none');
    }

    function renderLastLocation(plate, lastLocation) {
        if (!lastLocation || Number.isNaN(Number(lastLocation.latitude)) || Number.isNaN(Number(lastLocation.longitude))) {
            $busLocationEmpty.removeClass('d-none').text('No GPS location found for this bus yet.');
            $busLocationMap.addClass('d-none');
            return;
        }

        const lat = Number(lastLocation.latitude);
        const lng = Number(lastLocation.longitude);
        const trackedAt = lastLocation.tracked_at ? new Date(lastLocation.tracked_at).toLocaleString() : 'Unknown';

        $busLocationCoords.text(`Coordinates: ${lat.toFixed(6)}, ${lng.toFixed(6)}`);
        $busLocationTime.text(`Updated: ${trackedAt}`);
        $busLocationEmpty.addClass('d-none');
        $busLocationMap.removeClass('d-none');

        const map = ensureLocationMap();
        map.setView([lat, lng], 16);
        setTimeout(() => map.invalidateSize(), 80);

        if (locationMarker) {
            locationMarker.remove();
        }

        locationMarker = L.marker([lat, lng]).addTo(map);
        locationMarker.bindPopup(`<strong>${plate || 'Bus'}</strong><br>${lat.toFixed(6)}, ${lng.toFixed(6)}`).openPopup();
    }

    // ============================
    // VIEW BUS (AJAX)
    // ============================
    $(document).on('click', '.btn-view-bus', function () {

        const busId = $(this).data('id');

        // Optional: reset modal content while loading
        $busPlate.text('—');
        $busCapacity.text('—');
        $busCode.text('—');
        $busAssignment.html('<span class="text-muted">Loading…</span>');
        $busPhoto.attr('src', '/build/images/bus-placeholder.png');
        $busStatus.removeClass().addClass('badge').text('—');

        $.getJSON(`/admin/buses/${busId}/json`)
            .done(function (bus) {

                $busPlate.text(bus.plate_number);
                $busCapacity.text(bus.capacity);
                $busCode.text(bus.bus_code ?? '—');

                // PHOTO
                $busPhoto.attr(
                    'src',
                    bus.photo_url ?? '/build/images/bus-placeholder.png'
                );

                // STATUS
                const statusClass =
                    bus.status === 'active'
                        ? 'bg-success'
                        : bus.status === 'maintenance'
                            ? 'bg-warning'
                            : 'bg-secondary';

                $busStatus
                    .removeClass()
                    .addClass(`badge ${statusClass}`)
                    .text(bus.status.toUpperCase());

                // ASSIGNMENT
                const activeAssignment = bus.assignments?.find(a => a.status === 'active');

                if (activeAssignment) {
                    $busAssignment.html(
                        `<strong>${activeAssignment.driver.user.full_name}</strong><br>
                         <small class="text-muted">${activeAssignment.route.name}</small>`
                    );
                } else {
                    $busAssignment.html('<span class="text-muted">Unassigned</span>');
                }
            })
            .fail(function () {
                $busAssignment.html(
                    '<span class="text-danger">Failed to load bus details.</span>'
                );
            });
    });

    // ============================
    // VIEW LAST LOCATION (AJAX + MAP MODAL)
    // ============================
    $(document).on('click', '.btn-view-bus-location', function () {
        const busId = $(this).data('id');
        const plate = $(this).data('plate');

        resetLocationModal(plate);

        $.getJSON(`/admin/buses/${busId}/json`)
            .done(function (bus) {
                renderLastLocation(bus.plate_number || plate, bus.last_location || null);
            })
            .fail(function () {
                $busLocationEmpty
                    .removeClass('d-none')
                    .text('Failed to load latest location for this bus.');
                $busLocationMap.addClass('d-none');
            });
    });

    $('#busLocationModal').on('shown.bs.modal', function () {
        if (locationMap) {
            locationMap.invalidateSize();
        }
    });

    // ============================
    // DATATABLE INIT
    // ============================
    $('#buses-table').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[1, 'asc']], // Plate Number
        columnDefs: [
            { targets: [0, 5], orderable: false }, // Photo + Actions
        ],
    });

});

</script>
<script>
$(function () {
    $(document).on('change', '.bus-photo-input', function () {
        const file = this.files && this.files[0];
        if (!file) return;

        const busId = $(this).data('bus-id');
        const $img = $(`.bus-photo-preview[data-bus-id="${busId}"]`);

        // preview selected image
        const url = URL.createObjectURL(file);
        $img.attr('src', url);

        // cleanup object URL when image loads
        $img.on('load', function () {
            URL.revokeObjectURL(url);
        });
    });
});
</script>


@endsection
