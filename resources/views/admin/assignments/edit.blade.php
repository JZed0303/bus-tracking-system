@extends('layouts.master')

@section('title')
    Edit Assignment
@endsection

@section('page-title')
    Edit Assignment
@endsection

@section('body')
    <body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <!-- PAGE HEADER / ACTIONS -->
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h4 class="mb-1">Edit Assignment</h4>
            <p class="text-muted mb-0">Update assignment details and effective dates.</p>
        </div>
        <div class="col text-end">
            <a href="{{ route('admin.assignments.index') }}" class="btn btn-light">
                <i class="mdi mdi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- FORM -->
    <form method="POST" action="{{ route('admin.assignments.update', $assignment->id) }}">
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Assignment Details</h5>

                <div class="row g-3">

                    <!-- DRIVER -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Driver <span class="text-danger">*</span>
                        </label>
                        <select name="driver_id"
                                class="form-select @error('driver_id') is-invalid @enderror"
                                required>
                            <option value="">Select Driver</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}"
                                    @selected(old('driver_id', $assignment->driver_id) == $driver->id)>
                                    {{ $driver->user->full_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('driver_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- BUS -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Bus <span class="text-danger">*</span>
                        </label>
                        <select name="bus_id"
                                class="form-select @error('bus_id') is-invalid @enderror"
                                required>
                            <option value="">Select Bus</option>
                            @foreach($buses as $bus)
                                <option value="{{ $bus->id }}"
                                    @selected(old('bus_id', $assignment->bus_id) == $bus->id)>
                                    {{ $bus->plate_number }}
                                </option>
                            @endforeach
                        </select>
                        @error('bus_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- ROUTE -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Route <span class="text-danger">*</span>
                        </label>
                        <select name="route_id"
                                class="form-select @error('route_id') is-invalid @enderror"
                                required>
                            <option value="">Select Route</option>
                            @foreach($routes as $route)
                                <option value="{{ $route->id }}"
                                    @selected(old('route_id', $assignment->route_id) == $route->id)>
                                    {{ $route->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('route_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- COMPANY -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Company <span class="text-danger">*</span>
                        </label>
                        <select name="company_id"
                                class="form-select @error('company_id') is-invalid @enderror"
                                required>
                            <option value="">Select Company</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}"
                                    @selected(old('company_id', $assignment->company_id) == $company->id)>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('company_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- EFFECTIVE FROM -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Effective From <span class="text-danger">*</span>
                        </label>
                        <input type="date"
                               name="effective_from"
                               class="form-control @error('effective_from') is-invalid @enderror"
                               value="{{ old('effective_from', optional($assignment->effective_from)->format('Y-m-d')) }}"
                               required>
                        @error('effective_from')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- EFFECTIVE TO -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Effective To</label>
                        <input type="date"
                               name="effective_to"
                               class="form-control @error('effective_to') is-invalid @enderror"
                               value="{{ old('effective_to', optional($assignment->effective_to)->format('Y-m-d')) }}">
                        <small class="text-muted">Leave blank for ongoing assignment.</small>
                        @error('effective_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- TRIP LEG -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Assignment Leg <span class="text-danger">*</span>
                        </label>
                        <select name="leg"
                                class="form-select @error('leg') is-invalid @enderror"
                                required>
                            @php($leg = old('leg', $assignment->leg ?? 'both'))
                            <option value="both" @selected($leg === 'both')>Both (Pickup + Drop-off)</option>
                            <option value="pickup" @selected($leg === 'pickup')>Pickup Only</option>
                            <option value="dropoff" @selected($leg === 'dropoff')>Drop-off Only</option>
                        </select>
                        <small class="text-muted">Use this if pickup and drop-off use different buses.</small>
                        @error('leg')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
            </div>

            <!-- FOOTER ACTIONS -->
            <div class="card-footer d-flex justify-content-end gap-2">
                <a href="{{ route('admin.assignments.index') }}" class="btn btn-light">Cancel</a>
                <button class="btn btn-primary" type="submit">
                    <i class="mdi mdi-content-save"></i> Update Assignment
                </button>
            </div>
        </div>

    </form>

</div>
@endsection

@section('scripts')
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
