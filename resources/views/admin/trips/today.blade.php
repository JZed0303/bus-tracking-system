@extends('layouts.master')

@section('title')
Today’s Trips
@endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
<style>
    #trips-table_filter {
        display: none;
    }
</style>
@endsection

@section('page-title')
Today’s Trips
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <!-- PAGE CONTEXT -->
    <div class="row mb-3">
        <div class="col">
            <h4 class="mb-0">Today’s Trips</h4>
            <p class="text-muted mb-0">
                Active and completed trips for today.
            </p>
        </div>
        <div class="col text-end">
            <a href="{{ route('admin.trips.active') }}"
               class="btn btn-success btn-sm">
                <i class="mdi mdi-play-circle-outline"></i> View Active Trip
            </a>
        </div>
    </div>

    <!-- TRIPS TABLE -->
    <div class="card">
        <div class="card-body">
            @php
                $routeOptions = $trips
                    ->map(fn ($trip) => optional($trip->assignment->route)->name)
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();
            @endphp

            <div class="row g-2 mb-3 align-items-center">
                <div class="col-lg-5">
                    <div class="d-flex gap-2" role="group" aria-label="Trip direction tabs">
                        <button type="button" class="btn btn-outline-primary btn-direction active" data-direction="all">
                            All
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-direction" data-direction="pickup">
                            Pickup
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-direction" data-direction="dropoff">
                            Drop-off
                        </button>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select id="route-filter" class="form-select form-select-sm">
                                <option value="all">All Routes</option>
                                @foreach($routeOptions as $routeName)
                                    <option value="{{ $routeName }}">{{ $routeName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="status-filter" class="form-select form-select-sm">
                                <option value="all">All Status</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <input id="trip-search" type="search" class="form-control form-control-sm"
                                   placeholder="Search bus, driver, route...">
                        </div>
                    </div>
                </div>
            </div>

            <table id="trips-table"
                   class="table table-bordered table-striped dt-responsive nowrap"
                   style="width:100%">

                <thead>
                <tr>
                    <th>Bus</th>
                    <th>Driver</th>
                    <th>Route</th>
                    <th>Direction</th>
                    <th>Started</th>
                    <th>Ended</th>
                    <th>Onboard</th>
                    <th>Capacity</th>
                    <th>Available</th>
                    <th>Status</th>
                    <th width="240">Actions</th>
                </tr>
                </thead>

                <tbody>
                @foreach($trips as $trip)
                    @php
                        $statusColor = match ($trip->status) {
                            'ongoing'   => 'success',
                            'completed' => 'primary',
                            'cancelled' => 'danger',
                            default     => 'secondary',
                        };
                        $routeName = optional($trip->assignment->route)->name ?? '—';
                        $directionKey = $trip->direction ?? 'unknown';
                    @endphp
                    <tr data-direction="{{ $directionKey }}"
                        data-route="{{ strtolower($routeName) }}"
                        data-status="{{ strtolower($trip->status) }}">
                        <td class="fw-semibold">
                            {{ optional($trip->assignment->bus)->plate_number ?? '—' }}
                        </td>

                        <td>
                            {{ optional($trip->assignment->driver?->user)->full_name ?? '—' }}
                        </td>

                        <td>
                            {{ optional($trip->assignment->route)->name ?? '—' }}
                        </td>

                        <td>
                            <span class="badge bg-info">
                                {{ $trip->direction_label }}
                            </span>
                        </td>

                        <td>
                            {{ $trip->actual_start_time?->timezone('Asia/Manila')->format('h:iA') ?? '—' }}
                        </td>

                        <td>
                            {{ $trip->actual_end_time?->timezone('Asia/Manila')->format('h:iA') ?? '—' }}
                        </td>

                        <td>{{ (int) ($trip->onboard_count ?? 0) }}</td>

                        <td>{{ (int) ($trip->bus_capacity ?? 0) }}</td>

                        <td>
                            @php($available = (int) ($trip->available_capacity ?? 0))
                            <span class="badge bg-{{ $available > 0 ? 'success' : 'danger' }}">
                                {{ $available }}
                            </span>
                        </td>

                        <td>
                            <span class="badge bg-{{ $statusColor }}">
                                {{ ucfirst($trip->status) }}
                            </span>
                        </td>

                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                @if($trip->status === 'ongoing')
                                    <a href="{{ route('admin.live-map', ['trip' => $trip->id]) }}"
                                       class="btn btn-sm btn-success">
                                        <i class="mdi mdi-map-marker-radius-outline"></i> Live
                                    </a>
                                @endif

                                <a href="{{ route('admin.trips.show', $trip) }}"
                                   class="btn btn-sm btn-secondary">
                                    <i class="mdi mdi-file-document-outline"></i> Trip Details
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>

            </table>

        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>

<script>
$(function () {
    const table = $('#trips-table').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[4, 'asc']],
        dom: 'rtip'
    });

    let directionFilter = 'all';

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (settings.nTable.id !== 'trips-table') {
            return true;
        }

        const rowNode = table.row(dataIndex).node();
        const rowDirection = (rowNode.getAttribute('data-direction') || 'all').toLowerCase();
        const rowRoute = (rowNode.getAttribute('data-route') || '').toLowerCase();
        const rowStatus = (rowNode.getAttribute('data-status') || '').toLowerCase();

        const selectedRoute = ($('#route-filter').val() || 'all').toLowerCase();
        const selectedStatus = ($('#status-filter').val() || 'all').toLowerCase();

        const directionMatch = directionFilter === 'all' || rowDirection === directionFilter;
        const routeMatch = selectedRoute === 'all' || rowRoute === selectedRoute.toLowerCase();
        const statusMatch = selectedStatus === 'all' || rowStatus === selectedStatus;

        return directionMatch && routeMatch && statusMatch;
    });

    $('.btn-direction').on('click', function () {
        $('.btn-direction').removeClass('active');
        $(this).addClass('active');
        directionFilter = ($(this).data('direction') || 'all').toLowerCase();
        table.draw();
    });

    $('#route-filter, #status-filter').on('change', function () {
        table.draw();
    });

    $('#trip-search').on('keyup search', function () {
        table.search(this.value).draw();
    });
});
</script>
@endsection
