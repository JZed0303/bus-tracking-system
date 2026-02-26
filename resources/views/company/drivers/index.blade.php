@extends('layouts.master')

@section('title')
    Driver Management
@endsection

@section('css')
    <link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
    <style>
        .driver-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #e9edf4;
            background: #f8fafc;
            flex-shrink: 0;
        }
    </style>
@endsection

@section('page-title')
    Drivers
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h4 class="card-title mb-1">Assigned Drivers</h4>
            <p class="card-title-desc mb-0">
                Drivers currently assigned to buses in your company.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="drivers-table" class="table table-bordered table-striped dt-responsive nowrap align-middle w-100">
                <thead>
                    <tr>
                        <th>Driver</th>
                        <th>License</th>
                        <th>Bus</th>
                        <th>Route</th>
                        <th>Assignment Period</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($drivers as $driver)
                        @php
                            $assignment = $driver->currentAssignment;
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $driver->photo_url }}"
                                         alt="{{ $driver->user->full_name }} profile"
                                         class="driver-avatar">
                                    <div>
                                        <strong>{{ $driver->user->full_name }}</strong><br>
                                        <small class="text-muted">{{ $driver->user->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $driver->license_number ?? '—' }}</td>
                            <td>{{ optional($assignment?->bus)->plate_number ?? '—' }}</td>
                            <td>{{ optional($assignment?->route)->name ?? '—' }}</td>
                            <td>
                                @if($assignment)
                                    {{ optional($assignment->effective_from)->format('M d, Y') ?? '—' }}
                                    -
                                    {{ optional($assignment->effective_to)->format('M d, Y') ?? 'Open' }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $driver->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($driver->status) }}
                                </span>
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
            $('#drivers-table').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[0, 'asc']]
            });
        });
    </script>
@endsection
