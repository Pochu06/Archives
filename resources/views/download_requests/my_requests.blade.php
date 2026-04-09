@extends('layouts.app')
@section('title', 'My Download Requests')
@section('page-title', 'My Download Requests')
@section('page-subtitle', 'Track the status of your download requests')
@section('content')
<div class="space-y-5">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-orange-50 border-b border-orange-100">
                <tr>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Research Paper</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Purpose</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Date</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-orange-800 uppercase tracking-wider">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($requests as $req)
                <tr class="hover:bg-orange-50/30">
                    <td class="px-5 py-4">
                        <a href="{{ route('research.show', $req->research_id) }}" class="text-sm text-orange-600 font-medium hover:underline block max-w-xs truncate">
                            {{ $req->research->title ?? 'Deleted Paper' }}
                        </a>
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-sm text-gray-600 max-w-xs truncate">{{ $req->purpose }}</p>
                    </td>
                    <td class="px-5 py-4">
                        @if($req->status === 'pending')
                        <span class="bg-yellow-100 text-yellow-800 px-2.5 py-1 rounded-full text-xs font-semibold">Pending</span>
                        @elseif($req->status === 'approved')
                        <span class="bg-green-100 text-green-800 px-2.5 py-1 rounded-full text-xs font-semibold">Approved</span>
                        @else
                        <div>
                            <span class="bg-red-100 text-red-800 px-2.5 py-1 rounded-full text-xs font-semibold">Rejected</span>
                            @if($req->rejection_reason)
                            <p class="text-xs text-red-500 mt-1">{{ $req->rejection_reason }}</p>
                            @endif
                        </div>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <p class="text-xs text-gray-500">{{ $req->created_at->format('M d, Y') }}</p>
                        <p class="text-xs text-gray-400">{{ $req->created_at->diffForHumans() }}</p>
                    </td>
                    <td class="px-5 py-4 text-right">
                        @if($req->status === 'approved')
                        <a href="{{ route('research.download', $req->research_id) }}" class="bg-orange-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-orange-700 transition">
                            <i class="fas fa-download mr-1"></i> Download
                        </a>
                        @elseif($req->status === 'pending')
                        <span class="text-xs text-gray-400">Awaiting review</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-3xl text-gray-300 mb-2"></i>
                        <p>You haven't made any download requests yet.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
</div>
@endsection
