@extends('layouts.app')
@section('title', 'My Submissions')
@section('page-title', 'My Research Submissions')
@section('page-subtitle', 'Track the status of your submitted research')
@section('content')
<div class="space-y-5">
    <div class="flex flex-col sm:flex-row justify-between gap-3">
        <form method="GET" class="flex gap-3">
            <select name="status" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500">
                <option value="">All Status</option>
                <option value="pending_college" {{ request('status') === 'pending_college' ? 'selected' : '' }}>Pending College Approval</option>
                <option value="pending_rde" {{ request('status') === 'pending_rde' ? 'selected' : '' }}>Pending RDE Approval</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected_college" {{ request('status') === 'rejected_college' ? 'selected' : '' }}>Rejected by College</option>
                <option value="rejected_rde" {{ request('status') === 'rejected_rde' ? 'selected' : '' }}>Rejected by RDE</option>
            </select>
            <button type="submit" class="bg-orange-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-orange-700">Filter</button>
        </form>
        <a href="{{ route('research.create') }}" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-5 py-2.5 rounded-xl font-semibold hover:from-orange-700 hover:to-orange-800 transition shadow">
            <i class="fas fa-plus mr-1"></i> Submit New Research
        </a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-orange-50 border-b border-orange-100">
                <tr>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Title</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Category</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Year</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Status</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Submitted</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-orange-800 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($research as $r)
                <tr class="hover:bg-orange-50/20">
                    <td class="px-5 py-4 max-w-xs">
                        <a href="{{ route('research.show', $r->id) }}" class="font-semibold text-gray-800 hover:text-orange-600 text-sm line-clamp-2">{{ $r->title }}</a>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $r->category->name ?? 'N/A' }}</td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $r->publication_year }}</td>
                    <td class="px-5 py-4">
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $r->status_badge }}">{{ $r->status_label }}</span>
                        @if($r->rejection_reason)
                        <p class="text-xs text-red-500 mt-1">{{ Str::limit($r->rejection_reason, 50) }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-xs text-gray-500">{{ $r->created_at->format('M d, Y') }}</td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('research.show', $r->id) }}" class="text-xs bg-orange-50 text-orange-700 px-3 py-1.5 rounded-lg hover:bg-orange-100 font-medium">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($r->status !== \App\Models\Research::STATUS_APPROVED)
                            <a href="{{ route('research.edit', $r->id) }}" class="text-xs bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg hover:bg-blue-100">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-16 text-center">
                        <i class="fas fa-file-alt text-5xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500 mb-4">You haven't submitted any research yet.</p>
                        <a href="{{ route('research.create') }}" class="bg-orange-600 text-white px-6 py-2.5 rounded-xl font-semibold hover:bg-orange-700 transition">
                            Submit Your First Research
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $research->links() }}</div>
</div>
@endsection
