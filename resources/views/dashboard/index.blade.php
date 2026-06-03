@extends('layouts.master')

@section('title', 'Company Dashboard')
@section('page-title', 'Company Dashboard')
@section('css')
<style>
    :root {
        --hm-ink-900: #101828;
        --hm-ink-700: #344054;
        --hm-ink-500: #667085;
        --hm-line: #e4e7ec;
        --hm-surface: #ffffff;
        --hm-soft: #f8fafc;
        --hm-brand: #0f5dbb;
        --hm-brand-soft: #e7f0ff;
        --hm-success: #12b76a;
    }

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

    /* .hm-kpi-progress-fill {
        height: 100%;
        border-radius: inherit;
        background: #ef4444;
    } */

    .hm-ops-card {
        border: 1px solid var(--hm-line);
        border-radius: 0.9rem;
        background: linear-gradient(180deg, #fff 0%, #fbfcff 100%);
        box-shadow: 0 12px 28px rgba(16, 24, 40, 0.06);
    }

    .hm-ops-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .hm-ops-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.28rem 0.55rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        background: var(--hm-brand-soft);
        color: var(--hm-brand);
    }

    .hm-ops-title {
        margin: 0;
        color: var(--hm-ink-900);
        font-size: 1.02rem;
        font-weight: 700;
    }

    .hm-ops-subtitle {
        margin: 0.2rem 0 0;
        color: var(--hm-ink-500);
        font-size: 0.78rem;
    }

    .hm-ops-list {
        display: grid;
        gap: 0.6rem;
    }

    .hm-ops-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border: 1px solid #eef2f6;
        border-radius: 0.7rem;
        background: var(--hm-soft);
        padding: 0.6rem 0.75rem;
        color: var(--hm-ink-700);
        font-size: 0.88rem;
    }

    .hm-ops-item strong {
        color: var(--hm-ink-900);
        font-size: 1rem;
    }

    .hm-ops-metric {
        color: var(--hm-brand);
        font-size: 2.15rem;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin-bottom: 0.45rem;
    }

    .hm-ops-note {
        margin: 0;
        color: var(--hm-ink-500);
        font-size: 0.8rem;
    }

    .hm-ops-trend {
        margin: 0.45rem 0 0;
        font-size: 0.82rem;
        color: var(--hm-success);
        font-weight: 700;
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
                    'value' => $totalEmployees ?? 0,
                    'icon'  => 'mdi-account-group',
                    'meta'  => number_format($inactiveEmployees ?? 0) . ' inactive',
                    'progress' => 100,
                ],
                [
                    'label' => 'Employees Transported Today',
                    'value' => $employeesTransportedToday ?? 0,
                    'icon'  => 'mdi-account-check',
                    'meta'  => number_format($transportCoveragePercent ?? 0, 1) . '% workforce coverage',
                    'progress' => min(100, max(0, (int) round($transportCoveragePercent ?? 0))),
                ],
                [
                    'label' => 'Assigned Buses',
                    'value' => $assignedBusesCount ?? 0,
                    'icon'  => 'mdi-bus',
                    'meta'  => 'Company network in service',
                    'progress' => 67,
                ],
                [
                    'label' => 'Active Routes',
                    'value' => $activeRoutes ?? 0,
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
            <div class="card hm-ops-card h-100">
                <div class="card-body">
                    <div class="hm-ops-header">
                        <div>
                            <h6 class="hm-ops-title">Trips Today</h6>
                            <p class="hm-ops-subtitle">Operational snapshot by status</p>
                        </div>
                        <span class="hm-ops-badge">
                            <i class="mdi mdi-calendar-check-outline"></i>
                            Daily
                        </span>
                    </div>

                    <div class="hm-ops-list">
                        <div class="hm-ops-item">
                            <span><i class="mdi mdi-calendar-clock me-1"></i> Scheduled</span>
                            <strong>{{ number_format($todayTripsScheduled ?? 0) }}</strong>
                        </div>
                        <div class="hm-ops-item">
                            <span><i class="mdi mdi-bus-clock me-1"></i> Ongoing</span>
                            <strong>{{ number_format($todayTripsOngoing ?? 0) }}</strong>
                        </div>
                        <div class="hm-ops-item">
                            <span><i class="mdi mdi-check-circle-outline me-1"></i> Completed</span>
                            <strong>{{ number_format($todayTripsCompleted ?? 0) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card hm-ops-card h-100">
                <div class="card-body">
                    <div class="hm-ops-header">
                        <div>
                            <h6 class="hm-ops-title">Live Buses</h6>
                            <p class="hm-ops-subtitle">Current active fleet status</p>
                        </div>
                        <span class="hm-ops-badge">
                            <i class="mdi mdi-access-point"></i>
                            Realtime
                        </span>
                    </div>
                    <div class="hm-ops-metric text-success">{{ number_format($onlineBusesCount ?? 0) }}</div>
                    <p class="hm-ops-note">
                        {{ number_format($onlineBusesCount ?? 0) }} of {{ number_format($assignedBusesCount ?? 0) }} assigned buses are currently active.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12">
            <div class="card hm-ops-card h-100">
                <div class="card-body">
                    <div class="hm-ops-header">
                        <div>
                            <h6 class="hm-ops-title">On-Time Performance</h6>
                            <p class="hm-ops-subtitle">Weekly punctuality benchmark</p>
                        </div>
                        <span class="hm-ops-badge">
                            <i class="mdi mdi-timer-check-outline"></i>
                            KPI
                        </span>
                    </div>
                    <div class="hm-ops-metric">{{ number_format($onTimePerformance ?? 0, 1) }}%</div>
                    <p class="hm-ops-trend">
                        <i class="mdi {{ ($onTimeDelta ?? 0) >= 0 ? 'mdi-arrow-up' : 'mdi-arrow-down' }}"></i>
                        {{ ($onTimeDelta ?? 0) >= 0 ? '+' : '' }}{{ number_format($onTimeDelta ?? 0, 1) }}% vs last week
                    </p>
                    <p class="hm-ops-note">On-time: {{ number_format($onTimeToday ?? 0) }} | Delayed: {{ number_format($delayedToday ?? 0) }}</p>
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
        const dashboardLabels = @json($chartLabels ?? []);
        const dailyTripsSeries = @json($dailyTripsSeries ?? []);
        const dailyEmployeesSeries = @json($dailyEmployeesSeries ?? []);
        const routeUtilizationLabels = @json($routeUtilizationLabels ?? []);
        const routeUtilizationSeries = @json($routeUtilizationSeries ?? []);
        const onTimePie = @json([(int)($onTimeToday ?? 0), (int)($delayedToday ?? 0)]);

        new ApexCharts(document.querySelector("#dailyTripsChart"), {
            chart: { type: 'line', toolbar: { show: false } },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            series: [{ name: 'Trips', data: dailyTripsSeries }],
            xaxis: { categories: dashboardLabels }
            }).render();

        new ApexCharts(document.querySelector("#employeesTransportedChart"), {
            chart: { type: 'bar', toolbar: { show: false } },
            series: [{ name: 'Employees', data: dailyEmployeesSeries }],
            xaxis: { categories: dashboardLabels }
            }).render();

        new ApexCharts(document.querySelector("#routeUtilizationChart"), {
            chart: { type: 'donut' },
            series: routeUtilizationSeries.length ? routeUtilizationSeries : [1],
            labels: routeUtilizationLabels.length ? routeUtilizationLabels : ['No Route Data']
            }).render();

        new ApexCharts(document.querySelector("#onTimeDelayedChart"), {
            chart: { type: 'pie' },
            series: (onTimePie[0] + onTimePie[1]) > 0 ? onTimePie : [1, 0],
            labels: ['On-time','Delayed']
            }).render();
</script>
@endsection
