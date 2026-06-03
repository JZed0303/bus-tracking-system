<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class LiveMapPageController extends Controller
{
    public function index()
    {
        return view('admin.live-map.index');
    }

    public function videoCall()
    {
        return view('admin.video-calls.index');
    }
}
