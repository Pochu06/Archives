@extends('layouts.app')
@section('title', 'Super Admin Dashboard')
@section('page-title', 'Super Admin Dashboard')
@section('page-subtitle', 'System-wide overview and statistics')
@section('content')
<div class="space-y-6">
    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-gradient-to-br from-orange-500 to-orange-700 rounded-2xl p-5 text-white shadow-lg">
            <i class="fas fa-file-alt text-2xl mb-3 opacity-80"></i>
            <p class="text-3xl font-extrabold">{{ $totalResearch }}</p>
            <p class="text-orange-100 text-sm">Total Research</p>
        </div>
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl p-5 text-white shadow-lg">
            <i class="fas fa-clock text-2xl mb-3 opacity-80"></i>
            <p class="text-3xl font-extrabold">{{ $pendingResearch }}</p>
            <p class="text-yellow-100 text-sm">Pending Review</p>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-2xl p-5 text-white shadow-lg">
            <i class="fas fa-check-circle text-2xl mb-3 opacity-80"></i>
            <p class="text-3xl font-extrabold">{{ $approvedResearch }}</p>
            <p class="text-green-100 text-sm">Approved</p>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl p-5 text-white shadow-lg">
            <i class="fas fa-users text-2xl mb-3 opacity-80"></i>
            <p class="text-3xl font-extrabold">{{ $totalUsers }}</p>
            <p class="text-blue-100 text-sm">Total Users</p>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl p-5 text-white shadow-lg">
            <i class="fas fa-university text-2xl mb-3 opacity-80"></i>
            <p class="text-3xl font-extrabold">{{ $totalColleges }}</p>
            <p class="text-purple-100 text-sm">Colleges</p>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-700 rounded-2xl p-5 text-white shadow-lg">
            <i class="fas fa-tags text-2xl mb-3 opacity-80"></i>
            <p class="text-3xl font-extrabold">{{ $totalCategories }}</p>
            <p class="text-red-100 text-sm">Categories</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Research -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-bold text-gray-800 text-lg">Recent Research Submissions</h3>
                <a href="{{ route('research.index') }}" class="text-orange-600 text-sm font-semibold hover:underline">View All</a>
            </div>
            <div class="divide-y divide-gray-50">
                @forelse($recentResearch as $r)
                <div class="p-4 flex items-start space-x-4 hover:bg-orange-50/30 transition">
                    <div class="bg-orange-100 p-2.5 rounded-xl flex-shrink-0">
                        <i class="fas fa-file-alt text-orange-600"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('research.show', $r->id) }}" class="font-semibold text-gray-800 text-sm hover:text-orange-600 line-clamp-1 block">{{ $r->title }}</a>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $r->college->code ?? 'N/A' }} &bull; {{ $r->user->name ?? 'Unknown' }} &bull; {{ $r->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-full {{ $r->status_badge }} font-medium flex-shrink-0">{{ ucfirst($r->status) }}</span>
                </div>
                @empty
                <p class="p-6 text-gray-500 text-center">No research submissions yet.</p>
                @endforelse
            </div>
        </div>

        <!-- College Stats -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h3 class="font-bold text-gray-800 text-lg">Research by College</h3>
            </div>
            <div class="p-4 space-y-3">
                @foreach($researchByCollege as $college)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-semibold text-gray-700">{{ $college->code }}</span>
                        <span class="text-orange-600 font-bold">{{ $college->research_count }}</span>
                    </div>
                    <div class="w-full bg-orange-100 rounded-full h-2">
                        <div class="bg-gradient-to-r from-orange-500 to-orange-600 h-2 rounded-full" style="width: {{ $totalResearch > 0 ? ($college->research_count / $totalResearch * 100) : 0 }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-gray-800 text-lg mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('research.index') }}" class="flex flex-col items-center bg-orange-50 hover:bg-orange-100 rounded-xl p-5 transition">
                <i class="fas fa-file-alt text-orange-600 text-2xl mb-2"></i>
                <span class="text-sm font-semibold text-gray-700">Manage Research</span>
            </a>
            <a href="{{ route('users.index') }}" class="flex flex-col items-center bg-blue-50 hover:bg-blue-100 rounded-xl p-5 transition">
                <i class="fas fa-users text-blue-600 text-2xl mb-2"></i>
                <span class="text-sm font-semibold text-gray-700">Manage Users</span>
            </a>
            <a href="{{ route('colleges.index') }}" class="flex flex-col items-center bg-purple-50 hover:bg-purple-100 rounded-xl p-5 transition">
                <i class="fas fa-university text-purple-600 text-2xl mb-2"></i>
                <span class="text-sm font-semibold text-gray-700">Manage Colleges</span>
            </a>
            <a href="{{ route('categories.index') }}" class="flex flex-col items-center bg-green-50 hover:bg-green-100 rounded-xl p-5 transition">
                <i class="fas fa-tags text-green-600 text-2xl mb-2"></i>
                <span class="text-sm font-semibold text-gray-700">Manage Categories</span>
            </a>
        </div>
    </div>
</div>
@endsection
