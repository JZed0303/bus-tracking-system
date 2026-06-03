@extends('layouts.master')

@section('title')
    Create Assignment
@endsection

@section('page-title')
    Create Assignment
@endsection

@section('css')
    <link rel="stylesheet" href="{{ URL::asset('build/libs/twitter-bootstrap-wizard/prettify.css') }}">
    <style>
        #assignment-progress-wizard .nav-pills .nav-link.active,
        #assignment-progress-wizard .twitter-bs-wizard-nav .nav-link.active {
            background: #ffffff !important;
            border-color: #d2def1 !important;
            color: #1f2a37 !important;
        }
        .assignment-wizard-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        }
        .assignment-wizard-intro {
            max-width: 720px;
        }
        .assignment-wizard .twitter-bs-wizard-nav {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 1.25rem;
            padding-left: 0;
            list-style: none;
        }
        .assignment-wizard .twitter-bs-wizard-nav .nav-item {
            float: none;
            width: auto;
        }
        .assignment-wizard .twitter-bs-wizard-nav .nav-link {
            display: block;
            height: 100%;
            padding: 14px 16px;
            border: 1px solid #e5ebf3;
            border-radius: 16px;
            background: #f8fbff;
            color: #526176;
            transition: all 0.2s ease;
        }
        .assignment-wizard .twitter-bs-wizard-nav .nav-link.active,
        .assignment-wizard .twitter-bs-wizard-nav .nav-link:hover {
            border-color: #d2def1;
            background: #ffffff;
            box-shadow: 0 10px 24px rgba(31, 92, 192, 0.08);
        }
        .assignment-wizard .step-number {
            display: block;
            margin-bottom: 4px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #7a8799;
        }
        .assignment-wizard .step-title {
            display: block;
            font-size: 1rem;
            font-weight: 700;
            color: #1f2a37;
        }
        .assignment-wizard-progress {
            height: 8px;
            border-radius: 999px;
            background: #edf3fb;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        .assignment-wizard-progress .progress-bar {
            border-radius: 999px;
            background: linear-gradient(90deg, #1f7ae0 0%, #3fbf88 100%);
        }
        .assignment-step-panel {
            min-height: 380px;
        }
        .assignment-step-kicker {
            margin-bottom: 4px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #7a8799;
        }
        .assignment-step-title {
            margin-bottom: 0.35rem;
            font-size: 1.45rem;
            font-weight: 700;
            color: #1f2a37;
        }
        .assignment-step-copy {
            margin-bottom: 1.5rem;
            color: #6b7788;
        }
        .assignment-field-card {
            height: 100%;
            padding: 18px;
            border: 1px solid #e6edf6;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbfe 100%);
        }
        .assignment-field-card .form-label {
            font-weight: 700;
            color: #1f2a37;
        }
        .assignment-field-note {
            margin-top: 8px;
            color: #7a8799;
            font-size: 0.82rem;
        }
        .assignment-review-card {
            height: 100%;
            padding: 18px;
            border: 1px solid #e6edf6;
            border-radius: 16px;
            background: #fbfdff;
        }
        .assignment-review-label {
            margin-bottom: 4px;
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #7a8799;
        }
        .assignment-review-value {
            margin-bottom: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #1f2a37;
        }
        .assignment-review-empty {
            color: #98a4b5;
        }
        .assignment-review-hero {
            padding: 24px;
            border: 1px solid #dce6f3;
            border-radius: 18px;
            background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
        }
        .assignment-review-hero-icon {
            width: 60px;
            height: 60px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(63, 191, 136, 0.12);
            color: #1f7ae0;
            font-size: 1.8rem;
        }
        .assignment-preview-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
            margin-top: 1.5rem;
        }
        .assignment-preview-grid.assignment-preview-grid-triple {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .assignment-preview-grid.assignment-preview-grid-single {
            grid-template-columns: 1fr;
        }
        .assignment-preview-card {
            min-height: 250px;
            padding: 18px;
            border: 1px solid #e6edf6;
            border-radius: 18px;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbfe 100%);
        }
        .assignment-preview-kicker {
            margin-bottom: 10px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #7a8799;
        }
        .assignment-preview-media {
            width: 100%;
            height: 170px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
            background: #f1f6fb;
            overflow: hidden;
            margin-bottom: 14px;
        }
        .assignment-preview-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .assignment-preview-media.is-avatar img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }
        .assignment-preview-title {
            margin-bottom: 4px;
            font-size: 1.05rem;
            font-weight: 700;
            color: #1f2a37;
        }
        .assignment-preview-subtitle {
            margin-bottom: 0;
            color: #6b7788;
            font-size: 0.88rem;
        }
        .assignment-preview-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            height: 100%;
            color: #93a1b3;
            gap: 10px;
        }
        .assignment-preview-empty i {
            font-size: 2rem;
        }
        .assignment-wizard .pager {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 1.5rem;
            padding-left: 0;
            list-style: none;
        }
        .assignment-wizard .pager li > a,
        .assignment-wizard .pager li > button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 0.72rem 1.2rem;
            border: 1px solid #d8e3f0;
            border-radius: 999px;
            background: #fff;
            color: #1f2a37;
            font-weight: 700;
        }
        .assignment-wizard .pager li.next > a {
            background: #1f7ae0;
            border-color: #1f7ae0;
            color: #fff;
        }
        .assignment-wizard .pager li.finish {
            margin-left: auto;
            display: none;
        }
        .assignment-wizard .pager li.finish .btn {
            border-radius: 999px;
            padding: 0.72rem 1.2rem;
            font-weight: 700;
        }
        @media (max-width: 991.98px) {
            .assignment-wizard .twitter-bs-wizard-nav {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .assignment-preview-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 575.98px) {
            .assignment-wizard .twitter-bs-wizard-nav {
                grid-template-columns: 1fr;
            }
            .assignment-wizard .pager {
                flex-wrap: wrap;
            }
            .assignment-wizard .pager li,
            .assignment-wizard .pager li > a,
            .assignment-wizard .pager li > button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endsection

@section('body')
    <body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        
        <div class="col-auto">
            <a href="{{ route('admin.assignments.index') }}" class="btn btn-light">
                <i class="mdi mdi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.assignments.store') }}" id="assignment-wizard-form">
        @csrf

        <div class="card assignment-wizard-card">
            <div class="card-body p-4 p-lg-5">
                <div id="assignment-progress-wizard" class="twitter-bs-wizard assignment-wizard">
                    <ul class="twitter-bs-wizard-nav nav-justified">
                        <li class="nav-item wizard-border">
                            <a href="#assignment-step-resources" class="nav-link wizard-step" data-toggle="tab">
                                <span class="step-number">01. Team</span>
                                <span class="step-title">Driver, Bus & Company</span>
                            </a>
                        </li>
                        <li class="nav-item wizard-border">
                            <a href="#assignment-step-routing" class="nav-link wizard-step " data-toggle="tab">
                                <span class="step-number">02. Route</span>
                                <span class="step-title">Route Preview</span>
                            </a>
                        </li>
                        <li class="nav-item wizard-border">
                            <a href="#assignment-step-schedule" class="nav-link wizard-step" data-toggle="tab">
                                <span class="step-number">03. Timing</span>
                                <span class="step-title">Schedule & Leg</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#assignment-step-review" class="nav-link wizard-step" data-toggle="tab">
                                <span class="step-number">04. Review</span>
                                <span class="step-title">Confirm Assignment</span>
                            </a>
                        </li>
                    </ul>

                    <div class="assignment-wizard-progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated"></div>
                    </div>

                    <div class="tab-content twitter-bs-wizard-tab-content">
                        <div class="tab-pane" id="assignment-step-resources">
                            <div class="assignment-step-panel">
                                <p class="assignment-step-kicker">Step 1</p>
                                <h5 class="assignment-step-title">Choose the operating team</h5>
                                <p class="assignment-step-copy">Pick the driver, bus, and company that will handle this assignment. Resources already used by ongoing trips are hidden.</p>

                                <div class="row g-4">
                                    <div class="col-lg-4">
                                        <div class="assignment-field-card">
                                            <label class="form-label">Driver <span class="text-danger">*</span></label>
                                            <select name="driver_id" class="form-select @error('driver_id') is-invalid @enderror" required data-summary-target="driver">
                                                <option value="">Select Driver</option>
                                                @foreach($drivers as $driver)
                                                    <option
                                                        value="{{ $driver->id }}"
                                                        data-summary="{{ $driver->user->full_name }}"
                                                        data-image="{{ $driver->photo_url }}"
                                                        data-subtitle="{{ $driver->company?->name ?? 'Assigned driver' }}"
                                                        @selected(old('driver_id') == $driver->id)
                                                    >
                                                        {{ $driver->user->full_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="assignment-field-note">Only available drivers are shown here.</div>
                                            @error('driver_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="assignment-field-card">
                                            <label class="form-label">Bus <span class="text-danger">*</span></label>
                                            <select name="bus_id" class="form-select @error('bus_id') is-invalid @enderror" required data-summary-target="bus">
                                                <option value="">Select Bus</option>
                                                @foreach($buses as $bus)
                                                    <option
                                                        value="{{ $bus->id }}"
                                                        data-summary="{{ $bus->plate_number }}"
                                                        data-image="{{ $bus->photo_url }}"
                                                        data-subtitle="{{ $bus->brand_model ?? 'Selected vehicle' }}"
                                                        @selected(old('bus_id') == $bus->id)
                                                    >
                                                        {{ $bus->plate_number }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="assignment-field-note">Choose the vehicle that will be tied to the assignment.</div>
                                            @error('bus_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="assignment-field-card">
                                            <label class="form-label">Company <span class="text-danger">*</span></label>
                                            <select name="company_id" class="form-select @error('company_id') is-invalid @enderror" required data-summary-target="company">
                                                <option value="">Select Company</option>
                                                @foreach($companies as $company)
                                                    <option
                                                        value="{{ $company->id }}"
                                                        data-summary="{{ $company->name }}"
                                                        data-image="{{ $company->logo_url }}"
                                                        data-subtitle="{{ $company->address ?? 'Operating company' }}"
                                                        @selected(old('company_id') == $company->id)
                                                    >
                                                        {{ $company->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="assignment-field-note">Choose the company responsible for this assignment.</div>
                                            @error('company_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="assignment-preview-grid assignment-preview-grid-triple">
                                    <div class="assignment-preview-card">
                                        <p class="assignment-preview-kicker">Driver Preview</p>
                                        <div id="driver-preview-card"></div>
                                    </div>
                                    <div class="assignment-preview-card">
                                        <p class="assignment-preview-kicker">Bus Preview</p>
                                        <div id="bus-preview-card"></div>
                                    </div>
                                    <div class="assignment-preview-card">
                                        <p class="assignment-preview-kicker">Company Preview</p>
                                        <div id="company-preview-card"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane" id="assignment-step-routing">
                            <div class="assignment-step-panel">
                                <p class="assignment-step-kicker">Step 2</p>
                                <h5 class="assignment-step-title">Select the route being operated</h5>
                                <p class="assignment-step-copy">Choose the saved route and review its map before continuing.</p>

                                <div class="row g-4">
                                    <div class="col-lg-12">
                                        <div class="assignment-field-card">
                                            <label class="form-label">Route <span class="text-danger">*</span></label>
                                            <select name="route_id" class="form-select @error('route_id') is-invalid @enderror" required data-summary-target="route">
                                                <option value="">Select Route</option>
                                                @foreach($routes as $route)
                                                    <option
                                                        value="{{ $route->id }}"
                                                        data-summary="{{ $route->name }}"
                                                        data-subtitle="{{ $route->company?->name ?? 'Operational route' }}"
                                                        @selected(old('route_id') == $route->id)
                                                    >
                                                        {{ $route->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="assignment-field-note">Pick the saved route that this assignment will follow.</div>
                                            @error('route_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="tab-pane" id="assignment-step-schedule">
                            <div class="assignment-step-panel">
                                <p class="assignment-step-kicker">Step 3</p>
                                <h5 class="assignment-step-title">Define the assignment timing</h5>
                                <p class="assignment-step-copy">Set when the assignment starts, when it ends, and whether it covers pickup, drop-off, or both.</p>

                                <div class="row g-4">
                                    <div class="col-lg-4">
                                        <div class="assignment-field-card">
                                            <label class="form-label">Effective From <span class="text-danger">*</span></label>
                                            <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror" value="{{ old('effective_from') }}" required data-summary-target="effective_from">
                                            <div class="assignment-field-note">This is the first active date for the assignment.</div>
                                            @error('effective_from')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="assignment-field-card">
                                            <label class="form-label">Effective To</label>
                                            <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror" value="{{ old('effective_to') }}" data-summary-target="effective_to">
                                            <div class="assignment-field-note">Leave blank if this should remain ongoing.</div>
                                            @error('effective_to')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="assignment-field-card">
                                            <label class="form-label">Assignment Leg <span class="text-danger">*</span></label>
                                            <select name="leg" class="form-select @error('leg') is-invalid @enderror" required data-summary-target="leg">
                                                <option value="both" data-summary="Both (Pickup + Drop-off)" @selected(old('leg', 'both') === 'both')>Both (Pickup + Drop-off)</option>
                                                <option value="pickup" data-summary="Pickup Only" @selected(old('leg') === 'pickup')>Pickup Only</option>
                                                <option value="dropoff" data-summary="Drop-off Only" @selected(old('leg') === 'dropoff')>Drop-off Only</option>
                                            </select>
                                            <div class="assignment-field-note">Use separate legs when pickup and drop-off need different assignments.</div>
                                            @error('leg')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane" id="assignment-step-review">
                            <div class="assignment-step-panel">
                                <p class="assignment-step-kicker">Step 4</p>
                                <h5 class="assignment-step-title">Review before saving</h5>
                                <p class="assignment-step-copy">Double-check the selected resources, route ownership, and schedule details before creating the assignment.</p>

                                <div class="assignment-review-hero mb-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="assignment-review-hero-icon">
                                            <i class="mdi mdi-check-decagram-outline"></i>
                                        </span>
                                        <div>
                                            <h6 class="mb-1">Assignment is ready for confirmation</h6>
                                            <p class="text-muted mb-0">Use Previous if you want to adjust any part before saving.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-6 col-xl-4">
                                        <div class="assignment-review-card">
                                            <p class="assignment-review-label">Driver</p>
                                            <p class="assignment-review-value" data-review="driver">Not selected</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4">
                                        <div class="assignment-review-card">
                                            <p class="assignment-review-label">Bus</p>
                                            <p class="assignment-review-value" data-review="bus">Not selected</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4">
                                        <div class="assignment-review-card">
                                            <p class="assignment-review-label">Route</p>
                                            <p class="assignment-review-value" data-review="route">Not selected</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4">
                                        <div class="assignment-review-card">
                                            <p class="assignment-review-label">Company</p>
                                            <p class="assignment-review-value" data-review="company">Not selected</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4">
                                        <div class="assignment-review-card">
                                            <p class="assignment-review-label">Effective From</p>
                                            <p class="assignment-review-value" data-review="effective_from">Not set</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4">
                                        <div class="assignment-review-card">
                                            <p class="assignment-review-label">Effective To</p>
                                            <p class="assignment-review-value" data-review="effective_to">Ongoing</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4">
                                        <div class="assignment-review-card">
                                            <p class="assignment-review-label">Assignment Leg</p>
                                            <p class="assignment-review-value" data-review="leg">Both (Pickup + Drop-off)</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <ul class="pager wizard twitter-bs-wizard-pager-link">
                        <li class="previous"><a href="javascript: void(0);"><i class="mdi mdi-arrow-left"></i> Previous</a></li>
                        <li class="next"><a href="javascript: void(0);">Next <i class="mdi mdi-arrow-right"></i></a></li>
                        <li class="finish">
                            <button class="btn btn-primary" type="submit">
                                <i class="mdi mdi-content-save"></i> Save Assignment
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
    <script src="{{ URL::asset('build/libs/twitter-bootstrap-wizard/jquery.bootstrap.wizard.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/twitter-bootstrap-wizard/prettify.js') }}"></script>
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        $(function () {
            const $wizard = $('#assignment-progress-wizard');
            const $form = $('#assignment-wizard-form');
            const stepFields = {
                0: ['driver_id', 'bus_id', 'company_id'],
                1: ['route_id'],
                2: ['effective_from', 'effective_to', 'leg'],
                3: []
            };

            function setProgress(index, total) {
                const percent = ((index + 1) / total) * 100;
                $wizard.find('.progress-bar').css({ width: percent + '%' });
            }

            function getDisplayValue($field) {
                if (!$field.length) {
                    return '';
                }

                if ($field.is('select')) {
                    const $selected = $field.find('option:selected');
                    return $selected.data('summary') || $selected.text().trim();
                }

                return $field.val();
            }

            function normalizeDisplayValue(target, value) {
                if (!value) {
                    if (target === 'effective_to') {
                        return 'Ongoing';
                    }

                    return 'Not selected';
                }

                return value;
            }

            function refreshReview() {
                $form.find('[data-summary-target]').each(function () {
                    const $field = $(this);
                    const target = $field.data('summary-target');
                    const value = normalizeDisplayValue(target, getDisplayValue($field));
                    const $review = $form.find(`[data-review="${target}"]`);
                    $review.text(value);
                    $review.toggleClass('assignment-review-empty', /^(Not selected|Not set|Ongoing)$/.test(value));
                });
            }

            function validateStep(index) {
                const names = stepFields[index] || [];
                let valid = true;

                names.forEach(function (name) {
                    const field = $form.find(`[name="${name}"]`).get(0);
                    if (!field) {
                        return;
                    }

                    if (!field.checkValidity()) {
                        field.reportValidity();
                        valid = false;
                    }
                });

                return valid;
            }

            function renderEmptyPreview(icon, title, subtitle) {
                return `
                    <div class="assignment-preview-empty">
                        <i class="mdi ${icon}"></i>
                        <div>
                            <div class="assignment-preview-title">${title}</div>
                            <p class="assignment-preview-subtitle">${subtitle}</p>
                        </div>
                    </div>
                `;
            }

            function updatePreview(containerId, fieldName, options = {}) {
                const field = $form.find(`[name="${fieldName}"]`);
                const selected = field.find('option:selected');
                const container = document.getElementById(containerId);

                if (!container) {
                    return;
                }

                if (!selected.length || !selected.val()) {
                    container.innerHTML = renderEmptyPreview(
                        options.emptyIcon || 'mdi-image-outline',
                        options.emptyTitle || 'Nothing selected yet',
                        options.emptySubtitle || 'Choose an item to see its preview.'
                    );
                    return;
                }

                const title = selected.data('summary') || selected.text().trim();
                const subtitle = selected.data('subtitle') || options.fallbackSubtitle || '';
                const image = selected.data('image') || '';
                const mediaClass = options.avatar ? 'assignment-preview-media is-avatar' : 'assignment-preview-media';

                container.innerHTML = `
                    <div class="${mediaClass}">
                        <img src="${image}" alt="${title}">
                    </div>
                    <div class="assignment-preview-title">${title}</div>
                    <p class="assignment-preview-subtitle">${subtitle}</p>
                `;
            }

            function refreshVisualPreviews() {
                updatePreview('driver-preview-card', 'driver_id', {
                    avatar: true,
                    emptyIcon: 'mdi-account-circle-outline',
                    emptyTitle: 'No driver selected',
                    emptySubtitle: 'Pick a driver to show their photo here.',
                    fallbackSubtitle: 'Assigned driver'
                });

                updatePreview('bus-preview-card', 'bus_id', {
                    emptyIcon: 'mdi-bus-side',
                    emptyTitle: 'No bus selected',
                    emptySubtitle: 'Pick a bus to show its photo here.',
                    fallbackSubtitle: 'Selected vehicle'
                });

                updatePreview('company-preview-card', 'company_id', {
                    emptyIcon: 'mdi-domain',
                    emptyTitle: 'No company selected',
                    emptySubtitle: 'Pick a company to show its logo here.',
                    fallbackSubtitle: 'Operating company'
                });
            }

            $wizard.bootstrapWizard({
                tabClass: 'nav nav-pills nav-justified',
                onTabShow: function (tab, navigation, index) {
                    const total = navigation.find('li').length;
                    setProgress(index, total);
                    refreshReview();
                    refreshVisualPreviews();

                    const isLast = index === total - 1;
                    $wizard.find('.next').toggle(!isLast);
                    $wizard.find('.finish').toggle(isLast);
                },
                onNext: function (tab, navigation, index) {
                    return validateStep(index - 1);
                },
                onTabClick: function (tab, navigation, index, clickedIndex) {
                    if (clickedIndex > index && !validateStep(index)) {
                        return false;
                    }

                    return true;
                }
            });

            $form.find('select, input').on('change input', function () {
                refreshReview();
                refreshVisualPreviews();
            });
            refreshReview();
            refreshVisualPreviews();

            @if($errors->any())
                const errorFieldNames = @json(array_keys($errors->toArray()));
                let targetStep = 0;

                if (errorFieldNames.some(name => ['route_id'].includes(name))) {
                    targetStep = 1;
                } else if (errorFieldNames.some(name => ['effective_from', 'effective_to', 'leg'].includes(name))) {
                    targetStep = 2;
                }

                $wizard.bootstrapWizard('show', targetStep);
            @endif
        });
    </script>
@endsection
