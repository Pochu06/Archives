<?php

namespace App\Http\Controllers;

use App\Models\AdminActionLog;
use Illuminate\View\View;

class UserGuideController extends Controller
{
    public function index(): View
    {
        $role = (string) session('user_role');
        $activities = collect();

        if ($role === 'super_admin') {
            $activities = AdminActionLog::query()
                ->whereIn('action', [
                    'backup.created',
                    'backup.failed',
                    'restore.completed',
                    'restore.failed',
                ])
                ->with('user:id,name')
                ->latest()
                ->limit(10)
                ->get();
        }

        return view('help.user-guide', compact('role', 'activities'));
    }
}
