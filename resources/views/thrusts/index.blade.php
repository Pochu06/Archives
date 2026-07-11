@extends('layouts.app')
@section('title', 'Thrusts')
@section('page-title', 'CSU Thrusts')
@section('page-subtitle', 'Manage research thrusts and control which ones are available for auto-selection')
@section('content')
<div class="space-y-5">
    <div class="flex justify-end">
        <a href="{{ route('thrusts.create') }}" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-5 py-2.5 rounded-xl font-semibold hover:from-orange-700 hover:to-orange-800 transition shadow">
            <i class="fas fa-plus mr-1"></i> Add Thrust
        </a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-orange-50 border-b border-orange-100">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Thrust</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Description</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Keywords</th>
                    <th class="px-6 py-3.5 text-left text-xs font-bold text-orange-800 uppercase">Status</th>
                    <th class="px-6 py-3.5 text-right text-xs font-bold text-orange-800 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($thrusts as $thrust)
                <tr class="hover:bg-orange-50/20">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-orange-100 p-2 rounded-lg"><i class="fas fa-bullseye text-orange-600 text-sm"></i></div>
                            <span class="font-semibold text-gray-800">{{ $thrust->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $thrust->description ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xl">{{ $thrust->keywords ?? '—' }}</td>
                    <td class="px-6 py-4">
                        @if($thrust->active)
                        <span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full">Active</span>
                        @else
                        <span class="bg-gray-100 text-gray-700 text-xs font-bold px-3 py-1 rounded-full">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('thrusts.edit', $thrust->id) }}" class="text-xs bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg hover:bg-blue-100 font-medium">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('thrusts.toggle', $thrust->id) }}" method="POST">
                                @csrf
                                <button class="text-xs {{ $thrust->active ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-green-50 text-green-700 hover:bg-green-100' }} px-3 py-1.5 rounded-lg font-medium">
                                    <i class="fas {{ $thrust->active ? 'fa-toggle-off' : 'fa-toggle-on' }} mr-1"></i>{{ $thrust->active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            <form action="{{ route('thrusts.destroy', $thrust->id) }}" method="POST" onsubmit="return confirm('Delete this thrust?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs bg-red-50 text-red-700 px-3 py-1.5 rounded-lg hover:bg-red-100"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">No thrusts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $thrusts->links() }}</div>
</div>
@endsection