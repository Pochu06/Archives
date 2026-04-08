@extends('layouts.app')
@section('title', 'Research Archive')
@section('page-title', 'Research Archive')
@section('page-subtitle', 'Browse and manage research submissions')
@section('content')
<div class="space-y-6">
    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <form method="GET" action="{{ route('research.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="md:col-span-2">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title, abstract, keywords..."
                        class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-orange-500">
                </div>
            </div>
            <select name="college_id" class="border border-gray-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-orange-500">
                <option value="">All Colleges</option>
                @foreach($colleges as $college)
                <option value="{{ $college->id }}" {{ request('college_id') == $college->id ? 'selected' : '' }}>{{ $college->code }}</option>
                @endforeach
            </select>
            <select name="category_id" class="border border-gray-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-orange-500">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            @if(in_array(session('user_role'), ['super_admin', 'admin', 'adviser']))
            <select name="status" class="border border-gray-200 rounded-xl px-3 py-3 text-sm focus:outline-none focus:border-orange-500">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            @endif
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-orange-600 text-white rounded-xl py-3 text-sm font-semibold hover:bg-orange-700 transition">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('research.index') }}" class="bg-gray-100 text-gray-700 px-4 rounded-xl py-3 text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="flex justify-between items-center">
        <p class="text-gray-600 text-sm">{{ $research->total() }} research paper(s) found</p>
        @if(in_array(session('user_role'), ['student', 'adviser', 'admin', 'super_admin']))
        <a href="{{ route('research.create') }}" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm hover:from-orange-700 hover:to-orange-800 transition shadow">
            <i class="fas fa-plus mr-1"></i> Submit Research
        </a>
        @endif
    </div>

    <!-- Research Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($research as $r)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md hover:border-orange-200 transition flex flex-col">
            <div class="p-5 flex-1">
                <div class="flex items-start justify-between mb-3">
                    <span class="text-xs font-semibold bg-orange-100 text-orange-700 px-2.5 py-1 rounded-full">{{ $r->college->code ?? 'N/A' }}</span>
                    <span class="text-xs px-2 py-1 rounded-full {{ $r->status_badge }} font-medium">{{ ucfirst($r->status) }}</span>
                </div>
                <h3 class="font-bold text-gray-800 text-sm mb-2 line-clamp-2">
                    <a href="{{ route('research.show', $r->id) }}" class="hover:text-orange-600 transition">{{ $r->title }}</a>
                </h3>
                <p class="text-gray-500 text-xs mb-3 line-clamp-2">{{ $r->abstract }}</p>
                <div class="flex flex-wrap gap-1 mb-3">
                    @foreach(explode(',', $r->keywords) as $keyword)
                    @if(trim($keyword))
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ trim($keyword) }}</span>
                    @endif
                    @endforeach
                </div>
                <div class="text-xs text-gray-500 space-y-1">
                    <p><i class="fas fa-user mr-1 text-orange-400"></i> {{ $r->authors }}</p>
                    <p><i class="fas fa-tag mr-1 text-orange-400"></i> {{ $r->category->name ?? 'N/A' }} &bull; {{ $r->publication_year }}</p>
                </div>
            </div>
            <div class="border-t border-gray-100 p-4 flex gap-2">
                <a href="{{ route('research.show', $r->id) }}" class="flex-1 text-center text-xs bg-orange-50 text-orange-700 py-2 rounded-lg hover:bg-orange-100 transition font-semibold">
                    <i class="fas fa-eye mr-1"></i> View
                </a>
                @if(in_array(session('user_role'), ['super_admin', 'admin']) || $r->user_id == session('user_id'))
                <a href="{{ route('research.edit', $r->id) }}" class="text-xs bg-blue-50 text-blue-700 px-3 py-2 rounded-lg hover:bg-blue-100 transition">
                    <i class="fas fa-edit"></i>
                </a>
                @endif
                @if(in_array(session('user_role'), ['super_admin', 'admin', 'adviser']) && $r->status === 'pending')
                <form action="{{ route('research.approve', $r->id) }}" method="POST">
                    @csrf
                    <button class="text-xs bg-green-50 text-green-700 px-3 py-2 rounded-lg hover:bg-green-100 transition"><i class="fas fa-check"></i></button>
                </form>
                @endif
                @if(in_array(session('user_role'), ['super_admin', 'admin']))
                <form action="{{ route('research.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Delete this research?')">
                    @csrf
                    @method('DELETE')
                    <button class="text-xs bg-red-50 text-red-700 px-3 py-2 rounded-lg hover:bg-red-100 transition"><i class="fas fa-trash"></i></button>
                </form>
                @endif
            </div>
        </div>
        @empty
        <div class="col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100 p-16 text-center">
            <i class="fas fa-file-alt text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-bold text-gray-600 mb-2">No Research Found</h3>
            <p class="text-gray-500 mb-6">No research papers match your current filters.</p>
            <a href="{{ route('research.create') }}" class="bg-orange-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-orange-700 transition">
                <i class="fas fa-plus mr-2"></i> Submit First Research
            </a>
        </div>
        @endforelse
    </div>

    <div>{{ $research->withQueryString()->links() }}</div>
</div>
@endsection
