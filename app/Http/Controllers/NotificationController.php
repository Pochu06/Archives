<?php

namespace App\Http\Controllers;

class NotificationController extends Controller
{
    public function markRead(string $id)
    {
        if (! session('user_id')) {
            return redirect()->route('login');
        }

        app('App\\Services\\NotificationCenterService')->markAsRead($id);

        return redirect()->back();
    }

    public function markAllRead()
    {
        if (! session('user_id')) {
            return redirect()->route('login');
        }

        app('App\\Services\\NotificationCenterService')->markAllAsRead();

        return redirect()->back();
    }
}