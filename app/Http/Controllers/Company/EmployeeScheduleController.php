<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\TransportRoute;
use App\Models\Bus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeScheduleController extends Controller
{
    /**
     * List schedules
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        if (!$companyId) {
            abort(403, 'No company assigned to your account.');
        }

        $selectedEmployeeId = $request->integer('employee_id');

        $employees = Employee::with('user')
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->get();

        $schedules = EmployeeSchedule::with([
                'employee.user',
                'route',
                'bus'
            ])
            ->where('company_id', $companyId)
            ->when($selectedEmployeeId, fn ($q) =>
                $q->where('employee_id', $selectedEmployeeId)
            )
            ->when($request->date, fn ($q) =>
                $q->whereDate('schedule_date', $request->date)
            )
            ->when($request->status, fn ($q) =>
                $q->where('status', $request->status)
            )
            ->orderBy('schedule_date')
            ->get();

        return view('company.schedules.index', compact(
            'schedules',
            'employees',
            'selectedEmployeeId'
        ));
    }

    /**
     * Create schedule form
     */
    public function create()
    {
        $companyId = auth()->user()->company_id;
        if (!$companyId) {
            abort(403, 'No company assigned to your account.');
        }

        $activeAssignments = Assignment::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->get([
                'bus_id',
                'route_id',
                'effective_from',
                'effective_to',
            ]);

        $busIds = $activeAssignments->pluck('bus_id')->filter()->unique()->values();
        $busRouteMap = $activeAssignments
            ->groupBy('bus_id')
            ->map(fn ($rows) => $rows->pluck('route_id')->filter()->unique()->values()->all())
            ->all();

        return view('company.schedules.create', [
            'employees' => Employee::with('user')
                ->where('company_id', $companyId)
                ->active()
                ->get(),
            'routes' => TransportRoute::where('company_id', $companyId)
                ->active()
                ->orderBy('name')
                ->get(),
            'buses' => Bus::query()
                ->whereIn('id', $busIds)
                ->active()
                ->orderBy('plate_number')
                ->get(),
            'busRouteMap' => $busRouteMap,
        ]);
    }



    /**
     * Store schedule (single or bulk employees)
     */
    public function store(Request $request)
    {
        $companyId = (int) Auth::user()->company_id;
        if (!$companyId) {
            abort(403, 'No company assigned to your account.');
        }

        $request->validate([
            'employee_id' => [
                'nullable',
                Rule::exists('employees', 'id')->where(fn ($q) =>
                    $q->where('company_id', $companyId)
                ),
            ],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => [
                'integer',
                Rule::exists('employees', 'id')->where(fn ($q) =>
                    $q->where('company_id', $companyId)
                ),
            ],
            'route_id' => [
                'required',
                Rule::exists('routes', 'id')->where(fn ($q) =>
                    $q->where('company_id', $companyId)
                ),
            ],
            'bus_id' => ['nullable', 'integer', 'exists:buses,id'],
            'schedule_date' => ['required', 'date'],
            'shift_name' => ['nullable', 'string', 'max:50'],
            'expected_pickup_time' => ['nullable', 'date_format:H:i'],
            'expected_dropoff_time' => ['nullable', 'date_format:H:i', 'after:expected_pickup_time'],
            'status' => ['nullable', Rule::in(['scheduled', 'completed', 'missed', 'cancelled'])],
        ]);

        $employeeIds = collect($request->input('employee_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($employeeIds->isEmpty() && $request->filled('employee_id')) {
            $employeeIds = collect([(int) $request->integer('employee_id')]);
        }

        if ($employeeIds->isEmpty()) {
            throw ValidationException::withMessages([
                'employee_ids' => 'Please select at least one employee.',
            ]);
        }

        if ($request->filled('bus_id')) {
            $hasMatchingAssignment = Assignment::query()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->where('bus_id', $request->integer('bus_id'))
                ->where('route_id', $request->integer('route_id'))
                ->whereDate('effective_from', '<=', $request->date('schedule_date'))
                ->where(function ($q) use ($request) {
                    $q->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $request->date('schedule_date'));
                })
                ->exists();

            if (!$hasMatchingAssignment) {
                throw ValidationException::withMessages([
                    'bus_id' => 'Selected bus is not assigned to the selected route for the chosen date.',
                ]);
            }
        }

        $scheduleDate = $request->date('schedule_date')->toDateString();
        $payload = [
            'company_id' => $companyId,
            'route_id' => $request->integer('route_id'),
            'bus_id' => $request->integer('bus_id') ?: null,
            'shift_name' => $request->input('shift_name'),
            'expected_pickup_time' => $request->input('expected_pickup_time'),
            'expected_dropoff_time' => $request->input('expected_dropoff_time'),
            'status' => $request->input('status', 'scheduled'),
        ];

        $createdCount = 0;
        $updatedCount = 0;

        DB::transaction(function () use (
            $employeeIds,
            $scheduleDate,
            $payload,
            &$createdCount,
            &$updatedCount
        ) {
            foreach ($employeeIds as $employeeId) {
                $schedule = EmployeeSchedule::query()->updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'schedule_date' => $scheduleDate,
                    ],
                    $payload
                );

                if ($schedule->wasRecentlyCreated) {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }
            }
        });

        return redirect()
            ->route('company.schedules.index')
            ->with(
                'success',
                "Schedule applied to {$employeeIds->count()} employee(s). Created: {$createdCount}, Updated: {$updatedCount}."
            );
    }
}
