@extends('layouts.master')

@section('title', 'Driver Assignment')

@section('page-title', 'Driver Assignment')

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
                {{ $driver->company->name ?? 'No company' }}
            </small>
        </div>
        <div class="col text-end">
            <a href="{{ route('admin.drivers.show', $driver->id) }}"
               class="btn btn-secondary btn-sm">
                Back to Profile
            </a>
        </div>
    </div>

    @if($driver->currentAssignment)

        <div class="card">
            <div class="card-body">

                <h5 class="mb-3">Current Assignment</h5>

                <div class="row g-3">

                    <div class="col-md-6">
                        <strong>Bus</strong>
                        <div class="text-muted">
                            {{ $driver->currentAssignment->bus->plate_number }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <strong>Route</strong>
                        <div class="text-muted">
                            {{ $driver->currentAssignment->route->name }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <strong>Company</strong>
                        <div class="text-muted">
                            {{ $driver->currentAssignment->company->name }}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <strong>Effective From</strong>
                        <div class="text-muted">
                            {{ $driver->currentAssignment->effective_from->format('M d, Y') }}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <strong>Effective To</strong>
                        <div class="text-muted">
                            {{ $driver->currentAssignment->effective_to
                                ? $driver->currentAssignment->effective_to->format('M d, Y')
                                : 'Ongoing' }}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <strong>Status</strong><br>
                        <span class="badge bg-success">
                            {{ strtoupper($driver->currentAssignment->status) }}
                        </span>
                    </div>

                </div>

            </div>
        </div>

    @else

        <div class="alert alert-warning">
            This driver is currently <strong>not assigned</strong> to any bus or route.
        </div>

    @endif

</div>
@endsection
