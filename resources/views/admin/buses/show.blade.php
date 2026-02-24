@extends('layouts.master')

@section('title')
    Bus Profile
@endsection

@section('page-title')
    Bus Details
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="row">

    <!-- ================= BUS SUMMARY ================= -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body text-center">

                <!-- ACTIONS -->
                <div class="d-flex justify-content-end mb-2 gap-2">
                    @php
                        $activeAssignment = $bus->assignments->where('status', 'active')->first();
                    @endphp

                    @if($activeAssignment)
                        <a href="{{ route('admin.live-map', ['bus' => $bus->id]) }}"
                           class="btn btn-sm btn-outline-primary"
                           title="View Live Map">
                            <i class="mdi mdi-map-marker"></i>
                        </a>
                    @endif

                    <button class="btn btn-sm btn-outline-secondary"
        data-bs-toggle="modal"
        data-bs-target="#editBusModal{{ $bus->id }}"
        title="Edit Bus">
    <i class="mdi mdi-pencil"></i>
</button>

                </div>

                <div class="avatar-sm mx-auto mb-3">
                    <span class="avatar-title rounded-circle bg-primary text-white font-size-18">
                        <i class="mdi mdi-bus"></i>
                    </span>
                </div>

                <h5 class="mb-1">{{ $bus->plate_number }}</h5>
                <p class="text-muted mb-2">{{ $bus->bus_code ?? '—' }}</p>

                <div class="mb-2">
                    <span class="badge bg-info">
                        Capacity: {{ $bus->capacity }}
                    </span>
                </div>

                <span class="badge bg-{{ $bus->status === 'active' ? 'success' : 'secondary' }}">
                    {{ ucfirst($bus->status) }}
                </span>

            </div>
        </div>

        <!-- ================= CURRENT ASSIGNMENT ================= -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Current Assignment</h5>
            </div>
            <div class="card-body">
                @if($activeAssignment)
                    <p class="mb-1">
                        <strong>Driver:</strong>
                        {{ $activeAssignment->driver->user->full_name }}
                    </p>
                    <p class="mb-1">
                        <strong>Company:</strong>
                        {{ $activeAssignment->company->name }}
                    </p>
                    <p class="mb-1">
                        <strong>Route:</strong>
                        {{ $activeAssignment->route->name }}
                    </p>
                    <p class="mb-0 text-muted">
                        Active since {{ $activeAssignment->effective_from->format('M d, Y') }}
                    </p>
                @else
                    <p class="text-muted mb-0">
                        This bus is currently not assigned.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <!-- ================= ASSIGNMENT HISTORY ================= -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Assignment History</h5>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Driver</th>
                                <th>Company</th>
                                <th>Route</th>
                                <th>Effective From</th>
                                <th>Effective To</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bus->assignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->driver->user->full_name }}</td>
                                    <td>{{ $assignment->company->name }}</td>
                                    <td>{{ $assignment->route->name }}</td>
                                    <td>{{ $assignment->effective_from->format('M d, Y') }}</td>
                                    <td>
                                        {{ $assignment->effective_to
                                            ? $assignment->effective_to->format('M d, Y')
                                            : '—'
                                        }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $assignment->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($assignment->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        No assignment history found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
