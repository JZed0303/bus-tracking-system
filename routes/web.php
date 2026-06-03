<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SettingsController;

// Admin Controllers
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeQrController;
use App\Http\Controllers\Admin\EmployeeAttendanceController;
use App\Http\Controllers\Admin\EmployeeTripController;
use App\Http\Controllers\Admin\BusController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\DriverTripController;
use App\Http\Controllers\Admin\DriverAssignmentController;
use App\Http\Controllers\Admin\DriverAuthLogController;
use App\Http\Controllers\Admin\LiveMapPageController;
use App\Http\Controllers\Admin\LiveTrackingController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\RouteDirectionsController;
use App\Http\Controllers\Admin\TripController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\UserPermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ActiveBusPageController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;

use App\Http\Controllers\Admin\AuditTrailController;
use App\Http\Controllers\Admin\DeveloperToolsController;

use App\Http\Controllers\Admin\ModuleController;


// Chat (admin + company reuse)
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\BusChatController;
use App\Http\Controllers\Admin\GroupChatController;

// Company Controllers
use App\Http\Controllers\Company\DashboardController as CompanyDashboardController;
use App\Http\Controllers\Company\EmployeeController as CompanyEmployeeController;
use App\Http\Controllers\Company\DriverController as CompanyDriverController;
use App\Http\Controllers\Company\TripController as CompanyTripController;
use App\Http\Controllers\Company\ReportController as CompanyReportController;
use App\Http\Controllers\Company\NotificationController as CompanyNotificationController;
use App\Http\Controllers\Company\ProfileController as CompanyProfileController;
use App\Http\Controllers\Company\EmployeeQrController as CompanyEmployeeQrController;
use App\Http\Controllers\Company\EmployeeScheduleController;
use App\Http\Controllers\Company\AssignmentController as CompanyAssignmentController;
use App\Http\Controllers\Company\BusController as CompanyBusController;
use App\Events\ChatMessageSent;

Auth::routes();

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'dashboard'])->name('home');

Route::middleware('auth')->group(function () {
    Route::redirect('/settings', '/settings/theme-override')->name('settings.index');
    Route::get('/settings/theme-override', [SettingsController::class, 'themeOverride'])->name('settings.theme-override.index');
    Route::post('/settings/theme-override', [SettingsController::class, 'updateThemeOverride'])->name('settings.theme-override.update');
});


Route::get('/debug/trips/{trip}/gps', function(\App\Models\Trip $trip) {
    return $trip->locations()
        ->orderBy('tracked_at')
        ->get([
            'latitude as lat',
            'longitude as lng',
            'speed',
            'tracked_at'
        ])
        ->map(function ($item) {
            $item->tracked_at = \Carbon\Carbon::parse($item->tracked_at)->format('h:i A');
            return $item;
        });
});


