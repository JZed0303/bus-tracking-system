@extends('layouts.master')
@section('title')
    Starter page
@endsection
@section('page-title')
    Starter page
@endsection
@section('css')
<style>
    .kpi-highlight-card {
        border: 0;
        border-radius: 12px;
        color: #fff;
        background: linear-gradient(135deg, #b61f1f 0%, #de6b28 100%)!important;
        box-shadow: 0 6px 16px rgba(182, 31, 31, 0.22);
    }

    .kpi-highlight-card .kpi-label {
        font-size: .72rem;
        font-weight: 600;
        text-transform: none;
        opacity: .95;
        margin-bottom: .35rem;
    }

    .kpi-highlight-card .kpi-value {
        font-size: 1.8rem;
        line-height: 1;
        font-weight: 700;
        margin-bottom: .25rem;
    }

    .kpi-highlight-card .kpi-meta {
        font-size: .72rem;
        opacity: .9;
        margin: 0;
    }

    .kpi-highlight-card .kpi-icon-wrap {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, .2);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255, 255, 255, .35);
    }

    .kpi-highlight-card .kpi-icon-wrap i {
        font-size: 1.05rem;
        color: #fff;
    }
</style>
@endsection
@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
    @if(!empty($isSuperAdmin))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                                <h6 class="mb-1">
                                    <i class="mdi mdi-domain me-1 text-primary"></i>
                                    Company Context
                                </h6>
                                <small class="text-muted">Switch company to refresh analytics instantly.</small>
                            </div>
                            <span class="badge bg-primary">
                                Viewing: {{ $companyName }}
                            </span>
                        </div>

                        <form method="GET" action="{{ route('company.dashboard') }}" class="row g-2">
                            <div class="col-xl-6 col-lg-8 col-md-10">
                                <label for="company_id" class="form-label mb-1">Select Company</label>
                                <select
                                    id="company_id"
                                    name="company_id"
                                    class="form-select form-select-lg"
                                    aria-label="Select company dashboard context"
                                    onchange="this.form.submit()"
                                    @disabled(($selectableCompanies ?? collect())->isEmpty())
                                >
                                    @forelse($selectableCompanies as $companyOption)
                                        <option value="{{ $companyOption->id }}" @selected((int) $selectedCompanyId === (int) $companyOption->id)>
                                            {{ $companyOption->name }}
                                        </option>
                                    @empty
                                        <option value="">No companies available</option>
                                    @endforelse
                                </select>
                                <small class="text-muted d-block mt-2">
                                    Auto-applies when you select a company.
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ===================== COMPANY KPI OVERVIEW (startpage style) ===================== --}}
    <div class="row g-3 mb-4">
        @php
            $activeEmployeesCount = max(0, (int) $totalEmployees - (int) $inactiveEmployees);
            $kpis = [
                [
                    'label' => 'Active Employees',
                    'value' => $activeEmployeesCount,
                    'meta'  => number_format($inactiveEmployees).' inactive',
                    'icon'  => 'mdi-account-group',
                ],
                [
                    'label' => 'Employees Transported Today',
                    'value' => $employeesTransportedToday,
                    'meta'  => $transportCoveragePercent.'% workforce coverage',
                    'icon'  => 'mdi-account-check',
                ],
                [
                    'label' => 'Assigned Buses',
                    'value' => $assignedBusesCount,
                    'meta'  => 'Company network in service',
                    'icon'  => 'mdi-bus',
                ],
            ];
        @endphp

        @foreach($kpis as $kpi)
            <div class="col-xl-4 col-md-6">
                <div class="card kpi-highlight-card h-100">
                    <div class="card-body d-flex justify-content-between align-items-start">
                        <div>
                            <p class="kpi-label">{{ $kpi['label'] }}</p>
                            <h3 class="kpi-value">{{ number_format($kpi['value']) }}</h3>
                            <p class="kpi-meta">{{ $kpi['meta'] }}</p>
                        </div>
                        <div class="kpi-icon-wrap">
                            <i class="mdi {{ $kpi['icon'] }}"></i>
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
                            <span><i class="mdi mdi-bus-clock me-1"></i> Ongoing</span>
                            <strong>{{ number_format($todayTripsOngoing) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="mdi mdi-check-circle-outline me-1"></i> Completed</span>
                            <strong>{{ number_format($todayTripsCompleted) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span><i class="mdi mdi-close-circle-outline me-1"></i> Cancelled</span>
                            <strong>{{ number_format($todayTripsCancelled) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                            <span><i class="mdi mdi-format-list-numbered me-1"></i> Total</span>
                            <strong>{{ number_format($todayTrips) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Live Buses</small>
                    <h3 class="mb-1 text-success">{{ number_format($onlineBusesCount) }}</h3>
                    <small class="text-muted">
                        Out of {{ number_format($assignedBusesCount) }} assigned buses currently in operation
                    </small>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">Trip Completion Today</small>
                    <h3 class="mb-1">{{ $tripCompletionPercent }}%</h3>
                    <small class="{{ $tripCompletionPercent >= 75 ? 'text-success' : 'text-warning' }}">
                        <i class="mdi {{ $tripCompletionPercent >= 75 ? 'mdi-arrow-up' : 'mdi-alert-outline' }}"></i>
                        {{ $tripCompletionPercent >= 75 ? 'Healthy completion trend' : 'Needs monitoring today' }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== ANALYTICS (startpage chart block pattern) ===================== --}}
    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="mdi mdi-chart-line me-1"></i>
                        Daily Trips Status Trend (14 Days)
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
                        Employees Transported (14 Days)
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
                        On-time vs Delayed Trips (Today)
                    </h6>
                </div>
                <div class="card-body">
                    <div id="onTimeDelayedChart" style="height:260px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== FLEET TABLE ===================== --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Assigned Fleet Overview</h5>
                </div>
                <div class="card-body">
                    @if($companyBuses->isEmpty())
                        <p class="text-muted mb-0">No active bus assignments found for this company.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Bus</th>
                                        <th>Driver</th>
                                        <th>Route</th>
                                        <th>Presence</th>
                                        <th class="text-end">Trips Today</th>
                                        <th class="text-end">Employees Transported</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($companyBuses as $bus)
                                        @php
                                            $presenceClass = match($bus['presence']) {
                                                'online' => 'bg-success',
                                                'away' => 'bg-warning text-dark',
                                                default => 'bg-secondary',
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $bus['plate_number'] }}</div>
                                                <div class="text-muted small">{{ $bus['model'] }} • Cap {{ $bus['capacity'] }}</div>
                                            </td>
                                            <td>{{ $bus['driver_name'] }}</td>
                                            <td>{{ $bus['route_name'] }}</td>
                                            <td>
                                                <span class="badge {{ $presenceClass }}">
                                                    {{ ucfirst($bus['presence']) }}
                                                </span>
                                            </td>
                                            <td class="text-end">{{ $bus['today_trips'] }}</td>
                                            <td class="text-end fw-semibold">{{ $bus['employees_transported'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@php
    $transportTrendData = $analytics['transport_trend'] ?? ['labels' => [], 'series' => []];
    $tripStatusTrendData = $analytics['trip_status_trend'] ?? ['labels' => [], 'ongoing' => [], 'completed' => [], 'cancelled' => []];
    $routeUtilizationData = $analytics['route_utilization'] ?? ['labels' => [], 'series' => []];
@endphp
<script>
(() => {
    const transportTrend = @json($transportTrendData);
    const tripStatusTrend = @json($tripStatusTrendData);
    const routeUtilization = @json($routeUtilizationData);

    const todayCompleted = Number(@json($todayTripsCompleted ?? 0));
    const todayOngoing = Number(@json($todayTripsOngoing ?? 0));
    const todayCancelled = Number(@json($todayTripsCancelled ?? 0));
    const todayDelayed = todayOngoing + todayCancelled;

    const hasRouteData = Array.isArray(routeUtilization.series) && routeUtilization.series.some((v) => Number(v) > 0);
    const hasDayStatus = (tripStatusTrend.ongoing || []).length > 0
        || (tripStatusTrend.completed || []).length > 0
        || (tripStatusTrend.cancelled || []).length > 0;

    const dailyTripsOptions = {
        chart: { type: 'line', toolbar: { show: false }, height: 260 },
        series: hasDayStatus
            ? [
                { name: 'Ongoing', data: tripStatusTrend.ongoing || [] },
                { name: 'Completed', data: tripStatusTrend.completed || [] },
                { name: 'Cancelled', data: tripStatusTrend.cancelled || [] },
            ]
            : [{ name: 'Trips', data: [] }],
        xaxis: { categories: tripStatusTrend.labels || [] },
        stroke: { curve: 'smooth', width: 2 },
        dataLabels: { enabled: false },
        colors: ['#0dcaf0', '#198754', '#dc3545'],
        noData: { text: 'No trip trend data yet' }
    };

    const employeesTransportedOptions = {
        chart: { type: 'bar', toolbar: { show: false }, height: 260 },
        series: [{ name: 'Employees', data: transportTrend.series || [] }],
        xaxis: { categories: transportTrend.labels || [] },
        dataLabels: { enabled: false },
        colors: ['#0d6efd'],
        noData: { text: 'No transport data yet' }
    };

    const routeUtilizationOptions = {
        chart: { type: 'donut', height: 260 },
        series: hasRouteData ? routeUtilization.series : [1],
        labels: hasRouteData ? (routeUtilization.labels || []) : ['No Route Data'],
        colors: hasRouteData ? undefined : ['#ced4da'],
        legend: { position: 'bottom' },
    };

    const onTimeDelayedOptions = {
        chart: { type: 'pie', height: 260 },
        series: (todayCompleted + todayDelayed) > 0 ? [todayCompleted, todayDelayed] : [1],
        labels: (todayCompleted + todayDelayed) > 0 ? ['On-time (Completed)', 'Delayed / Pending'] : ['No Trip Data'],
        colors: (todayCompleted + todayDelayed) > 0 ? ['#198754', '#fd7e14'] : ['#ced4da'],
    };

    new ApexCharts(document.querySelector('#dailyTripsChart'), dailyTripsOptions).render();
    new ApexCharts(document.querySelector('#employeesTransportedChart'), employeesTransportedOptions).render();
    new ApexCharts(document.querySelector('#routeUtilizationChart'), routeUtilizationOptions).render();
    new ApexCharts(document.querySelector('#onTimeDelayedChart'), onTimeDelayedOptions).render();
})();
</script>
@endsection
