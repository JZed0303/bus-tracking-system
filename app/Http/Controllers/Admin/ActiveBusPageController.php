<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActiveBusSummaryService;

class ActiveBusPageController extends Controller
{
    public function index(ActiveBusSummaryService $service)
    {
        $trips = $service->get();

        return view('admin.buses.active', [
            'trips' => $trips,
            'service' => $service,
        ]);
    }
}
