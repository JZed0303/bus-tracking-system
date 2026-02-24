@extends('layouts.master')

@section('title', 'Company Dashboard')
@section('page-title', 'Company Dashboard')

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
                    'color' => 'primary'
                ],
                [
                    'label' => 'Employees Transported Today',
                    'value' => 318,
                    'icon'  => 'mdi-account-check',
                    'color' => 'success'
                ],
                [
                    'label' => 'Assigned Buses',
                    'value' => 14,
                    'icon'  => 'mdi-bus',
                    'color' => 'info'
                ],
                [
                    'label' => 'Active Routes',
                    'value' => 9,
                    'icon'  => 'mdi-map-marker-path',
                    'color' => 'warning'
                ],
            ];
        @endphp

        @foreach($kpis as $kpi)
            <div class="col-xl-3 col-md-6">
                <div class="card h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">{{ $kpi['label'] }}</small>
                            <h3 class="mb-0">{{ number_format($kpi['value']) }}</h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-{{ $kpi['color'] }} rounded-circle">
                                <i class="mdi {{ $kpi['icon'] }} font-size-20"></i>
                            </span>
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
