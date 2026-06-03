@extends('layouts.master')
@section('title')
    Starter page
@endsection
@section('page-title')
    Starter page
@endsection
@section('body')
    <body data-sidebar="colored">
@endsection
@section('css')
    <style>
        .table {
            border: 1px solid #e9ecef;
        }

        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
            border: 1px solid #edf0f2;
        }
    </style>
@endsection
@section('content')
@php
    $hasAssignedDriver = $assignment->isActive() && $assignment->driver && $assignment->driver->user;
@endphp
<div class="container-fluid">

    {{-- EXECUTIVE SUMMARY --}}
    <div class="card mb-4">
        <div class="card-body row align-items-center">
            <div class="col-md-8">
                <h4 class="mb-1 fw-semibold">
                    {{ $hasAssignedDriver ? $assignment->driver->user->full_name : 'No active driver assigned' }}
                </h4>
                <div class="text-muted">
                    {{ $assignment->company->name ?? '—' }} ·
                    Assignment ID: <strong>#{{ $assignment->id }}</strong>
                </div>
            </div>

            <div class="col-md-4 text-end">
                <span class="badge fs-6 bg-{{ $assignment->status === 'active' ? 'success' : 'secondary' }}">
                    {{ strtoupper($assignment->status) }}
                </span>
            </div>
        </div>
    </div>

    {{-- DRIVER & BUS IMAGES --}}
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Driver Photo</h5>
                    <div class="d-flex align-items-center gap-3">
                        <img
                            src="{{ $hasAssignedDriver ? $assignment->driver->photo_url : asset('build/images/user-placeholder.png') }}"
                            alt="Driver Photo"
                            class="rounded-circle border"
                            width="96"
                            height="96"
                            style="object-fit: cover;"
                        >
                        <div>
                            <div class="fw-semibold">
                                {{ $hasAssignedDriver ? $assignment->driver->user->full_name : 'No active driver assigned' }}
                            </div>
                            <small class="text-muted">
                                {{ $hasAssignedDriver ? ($assignment->driver->license_number ?? 'No license number') : 'No driver details available' }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="mb-3">Bus Photo</h5>
                    <div class="d-flex align-items-center gap-3">
                        <img
                            src="{{ optional($assignment->bus)->photo_url ?? asset('build/images/bus-placeholder.png') }}"
                            alt="Bus Photo"
                            class="rounded border"
                            width="140"
                            height="96"
                            style="object-fit: cover;"
                        >
                        <div>
                            <div class="fw-semibold">{{ optional($assignment->bus)->plate_number ?? 'No bus assigned' }}</div>
                            <small class="text-muted">
                                {{ optional($assignment->bus)->brand_model ?? (optional($assignment->bus)->model ?? 'No model details') }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        {{-- ASSIGNMENT OVERVIEW --}}
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Assignment Overview</h5>

                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th width="160">Driver</th>
                            <td>{{ $hasAssignedDriver ? $assignment->driver->user->full_name : 'No active driver assigned' }}</td>
                        </tr>
                        <tr>
                            <th>Bus Plate Number</th>
                            <td>{{ $assignment->bus->plate_number ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Assigned Route</th>
                            <td>{{ $assignment->route->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Company</th>
                            <td>{{ $assignment->company->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-{{ $assignment->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($assignment->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Assignment Leg</th>
                            <td>
                                @php($leg = $assignment->leg ?? 'both')
                                <span class="badge bg-{{ $leg === 'both' ? 'dark' : ($leg === 'pickup' ? 'info' : 'primary') }}">
                                    {{ strtoupper($leg) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- DRIVER & BUS DETAILS --}}
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Driver & Fleet Information</h5>

                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th width="160">Driver Contact</th>
                            <td>{{ $hasAssignedDriver ? ($assignment->driver->user->contact_number ?? '—') : '—' }}</td>
                        </tr>
                        <tr>
                            <th>Driver License No.</th>
                            <td>{{ $hasAssignedDriver ? ($assignment->driver->license_number ?? '—') : '—' }}</td>
                        </tr>
                        <tr>
                            <th>Bus Model</th>
                            <td>{{ $assignment->bus->model ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Bus Capacity</th>
                            <td>{{ $assignment->bus->capacity ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Bus Status</th>
                            <td>
                                <span class="badge bg-info">
                                    {{ ucfirst($assignment->bus->status ?? 'operational') }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- VALIDITY & LIFECYCLE --}}
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Assignment Validity</h5>

                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th width="160">Effective From</th>
                            <td>{{ $assignment->effective_from->format('M d, Y') }}</td>
                        </tr>
                        <tr>
                            <th>Effective To</th>
                            <td>
                                {{ $assignment->effective_to
                                    ? $assignment->effective_to->format('M d, Y')
                                    : 'Ongoing' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Duration</th>
                            <td>
                                {{ $assignment->effective_to
                                    ? $assignment->effective_from->diffInDays($assignment->effective_to).' days'
                                    : 'Indefinite' }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- SYSTEM METADATA (HM / ADMIN) --}}
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-3">System Metadata</h5>

                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th width="160">Created At</th>
                            <td>{{ $assignment->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        <tr>
                            <th>Last Updated</th>
                            <td>{{ $assignment->updated_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        <tr>
                            <th>Assigned By</th>
                            <td>{{ $assignment->createdBy->full_name ?? 'System' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- ACTIONS --}}
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-wrap gap-2">

                    <a href="{{ route('company.assignments.timeline', $assignment->id) }}"
                       class="btn btn-outline-primary">
                        <i class="mdi mdi-timeline"></i> View Assignment Timeline
                    </a>


                    <a href="{{ route('company.assignments.index') }}"
                       class="btn btn-outline-secondary ms-auto">
                        Back to Assignments
                    </a>

                </div>
            </div>
        </div>

    </div>
</div>

@endsection
@section('scripts')
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
