@extends('layouts.master')

@section('title', 'Active Bus Summary')
@section('page-title', 'Active Bus Summary')

@section('content')
<div class="container-fluid">

@forelse ($trips as $trip)
<div class="card mb-4">
    <div class="card-header">
       <strong>Bus:</strong> {{ optional($trip->bus)->bus_number ?? 'Unassigned' }}
        |
     <strong>Company:</strong> {{ optional($trip->assignment?->company)->name ?? 'N/A' }}
        |
     <strong>Driver:</strong> {{ optional($trip->driver)->full_name ?? 'Unassigned' }}
    </div>

    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>QR Status</th>
                    <th>Scan Status</th>
                </tr>
            </thead>
            <tbody>
@foreach ($service->employeesForTrip($trip) as $employee)                @php
                    $qr = $service->qrStatus($employee->latestQr);
                @endphp
                <tr>
                    <td>{{ $employee->full_name }}</td>
                    <td>
                        <span class="badge bg-{{ $qr['color'] }}">
                            {{ $qr['label'] }}
                        </span>
                    </td>
                    <td>{{ $service->scanStatus($trip, $employee) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<div class="alert alert-info">
    No active buses at the moment.
</div>
@endforelse

</div>
@endsection
