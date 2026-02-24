@extends('layouts.master')

@section('title')
Trip Details
@endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
@endsection

@section('page-title')
Trip Details
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
@php
    $tab = request()->query('tab', 'timeline');
@endphp

<div class="container-fluid">

    <!-- PAGE CONTEXT -->
    <div class="row mb-3">
        <div class="col">
            <h4 class="mb-0">Trip Details</h4>
            <p class="text-muted mb-0">
                View timeline, employee check-ins, and GPS playback for this trip.
            </p>
        </div>
        <div class="col text-end">
            <a href="{{ route('admin.trips.today') }}" class="btn btn-light btn-sm">
                <i class="mdi mdi-arrow-left"></i> Back to Trips
            </a>
        </div>
    </div>

    <!-- TRIP HEADER -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">

                <!-- Bus -->
                <div class="col-md-4 d-flex align-items-start">
                    <div class="me-2 text-primary">
                        <i class="mdi mdi-bus font-size-24"></i>
                    </div>
                    <div>
                        <strong>Bus</strong>
                        <div class="text-muted">
                            {{ optional($trip->assignment->bus)->plate_number ?? '—' }}
                        </div>
                    </div>
                </div>

                <!-- Driver -->
                <div class="col-md-4 d-flex align-items-start">
                    <div class="me-2 text-success">
                        <i class="mdi mdi-account-tie font-size-24"></i>
                    </div>
                    <div>
                        <strong>Driver</strong>
                        <div class="text-muted">
                            {{ optional($trip->assignment->driver?->user)->full_name ?? '—' }}
                        </div>
                    </div>
                </div>

                <!-- Route -->
                <div class="col-md-4 d-flex align-items-start">
                    <div class="me-2 text-info">
                        <i class="mdi mdi-map-marker-path font-size-24"></i>
                    </div>
                    <div>
                        <strong>Route</strong>
                        <div class="text-muted">
                            {{ optional($trip->assignment->route)->name ?? '—' }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- TABS -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'timeline' ? 'active' : '' }}"
               href="{{ route('admin.trips.show', $trip) }}?tab=timeline">
                <i class="mdi mdi-timeline-outline"></i> Timeline
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ $tab === 'checkins' ? 'active' : '' }}"
               href="{{ route('admin.trips.show', $trip) }}?tab=checkins">
                <i class="mdi mdi-account-clock-outline"></i> Employee Check-ins
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ $tab === 'gps' ? 'active' : '' }}"
               href="{{ route('admin.trips.show', $trip) }}?tab=gps">
                <i class="mdi mdi-map-marker-path"></i> GPS Playback
            </a>
        </li>
    </ul>

    <!-- TAB CONTENT -->
    <div class="card">
        <div class="card-body">

            {{-- TIMELINE --}}
            @if($tab === 'timeline')
                <h4 class="card-title mb-3">Trip Timeline</h4>

                <table class="table table-bordered table-sm">
                    <tr>
                        <th width="200">Scheduled Start</th>
                        <td>{{ $trip->scheduled_start_time?->format('M d, Y H:i') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Actual Start</th>
                        <td>{{ $trip->actual_start_time?->format('M d, Y H:i') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge bg-success">
                                {{ ucfirst($trip->status) }}
                            </span>
                        </td>
                    </tr>
                </table>
            @endif

            {{-- CHECKINS --}}
          @if($tab === 'checkins')
    <h4 class="card-title mb-3">Employee Check-ins</h4>

    @if($trip->checkins->isEmpty())
        <p class="text-muted mb-0">No check-ins recorded.</p>
    @else
        <table id="checkins-table"
               class="table table-bordered table-striped table-sm dt-responsive nowrap"
               style="width:100%">

            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Type</th>
                    <th width="220">Scanned At</th>
                </tr>
            </thead>

            <tbody>
            @foreach($trip->checkins as $checkin)
                <tr>
                    <td>
                        {{ optional($checkin->employee?->user)->full_name ?? '—' }}
                    </td>
                    <td>
                        @if($checkin->scan_type === 'checkin')
                            <span class="badge bg-success">
                                <i class="mdi mdi-login"></i> Check-in
                            </span>
                        @else
                            <span class="badge bg-secondary">
                                <i class="mdi mdi-logout"></i> Check-out
                            </span>
                        @endif
                    </td>
                    <td>
                        {{ $checkin->scan_time
                            ->timezone(config('app.timezone'))
                            ->format('M d, Y H:i') }}
                    </td>
                </tr>
            @endforeach
            </tbody>

        </table>
    @endif
@endif


            {{-- GPS --}}
            @if($tab === 'gps')
                <h4 class="card-title mb-3">GPS Playback</h4>

                <div id="gps-map"
                     class="border rounded bg-light d-flex align-items-center justify-content-center text-muted"
                     style="height: 400px;">
                    GPS map will render here
                </div>
            @endif

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
    @if($tab === 'checkins')
        $('#checkins-table').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[2, 'asc']]
        });
    @endif
});
</script>
@endsection
