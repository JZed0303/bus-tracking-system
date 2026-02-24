<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Company\DashboardController;
use App\Http\Controllers\Company\EmployeeController;
use App\Http\Controllers\Company\TripController;
use App\Http\Controllers\Company\ReportController;
use App\Http\Controllers\Company\NotificationController;
use App\Http\Controllers\Company\ProfileController;
use App\Http\Controllers\Company\EmployeeQrController;
use App\Http\Controllers\Company\EmployeeScheduleController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\EmployeeAttendanceController;

Route::prefix('company')
    ->name('company.')
    ->middleware(['web', 'auth', 'role:company_admin|super_admin'])
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');
Route::resource('employees', EmployeeController::class)
    ->only(['index', 'show', 'store', 'update', 'create', 'edit']);


        Route::get('employees/{employee}/qr', [EmployeeQrController::class, 'show'])
            ->name('employees.qr');

        Route::post('employees/{employee}/qr', [EmployeeQrController::class, 'generate'])
            ->name('employees.qr.generate');

        Route::get('employees/{employee}/attendance', [EmployeeAttendanceController::class, 'index'])
            ->name('employees.attendance');


        Route::get('/reports', [ReportController::class, 'index'])
            ->name('reports.index');

        Route::get('/reports/export/{type}', [ReportController::class, 'export'])
            ->name('reports.export');

        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');

        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->name('notifications.read');

        Route::get('/profile', [ProfileController::class, 'show'])
            ->name('profile.show');

        Route::post('/profile', [ProfileController::class, 'update'])
            ->name('profile.update');

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
    });
