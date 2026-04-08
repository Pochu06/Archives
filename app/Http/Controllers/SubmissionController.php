<?php

namespace App\Http\Controllers;

use App\Models\Research;

class SubmissionController extends Controller
{
    public function index()
    {
        if (!session('user_id')) return redirect()->route('login');
        if (session('user_role') !== 'student') return redirect()->route('dashboard');

        $research = Research::with(['college', 'category', 'adviser'])
            ->where('user_id', session('user_id'))
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('submissions.index', compact('research'));
    }
}
