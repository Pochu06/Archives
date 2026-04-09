@extends('layouts.app')
@section('title', 'User Management')
@section('page-title', 'User Management')
@section('page-subtitle', 'Manage system users and roles')
@section('content')
<div class="space-y-5">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 relative min-w-48">
                <i class="fas fa-search absolute left-3 top-3.5 text-gray-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email..."
                    class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-orange-500">
            </div>
            <select name="role_filter" class="border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-500">
                <option value="">All Roles</option>
                <option value="super_admin" {{ request('role_filter') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                <option value="admin" {{ request('role_filter') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="student" {{ request('role_filter') === 'student' ? 'selected' : '' }}>Student</option>
            </select>
            @if(session('user_role') === 'super_admin')
            <select name="college_filter" class="border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-orange-500">
                <option value="">All Colleges</option>
                @foreach($colleges as $college)
                <option value="{{ $college->id }}" {{ request('college_filter') == $college->id ? 'selected' : '' }}>{{ $college->code }}</option>
                @endforeach
            </select>
            @endif
            <button type="submit" class="bg-orange-600 text-white px-5 py-3 rounded-xl text-sm font-semibold hover:bg-orange-700 transition">Filter</button>
            <a href="{{ route('users.index') }}" class="bg-gray-100 text-gray-700 px-4 py-3 rounded-xl text-sm hover:bg-gray-200">Clear</a>
            <a href="{{ route('users.create') }}" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-5 py-3 rounded-xl text-sm font-semibold hover:from-orange-700 hover:to-orange-800 transition shadow">
                <i class="fas fa-plus mr-1"></i> Add User
            </a>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-orange-50 border-b border-orange-100">
                <tr>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">User</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Role</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">College</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Student ID</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-orange-800 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-orange-800 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $user)
                <tr class="hover:bg-orange-50/20 transition">
                    <td class="px-5 py-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold text-sm">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $user->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $user->role_badge }}">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
                    </td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $user->college->code ?? 'N/A' }}</td>
                    <td class="px-5 py-4 text-sm text-gray-600">{{ $user->student_id ?? '—' }}</td>
                    <td class="px-5 py-4">
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ ucfirst($user->status) }}</span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('users.edit', $user->id) }}" class="text-xs bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition font-medium">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if($user->id != session('user_id'))
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Delete this user?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs bg-red-50 text-red-700 px-3 py-1.5 rounded-lg hover:bg-red-100 transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-12 text-center text-gray-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $users->withQueryString()->links() }}</div>
</div>
@endsection
