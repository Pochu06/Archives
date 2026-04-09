@extends('layouts.app')
@section('title', 'Download Requests')
@section('page-title', 'Download Requests')
@section('page-subtitle', 'Review and manage download requests from users')
@section('content')
<div class="space-y-5">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 relative min-w-48">
                <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by user or paper title..."
                    class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-orange-500">
            </div>
            <select name="status_filter" class="border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-500">
                <option value="pending" {{ request('status_filter', 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status_filter') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status_filter') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="" {{ request()->has('status_filter') && request('status_filter') === '' ? 'selected' : '' }}>All</option>
            </select>
            <button type="submit" class="bg-orange-600 text-white px-5 py-3 rounded-xl text-sm font-semibold hover:bg-orange-700 transition">Filter</button>
            <a href="{{ route('download-request.index') }}" class="bg-gray-100 text-gray-700 px-4 py-3 rounded-xl text-sm hover:bg-gray-200">Clear</a>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-orange-50 border-b border-orange-100">
                <tr>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Requested By</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Research Paper</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Purpose</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Date</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-orange-800 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($requests as $req)
                <tr class="hover:bg-orange-50/30">
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-orange-700 font-bold text-xs">
                                {{ strtoupper(substr($req->user->name ?? 'U', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ $req->user->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500">{{ $req->user->email ?? '' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <a href="{{ route('research.show', $req->research_id) }}" class="text-sm text-orange-600 font-medium hover:underline block max-w-xs truncate">
                            {{ $req->research->title ?? 'Deleted Paper' }}
                        </a>
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-sm text-gray-600 max-w-xs truncate" title="{{ $req->purpose }}">{{ $req->purpose }}</p>
                    </td>
                    <td class="px-5 py-4">
                        @if($req->status === 'pending')
                        <span class="bg-yellow-100 text-yellow-800 px-2.5 py-1 rounded-full text-xs font-semibold">Pending</span>
                        @elseif($req->status === 'approved')
                        <span class="bg-green-100 text-green-800 px-2.5 py-1 rounded-full text-xs font-semibold">Approved</span>
                        @else
                        <span class="bg-red-100 text-red-800 px-2.5 py-1 rounded-full text-xs font-semibold">Rejected</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-xs text-gray-500">{{ $req->created_at->diffForHumans() }}</p>
                    </td>
                    <td class="px-5 py-4 text-right">
                        @if($req->status === 'pending')
                        <div class="flex gap-2 justify-end">
                            <form action="{{ route('download-request.approve', $req->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-green-700 transition">
                                    <i class="fas fa-check mr-1"></i> Approve
                                </button>
                            </form>
                            <button onclick="document.getElementById('rejectModal{{ $req->id }}').classList.remove('hidden')" class="bg-red-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-red-700 transition">
                                <i class="fas fa-times mr-1"></i> Reject
                            </button>
                        </div>
                        @elseif($req->status === 'approved')
                        <span class="text-xs text-gray-500">by {{ $req->reviewer->name ?? 'Unknown' }}</span>
                        @else
                        <span class="text-xs text-gray-500" title="{{ $req->rejection_reason }}">{{ Str::limit($req->rejection_reason, 30) }}</span>
                        @endif
                    </td>
                </tr>

                {{-- Reject Modal --}}
                @if($req->status === 'pending')
                <tr class="hidden" id="rejectModal{{ $req->id }}">
                    <td colspan="6">
                        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 overflow-hidden">
                                <div class="bg-red-600 p-5 text-white">
                                    <h3 class="font-bold"><i class="fas fa-times-circle mr-2"></i>Reject Request</h3>
                                </div>
                                <form action="{{ route('download-request.reject', $req->id) }}" method="POST" class="p-6 space-y-4">
                                    @csrf
                                    <div>
                                        <label class="block text-gray-700 font-semibold mb-2 text-sm">Reason for Rejection <span class="text-red-500">*</span></label>
                                        <textarea name="rejection_reason" rows="3" required placeholder="Provide a reason..."
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-red-400 text-sm"></textarea>
                                    </div>
                                    <div class="flex gap-3 justify-end">
                                        <button type="button" onclick="document.getElementById('rejectModal{{ $req->id }}').classList.add('hidden')" class="bg-gray-100 text-gray-700 px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-200">Cancel</button>
                                        <button type="submit" class="bg-red-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-red-700">Reject</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-3xl text-gray-300 mb-2"></i>
                        <p>No download requests found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->withQueryString()->links() }}</div>
</div>
@endsection
