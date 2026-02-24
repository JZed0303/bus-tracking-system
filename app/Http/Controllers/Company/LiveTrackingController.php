<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

class LiveTrackingController extends Controller
{
    public function index()
    {
        return view('company.live-tracking');
    }
}
