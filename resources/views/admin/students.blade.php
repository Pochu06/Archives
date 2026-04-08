@extends('layouts.app')
@section('title', 'My Students')
@section('page-title', 'My Students')
@section('page-subtitle', 'Students under your advisership')
@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-orange-50 border-b border-orange-100">
            <tr>
                <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Student</th>
                <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Student ID</th>
                <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">College</th>
                <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Status</th>
                <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Joined</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($students as $student)
            <tr class="hover:bg-orange-50/20">
                <td class="px-5 py-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold text-sm">
                            {{ strtoupper(substr($student->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800 text-sm">{{ $student->name }}</p>
                            <p class="text-xs text-gray-500">{{ $student->email }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-5 py-4 text-sm text-gray-600">{{ $student->student_id ?? '—' }}</td>
                <td class="px-5 py-4 text-sm text-gray-600">{{ $student->college->code ?? 'N/A' }}</td>
                <td class="px-5 py-4">
                    <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $student->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ ucfirst($student->status) }}</span>
                </td>
                <td class="px-5 py-4 text-xs text-gray-500">{{ $student->created_at->format('M d, Y') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-5 py-12 text-center text-gray-500">No students assigned to you yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $students->links() }}</div>
@endsection
