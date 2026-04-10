@extends('layouts.app')
@section('title', $pageTitle)
@section('page-title', $pageTitle)
@section('page-subtitle', $pageSubtitle)
@section('content')
<div class="space-y-5">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex gap-4">
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
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-orange-50 border-b border-orange-100">
                <tr>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Title</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Student</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Category</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">College</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Year</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Status</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-orange-800 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($research as $r)
                <tr class="hover:bg-orange-50/20">
                    <td class="px-5 py-4 max-w-md">
                        <a href="{{ route('research.show', $r->id) }}" class="font-semibold text-gray-800 hover:text-orange-600 text-sm line-clamp-2">{{ $r->title }}</a>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $r->user->name ?? 'Unknown' }}</td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $r->category->name ?? 'N/A' }}</td>
                    <td class="px-5 py-4">
                        <span class="text-xs bg-orange-100 text-orange-700 px-2.5 py-1 rounded-full font-semibold">{{ $r->college->code ?? 'N/A' }}</span>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $r->publication_year }}</td>
                    <td class="px-5 py-4">
                        <span class="text-xs px-2.5 py-1 rounded-full {{ $r->status_badge }} font-medium">{{ $r->status_label }}</span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('research.show', $r->id) }}" class="text-xs bg-orange-50 text-orange-700 px-3 py-1.5 rounded-lg hover:bg-orange-100 font-medium">View</a>
                            @if($r->status === $defaultPendingStatus)
                            <form action="{{ route($approveRouteName, $r->id) }}" method="POST">
                                @csrf
                                <button class="text-xs bg-green-500 text-white px-3 py-1.5 rounded-lg hover:bg-green-600 font-medium">
                                    <i class="fas fa-check mr-1"></i>{{ $showRdeLabels ? 'Final Approve' : 'Forward to RDE' }}
                                </button>
                            </form>
                            <button onclick="openRejectModal({{ $r->id }})" class="text-xs bg-red-500 text-white px-3 py-1.5 rounded-lg hover:bg-red-600 font-medium">
                                <i class="fas fa-times"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-16 text-center">
                        <i class="fas fa-tasks text-5xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No student submissions found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $research->links() }}</div>
</div>

<div id="rejectModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
        <h3 class="font-bold text-gray-800 mb-4">Reject Research</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <textarea name="reason" rows="4" placeholder="Provide rejection reason..." required class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm mb-4"></textarea>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-red-600 text-white py-2.5 rounded-xl font-semibold">Reject</button>
                <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="flex-1 bg-gray-200 text-gray-700 py-2.5 rounded-xl font-semibold">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection
@section('scripts')
<script>
function openRejectModal(id) {
    const template = @json(route($rejectRouteName, ['id' => '__ID__']));
    document.getElementById('rejectForm').action = template.replace('__ID__', id);
    document.getElementById('rejectModal').classList.remove('hidden');
}
</script>
@endsection
