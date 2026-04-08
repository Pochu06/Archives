<?php

namespace App\Http\Controllers;

use App\Models\Research;
use App\Models\User;
use App\Models\College;
use App\Models\Category;

class DashboardController extends Controller
{
    public function index()
    {
        if (!session('user_id')) {
            return redirect()->route('login');
        }

        $role = session('user_role');
        $userId = session('user_id');
        $collegeId = session('user_college_id');

        if ($role === 'super_admin') {
            $totalResearch = Research::count();
            $pendingResearch = Research::where('status', 'pending')->count();
            $approvedResearch = Research::where('status', 'approved')->count();
            $totalUsers = User::count();
            $totalColleges = College::count();
            $totalCategories = Category::count();
            $recentResearch = Research::with(['user', 'college', 'category'])->orderBy('created_at', 'desc')->take(8)->get();
            $collegeStats = College::withCount(['research'])->get();
            $researchByCollege = College::withCount('research')->get();

            return view('dashboard.super_admin', compact(
                'totalResearch', 'pendingResearch', 'approvedResearch',
                'totalUsers', 'totalColleges', 'totalCategories',
                'recentResearch', 'collegeStats', 'researchByCollege'
            ));
        }

        if ($role === 'admin') {
            $totalResearch = Research::where('college_id', $collegeId)->count();
            $pendingResearch = Research::where('college_id', $collegeId)->where('status', 'pending')->count();
            $approvedResearch = Research::where('college_id', $collegeId)->where('status', 'approved')->count();
            $totalStudents = User::where('college_id', $collegeId)->where('role', 'student')->count();
            $totalAdvisers = User::where('college_id', $collegeId)->where('role', 'adviser')->count();
            $recentResearch = Research::with(['user', 'category'])->where('college_id', $collegeId)->orderBy('created_at', 'desc')->take(8)->get();

            return view('dashboard.admin', compact(
                'totalResearch', 'pendingResearch', 'approvedResearch',
                'totalStudents', 'totalAdvisers', 'recentResearch'
            ));
        }

        if ($role === 'adviser') {
            $user = \App\Models\User::find($userId);
            $myStudentIds = $user->students()->pluck('users.id');
            $totalSubmissions = Research::whereIn('user_id', $myStudentIds)->count();
            $pendingReview = Research::whereIn('user_id', $myStudentIds)->where('status', 'pending')->count();
            $approved = Research::whereIn('user_id', $myStudentIds)->where('status', 'approved')->count();
            $totalStudents = $myStudentIds->count();
            $recentResearch = Research::with(['user', 'category'])->whereIn('user_id', $myStudentIds)->orderBy('created_at', 'desc')->take(8)->get();

            return view('dashboard.adviser', compact(
                'totalSubmissions', 'pendingReview', 'approved', 'totalStudents', 'recentResearch'
            ));
        }

        // Student
        $myResearch = Research::where('user_id', $userId)->count();
        $pendingResearch = Research::where('user_id', $userId)->where('status', 'pending')->count();
        $approvedResearch = Research::where('user_id', $userId)->where('status', 'approved')->count();
        $rejectedResearch = Research::where('user_id', $userId)->where('status', 'rejected')->count();
        $recentResearch = Research::with(['category'])->where('user_id', $userId)->orderBy('created_at', 'desc')->take(5)->get();
        $browseResearch = Research::with(['user', 'college', 'category'])->where('status', 'approved')->orderBy('created_at', 'desc')->take(6)->get();

        return view('dashboard.student', compact(
            'myResearch', 'pendingResearch', 'approvedResearch', 'rejectedResearch',
            'recentResearch', 'browseResearch'
        ));
    }
}
