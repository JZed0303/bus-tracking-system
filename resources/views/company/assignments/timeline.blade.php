@extends('layouts.master')

@section('title', 'Assignment Timeline')
@section('page-title', 'Assignment Timeline')

@section('content')
<div class="container-fluid">

    {{-- HEADER --}}
    <div class="card mb-3">
        <div class="card-body row">
            <div class="col-md-4">
                <strong>Driver:</strong><br>
                @if($assignment->isActive() && $assignment->driver && $assignment->driver->user)
                    {{ $assignment->driver->user->full_name }}
                @else
                    No active driver assigned
                @endif
            </div>
            <div class="col-md-4">
                <strong>Route:</strong><br>
                {{ $assignment->route->name ?? '—' }}
            </div>
            <div class="col-md-4">
                <strong>Bus:</strong><br>
                {{ $assignment->bus->plate_number ?? '—' }}
            </div>
        </div>
    </div>

    {{-- TIMELINE --}}
    <div class="card">
        <div class="card-body">
            <h5 class="mb-4">Trip Timeline</h5>

            @if($assignment->trips->isEmpty())
                <div class="text-muted">
                    No trips recorded for this assignment.
                </div>
            @else
                <ul class="list-group list-group-flush">

                    @foreach(
                        $assignment->trips->sortByDesc(fn ($trip) =>
                            $trip->actual_start_time
                            ?? $trip->scheduled_start_time
                            ?? $trip->trip_date
                        )
                        as $trip
                    )
                        <li class="list-group-item">

                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>
                                        @if($trip->actual_start_time)
                                            {{ $trip->actual_start_time->format('M d, Y h:i A') }}
                                        @elseif($trip->scheduled_start_time)
                                            Scheduled: {{ $trip->scheduled_start_time->format('M d, Y h:i A') }}
                                        @else
                                            Not started yet
                                        @endif
                                    </strong>

                                    <div class="text-muted small">
                                        Trip #{{ $trip->id }}
                                        • {{ $trip->direction_label }}
                                    </div>
                                </div>

                                <div class="text-end">
                                    <span class="badge bg-{{
                                        $trip->status === 'completed' ? 'success' :
                                        ($trip->status === 'ongoing' ? 'primary' : 'secondary')
                                    }}">
                                        {{ ucfirst($trip->status) }}
                                    </span>

                                    @if($trip->status === 'ongoing' && !$trip->actual_start_time)
                                        <div class="small text-warning mt-1">
                                            Awaiting start
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-2">
                                <a href="{{ route('admin.trips.show', $trip->id) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    View Trip Details
                                </a>
                            </div>

                        </li>
                    @endforeach

                </ul>
            @endif
        </div>
    </div>

    {{-- FOOTER ACTIONS --}}
    <div class="mt-3">
        <a href="{{ route('admin.assignments.show', $assignment->id) }}"
           class="btn btn-secondary">
            Back to Assignment
        </a>
    </div>

</div>
@endsection
