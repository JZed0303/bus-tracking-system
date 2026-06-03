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

    .hm-context-card {
        border: 1px solid var(--hm-line);
        border-radius: 0.9rem;
        background: linear-gradient(180deg, #fff 0%, #fbfcff 100%);
        box-shadow: 0 12px 28px rgba(16, 24, 40, 0.06);
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

    .hm-chart-card {
        border: 1px solid var(--hm-line);
        border-radius: 0.9rem;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(16, 24, 40, 0.06);
    }
</style>
@endsection
@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">
    @if(!empty($isSuperAdmin))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card hm-context-card">
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
                [
                    'label' => 'Active Routes',
                    'value' => $activeRoutes,
                    'meta'  => 'Routes currently available',
                    'icon'  => 'mdi-map-marker-path',
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
                    <div class="hm-ops-metric text-success">{{ number_format($onlineBusesCount) }}</div>
                    <p class="hm-ops-note">
                        Out of {{ number_format($assignedBusesCount) }} assigned buses currently in operation
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12">
            <div class="card hm-ops-card h-100">
                <div class="card-body">
                    <div class="hm-ops-header">
                        <div>
                            <h6 class="hm-ops-title">Trip Completion</h6>
                            <p class="hm-ops-subtitle">Today's completion health</p>
                        </div>
                        <span class="hm-ops-badge">
                            <i class="mdi mdi-target"></i>
                            KPI
                        </span>
                    </div>
                    <div class="hm-ops-metric">{{ $tripCompletionPercent }}%</div>
                    <p class="{{ $tripCompletionPercent >= 75 ? 'text-success' : 'text-warning' }}">
                        <i class="mdi {{ $tripCompletionPercent >= 75 ? 'mdi-arrow-up' : 'mdi-alert-outline' }}"></i>
                        {{ $tripCompletionPercent >= 75 ? 'Healthy completion trend' : 'Needs monitoring today' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card hm-ops-card h-100">
                <div class="card-body">
                    <small class="text-muted">Incidents Today</small>
                    <h3 class="mb-1 text-danger">{{ number_format($incidentsTodayCount) }}</h3>
                    <small class="text-muted">Maintenance/Breakdown/Emergency reported today</small>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card hm-ops-card h-100">
                <div class="card-body">
                    <small class="text-muted">Pending Transfer Confirmations</small>
                    <h3 class="mb-1 text-warning">{{ number_format($pendingTransferConfirmationsCount) }}</h3>
                    <small class="{{ ($pendingTransferEscalationsCount ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ number_format($pendingTransferEscalationsCount ?? 0) }} beyond SLA
                    </small>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12">
            <div class="card hm-ops-card h-100">
                <div class="card-body">
                    <small class="text-muted">Unresolved Reassignment Cases</small>
                    <h3 class="mb-1 {{ $unresolvedReassignmentCasesCount > 0 ? 'text-danger' : 'text-success' }}">
                        {{ number_format($unresolvedReassignmentCasesCount) }}
                    </h3>
                    <small class="text-muted">Incident exceeded replacement SLA with no replacement trip</small>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== ANALYTICS (startpage chart block pattern) ===================== --}}
    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card hm-chart-card h-100">
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
            <div class="card hm-chart-card h-100">
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
            <div class="card hm-chart-card h-100">
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
            <div class="card hm-chart-card h-100">
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
            <div class="card hm-chart-card">
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
        noData: { text: 'No trip trend data yet' }
    };

    const employeesTransportedOptions = {
        chart: { type: 'bar', toolbar: { show: false }, height: 260 },
        series: [{ name: 'Employees', data: transportTrend.series || [] }],
        xaxis: { categories: transportTrend.labels || [] },
        dataLabels: { enabled: false },
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
        colors: (todayCompleted + todayDelayed) > 0 ? undefined : ['#ced4da'],
    };

    new ApexCharts(document.querySelector('#dailyTripsChart'), dailyTripsOptions).render();
    new ApexCharts(document.querySelector('#employeesTransportedChart'), employeesTransportedOptions).render();
    new ApexCharts(document.querySelector('#routeUtilizationChart'), routeUtilizationOptions).render();
    new ApexCharts(document.querySelector('#onTimeDelayedChart'), onTimeDelayedOptions).render();
})();
</script>
@endsection
