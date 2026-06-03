<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\ChatThread;
use App\Models\Checkin;
use App\Models\TransportRoute;
use App\Models\Trip;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class DeveloperToolsController extends Controller
{
    public function index(Request $request): View
    {
        $tripId = null;
        if ($request->filled('trip_id')) {
            $candidateTripId = (int) $request->input('trip_id');
            if ($candidateTripId > 0) {
                $tripId = $candidateTripId;
            }
        }

        $assignments = Assignment::query()
            ->with(['driver.user', 'bus', 'route', 'company'])
            ->latest('id')
            ->limit(50)
            ->get();

        $trips = Trip::query()
            ->with(['assignment.bus', 'assignment.route'])
            ->latest('id')
            ->limit(50)
            ->get();

        $checkins = collect();
        if ($tripId) {
            $checkins = Checkin::query()
                ->with('employee.user')
                ->where('trip_id', $tripId)
                ->orderByDesc('scan_time')
                ->limit(200)
                ->get();
        }

        $threads = ChatThread::query()
            ->withCount(['participants', 'messages'])
            ->latest('id')
            ->limit(50)
            ->get();

        return view('admin.developer-tools.index', [
            'tripId' => $tripId,
            'assignments' => $assignments,
            'trips' => $trips,
            'checkins' => $checkins,
            'threads' => $threads,
            'assignmentCount' => Assignment::query()->count(),
            'tripCount' => Trip::query()->count(),
            'checkinCount' => Checkin::query()->count(),
            'routeCount' => TransportRoute::query()->count(),
            'threadCount' => ChatThread::query()->count(),
        ]);
    }

    public function run(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string'],
            'confirm_text' => ['required', 'string', 'in:RESET'],
            'trip_id' => ['nullable', 'integer', 'min:1'],
            'route_id' => ['nullable', 'integer', 'min:1'],
            'thread_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $action = $validated['action'];
        $tripId = $validated['trip_id'] ?? null;
        $routeId = $validated['route_id'] ?? null;
        $threadId = $validated['thread_id'] ?? null;

        $message = match ($action) {
            'truncate_assignments' => $this->truncateTable('assignments'),
            'truncate_trips' => $this->truncateTable('trips'),
            'truncate_checkins' => $this->truncateTable('checkins'),
            'truncate_routes' => $this->truncateTable('routes'),
            'delete_trip_checkins' => $this->deleteTripCheckins($tripId),
            'delete_trip' => $this->deleteTrip($tripId),
            'delete_route' => $this->deleteRoute($routeId),
            'delete_chat_thread' => $this->deleteChatThread($threadId),
            default => throw new InvalidArgumentException('Unknown action selected.'),
        };

        return back()->with('success', $message);
    }

    private function truncateTable(string $table): string
    {
        if (!Schema::hasTable($table)) {
            return "Table {$table} does not exist. Nothing changed.";
        }

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('TRUNCATE TABLE "' . $table . '" RESTART IDENTITY CASCADE');
        } elseif ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table($table)->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            DB::table($table)->delete();
        }

        return "Table {$table} truncated successfully.";
    }

    private function deleteTripCheckins(?int $tripId): string
    {
        if (!$tripId) {
            throw new InvalidArgumentException('Trip ID is required for delete_trip_checkins.');
        }

        $deleted = Checkin::query()
            ->where('trip_id', $tripId)
            ->delete();

        return "Deleted {$deleted} checkin records for trip {$tripId}.";
    }

    private function deleteTrip(?int $tripId): string
    {
        if (!$tripId) {
            throw new InvalidArgumentException('Trip ID is required for delete_trip.');
        }

        $trip = Trip::query()->find($tripId);
        if (!$trip) {
            return "Trip {$tripId} not found. Nothing changed.";
        }

        $deletedCheckins = 0;
        $deletedLocations = 0;
        $deletedTransfers = 0;

        DB::transaction(function () use ($tripId, $trip, &$deletedCheckins, &$deletedLocations, &$deletedTransfers): void {
            $deletedCheckins = Checkin::query()
                ->where('trip_id', $tripId)
                ->delete();

            if (Schema::hasTable('trip_locations')) {
                $deletedLocations = DB::table('trip_locations')
                    ->where('trip_id', $tripId)
                    ->delete();
            }

            if (Schema::hasTable('trip_employee_transfers')) {
                $deletedTransfers += DB::table('trip_employee_transfers')
                    ->where('from_trip_id', $tripId)
                    ->orWhere('to_trip_id', $tripId)
                    ->delete();
            }

            $trip->delete();
        });

        return "Trip {$tripId} deleted. Removed {$deletedCheckins} checkins, {$deletedLocations} locations, {$deletedTransfers} transfers.";
    }

    private function deleteRoute(?int $routeId): string
    {
        if (!$routeId) {
            throw new InvalidArgumentException('Route ID is required for delete_route.');
        }

        $route = TransportRoute::query()->find($routeId);
        if (!$route) {
            return "Route {$routeId} not found. Nothing changed.";
        }

        $activeAssignmentCount = $route->assignments()->active()->count();
        if ($activeAssignmentCount > 0) {
            return "Cannot delete route {$routeId}. It has {$activeAssignmentCount} active assignment(s).";
        }

        $assignmentIds = Assignment::query()
            ->where('route_id', $routeId)
            ->pluck('id');

        $tripIds = Trip::query()
            ->whereIn('assignment_id', $assignmentIds)
            ->pluck('id');

        $assignmentCount = $assignmentIds->count();
        $tripCount = $tripIds->count();
        $checkinCount = $tripCount > 0
            ? Checkin::query()->whereIn('trip_id', $tripIds)->count()
            : 0;
        $locationCount = ($tripCount > 0 && Schema::hasTable('trip_locations'))
            ? DB::table('trip_locations')->whereIn('trip_id', $tripIds)->count()
            : 0;
        $transferCount = ($tripCount > 0 && Schema::hasTable('trip_employee_transfers'))
            ? DB::table('trip_employee_transfers')
                ->whereIn('from_trip_id', $tripIds)
                ->orWhereIn('to_trip_id', $tripIds)
                ->count()
            : 0;
        $stopCount = Schema::hasTable('route_stops')
            ? DB::table('route_stops')->where('route_id', $routeId)->count()
            : 0;
        $scheduleCount = Schema::hasTable('employee_schedules')
            ? DB::table('employee_schedules')->where('route_id', $routeId)->count()
            : 0;

        DB::transaction(function () use ($routeId, $route): void {
            if (Schema::hasTable('employee_schedules')) {
                DB::table('employee_schedules')->where('route_id', $routeId)->delete();
            }

            Assignment::query()
                ->where('route_id', $routeId)
                ->delete();

            $route->delete();
        });

        return "Route {$routeId} deleted. Removed {$assignmentCount} assignments, {$tripCount} trips, {$checkinCount} checkins, {$locationCount} locations, {$transferCount} transfers, {$stopCount} route stops, {$scheduleCount} schedules.";
    }

    private function deleteChatThread(?int $threadId): string
    {
        if (!$threadId) {
            throw new InvalidArgumentException('Thread ID is required for delete_chat_thread.');
        }

        $thread = ChatThread::query()->find($threadId);
        if (!$thread) {
            return "Chat thread {$threadId} not found. Nothing changed.";
        }

        $messageCount = ChatMessage::query()->where('thread_id', $threadId)->count();
        $participantCount = ChatParticipant::query()->where('thread_id', $threadId)->count();

        DB::transaction(function () use ($thread): void {
            $thread->delete();
        });

        return "Chat thread {$threadId} deleted. Removed {$messageCount} messages and {$participantCount} participants.";
    }
}
