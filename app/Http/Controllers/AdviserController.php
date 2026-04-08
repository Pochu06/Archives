<?php

namespace App\Http\Controllers;

use App\Models\Research;
use App\Models\User;
use Illuminate\Http\Request;

class AdviserController extends Controller
{
    private function requireAdviser()
    {
        if (!session('user_id')) return redirect()->route('login');
        if (session('user_role') !== 'adviser') return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        return null;
    }

    public function submissions(Request $request)
    {
        if ($r = $this->requireAdviser()) return $r;

        $adviser = User::find(session('user_id'));
        $studentIds = $adviser->students()->pluck('users.id');

        $query = Research::with(['user', 'category', 'college'])->whereIn('user_id', $studentIds);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $research = $query->orderBy('created_at', 'desc')->paginate(12);
        return view('adviser.submissions', compact('research'));
    }

    public function students()
    {
        if ($r = $this->requireAdviser()) return $r;

        $adviser = User::find(session('user_id'));
        $students = $adviser->students()->with('college')->paginate(15);
        return view('adviser.students', compact('students'));
    }
}
