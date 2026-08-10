<?php

namespace App\Http\Controllers;

use App\Models\AdminActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminActionLogController extends Controller
{
    public function index(Request $request)
    {
        if (! session('user_id')) {
            return redirect()->route('login');
        }

        if (session('user_role') !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        $query = AdminActionLog::query()->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('route_name', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('super-admin.admin-action-logs.index', compact('logs'));
    }
}
