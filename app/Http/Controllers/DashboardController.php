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
            $pendingResearch = Research::whereIn('status', [Research::STATUS_PENDING_COLLEGE, Research::STATUS_PENDING_RDE])->count();
            $approvedResearch = Research::where('status', Research::STATUS_APPROVED)->count();
            $totalUsers = User::count();
            $totalColleges = College::count();
            $totalCategories = Category::count();
            $recentResearch = Research::with(['user', 'college', 'category'])->orderBy('created_at', 'desc')->take(8)->get();
            $researchByCollege = College::withCount('research')->get();

            return view('dashboards.super_admin', compact(
                'totalResearch', 'totalUsers', 'totalColleges', 'totalCategories',
                'recentResearch', 'researchByCollege'
            ));
        }

        if ($role === 'admin') {
            $researchQuery = Research::query();
            $userQuery = User::where('role', 'student');
            $recentQuery = Research::with(['user', 'category']);
            $pendingQuery = Research::query();

            if ($collegeId) {
                $researchQuery->where('college_id', $collegeId);
                $userQuery->where('college_id', $collegeId);
                $recentQuery->where('college_id', $collegeId);
                $pendingQuery->where('college_id', $collegeId)->where('status', Research::STATUS_PENDING_COLLEGE);
            } else {
                $pendingQuery->where('status', Research::STATUS_PENDING_RDE);
            }

            $totalResearch = $researchQuery->count();
            $totalStudents = $userQuery->count();
            $recentResearch = $recentQuery->orderBy('created_at', 'desc')->take(8)->get();
            $pendingResearch = $pendingQuery->count();

            return view('dashboards.admin', compact(
                'totalResearch', 'totalStudents', 'recentResearch', 'pendingResearch'
            ));
        }

        // Student
        $myResearch = Research::where('user_id', $userId)->count();
        $recentResearch = Research::with(['category'])->where('user_id', $userId)->orderBy('created_at', 'desc')->take(5)->get();
        $browseResearch = Research::with(['user', 'college', 'category'])->approved()->orderBy('created_at', 'desc')->take(6)->get();

        return view('dashboards.student', compact(
            'myResearch', 'recentResearch', 'browseResearch'
        ));
    }
}