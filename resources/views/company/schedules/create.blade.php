@extends('layouts.master')

@section('title')
    Create Employee Schedule
@endsection

@section('page-title')
    Employee Schedules
@endsection

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
<div class="container-fluid">

    <div class="row">
        <div class="col-xl-12 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Create Schedule</h5>
                </div>

                <div class="card-body">

                    <form method="POST" action="{{ route('company.schedules.store') }}">
                        @csrf

                        @php
                            $oldEmployeeIds = collect(old('employee_ids', old('employee_id') ? [old('employee_id')] : []))
                                ->map(fn ($id) => (int) $id)
                                ->all();
                        @endphp

                        <div class="row g-3">
                            {{-- Schedule Date --}}
                            <div class="col-md-4">
                                <label class="form-label">Schedule Date</label>
                                <input type="date"
                                       name="schedule_date"
                                       class="form-control @error('schedule_date') is-invalid @enderror"
                                       value="{{ old('schedule_date', now()->toDateString()) }}"
                                       required>
                                @error('schedule_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Route --}}
                            <div class="col-md-4">
                                <label class="form-label">Route</label>
                                <select id="route_id"
                                        name="route_id"
                                        class="form-select @error('route_id') is-invalid @enderror"
                                        required>
                                    <option value="">Select Route</option>
                                    @foreach($routes as $route)
                                        <option value="{{ $route->id }}" @selected(old('route_id') == $route->id)>
                                            {{ $route->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('route_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Bus --}}
                            <div class="col-md-4">
                                <label class="form-label">Bus (Optional)</label>
                                <select id="bus_id"
                                        name="bus_id"
                                        class="form-select @error('bus_id') is-invalid @enderror">
                                    <option value="">Unassigned</option>
                                    @foreach($buses as $bus)
                                        @php
                                            $routeIds = $busRouteMap[$bus->id] ?? [];
                                        @endphp
                                        <option value="{{ $bus->id }}"
                                                data-route-ids='@json($routeIds)'
                                                @selected(old('bus_id') == $bus->id)>
                                            {{ $bus->plate_number }}{{ $bus->brand_model ? ' • '.$bus->brand_model : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('bus_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Only buses assigned to the selected route are available.</small>
                            </div>

                            {{-- Shift --}}
                            <div class="col-md-4">
                                <label class="form-label">Shift</label>
                                <select name="shift_name" class="form-select @error('shift_name') is-invalid @enderror">
                                    <option value="">Auto / Not Set</option>
                                    <option value="morning" @selected(old('shift_name') === 'morning')>Morning</option>
                                    <option value="afternoon" @selected(old('shift_name') === 'afternoon')>Afternoon</option>
                                    <option value="night" @selected(old('shift_name') === 'night')>Night</option>
                                </select>
                                @error('shift_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Pickup Time --}}
                            <div class="col-md-4">
                                <label class="form-label">Expected Pickup Time</label>
                                <input type="time"
                                       name="expected_pickup_time"
                                       class="form-control @error('expected_pickup_time') is-invalid @enderror"
                                       value="{{ old('expected_pickup_time') }}">
                                @error('expected_pickup_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Drop-off Time --}}
                            <div class="col-md-4">
                                <label class="form-label">Expected Drop-off Time</label>
                                <input type="time"
                                       name="expected_dropoff_time"
                                       class="form-control @error('expected_dropoff_time') is-invalid @enderror"
                                       value="{{ old('expected_dropoff_time') }}">
                                @error('expected_dropoff_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <hr class="my-1">
                            </div>

                            {{-- Employees (bulk table with faces + checklist) --}}
                            <div class="col-12">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                    <div>
                                        <label class="form-label mb-0">Employees For This Schedule</label>
                                        <small class="text-muted d-block">
                                            <span id="selected_employees_count">0</span> selected
                                        </small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="text"
                                               id="employee_search"
                                               class="form-control form-control-sm"
                                               style="min-width: 220px;"
                                               placeholder="Search name, code, department">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-light" id="select_all_employees">Select All</button>
                                            <button type="button" class="btn btn-light" id="clear_all_employees">Clear</button>
                                        </div>
                                    </div>
                                </div>

                                @if($errors->has('employee_ids') || $errors->has('employee_ids.*'))
                                    <div class="alert alert-danger py-2">
                                        {{ $errors->first('employee_ids') ?: $errors->first('employee_ids.*') }}
                                    </div>
                                @endif

                                <div class="table-responsive border rounded">
                                    <table class="table table-sm table-hover align-middle mb-0" id="employee_schedule_table">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:40px;">
                                                    <input type="checkbox" id="check_all_employees" class="form-check-input">
                                                </th>
                                                <th style="width:58px;">Photo</th>
                                                <th>Employee</th>
                                                <th>Code</th>
                                                <th>Department</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($employees as $employee)
                                                @php
                                                    $avatarUrl = $employee->photo_path
                                                        ? asset('storage/'.$employee->photo_path)
                                                        : asset('build/images/users/avatar-1.jpg');
                                                    $searchText = strtolower(trim(
                                                        ($employee->user?->full_name ?? '').' '.
                                                        ($employee->employee_code ?? '').' '.
                                                        ($employee->department ?? '')
                                                    ));
                                                @endphp
                                                <tr data-search="{{ $searchText }}">
                                                    <td>
                                                        <input type="checkbox"
                                                               class="form-check-input employee-checkbox"
                                                               name="employee_ids[]"
                                                               value="{{ $employee->id }}"
                                                               @checked(in_array((int) $employee->id, $oldEmployeeIds, true))>
                                                    </td>
                                                    <td>
                                                        <img src="{{ $avatarUrl }}"
                                                             alt="Avatar"
                                                             class="rounded-circle"
                                                             style="width:38px;height:38px;object-fit:cover;">
                                                    </td>
                                                    <td>{{ $employee->user?->full_name ?? 'No User Assigned' }}</td>
                                                    <td>{{ $employee->employee_code ?? '—' }}</td>
                                                    <td>{{ $employee->department ?? '—' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-3">
                                                        No active employees available.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Submit --}}
                        <div class="text-end">
                            <a href="{{ route('company.schedules.index') }}"
                               class="btn btn-light">
                                Cancel
                            </a>
                            <button class="btn btn-primary">
                                Save Schedule
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>

</div>
@endsection
@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const employeeSearch = document.getElementById('employee_search');
    const employeeRows = Array.from(document.querySelectorAll('#employee_schedule_table tbody tr[data-search]'));
    const employeeCheckboxes = Array.from(document.querySelectorAll('.employee-checkbox'));
    const checkAllEmployees = document.getElementById('check_all_employees');
    const selectedEmployeesCount = document.getElementById('selected_employees_count');
    const selectAllEmployeesBtn = document.getElementById('select_all_employees');
    const clearAllEmployeesBtn = document.getElementById('clear_all_employees');

    const routeSelect = document.getElementById('route_id');
    const busSelect = document.getElementById('bus_id');
    const pickupInput = document.querySelector('input[name="expected_pickup_time"]');
    const shiftSelect = document.querySelector('select[name="shift_name"]');

    function getVisibleEmployeeRows() {
        return employeeRows.filter((row) => !row.classList.contains('d-none'));
    }

    function updateSelectedEmployeesCount() {
        if (!selectedEmployeesCount) return;

        const selected = employeeCheckboxes.filter((cb) => cb.checked).length;
        selectedEmployeesCount.textContent = String(selected);

        if (!checkAllEmployees) return;

        const visibleCheckboxes = getVisibleEmployeeRows()
            .map((row) => row.querySelector('.employee-checkbox'))
            .filter(Boolean);

        const allChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every((cb) => cb.checked);
        const someChecked = visibleCheckboxes.some((cb) => cb.checked);

        checkAllEmployees.checked = allChecked;
        checkAllEmployees.indeterminate = !allChecked && someChecked;
    }

    function filterEmployees() {
        const query = (employeeSearch?.value || '').trim().toLowerCase();

        employeeRows.forEach((row) => {
            const haystack = row.dataset.search || '';
            const matched = !query || haystack.includes(query);
            row.classList.toggle('d-none', !matched);
        });

        updateSelectedEmployeesCount();
    }

    function filterBusesByRoute() {
        if (!routeSelect || !busSelect) return;

        const selectedRouteId = routeSelect.value;
        const currentBus = busSelect.value;

        Array.from(busSelect.options).forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            let routeIds = [];
            try {
                routeIds = JSON.parse(option.dataset.routeIds || '[]');
            } catch (e) {
                routeIds = [];
            }

            const allowed = !selectedRouteId || routeIds.includes(Number(selectedRouteId));
            option.hidden = !allowed;
        });

        const selectedOption = busSelect.options[busSelect.selectedIndex];
        if (selectedOption && selectedOption.hidden) {
            busSelect.value = '';
        } else if (currentBus) {
            busSelect.value = currentBus;
        }
    }

    function autoPickShiftFromPickupTime() {
        if (!pickupInput || !shiftSelect || !pickupInput.value || shiftSelect.value) return;

        const hour = Number(pickupInput.value.split(':')[0] || 0);
        if (hour >= 6 && hour < 14) {
            shiftSelect.value = 'morning';
        } else if (hour >= 14 && hour < 22) {
            shiftSelect.value = 'afternoon';
        } else {
            shiftSelect.value = 'night';
        }
    }

    routeSelect?.addEventListener('change', filterBusesByRoute);
    pickupInput?.addEventListener('change', autoPickShiftFromPickupTime);
    employeeSearch?.addEventListener('input', filterEmployees);

    employeeCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updateSelectedEmployeesCount);
    });

    checkAllEmployees?.addEventListener('change', function () {
        const checked = this.checked;
        getVisibleEmployeeRows().forEach((row) => {
            const checkbox = row.querySelector('.employee-checkbox');
            if (checkbox) {
                checkbox.checked = checked;
            }
        });
        updateSelectedEmployeesCount();
    });

    selectAllEmployeesBtn?.addEventListener('click', function () {
        employeeCheckboxes.forEach((checkbox) => {
            checkbox.checked = true;
        });
        updateSelectedEmployeesCount();
    });

    clearAllEmployeesBtn?.addEventListener('click', function () {
        employeeCheckboxes.forEach((checkbox) => {
            checkbox.checked = false;
        });
        updateSelectedEmployeesCount();
    });

    filterBusesByRoute();
    autoPickShiftFromPickupTime();
    filterEmployees();
});
</script>
@endsection
