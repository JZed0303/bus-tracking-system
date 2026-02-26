<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Trip;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class CalendarController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $companyId = $user->isCompanyUser() ? $user->company_id : null;
        $today = now('Asia/Manila')->startOfDay();

        $assignments = Assignment::query()
            ->with(['driver.user', 'route', 'company'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get();

        $trips = Trip::query()
            ->with(['assignment.route', 'assignment.driver.user', 'assignment.company', 'assignment.bus'])
            ->when($companyId, function ($q) use ($companyId) {
                $q->whereHas('assignment', fn ($aq) => $aq->where('company_id', $companyId));
            })
            ->get();

        $events = [];

        foreach ($assignments as $assignment) {
            $statusKey = $assignment->isUpcoming()
                ? 'upcoming'
                : ($assignment->isActive() ? 'ongoing' : 'completed');
            $driverName = optional($assignment->driver?->user)->full_name;
            $routeName = optional($assignment->route)->name;

            $start = $assignment->effective_from?->copy()->startOfDay();
            $endExclusive = $assignment->effective_to
                ? $assignment->effective_to->copy()->addDay()->startOfDay()
                : null;

            if (!$start) {
                continue;
            }

            $assignmentShowRoute = Route::has('company.assignments.show')
                ? route('company.assignments.show', $assignment->id)
                : route('admin.assignments.show', $assignment->id);

            $events[] = [
                'title' => 'Assignment: ' . ($driverName ?? 'Driver')
                    . ' • ' . ($routeName ?? 'Route'),
                'start' => $start->toDateString(),
                'end' => $endExclusive?->toDateString(),
                'allDay' => true,
                'url' => $user->isCompanyUser() ? $assignmentShowRoute : route('admin.assignments.show', $assignment->id),
                'className' => ['event-assignment', 'event-status-' . $statusKey],
                'extendedProps' => [
                    'module' => 'assignment',
                    'status' => $statusKey,
                    'route' => $routeName,
                    'driver' => $driverName,
                ],
            ];
        }

        foreach ($trips as $trip) {
            $tripDate = $trip->trip_date;
            if (!$tripDate) {
                continue;
            }

            $scheduledStartRaw = $trip->getRawOriginal('scheduled_start_time');
            $scheduledEndRaw = $trip->getRawOriginal('scheduled_end_time');

            $scheduledStart = $scheduledStartRaw
                ? Carbon::parse($tripDate->toDateString() . ' ' . $scheduledStartRaw, 'Asia/Manila')
                : null;
            $scheduledEnd = $scheduledEndRaw
                ? Carbon::parse($tripDate->toDateString() . ' ' . $scheduledEndRaw, 'Asia/Manila')
                : null;

            $actualStart = $trip->actual_start_time?->timezone('Asia/Manila');
            $actualEnd = $trip->actual_end_time?->timezone('Asia/Manila');

            $startAt = $actualStart ?? $scheduledStart ?? $tripDate->copy()->startOfDay();
            $endAt = $actualEnd ?? $scheduledEnd;

            $statusKey = match ($trip->status) {
                'ongoing' => 'ongoing',
                'completed', 'cancelled' => 'completed',
                default => ($startAt->greaterThan($today) ? 'upcoming' : 'completed'),
            };

            $tripShowRoute = $user->isCompanyUser() && Route::has('company.trips.show')
                ? route('company.trips.show', $trip->id)
                : route('admin.trips.show', $trip->id);

            $events[] = [
                'title' => 'Trip: ' . (optional($trip->assignment?->route)->name ?? 'Route')
                    . ' • ' . ucfirst((string) $trip->direction),
                'start' => $startAt->toIso8601String(),
                'end' => $endAt?->toIso8601String(),
                'allDay' => false,
                'url' => $tripShowRoute,
                'className' => ['event-trip', 'event-status-' . $statusKey],
                'extendedProps' => [
                    'module' => 'trip',
                    'status' => $statusKey,
                    'route' => optional($trip->assignment?->route)->name,
                    'driver' => optional($trip->assignment?->driver?->user)->full_name,
                    'bus' => optional($trip->assignment?->bus)->plate_number,
                ],
            ];
        }

        return view('calendar', [
            'calendarEvents' => $events,
        ]);
    }
}