Route::get('/debug-chat-sound/{threadId}', function ($threadId) {
    $user = auth()->user();

    event(new ChatMessageSent(
        threadId: (int) $threadId,
        payload: [
            'message_id' => 999999,
            'sender_id'  => 12345, // some OTHER ID, not $user->id
            'thread_id'  => (int) $threadId,
            'body'       => 'Test sound',
            'created_at' => now()->toDateTimeString(),
        ]
    ));

    return 'event fired';
})->middleware('auth');
/*
|--------------------------------------------------------------------------
| ADMIN ROUTES (SUPER ADMIN CORE MODULES)
|--------------------------------------------------------------------------
| Restricted to super_admin only.
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin'])
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

        // Super Admin profile
        Route::get('/profile', [AdminProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password.update');

        // Schedules (reuse Company\EmployeeScheduleController)
        Route::resource('schedules', EmployeeScheduleController::class)
            ->only(['index', 'create', 'store']);

        // Active buses page
        Route::get('buses/active', [ActiveBusPageController::class, 'index'])
            ->name('buses.active');

        // Users module
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index')->middleware('can:view_users');
            Route::get('/archive', [UserController::class, 'archive'])->name('archive')->middleware('can:manage_users');
            Route::get('/create', [UserController::class, 'create'])->name('create')->middleware('can:manage_users');
            Route::post('/', [UserController::class, 'store'])->name('store')->middleware('can:manage_users');
            Route::post('{id}/restore', [UserController::class, 'restore'])->name('restore')->middleware('can:manage_users');
            Route::get('{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('can:manage_users');
            Route::put('{user}', [UserController::class, 'update'])->name('update')->middleware('can:manage_users');
            Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('can:manage_users');
        });

        // User permission management
        Route::middleware('can:manage_user_permissions')->group(function () {
            Route::get('users/permissions', [UserPermissionController::class, 'index'])
                ->name('users.permissions.index');

            Route::get('users/{user}/permissions', [UserPermissionController::class, 'edit'])
                ->name('users.permissions.edit');

            Route::post('users/{user}/permissions', [UserPermissionController::class, 'update'])
                ->name('users.permissions.update');

        });

        Route::middleware('can:manage_role_permissions')->group(function () {
            Route::get('audit-trail', [AuditTrailController::class, 'index'])
                ->name('audit-trail.index');
        });

        // Companies
        Route::get('companies/archive', [CompanyController::class, 'archive'])->name('companies.archive');
        Route::post('companies/{id}/restore', [CompanyController::class, 'restore'])->name('companies.restore');
        Route::resource('companies', CompanyController::class);

        // Employees
        Route::get('employees/archive', [EmployeeController::class, 'archive'])->name('employees.archive');
        Route::post('employees/{id}/restore', [EmployeeController::class, 'restore'])->name('employees.restore');
        Route::resource('employees', EmployeeController::class)
            ->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);

        Route::get('/employees/{employee}/qr', [EmployeeQrController::class, 'show'])->name('employees.qr');
        Route::post('/employees/{employee}/qr', [EmployeeQrController::class, 'generate'])->name('employees.qr.generate');

        Route::get('/employees/{employee}/attendance', [EmployeeAttendanceController::class, 'index'])
            ->name('employees.attendance');

        Route::get('/employees/{employee}/trips', [EmployeeTripController::class, 'index'])
            ->name('employees.trips');

        // Buses
        Route::get('buses/archive', [BusController::class, 'archive'])->name('buses.archive');
        Route::post('buses/{id}/restore', [BusController::class, 'restore'])->name('buses.restore');
        Route::resource('buses', BusController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::get('buses/{bus}/json', [BusController::class, 'json'])->name('buses.json');

        // Routes
        Route::get('routes/{route}/preview', [RouteController::class, 'preview'])->name('routes.preview');
        Route::resource('routes', RouteController::class);

        // Drivers
        Route::prefix('drivers')->name('drivers.')->group(function () {
            Route::get('/', [DriverController::class, 'index'])->name('index');
            Route::get('/archive', [DriverController::class, 'archive'])->name('archive');
            Route::post('/', [DriverController::class, 'store'])->name('store');
            Route::post('{id}/restore', [DriverController::class, 'restore'])->name('restore');
            Route::get('{driver}', [DriverController::class, 'show'])->name('show');
            Route::put('{driver}', [DriverController::class, 'update'])->name('update');
            Route::delete('{driver}', [DriverController::class, 'destroy'])->name('destroy');
            Route::get('{driver}/assignment', [DriverAssignmentController::class, 'show'])->name('assignment');
            Route::post('{driver}/assignment', [DriverAssignmentController::class, 'store'])->name('assignment.store');
            Route::get('{driver}/auth-logs', [DriverAuthLogController::class, 'index'])->name('auth-logs');
            Route::get('{driver}/trips', [DriverTripController::class, 'index'])->name('trips');
        });

        // Assignments
        Route::prefix('assignments')->name('assignments.')->group(function () {

            Route::get('/', [AssignmentController::class, 'index'])->name('index');

            Route::get('/create', [AssignmentController::class, 'create'])->name('create');
            Route::post('/', [AssignmentController::class, 'store'])->name('store');

            Route::get('/{assignment}', [AssignmentController::class, 'show'])->name('show');

            Route::get('/{assignment}/edit', [AssignmentController::class, 'edit'])->name('edit');
            Route::put('/{assignment}', [AssignmentController::class, 'update'])->name('update');

            Route::get('/{assignment}/timeline', [AssignmentController::class, 'timeline'])->name('timeline');
        });

        // Trips (admin view)
        Route::prefix('trips')->name('trips.')->group(function () {
            Route::get('active', [TripController::class, 'active'])->name('active');
            Route::get('/', [TripController::class, 'today'])->name('today');
            Route::get('{trip}', [TripController::class, 'show'])->name('show');
            Route::get('{trip}/report', [TripController::class, 'report'])->name('report');
            Route::get('{trip}/report/export/{type}', [TripController::class, 'exportReport'])->name('report.export');
            Route::get('{trip}/gps-playback', [TripController::class, 'gpsPlayback'])->name('gps-playback');
            Route::post('{trip}/incident', [TripController::class, 'reportIncident'])->name('incident');
            Route::post('{trip}/checkins/{checkin}/void', [TripController::class, 'voidCheckin'])->name('checkins.void');
        });

        // Calendar module
        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');

        // Live map page (ADMIN: super_admin)
        Route::get('/live-map', [LiveMapPageController::class, 'index'])->name('live-map');
        Route::get('/video-calls', [LiveMapPageController::class, 'videoCall'])->name('video-calls.index');

        // Developer tools (SUPER ADMIN ONLY)
        Route::get('/developer-tools', [DeveloperToolsController::class, 'index'])
            ->middleware('role:super_admin')
            ->name('developer-tools.index');
        Route::post('/developer-tools/run', [DeveloperToolsController::class, 'run'])
            ->middleware('role:super_admin')
            ->name('developer-tools.run');

        // Live tracking JSON endpoints (ADMIN: super_admin)
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/live-buses', [LiveTrackingController::class, 'index'])->name('live-buses');
            // Secure ORS proxy for route-map pages (key remains server-side).
            Route::post('/routes/directions', [RouteDirectionsController::class, 'store'])->name('routes.directions');
        });
    });

/*
|--------------------------------------------------------------------------
| COMPANY ROUTES (COMPANY ADMIN MODULES)
|--------------------------------------------------------------------------
| All company panel routes now live here (no more in company.php).
*/
Route::prefix('company')
    ->name('company.')
    ->middleware(['auth', 'role:company_admin|super_admin'])
    ->group(function () {

        // Dashboard (separate company dashboard controller)
        Route::get('/dashboard', [CompanyDashboardController::class, 'index'])
            ->name('dashboard');

        // Employees (company view)
        Route::prefix('employees')->name('employees.')->group(function () {
            Route::get('/', [CompanyEmployeeController::class, 'index'])
                ->middleware('can:view_employees')
                ->name('index');
            Route::get('/create', [CompanyEmployeeController::class, 'create'])
                ->middleware('can:create_employees')
                ->name('create');
            Route::post('/', [CompanyEmployeeController::class, 'store'])
                ->middleware('can:create_employees')
                ->name('store');
            Route::get('/{employee}', [CompanyEmployeeController::class, 'show'])
                ->middleware('can:view_employees')
                ->name('show');
            Route::get('/{employee}/edit', [CompanyEmployeeController::class, 'edit'])
                ->middleware('can:update_employees')
                ->name('edit');
            Route::put('/{employee}', [CompanyEmployeeController::class, 'update'])
                ->middleware('can:update_employees')
                ->name('update');
        });

        // Drivers (company view: assigned drivers only)
        Route::get('drivers', [CompanyDriverController::class, 'index'])
            ->middleware('can:view_drivers')
            ->name('drivers.index');

        // Buses (company view)
        Route::prefix('buses')->name('buses.')->group(function () {
            Route::get('/', [CompanyBusController::class, 'index'])
                ->middleware('can:view_buses')
                ->name('index');
            Route::post('/', [CompanyBusController::class, 'store'])
                ->middleware('can:create_buses')
                ->name('store');
            Route::put('{bus}', [CompanyBusController::class, 'update'])
                ->middleware('can:update_buses')
                ->name('update');
            Route::get('{bus}/json', [CompanyBusController::class, 'json'])
                ->middleware('can:view_buses')
                ->name('json');
        });

        Route::get('employees/{employee}/qr', [CompanyEmployeeQrController::class, 'show'])
            ->middleware('can:view_employees')
            ->name('employees.qr');

        Route::post('employees/{employee}/qr', [CompanyEmployeeQrController::class, 'generate'])
            ->middleware('can:update_employees')
            ->name('employees.qr.generate');

        Route::get('employees/{employee}/attendance', [EmployeeAttendanceController::class, 'index'])
            ->middleware('can:view_employees')
            ->name('employees.attendance');




        Route::prefix('trips')->name('trips.')->group(function () {
            Route::get('active', [CompanyTripController::class, 'active'])->name('active');
            Route::get('/', [CompanyTripController::class, 'today'])->name('today');
            Route::get('{trip}', [CompanyTripController::class, 'show'])->name('show');
            Route::get('{trip}/report', [CompanyTripController::class, 'report'])->name('report');
            Route::get('{trip}/report/export/{type}', [CompanyTripController::class, 'exportReport'])->name('report.export');
            Route::get('{trip}/gps-playback', [CompanyTripController::class, 'gpsPlayback'])->name('gps-playback');
            Route::post('{trip}/incident', [CompanyTripController::class, 'reportIncident'])->name('incident');
            Route::post('{trip}/checkins/{checkin}/void', [CompanyTripController::class, 'voidCheckin'])->name('checkins.void');
        });

        // Calendar module (company scope)
        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');




        // Reports
        Route::get('/reports', [CompanyReportController::class, 'index'])
            ->middleware('can:view_reports')
            ->name('reports.index');

        Route::get('/reports/export/{type}', [CompanyReportController::class, 'export'])
            ->middleware('can:view_reports')
            ->name('reports.export');

        Route::get('/reports/print', [CompanyReportController::class, 'print'])
            ->middleware('can:view_reports')
            ->name('reports.print');

        // Notifications
        Route::get('/notifications', [CompanyNotificationController::class, 'index'])
            ->name('notifications.index');

        Route::post('/notifications/{notification}/read', [CompanyNotificationController::class, 'markAsRead'])
            ->name('notifications.read');

        // Profile
        Route::get('/profile', [CompanyProfileController::class, 'show'])
            ->name('profile.show');

        Route::post('/profile', [CompanyProfileController::class, 'update'])
            ->name('profile.update');

        // Schedules (company)
        Route::resource('schedules', EmployeeScheduleController::class)
            ->only(['index', 'create', 'store']);

        // ------------------------------
        // Company Chat (Web UI + JSON)
        // ------------------------------

        // UI list page
        Route::get('/chats', [ChatController::class, 'index'])
            ->name('chat.index');

        // JSON endpoints (MUST come BEFORE /chat/{thread})
        Route::prefix('chat')->name('chat.')->group(function () {

            Route::get('/threads', [ChatController::class, 'threads'])
                ->name('threads');

            Route::get('/threads/{thread}/meta', [ChatController::class, 'meta'])
                ->whereNumber('thread')
                ->name('meta');

            Route::get('/threads/{thread}/messages', [ChatController::class, 'messages'])
                ->whereNumber('thread')
                ->name('messages');

            Route::post('/threads/{thread}/messages', [ChatController::class, 'send'])
                ->whereNumber('thread')
                ->name('send');
        });

        // Thread page (AFTER /chat/threads)
        Route::get('/chat/{thread}', [ChatController::class, 'show'])
            ->whereNumber('thread')
            ->name('chat.show');

        // Live map page (COMPANY)
        Route::get('/live-map', [LiveMapPageController::class, 'index'])
            ->name('live-map');
        Route::get('/video-calls', [LiveMapPageController::class, 'videoCall'])
            ->name('video-calls.index');

        // OPTIONAL: old URL for backward compatibility
        Route::get('/live-tracking', [LiveMapPageController::class, 'index'])
            ->name('live-tracking');

        // Live tracking JSON endpoints (COMPANY)
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/live-buses', [LiveTrackingController::class, 'index'])
                ->name('live-buses'); // route('company.api.live-buses')
        });
        Route::get('assignments', [CompanyAssignmentController::class, 'index'])
            ->name('assignments.index');

        Route::get('assignments/{assignment}', [CompanyAssignmentController::class, 'show'])
            ->name('assignments.show');

        Route::get('assignments/{assignment}/timeline', [CompanyAssignmentController::class, 'timeline'])
            ->name('assignments.timeline');
    });

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES (CHAT SYSTEM - WEB UI)
|--------------------------------------------------------------------------
| Accessible to super_admin/admin/company_admin (add employee/driver if needed).
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin|admin|company_admin'])
    ->group(function () {

        // Inbox / list page
        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');

        // Chat JSON
        Route::prefix('chat')->name('chat.')->group(function () {

            Route::get('/threads', [ChatController::class, 'threads'])
                ->name('threads');

            Route::get('/threads/{thread}/meta', [ChatController::class, 'meta'])
                ->whereNumber('thread')
                ->name('meta');

            Route::get('/threads/{thread}/messages', [ChatController::class, 'messages'])
                ->whereNumber('thread')
                ->name('messages');

            Route::post('/threads/{thread}/messages', [ChatController::class, 'send'])
                ->whereNumber('thread')
                ->name('send');
        });

        // Thread page
        Route::get('/chat/{thread}', [ChatController::class, 'show'])
            ->whereNumber('thread')
            ->name('chat.show');

        // Optional: Bus chat
        Route::get('/bus-chat', [BusChatController::class, 'index'])->name('bus-chat.index');

        Route::prefix('bus-chat')->name('bus-chat.')->group(function () {

            Route::get('/threads', [BusChatController::class, 'threads'])
                ->name('threads');

            Route::get('/threads/{thread}/messages', [BusChatController::class, 'messages'])
                ->whereNumber('thread')
                ->name('messages');

            Route::post('/threads/{thread}/messages', [BusChatController::class, 'sendMessage'])
                ->whereNumber('thread')
                ->name('messages.send');
        });
    });

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES (GROUP CHAT MANAGEMENT - SUPER ADMIN)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:super_admin'])
    ->group(function () {

        Route::get('/group-chats', [GroupChatController::class, 'index'])
            ->name('group-chats.index');

        Route::prefix('group-chats')->name('group-chats.')->group(function () {
            Route::get('/users', [GroupChatController::class, 'users'])->name('users');
            Route::get('/threads', [GroupChatController::class, 'threads'])->name('threads');
            Route::post('/threads', [GroupChatController::class, 'store'])->name('store');

            Route::get('/threads/{thread}', [GroupChatController::class, 'show'])->name('show');
            Route::post('/threads/{thread}/participants', [GroupChatController::class, 'addParticipants'])->name('participants.add');
            Route::delete('/threads/{thread}/participants/{user}', [GroupChatController::class, 'removeParticipant'])->name('participants.remove');
        });
    });

/*
|--------------------------------------------------------------------------
| ROLES & PERMISSIONS
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'can:manage_role_permissions'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::post('permissions', [RoleController::class, 'storePermission'])
            ->middleware('role:super_admin')
            ->name('permissions.store');
        Route::get('roles/{role}/permissions', [RolePermissionController::class, 'edit'])->name('roles.permissions.edit');
        Route::post('roles/{role}/permissions', [RolePermissionController::class, 'update'])->name('roles.permissions.update');

        Route::prefix('modules')->name('modules.')->middleware('role:super_admin')->group(function () {
            Route::get('/', [ModuleController::class, 'index'])->name('index');
            Route::post('/', [ModuleController::class, 'store'])->name('store');
            Route::put('{module}', [ModuleController::class, 'update'])->name('update');
            Route::delete('{module}', [ModuleController::class, 'destroy'])->name('destroy');
        });
    });

/*
|--------------------------------------------------------------------------
| CATCH-ALL (MUST BE LAST)
|--------------------------------------------------------------------------
*/
Route::get('{any}', [HomeController::class, 'index'])
    ->where('any', '^(?!admin($|\/)|admin\/api|company|broadcasting|build|assets|storage).*$');
