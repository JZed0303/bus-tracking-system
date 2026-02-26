<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Contracts\View\View;

class DriverController extends Controller
{
    public function index(): View
    {
        $companyId = (int) auth()->user()->company_id;

        if ($companyId <= 0) {
            abort(403, 'No company assigned to your account.');
        }

        $drivers = Driver::query()
            ->with([
                'user',
                'currentAssignment.bus',
                'currentAssignment.route',
            ])
            ->whereHas('currentAssignment', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->latest()
            ->get();

        return view('company.drivers.index', compact('drivers'));
    }
}
