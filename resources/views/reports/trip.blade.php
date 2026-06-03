@extends('layouts.master')

@section('title', 'Trip Report')

@section('css')
    <link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
    <style>
        .trip-report-shell {
            display: grid;
            gap: 1.25rem;
        }
        .trip-report-hero {
            border: 0;
            border-radius: 18px;
            background:
                radial-gradient(circle at top right, rgba(255, 212, 140, 0.22), transparent 32%),
                linear-gradient(135deg, #16324f 0%, #224d73 58%, #2e6a93 100%);
            box-shadow: 0 16px 36px rgba(22, 50, 79, 0.18);
            color: #fff;
        }
        .trip-report-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            font-size: 0.82rem;
            font-weight: 600;
        }
        .trip-report-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.85rem;
            margin-top: 1rem;
        }
        .trip-report-meta-card {
            border-radius: 14px;
            padding: 0.95rem 1rem;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .trip-report-meta-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255, 255, 255, 0.72);
            margin-bottom: 0.35rem;
        }
        .trip-report-meta-value {
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            margin: 0;
        }
        .trip-report-card {
            border: 1px solid #e6edf5;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(18, 38, 63, 0.06);
        }
        .trip-report-kpi {
            border-radius: 14px;
            border: 1px solid #e8eef6;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding: 1rem;
            height: 100%;
        }
        .trip-report-kpi-label {
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #7a8799;
            margin-bottom: 0.45rem;
        }
        .trip-report-kpi-value {
            font-size: 1.35rem;
            font-weight: 800;
            color: #16324f;
            margin: 0;
        }
        .trip-report-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 0.85rem;
        }
        .trip-report-overview-item {
            border-radius: 14px;
            border: 1px solid #ebf0f6;
            background: #fbfdff;
            padding: 0.9rem 1rem;
        }
        .trip-report-overview-item small {
            display: block;
            margin-bottom: 0.3rem;
            color: #7a8799;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }
        .trip-report-overview-item strong {
            color: #203247;
            font-size: 0.98rem;
        }
        .trip-report-chart {
            min-height: 320px;
        }
        .trip-report-table th {
            background: #f5f8fc;
            color: #57667a;
            font-weight: 700;
            white-space: nowrap;
        }
    </style>
@endsection

@section('page-title', 'Trip Report')

@section('body')
    <body data-sidebar="colored">
@endsection

@section('content')
@php
    $tripModel = $report['trip'];
    $company = $report['company'];
    $routeName = optional($tripModel->assignment?->route)->name ?? 'No Route Assigned';
    $busLabel = optional($tripModel->assignment?->bus)->plate_number ?? 'Unassigned Bus';
    $driverName = optional($tripModel->assignment?->driver?->user)->full_name ?? 'Unassigned Driver';
@endphp
<div class="container-fluid trip-report-shell">
    <div class="card trip-report-hero">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
                <div>
                    <span class="trip-report-pill">
                        <i class="mdi mdi-file-chart-outline"></i>
                        Detailed Trip Report
                    </span>
                    <h3 class="mt-3 mb-2 text-white">Trip #{{ $tripModel->id }} for {{ $busLabel }}</h3>
                    <p class="mb-0 text-white-50">{{ $routeName }} | {{ $driverName }}</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route($routePrefix . '.report.export', ['trip' => $tripModel, 'type' => 'excel']) }}" class="btn btn-success btn-sm">
                        <i class="ri-file-excel-2-line me-1"></i> Excel
                    </a>
                    <a href="{{ route($routePrefix . '.report.export', ['trip' => $tripModel, 'type' => 'pdf', 'preview' => 1]) }}" target="_blank" class="btn btn-warning btn-sm">
                        <i class="ri-eye-line me-1"></i> Preview PDF
                    </a>
                    <a href="{{ route($routePrefix . '.report.export', ['trip' => $tripModel, 'type' => 'pdf']) }}" class="btn btn-danger btn-sm">
                        <i class="ri-file-pdf-line me-1"></i> Download PDF
                    </a>
                    <a href="{{ $backUrl }}" class="btn btn-light btn-sm">
                        <i class="mdi mdi-arrow-left me-1"></i> Back to Trip
                    </a>
                </div>
            </div>

            <div class="trip-report-meta">
                <div class="trip-report-meta-card">
                    <div class="trip-report-meta-label">Company</div>
                    <p class="trip-report-meta-value">{{ $company?->name ?? config('app.name', 'Bus Tracking System') }}</p>
                </div>
                <div class="trip-report-meta-card">
                    <div class="trip-report-meta-label">Status</div>
                    <p class="trip-report-meta-value">{{ ucfirst((string) $tripModel->status) }}</p>
                </div>
                <div class="trip-report-meta-card">
                    <div class="trip-report-meta-label">Direction</div>
                    <p class="trip-report-meta-value">{{ $tripModel->direction_label }}</p>
                </div>
                <div class="trip-report-meta-card">
                    <div class="trip-report-meta-label">Trip Date</div>
                    <p class="trip-report-meta-value">{{ $tripModel->trip_date?->format('M d, Y') ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        @foreach($report['summary'] as $item)
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="trip-report-kpi">
                    <div class="trip-report-kpi-label">{{ $item['label'] }}</div>
                    <p class="trip-report-kpi-value">{{ $item['value'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card trip-report-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Trip Overview</h5>
                    <p class="text-muted mb-0">Operational details, timestamps, and trip context for auditing.</p>
                </div>
            </div>
            <div class="trip-report-overview">
                @foreach($report['overview'] as $label => $value)
                    <div class="trip-report-overview-item">
                        <small>{{ $label }}</small>
                        <strong>{{ $value }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card trip-report-card h-100">
                <div class="card-body">
                    <h5 class="mb-1">Employee Status</h5>
                    <p class="text-muted mb-3">Distribution of expected employee boarding outcomes.</p>
                    <div id="employee-status-chart" class="trip-report-chart"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card trip-report-card h-100">
                <div class="card-body">
                    <h5 class="mb-1">Scan Activity Timeline</h5>
                    <p class="text-muted mb-3">Check-in and check-out volume over the trip timeline.</p>
                    <div id="scan-activity-chart" class="trip-report-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card trip-report-card">
        <div class="card-body">
            <h5 class="mb-1">Stop Performance</h5>
            <p class="text-muted mb-3">Expected riders versus boarded riders by pickup stop.</p>
            <div id="stop-performance-chart" class="trip-report-chart"></div>
        </div>
    </div>

    <div class="card trip-report-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Expected Employees</h5>
                    <p class="text-muted mb-0">Detailed rider manifest with boarding and completion status.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table id="trip-report-employees-table" class="table table-bordered table-striped table-hover trip-report-table w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>Code</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Pickup Stop</th>
                            <th>Expected Pickup</th>
                            <th>Status</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['tables']['employees'] as $row)
                            <tr>
                                <td>{{ $row['no'] }}</td>
                                <td>{{ $row['employee'] }}</td>
                                <td>{{ $row['employee_code'] }}</td>
                                <td>{{ $row['department'] }}</td>
                                <td>{{ $row['position'] }}</td>
                                <td>{{ $row['pickup_stop'] }}</td>
                                <td>{{ $row['expected_pickup'] }}</td>
                                <td>{{ $row['status'] }}</td>
                                <td>{{ $row['checkin_time'] }}</td>
                                <td>{{ $row['checkout_time'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card trip-report-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Scan Log</h5>
                    <p class="text-muted mb-0">Chronological scan audit including voided records.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table id="trip-report-scans-table" class="table table-bordered table-striped table-hover trip-report-table w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Record Status</th>
                            <th>Scanned At</th>
                            <th>Void Reason</th>
                            <th>Voided By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['tables']['scans'] as $row)
                            <tr>
                                <td>{{ $row['no'] }}</td>
                                <td>{{ $row['employee'] }}</td>
                                <td>{{ $row['employee_code'] }}</td>
                                <td>{{ $row['scan_type'] }}</td>
                                <td>{{ $row['record_status'] }}</td>
                                <td>{{ $row['scan_time'] }}</td>
                                <td>{{ $row['void_reason'] }}</td>
                                <td>{{ $row['voided_by'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card trip-report-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Route Stop Summary</h5>
                    <p class="text-muted mb-0">Stop-by-stop expected versus completed rider totals.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table id="trip-report-stops-table" class="table table-bordered table-striped table-hover trip-report-table w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Stop Name</th>
                            <th>Address</th>
                            <th>Expected</th>
                            <th>Boarded</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['tables']['stops'] as $row)
                            <tr>
                                <td>{{ $row['no'] }}</td>
                                <td>{{ $row['stop_name'] }}</td>
                                <td>{{ $row['address'] }}</td>
                                <td>{{ $row['expected'] }}</td>
                                <td>{{ $row['boarded'] }}</td>
                                <td>{{ $row['completed'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        $(function () {
            $('#trip-report-employees-table, #trip-report-scans-table, #trip-report-stops-table').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[0, 'asc']],
            });

            const employeeStatusChart = new ApexCharts(document.querySelector('#employee-status-chart'), {
                chart: {
                    type: 'donut',
                    height: 320,
                },
                labels: @json($report['charts']['employee_status']['labels']),
                series: @json($report['charts']['employee_status']['series']),
                colors: ['#2563eb', '#10b981', '#f59e0b', '#ef4444'],
                legend: {
                    position: 'bottom',
                },
                dataLabels: {
                    enabled: true,
                },
            });
            employeeStatusChart.render();

            const scanActivityChart = new ApexCharts(document.querySelector('#scan-activity-chart'), {
                chart: {
                    type: 'bar',
                    height: 320,
                    stacked: false,
                    toolbar: { show: false },
                },
                series: [
                    { name: 'Check-ins', data: @json($report['charts']['scan_activity']['checkins']) },
                    { name: 'Check-outs', data: @json($report['charts']['scan_activity']['checkouts']) },
                ],
                xaxis: {
                    categories: @json($report['charts']['scan_activity']['categories']),
                    labels: {
                        rotate: -25,
                    },
                },
                colors: ['#2563eb', '#14b8a6'],
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        columnWidth: '48%',
                    },
                },
                stroke: {
                    width: 0,
                },
                yaxis: {
                    min: 0,
                    forceNiceScale: true,
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                },
            });
            scanActivityChart.render();

            const stopPerformanceChart = new ApexCharts(document.querySelector('#stop-performance-chart'), {
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: { show: false },
                },
                series: [
                    { name: 'Expected', data: @json($report['charts']['stop_performance']['expected']) },
                    { name: 'Boarded', data: @json($report['charts']['stop_performance']['boarded']) },
                ],
                xaxis: {
                    categories: @json($report['charts']['stop_performance']['categories']),
                    labels: {
                        rotate: -20,
                        trim: true,
                    },
                },
                colors: ['#64748b', '#22c55e'],
                plotOptions: {
                    bar: {
                        horizontal: false,
                        borderRadius: 6,
                        columnWidth: '45%',
                    },
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                },
                yaxis: {
                    min: 0,
                    forceNiceScale: true,
                },
            });
            stopPerformanceChart.render();
        });
    </script>
@endsection
