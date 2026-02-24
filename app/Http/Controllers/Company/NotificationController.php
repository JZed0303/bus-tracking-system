<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function index()
    {
        return view('company.notifications.index');
    }

    public function markAsRead($notificationId)
    {
        return back();
    }
}
