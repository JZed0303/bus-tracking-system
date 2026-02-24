<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Contracts\View\View;

class DriverAuthLogController extends Controller
{
    public function index(Driver $driver): View
    {
        $logs = $driver->user->authLogs()->latest()->get();

        return view('admin.drivers.auth-logs', compact('driver', 'logs'));
    }
}
