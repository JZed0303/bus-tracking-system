@extends('layouts.master')

@section('title', 'Company Dashboard')
@section('page-title', 'Company Dashboard')
@section('css')
<style>
    .hm-kpi-card {
        position: relative;
        overflow: hidden;
        background-color: #ffffff;
        border: 1px solid rgba(225, 230, 238, 0.95);
        border-left: 2px solid #ef4444;
        box-shadow: 0 10px 30px rgba(16, 24, 40, 0.06);
        transition: transform 220ms ease, box-shadow 220ms ease, border-left-width 220ms ease;
    }

    .hm-kpi-card:hover {
        transform: translateY(-4px);
        border-left-width: 3px;
        box-shadow: 0 16px 34px rgba(16, 24, 40, 0.12);
    }

    .hm-kpi-label {
        margin-bottom: 0.25rem;
        font-size: 0.78rem;
        letter-spacing: 0.02em;
        color: #667085;
    }

    .hm-kpi-value {
        margin-bottom: 0.2rem;
        color: #101828;
    }

    .hm-kpi-meta {
        margin-bottom: 0;
        font-size: 0.76rem;
        color: #6b7280;
    }

    .hm-kpi-icon {
        width: 2.9rem;
        height: 2.9rem;
        border-radius: 0.85rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.3rem;
        background: #ef4444;
        box-shadow: 0 10px 20px rgba(239, 68, 68, 0.22);
    }

    .hm-kpi-progress-track {
        height: 6px;
        border-radius: 999px;
        background: #f2f4f7;
        overflow: hidden;
    }

    .hm-kpi-progress-fill {
        height: 100%;
        border-radius: inherit;
        background: #ef4444;
    }
</style>
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    {{-- ===================== COMPANY KPI OVERVIEW ===================== --}}
    <div class="row g-3 mb-4">

        @php
            $kpis = [
                [
                    'label' => 'Total Employees',
                    'value' => 420,
                    'icon'  => 'mdi-account-group',
                    'meta'  => '0 inactive',
                    'progress' => 100,
                ],
                [
                    'label' => 'Employees Transported Today',
                    'value' => 318,
                    'icon'  => 'mdi-account-check',
                    'meta'  => '0% workforce coverage',
                    'progress' => 76,
                ],
                [
                    'label' => 'Assigned Buses',
                    'value' => 14,
                    'icon'  => 'mdi-bus',
                    'meta'  => 'Company network in service',
                    'progress' => 67,
                ],
                [
                    'label' => 'Active Routes',
                    'value' => 9,
                    'icon'  => 'mdi-map-marker-path',
                    'meta'  => 'Routes currently available',
                    'progress' => 82,
                ],
            ];
        @endphp

        @foreach($kpis as $kpi)
            <div class="col-xl-3 col-md-6">
                <div class="card hm-kpi-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="hm-kpi-label">{{ $kpi['label'] }}</p>
                                <h3 class="hm-kpi-value">{{ number_format($kpi['value']) }}</h3>
                                <p class="hm-kpi-meta">{{ $kpi['meta'] }}</p>
                            </div>
                            <div class="hm-kpi-icon">
                                <i class="mdi {{ $kpi['icon'] }}"></i>
                            </div>
                        </div>
                        <div class="hm-kpi-progress mt-3">
                            <div class="hm-kpi-progress-track">
                                <div class="hm-kpi-progress-fill" style="width: {{ $kpi['progress'] }}%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

    </div>

    {{-- ===================== DAILY OPERATIONS ===================== --}}
    <div class="row g-3 mb-4">

        <div class="col-xl-4 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Trips Today</small>

                    <div class="mt-2">
                        <div class="d-flex justify-content-between">
                            <span><i class="mdi mdi-calendar-clock me-1"></i> Scheduled</span>
                            <strong>18</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="mdi mdi-bus-clock me-1"></i> Ongoing</span>
                            <strong>7</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="mdi mdi-check-circle-outline me-1"></i> Completed</span>
                            <strong>26</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Live Buses</small>
                    <h3 class="mb-1 text-success">6</h3>
                    <small class="text-muted">
                        Out of 14 assigned buses currently in operation
                    </small>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">On-Time Performance</small>
                    <h3 class="mb-1">92%</h3>
                    <small class="text-success">
                        <i class="mdi mdi-arrow-up"></i> +4% vs last week
                    </small>
                </div>
            </div>
        </div>

    </div>

    {{-- ===================== ANALYTICS ===================== --}}
    <div class="row g-3">

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="mdi mdi-chart-line me-1"></i>
                        Daily Trips (7 Days)
                    </h6>
                </div>
                <div class="card-body">
                    <div id="dailyTripsChart" style="height:260px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="mdi mdi-account-multiple me-1"></i>
                        Employees Transported
                    </h6>
                </div>
                <div class="card-body">
                    <div id="employeesTransportedChart" style="height:260px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="mdi mdi-map me-1"></i>
                        Route Utilization
                    </h6>
                </div>
                <div class="card-body">
                    <div id="routeUtilizationChart" style="height:260px;"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="mdi mdi-clock-alert me-1"></i>
                        On-time vs Delayed Trips
                    </h6>
                </div>
                <div class="card-body">
                    <div id="onTimeDelayedChart" style="height:260px;"></div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
        new ApexCharts(document.querySelector("#dailyTripsChart"), {
            chart: { type: 'line', toolbar: { show: false } },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            series: [{ name: 'Trips', data: [12, 14, 13, 18, 16, 20, 22] }],
            xaxis: { categories: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] }
            }).render();

        new ApexCharts(document.querySelector("#employeesTransportedChart"), {
            chart: { type: 'bar', toolbar: { show: false } },
            series: [{ name: 'Employees', data: [280, 300, 290, 330, 310, 350, 380] }],
            xaxis: { categories: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] }
            }).render();

        new ApexCharts(document.querySelector("#routeUtilizationChart"), {
            chart: { type: 'donut' },
            series: [35, 25, 20, 20],
            labels: ['Route A','Route B','Route C','Others']
            }).render();

        new ApexCharts(document.querySelector("#onTimeDelayedChart"), {
            chart: { type: 'pie' },
            series: [92, 8],
            labels: ['On-time','Delayed']
            }).render();
</script>
@endsection
