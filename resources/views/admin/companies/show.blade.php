@extends('layouts.master')

@section('title')
    Company Details
@endsection

@section('css')
    <link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />

    <style>
        /* =========================
           UNIFIED PALETTE (TEAL)
        ========================== */
        :root{
            --brand-900:#064e5a;  /* darkest */
            --brand-800:#0b6674;
            --brand-700:#0c768a;  /* primary base */
            --brand-600:#1293a8;
            --brand-500:#19a9bf;
            --brand-400:#32bad4;
            --brand-100: rgba(12,118,138,.10);
            --brand-075: rgba(12,118,138,.075);
            --brand-050: rgba(12,118,138,.05);
            --text-900:#0f172a;
        }

        /* =========================
           GLOBAL
        ========================== */
        .table td, .table th { vertical-align: middle; }
        .badge { font-weight: 600; }

        .section-gap { margin-bottom: .85rem; }

        .card-clean{
            border: 1px solid rgba(0,0,0,.06);
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
            border-radius: 12px;
        }
        .card-clean .card-body{ padding: .95rem 1.15rem; }

        /* =========================
           HERO
        ========================== */
        .company-hero{
            border-radius: 12px;
            padding: 12px 14px;
            background: linear-gradient(135deg, var(--brand-800) 0%, var(--brand-400) 60%, var(--brand-600) 100%);
            color: #fff;
        }
        .company-avatar{
            width: 44px; height: 44px;
            border-radius: 12px;
            background: rgba(255,255,255,.16);
            display:flex; align-items:center; justify-content:center;
            font-weight: 800; letter-spacing: .5px;
        }
        .company-hero h4{ font-size: 1.1rem; margin: 0; }
        .company-meta{ font-size: .82rem; opacity: .9; margin-top: 1px; }

        /* Buttons (unified) */
        .btn-brand{
            background: var(--brand-700);
            border-color: var(--brand-700);
            color: #fff;
            border-radius: 10px;
            padding: .35rem .7rem;
            line-height: 1.2;
        }
        .btn-brand:hover{
            background: var(--brand-800);
            border-color: var(--brand-800);
            color: #fff;
        }
        .btn-outline-brand{
            border-color: rgba(255,255,255,.75);
            color: #fff;
            border-radius: 10px;
            padding: .35rem .7rem;
            line-height: 1.2;
        }
        .btn-outline-brand:hover{
            background: rgba(255,255,255,.12);
            color: #fff;
        }

        /* =========================
           INFO GRID (denser + teal)
        ========================== */
        .info-grid { row-gap: .55rem; }
        .info-item{
            padding: .55rem .65rem;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,.06);
            background: rgba(0,0,0,.01);
            display:flex; gap:10px; align-items:flex-start;
        }
        .info-icon{
            width: 34px; height: 34px;
            border-radius: 10px;
            background: var(--brand-100);
            border: 1px solid rgba(0,0,0,.06);
            color: var(--brand-800);
            display:flex; align-items:center; justify-content:center;
            flex: 0 0 auto;
        }
        .info-label{ font-size: .82rem; color:#6c757d; margin:0 0 2px 0; line-height: 1.2; }
        .info-value{ font-weight: 600; margin:0; line-height: 1.2; color: var(--text-900); }

        /* =========================
           LOGO
        ========================== */
        .logo-box{
            height: 195px;
            display:flex; align-items:center; justify-content:center;
            border-radius: 12px;
            border: 1px dashed rgba(0,0,0,.16);
            background: rgba(0,0,0,.02);
        }
        .logo-box img{
            max-height: 120px;
            max-width: 100%;
            object-fit: contain;
            border-radius: 10px;
        }

        /* =========================
           SUMMARY (unified teal family)
        ========================== */
        .stat-tile{
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 14px rgba(0,0,0,.08);
            padding: 10px 12px;
            display:flex; align-items:center; justify-content:space-between;
            min-height: 68px;
            color: #fff;
        }
        .stat-title{ font-size:.82rem; color: rgba(255,255,255,.85); margin:0; }
        .stat-value{ font-size:1.3rem; font-weight:800; margin:0; line-height:1; color:#fff; }
        .stat-accent{
            width: 36px; height: 36px;
            border-radius: 12px;
            display:flex; align-items:center; justify-content:center;
            background: rgba(255,255,255,.18);
            color: #fff;
            border: none;
        }

        /* Same brand, slightly different gradients (still unified) */
        .bg-stat-1 { background: linear-gradient(135deg, var(--brand-900), var(--brand-600)); }
        .bg-stat-2 { background: linear-gradient(135deg, var(--brand-800), var(--brand-400)); }
        .bg-stat-3 { background: linear-gradient(135deg, var(--brand-700), var(--brand-500)); }
        .bg-stat-4 { background: linear-gradient(135deg, var(--brand-800), var(--brand-600)); }

        /* =========================
           TABS + TABLE AREA (teal active)
        ========================== */
        .nav-tabs .nav-link { font-weight: 600; padding: .5rem .85rem; color: #334155; }
        .nav-tabs .nav-link.active{
            color: var(--brand-800);
            border-bottom: 2px solid var(--brand-700);
        }

        /* DataTables tidy */
        .dataTables_wrapper .dataTables_filter input { border-radius: 10px; padding: .25rem .5rem; }
        .dataTables_wrapper .dataTables_length select { border-radius: 10px; padding: .15rem .4rem; }
        .table thead th { background: #f8f9fa !important; }

        /* Optional: unify small "Uploaded/Not set" badge look */
        .badge-soft{
            background: var(--brand-050);
            border: 1px solid rgba(0,0,0,.08);
            color: var(--brand-900);
            font-weight: 600;
        }
    </style>
@endsection

@section('page-title')
    Company Details
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    @php
        $initials = strtoupper(substr($company->name ?? 'C', 0, 2));
        $isActive = ($company->status ?? '') === 'active';
        $logoPath = $company->logo ? asset('storage/'.$company->logo) : null;

        $assignedBuses = $company->assignments->count();
        $driversCount  = $company->assignments->pluck('driver_id')->unique()->count();
    @endphp

    <!-- HERO -->
    <div class="company-hero section-gap">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">

                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h4 class="mb-0 text-white">{{ $company->name }}</h4>
                        <span class="badge rounded-pill {{ $isActive ? 'bg-success' : 'bg-danger' }}">
                            {{ $isActive ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="company-meta">Company Information Overview</div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.companies.edit', $company->id) }}" class="text-white btn btn-brand">
                    <i class="mdi mdi-pencil"></i> Edit
                </a>
                <a href="{{ route('admin.companies.index') }}" class="text-white btn btn-outline-brand">
                    <i class="mdi mdi-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <!-- INFO + LOGO -->
    <div class="row g-3 section-gap">
        <div class="col-lg-4">
            <div class="card card-clean h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="card-title mb-0">Company Logo</h5>
                        <span class="badge badge-soft">
                            {{ $logoPath ? 'Uploaded' : 'Not Set' }}
                        </span>
                    </div>

                    <div class="logo-box">
                        @if($logoPath)
                            <img src="{{ $logoPath }}" alt="Company Logo">
                        @else
                            <div class="text-center text-muted">
                                <i class="mdi mdi-image-outline" style="font-size:40px;"></i>
                                <div class="mt-1">No logo uploaded</div>
                            </div>
                        @endif
                    </div>

                    <div class="small text-muted text-center mt-2">
                        {{ $logoPath ? 'Uploaded Logo' : 'Upload a logo for better branding.' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-clean h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h5 class="card-title mb-0">Company Information</h5>
                        <span class="badge rounded-pill {{ $isActive ? 'bg-success' : 'bg-danger' }}">
                            {{ ucfirst($company->status ?? '—') }}
                        </span>
                    </div>

                    <div class="row g-2 info-grid">
                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-icon"><i class="mdi mdi-map-marker-outline"></i></div>
                                <div class="w-100">
                                    <p class="info-label">Address</p>
                                    <p class="info-value">{{ $company->address ?? '—' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-icon"><i class="mdi mdi-account-outline"></i></div>
                                <div class="w-100">
                                    <p class="info-label">Contact Person</p>
                                    <p class="info-value">{{ $company->contact_person ?? '—' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-icon"><i class="mdi mdi-phone-outline"></i></div>
                                <div class="w-100">
                                    <p class="info-label">Contact Number</p>
                                    <p class="info-value">{{ $company->contact_number ?? '—' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-icon"><i class="mdi mdi-email-outline"></i></div>
                                <div class="w-100">
                                    <p class="info-label">Email</p>
                                    <p class="info-value">{{ $company->email ?? '—' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-icon"><i class="mdi mdi-clipboard-list-outline"></i></div>
                                <div class="w-100">
                                    <p class="info-label">Total Assignments</p>
                                    <p class="info-value">{{ $company->assignments?->count() ?? 0 }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-item">
                                <div class="info-icon"><i class="mdi mdi-clock-outline"></i></div>
                                <div class="w-100">
                                    <p class="info-label">Last Updated</p>
                                    <p class="info-value">{{ optional($company->updated_at)->format('M d, Y h:i A') ?? '—' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="small text-muted mt-2">
                        Keep company details updated to ensure accurate assignments and reporting.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SUMMARY -->
    <div class="row g-3 section-gap">
        <div class="col-md-3">
            <div class="stat-tile bg-stat-1">
                <div>
                    <p class="stat-title">Employees</p>
                    <p class="stat-value">{{ $company->employees_count }}</p>
                </div>
                <div class="stat-accent"><i class="mdi mdi-account-group-outline"></i></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-tile bg-stat-2">
                <div>
                    <p class="stat-title">Routes</p>
                    <p class="stat-value">{{ $company->routes_count }}</p>
                </div>
                <div class="stat-accent"><i class="mdi mdi-map-outline"></i></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-tile bg-stat-3">
                <div>
                    <p class="stat-title">Assignments</p>
                    <p class="stat-value">{{ $assignedBuses }}</p>
                </div>
                <div class="stat-accent"><i class="mdi mdi-clipboard-list-outline"></i></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-tile bg-stat-4">
                <div>
                    <p class="stat-title">Drivers</p>
                    <p class="stat-value">{{ $driversCount }}</p>
                </div>
                <div class="stat-accent"><i class="mdi mdi-steering"></i></div>
            </div>
        </div>
    </div>

    <!-- RECORDS -->
    <div class="card card-clean mb-3">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div>
                    <h5 class="card-title mb-0">Company Records</h5>
                    <small class="text-muted">Employees, routes, and assignments</small>
                </div>
            </div>

            <ul class="nav nav-tabs mb-2">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#employees">
                        <i class="mdi mdi-account-group-outline me-1"></i> Employees
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#routes">
                        <i class="mdi mdi-map-outline me-1"></i> Routes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#assignments">
                        <i class="mdi mdi-clipboard-list-outline me-1"></i> Assignments
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="employees">
                    <table id="employeesTable" class="table table-bordered table-striped dt-responsive nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Employee Code</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($company->employees as $employee)
                                @php
                                    $empName = $employee->user->full_name ?? $employee->user->name ?? '—';
                                    $empStatus = $employee->status ?? '—';
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $empName }}</td>
                                    <td>{{ $employee->employee_code ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $empStatus === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($empStatus) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="routes">
                    <table id="routesTable" class="table table-bordered table-striped dt-responsive nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Route Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($company->routes as $route)
                                @php $routeStatus = $route->status ?? '—'; @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $route->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $routeStatus === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($routeStatus) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="assignments">
                    <table id="assignmentsTable" class="table table-bordered table-striped dt-responsive nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Driver</th>
                                <th>Bus</th>
                                <th>Route</th>
                                <th>Effective From</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($company->assignments as $assignment)
                                @php
                                    $drvName = $assignment->driver->user->full_name ?? $assignment->driver->user->name ?? '—';
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $drvName }}</td>
                                    <td>{{ $assignment->bus->plate_number ?? '—' }}</td>
                                    <td>{{ $assignment->route->name ?? '—' }}</td>
                                    <td>
                                        {{ $assignment->effective_from
                                            ? \Carbon\Carbon::parse($assignment->effective_from)->format('M d, Y')
                                            : '—'
                                        }}
                                    </td>
                                </tr>
                            @endforeach
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
    let routesLoaded = false;
    let assignmentsLoaded = false;

    $(document).ready(function () {
        $('#employeesTable').DataTable({
            responsive: true,
            pageLength: 10
        });

        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            const target = $(e.target).attr('href');

            if (target === '#routes' && !routesLoaded) {
                $('#routesTable').DataTable({ responsive: true, pageLength: 10 });
                routesLoaded = true;
            }

            if (target === '#assignments' && !assignmentsLoaded) {
                $('#assignmentsTable').DataTable({
                    responsive: true,
                    pageLength: 10,
                    order: [[3, 'desc']]
                });
                assignmentsLoaded = true;
            }
        });
    });
</script>

<script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
