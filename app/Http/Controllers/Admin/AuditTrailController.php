<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use OwenIt\Auditing\Models\Audit;

class AuditTrailController extends Controller
{
    public function index(): View
    {
        $auditTrails = Audit::query()
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('admin.audit-trail.index', compact('auditTrails'));
    }
}
