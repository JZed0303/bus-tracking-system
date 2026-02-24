@extends('layouts.master')

@section('title')
Today’s Trips
@endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
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
                    <th>Status</th>
                    <th width="160">Actions</th>
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
                    @endphp
                    <tr>
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
                            {{ $trip->actual_start_time?->format('H:i') ?? '—' }}
                        </td>

                        <td>
                            <span class="badge bg-{{ $statusColor }}">
                                {{ ucfirst($trip->status) }}
                            </span>
                        </td>

                        <td>
                            @if($trip->status === 'ongoing')
                                <a href="{{ route('admin.trips.show', $trip) }}?tab=checkins"
                                   class="btn btn-sm btn-success">
                                    <i class="mdi mdi-eye-outline"></i> Live
                                </a>
                            @else
                                <a href="{{ route('admin.trips.show', $trip) }}"
                                   class="btn btn-sm btn-secondary">
                                    <i class="mdi mdi-eye-outline"></i> View
                                </a>
                            @endif
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
    $('#trips-table').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[4, 'asc']]
    });
});
</script>
@endsection
