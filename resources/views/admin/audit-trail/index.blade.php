@extends('layouts.master')
@section('title')
    Audit Trail
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
    <style>
        :root {
            --audit-bg-soft: #f6f8fb;
            --audit-border: #e6ebf1;
            --audit-text: #28323f;
            --audit-muted: #64748b;
            --audit-accent: #0ea5a5;
            --audit-accent-soft: #e6fffb;
            --audit-old-bg: #fff6f6;
            --audit-old-border: #fecaca;
            --audit-new-bg: #f4fff8;
            --audit-new-border: #bbf7d0;
        }

        .audit-shell {
            background: linear-gradient(180deg, #ffffff 0%, var(--audit-bg-soft) 100%);
            border: 1px solid var(--audit-border);
            border-radius: 14px;
            box-shadow: 0 10px 28px rgba(16, 24, 40, 0.05);
            overflow: hidden;
        }

        .audit-header {
            background: linear-gradient(120deg, #0f172a 0%, #1f2937 100%);
            color: #f8fafc;
            padding: 1.25rem 1.5rem;
        }

        .audit-header h4 {
            color: #fff;
            margin-bottom: 0.3rem;
        }

        .audit-header p {
            margin: 0;
            color: #cbd5e1;
        }

        .audit-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .audit-kpi {
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 0.7rem 0.9rem;
        }

        .audit-kpi .label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #cbd5e1;
            margin-bottom: 0.2rem;
        }

        .audit-kpi .value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
        }

        .audit-body {
            padding: 1rem 1rem 1.25rem;
        }

        .audit-filters {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: end;
            margin-bottom: 0.95rem;
            padding: 0.8rem;
            background: #f8fafc;
            border: 1px solid var(--audit-border);
            border-radius: 10px;
        }

        .audit-filter-item {
            min-width: 220px;
        }

        .audit-filter-label {
            display: block;
            font-size: 0.74rem;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 0.35rem;
        }

        .audit-filter-control {
            width: 100%;
            border: 1px solid var(--audit-border);
            border-radius: 8px;
            padding: 0.42rem 0.55rem;
            color: #0f172a;
            background: #fff;
        }

        .audit-filter-actions {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }

        #audit-trail-table {
            color: var(--audit-text);
            border-color: var(--audit-border);
            margin-bottom: 0 !important;
        }

        #audit-trail-table thead th {
            background: #f8fafc;
            border-bottom: 1px solid var(--audit-border);
            color: #0f172a;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }

        #audit-trail-table tbody td {
            vertical-align: top;
            border-color: var(--audit-border);
        }

        #audit-trail-table tbody tr:nth-child(even) {
            background: #fbfdff;
        }

        .audit-user {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.2rem;
        }

        .audit-module {
            color: var(--audit-muted);
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .audit-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0.26rem 0.65rem;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .audit-pill-created { background: #dcfce7; color: #166534; }
        .audit-pill-updated { background: #e0f2fe; color: #075985; }
        .audit-pill-deleted { background: #fee2e2; color: #991b1b; }
        .audit-pill-login,
        .audit-pill-logout { background: #ede9fe; color: #5b21b6; }
        .audit-pill-default { background: #e2e8f0; color: #334155; }

        .audit-change-box {
            border: 1px solid var(--audit-border);
            border-radius: 10px;
            padding: 0.55rem 0.65rem;
            max-height: 180px;
            overflow: auto;
            background: #fff;
        }

        .audit-change-box.old {
            border-color: var(--audit-old-border);
            background: var(--audit-old-bg);
        }

        .audit-change-box.new {
            border-color: var(--audit-new-border);
            background: var(--audit-new-bg);
        }

        .audit-change-row {
            display: grid;
            grid-template-columns: 130px minmax(0, 1fr);
            gap: 0.5rem;
            align-items: start;
            padding: 0.3rem 0;
            border-bottom: 1px dashed rgba(100, 116, 139, 0.24);
        }

        .audit-change-row:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }

        .audit-change-key {
            font-size: 0.75rem;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .audit-change-value {
            font-size: 0.85rem;
            color: #0f172a;
            word-break: break-word;
        }

        .audit-empty {
            color: #94a3b8;
            font-style: italic;
            font-size: 0.84rem;
        }

        .audit-time {
            white-space: nowrap;
            color: #0f172a;
            font-weight: 600;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid var(--audit-border);
            border-radius: 8px;
            padding: 0.35rem 0.6rem;
            min-width: 220px;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid var(--audit-border);
            border-radius: 8px;
            padding: 0.28rem 1.9rem 0.28rem 0.65rem;
        }

        @media (max-width: 1200px) {
            .audit-kpis {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .audit-kpis {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
@section('page-title')
    Audit Trail
@endsection
@section('body')
    <body data-sidebar="colored">
@endsection
@section('content')
@php
    $todayCount = $auditTrails->filter(function ($audit) {
        return optional($audit->created_at)?->isToday();
    })->count();
    $activeUsers = $auditTrails->map(function ($audit) {
        return $audit->user?->id;
    })->filter()->unique()->count();
    $moduleCount = $auditTrails->map(function ($audit) {
        return $audit->auditable_type ? class_basename($audit->auditable_type) : 'System';
    })->unique()->count();
    $eventCount = $auditTrails->map(function ($audit) {
        return strtoupper((string) $audit->event);
    })->unique()->count();
@endphp
    <div class="row">
        <div class="col-12">
            <div class="audit-shell">
                <div class="audit-header">
                    <h4>Audit Trail</h4>
                    <p>Track who changed what, when it happened, and exactly what values moved.</p>
                    <div class="audit-kpis">
                        <div class="audit-kpi">
                            <div class="label">Total Records</div>
                            <div class="value">{{ number_format($auditTrails->count()) }}</div>
                        </div>
                        <div class="audit-kpi">
                            <div class="label">Events Today</div>
                            <div class="value">{{ number_format($todayCount) }}</div>
                        </div>
                        <div class="audit-kpi">
                            <div class="label">Distinct Users</div>
                            <div class="value">{{ number_format($activeUsers) }}</div>
                        </div>
                        <div class="audit-kpi">
                            <div class="label">Modules / Events</div>
                            <div class="value">{{ number_format($moduleCount) }} / {{ number_format($eventCount) }}</div>
                        </div>
                    </div>
                </div>

                <div class="audit-body">
                    <div class="audit-filters">
                        <div class="audit-filter-item">
                            <label for="audit-from-datetime" class="audit-filter-label">From Date & Time</label>
                            <input type="datetime-local" id="audit-from-datetime" class="audit-filter-control">
                        </div>
                        <div class="audit-filter-item">
                            <label for="audit-to-datetime" class="audit-filter-label">To Date & Time</label>
                            <input type="datetime-local" id="audit-to-datetime" class="audit-filter-control">
                        </div>
                        <div class="audit-filter-actions">
                            <button type="button" id="audit-filter-apply" class="btn btn-primary btn-sm">Apply</button>
                            <button type="button" id="audit-filter-reset" class="btn btn-light btn-sm">Reset</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="audit-trail-table" class="table table-bordered align-middle" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th style="min-width: 180px;">User / Module</th>
                                    <th style="min-width: 110px;">Event</th>
                                    <th style="min-width: 320px;">Before</th>
                                    <th style="min-width: 320px;">After</th>
                                    <th style="min-width: 170px;">Date & Time</th>
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
                                        $eventKey = strtolower((string) $audit->event);
                                        $eventClass = in_array($eventKey, ['created', 'updated', 'deleted', 'login', 'logout'], true)
                                            ? "audit-pill-{$eventKey}"
                                            : 'audit-pill-default';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="audit-user">{{ $userLabel }}</div>
                                            <div class="audit-module">{{ $module }}</div>
                                        </td>
                                        <td>
                                            <span class="audit-pill {{ $eventClass }}">{{ $audit->event }}</span>
                                        </td>
                                        <td>
                                            @if(!empty($oldRows))
                                                <div class="audit-change-box old">
                                                    @foreach($oldRows as $row)
                                                        <div class="audit-change-row">
                                                            <div class="audit-change-key">{{ $row['label'] }}</div>
                                                            <div class="audit-change-value">{{ $row['value'] }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="audit-empty">No previous value</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($newRows))
                                                <div class="audit-change-box new">
                                                    @foreach($newRows as $row)
                                                        <div class="audit-change-row">
                                                            <div class="audit-change-key">{{ $row['label'] }}</div>
                                                            <div class="audit-change-value">{{ $row['value'] }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="audit-empty">No updated value</span>
                                            @endif
                                        </td>
                                        <td class="audit-time" data-order="{{ optional($audit->created_at)->timestamp ?? 0 }}">
                                            {{ optional($audit->created_at)->format('Y-m-d h:i:s A') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No audit records found.</td>
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
            var fromInput = document.getElementById('audit-from-datetime');
            var toInput = document.getElementById('audit-to-datetime');
            var tableEl = document.getElementById('audit-trail-table');

            var dateRangeFilter = function (settings, data, dataIndex) {
                if (settings.nTable !== tableEl) {
                    return true;
                }

                var rowNode = settings.aoData[dataIndex].nTr;
                var timeCell = rowNode ? rowNode.querySelector('td.audit-time') : null;
                var rowSeconds = Number(timeCell ? timeCell.getAttribute('data-order') : 0);

                if (!rowSeconds) {
                    return true;
                }

                var rowMs = rowSeconds * 1000;
                var fromMs = fromInput && fromInput.value ? new Date(fromInput.value).getTime() : null;
                var toMs = toInput && toInput.value ? new Date(toInput.value).getTime() : null;

                if (fromMs && rowMs < fromMs) {
                    return false;
                }

                if (toMs) {
                    var toInclusive = toMs + 59999;
                    if (rowMs > toInclusive) {
                        return false;
                    }
                }

                return true;
            };

            $.fn.dataTable.ext.search.push(dateRangeFilter);

            var auditTable = $('#audit-trail-table').DataTable({
                responsive: true,
                pageLength: 15,
                stateSave: true,
                order: [[4, 'desc']],
                columnDefs: [
                    { responsivePriority: 1, targets: 4 },
                    { responsivePriority: 2, targets: 0 },
                    { responsivePriority: 3, targets: 1 },
                    { responsivePriority: 100, targets: [2, 3] }
                ],
                language: {
                    search: 'Search logs:',
                    lengthMenu: 'Show _MENU_ records',
                    info: 'Showing _START_ to _END_ of _TOTAL_ records',
                    infoEmpty: 'No records available',
                    zeroRecords: 'No matching audit logs found'
                }
            });

            $('#audit-filter-apply').on('click', function () {
                auditTable.draw();
            });

            $('#audit-filter-reset').on('click', function () {
                if (fromInput) {
                    fromInput.value = '';
                }
                if (toInput) {
                    toInput.value = '';
                }
                auditTable.draw();
            });

            $('#audit-from-datetime, #audit-to-datetime').on('change', function () {
                auditTable.draw();
            });
        });
    </script>

    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
