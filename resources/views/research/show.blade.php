@extends('layouts.app')
@section('title', $research->title)
@section('page-title', 'Research Details')
@section('page-subtitle', 'Full research paper information')
@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('research.index') }}" class="text-orange-600 hover:underline text-sm font-semibold">
            <i class="fas fa-arrow-left mr-1"></i> Back to Archive
        </a>
        <div class="flex gap-2">
            @if($research->file_path)
            <a href="{{ route('research.download', $research->id) }}" class="bg-orange-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-orange-700 transition">
                <i class="fas fa-download mr-1"></i> Download
            </a>
            @endif
            @if(in_array(session('user_role'), ['super_admin', 'admin']) || $research->user_id == session('user_id'))
            <a href="{{ route('research.edit', $research->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-blue-700 transition">
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-gradient-to-r from-orange-600 to-orange-800 p-8 text-white">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex gap-2 mb-4">
                        <span class="bg-white/20 text-white text-xs px-3 py-1 rounded-full font-semibold">{{ $research->college->code ?? 'N/A' }}</span>
                        <span class="bg-white/20 text-white text-xs px-3 py-1 rounded-full">{{ $research->category->name ?? 'N/A' }}</span>
                        <span class="bg-white/20 text-white text-xs px-3 py-1 rounded-full">{{ $research->publication_year }}</span>
                    </div>
                    <h1 class="text-2xl font-bold mb-3 leading-tight">{{ $research->title }}</h1>
                    <p class="text-orange-100 text-sm"><i class="fas fa-users mr-1"></i> {{ $research->authors }}</p>
                </div>
                <span class="ml-4 text-sm px-3 py-1.5 rounded-full font-semibold
                    @if($research->status === 'approved') bg-green-400 text-green-900
                    @elseif($research->status === 'pending') bg-yellow-400 text-yellow-900
                    @else bg-red-400 text-red-900 @endif">
                    {{ ucfirst($research->status) }}
                </span>
            </div>
        </div>

        <div class="p-8 space-y-6">
            <div>
                <h3 class="font-bold text-gray-800 text-lg mb-3 flex items-center">
                    <i class="fas fa-align-left text-orange-500 mr-2"></i> Abstract
                </h3>
                <p class="text-gray-700 leading-relaxed">{{ $research->abstract }}</p>
            </div>

            <div>
                <h3 class="font-bold text-gray-800 text-lg mb-3 flex items-center">
                    <i class="fas fa-tags text-orange-500 mr-2"></i> Keywords
                </h3>
                <div class="flex flex-wrap gap-2">
                    @foreach(explode(',', $research->keywords) as $kw)
                    <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-sm font-medium">{{ trim($kw) }}</span>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <h3 class="font-bold text-gray-800">Publication Details</h3>
                    <div class="bg-gray-50 rounded-xl p-4 space-y-2 text-sm">
                        <p><span class="text-gray-500">College:</span> <span class="font-semibold">{{ $research->college->name ?? 'N/A' }}</span></p>
                        <p><span class="text-gray-500">Category:</span> <span class="font-semibold">{{ $research->category->name ?? 'N/A' }}</span></p>
                        <p><span class="text-gray-500">Year:</span> <span class="font-semibold">{{ $research->publication_year }}</span></p>
                        <p><span class="text-gray-500">Submitted by:</span> <span class="font-semibold">{{ $research->user->name ?? 'N/A' }}</span></p>
                        @if($research->adviser)
                        <p><span class="text-gray-500">Adviser:</span> <span class="font-semibold">{{ $research->adviser->name }}</span></p>
                        @endif
                    </div>
                </div>
                <div class="space-y-3">
                    <h3 class="font-bold text-gray-800">Status & Approval</h3>
                    <div class="bg-gray-50 rounded-xl p-4 space-y-2 text-sm">
                        <p><span class="text-gray-500">Status:</span> <span class="font-semibold capitalize">{{ $research->status }}</span></p>
                        <p><span class="text-gray-500">Submitted:</span> <span class="font-semibold">{{ $research->created_at->format('M d, Y') }}</span></p>
                        @if($research->approved_at)
                        <p><span class="text-gray-500">Approved:</span> <span class="font-semibold">{{ $research->approved_at->format('M d, Y') }}</span></p>
                        <p><span class="text-gray-500">Approved by:</span> <span class="font-semibold">{{ $research->approvedBy->name ?? 'N/A' }}</span></p>
                        @endif
                        @if($research->rejection_reason)
                        <p><span class="text-gray-500">Reason:</span> <span class="text-red-600">{{ $research->rejection_reason }}</span></p>
                        @endif
                    </div>
                </div>
            </div>

            @if($research->file_path)
            <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-file-pdf text-orange-600 text-2xl mr-3"></i>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">{{ $research->file_name }}</p>
                        <p class="text-xs text-gray-500">Research document attached</p>
                    </div>
                </div>
                <a href="{{ route('research.download', $research->id) }}" class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-orange-700 transition">
                    <i class="fas fa-download mr-1"></i> Download
                </a>
            </div>
            @endif

            @if(in_array(session('user_role'), ['super_admin', 'admin', 'adviser']) && $research->status === 'pending')
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-5">
                <h3 class="font-bold text-yellow-800 mb-4"><i class="fas fa-gavel mr-2"></i>Review Actions</h3>
                <div class="flex gap-3">
                    <form action="{{ route('research.approve', $research->id) }}" method="POST">
                        @csrf
                        <button class="bg-green-600 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-green-700 transition">
                            <i class="fas fa-check mr-1"></i> Approve Research
                        </button>
                    </form>
                    <button onclick="document.getElementById('rejectModal').classList.remove('hidden')" class="bg-red-600 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-red-700 transition">
                        <i class="fas fa-times mr-1"></i> Reject
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
        <h3 class="font-bold text-gray-800 text-lg mb-4">Reject Research</h3>
        <form action="{{ route('research.reject', $research->id) }}" method="POST">
            @csrf
            <textarea name="reason" rows="4" placeholder="Please provide rejection reason..." required
                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-red-500 mb-4"></textarea>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-red-600 text-white py-2.5 rounded-xl font-semibold hover:bg-red-700">Reject</button>
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="flex-1 bg-gray-200 text-gray-700 py-2.5 rounded-xl font-semibold hover:bg-gray-300">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection
