@extends('layouts.master')
@section('title')
    Starter page
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
@endsection
@section('page-title')
    Starter page
@endsection
@section('body')
    <body data-sidebar="colored">
@endsection
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-1">Audit Trail</h4>
                <p class="card-title-desc mb-4">Complete history of critical actions in the system.</p>

                <div class="table-responsive">
                    <table id="audit-trail-table" class="table table-striped table-bordered align-middle mb-0 nowrap" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="min-width: 180px;">User</th>
                                <th style="min-width: 130px;">Event</th>
                                <th style="min-width: 180px;">Model / Module</th>
                                <th style="min-width: 300px;">Old Value</th>
                                <th style="min-width: 300px;">New Value</th>
                                <th style="min-width: 180px;">Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditTrails as $audit)
                                @php
                                    $user = $audit->user;
                                    $userLabel = $user?->full_name
                                        ?? $user?->name
                                        ?? $user?->email
                                        ?? ($audit->user_type ? class_basename($audit->user_type) . ' #' . $audit->user_id : null)
                                        ?? 'System';
                                    $module = $audit->auditable_type
                                        ? class_basename($audit->auditable_type)
                                        : 'System';
                                    $oldRows = \App\Support\AuditTrailFormatter::payload(
                                        is_array($audit->old_values) ? $audit->old_values : []
                                    );
                                    $newRows = \App\Support\AuditTrailFormatter::payload(
                                        is_array($audit->new_values) ? $audit->new_values : []
                                    );
                                @endphp
                                <tr>
                                    <td>{{ $userLabel }}</td>
                                    <td>
                                        <span class="badge bg-info text-uppercase">{{ $audit->event }}</span>
                                    </td>
                                    <td>{{ $module }}</td>
                                    <td>
                                        @if(!empty($oldRows))
                                            <div style="max-height: 180px; overflow:auto;">
                                                <table class="table table-sm table-borderless mb-0">
                                                    <tbody>
                                                        @foreach($oldRows as $row)
                                                            <tr>
                                                                <td class="fw-semibold text-nowrap pe-2">{{ $row['label'] }}</td>
                                                                <td class="text-break">{{ $row['value'] }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($newRows))
                                            <div style="max-height: 180px; overflow:auto;">
                                                <table class="table table-sm table-borderless mb-0">
                                                    <tbody>
                                                        @foreach($newRows as $row)
                                                            <tr>
                                                                <td class="fw-semibold text-nowrap pe-2">{{ $row['label'] }}</td>
                                                                <td class="text-break">{{ $row['value'] }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($audit->created_at)->format('Y-m-d h:i:s A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No audit records found.</td>
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
    <script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>

    <script>
        $(function () {
            $('#audit-trail-table').DataTable({
                responsive: true,
                pageLength: 10,
                stateSave: true,
                order: [[5, 'desc']],
                columnDefs: [
                    { responsivePriority: 1, targets: 5 },
                    { responsivePriority: 2, targets: 0 },
                    { responsivePriority: 100, targets: [3, 4] }
                ]
            });
        });
    </script>

    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
