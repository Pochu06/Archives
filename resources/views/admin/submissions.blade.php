@extends('layouts.app')
@section('title', 'Student Submissions')
@section('page-title', 'Student Research Submissions')
@section('page-subtitle', 'Review and manage your students\' research')
@section('content')
<div class="space-y-5">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
        <form method="GET" class="flex gap-4">
            <select name="status" class="border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-orange-500">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            <button type="submit" class="bg-orange-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-orange-700">Filter</button>
        </form>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @forelse($research as $r)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition">
            <div class="p-5">
                <div class="flex justify-between items-start mb-3">
                    <span class="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full font-semibold">{{ $r->college->code ?? 'N/A' }}</span>
                    <span class="text-xs px-2 py-1 rounded-full {{ $r->status_badge }} font-medium">{{ ucfirst($r->status) }}</span>
                </div>
                <h3 class="font-bold text-gray-800 text-sm mb-2 line-clamp-2">
                    <a href="{{ route('research.show', $r->id) }}" class="hover:text-orange-600">{{ $r->title }}</a>
                </h3>
                <p class="text-xs text-gray-500 mb-4">
                    <i class="fas fa-user mr-1 text-orange-400"></i> {{ $r->user->name ?? 'Unknown' }}
                    &bull; {{ $r->category->name ?? 'N/A' }} &bull; {{ $r->publication_year }}
                </p>
                <div class="flex gap-2">
                    <a href="{{ route('research.show', $r->id) }}" class="flex-1 text-center text-xs bg-orange-50 text-orange-700 py-2 rounded-lg hover:bg-orange-100 font-semibold">View</a>
                    @if($r->status === 'pending')
                    <form action="{{ route('research.approve', $r->id) }}" method="POST">
                        @csrf
                        <button class="text-xs bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600"><i class="fas fa-check mr-1"></i>Approve</button>
                    </form>
                    <button onclick="openRejectModal({{ $r->id }})" class="text-xs bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600"><i class="fas fa-times"></i></button>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-2 bg-white rounded-2xl border border-gray-100 p-12 text-center">
            <i class="fas fa-tasks text-5xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">No student submissions found.</p>
        </div>
        @endforelse
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
    document.getElementById('rejectForm').action = '/research/' + id + '/reject';
    document.getElementById('rejectModal').classList.remove('hidden');
}
</script>
@endsection
