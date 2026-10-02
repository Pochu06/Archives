@extends('layouts.app')
@section('title', 'Pending Registrations')
@section('page-title', 'Pending Registrations')
@section('page-subtitle', 'Review and approve student account requests')
@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-orange-50 border-b border-orange-100">
            <tr>
                <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Student</th>
                <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">College</th>
                <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Student ID</th>
                <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Registered</th>
                <th class="px-5 py-3.5 text-right text-xs font-bold text-orange-800 uppercase tracking-wider">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($users as $user)
            <tr class="hover:bg-orange-50/20 transition">
                <td class="px-5 py-4">
                    <p class="font-semibold text-gray-800 text-sm">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                </td>
                <td class="px-5 py-4 text-sm text-gray-600">{{ $user->college->code ?? 'N/A' }}</td>
                <td class="px-5 py-4 text-sm text-gray-600">{{ $user->student_id ?? '—' }}</td>
                <td class="px-5 py-4 text-sm text-gray-600">{{ $user->created_at?->format('M j, Y') ?? '—' }}</td>
                <td class="px-5 py-4 text-right">
                    <form action="{{ route('users.approve', $user->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-green-700 transition">
                            <i class="fas fa-check mr-1"></i> Approve
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-5 py-12 text-center text-gray-500">There are no pending registrations.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $users->links() }}</div>
@endsection
