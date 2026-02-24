@extends('layouts.master')

@section('title', 'Driver Trips')

@section('page-title', 'Driver Trips')

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <!-- HEADER -->
    <div class="row mb-3">
        <div class="col">
            <h4 class="mb-0">{{ $driver->user->full_name }}</h4>
            <small class="text-muted">
                Trip History
            </small>
        </div>
        <div class="col text-end">
            <a href="{{ route('admin.drivers.show', $driver) }}"
               class="btn btn-secondary btn-sm">
                Back to Profile
            </a>
        </div>
    </div>

    <!-- TRIPS TABLE -->
    <div class="card">
        <div class="card-body">

            <table id="trips-table"
                   class="table table-bordered table-striped dt-responsive nowrap"
                   style="width:100%">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Bus</th>
                        <th>Route</th>
                        <th>Direction</th>
                        <th>Status</th>
                        <th width="160">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($trips as $trip)
                        <tr>
                            <td>{{ $trip->trip_date->format('M d, Y') }}</td>

                            <td>
                                {{ $trip->assignment->bus->plate_number }}
                            </td>

                            <td>
                                {{ $trip->assignment->route->name }}
                            </td>

                            <td>
                                {{ ucfirst($trip->direction) }}
                            </td>

                            <td>
                                <span class="badge bg-{{ $trip->status === 'completed' ? 'success' : 'warning' }}">
                                    {{ ucfirst($trip->status) }}
                                </span>
                            </td>

                            <td>
                                <a href="{{ route('admin.trips.show', $trip) }}"
                                   class="btn btn-sm btn-info">
                                    Details
                                </a>

                                <a href="{{ route('admin.trips.timeline', $trip) }}"
                                   class="btn btn-sm btn-primary">
                                    Timeline
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No trips recorded for this driver.
                            </td>
                        </tr>
                    @endforelse
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
    $('#trips-table').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, 'desc']]
    });
});
</script>
@endsection
