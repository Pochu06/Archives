@extends('layouts.app')

@section('title', 'Admin Activity Logs')
@section('page-title', 'Admin Activity Logs')
@section('page-subtitle', 'Review privileged admin and super-admin actions across the system')
@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h3 class="font-bold text-gray-800 text-lg">Admin Activity Logs</h3>
                <p class="text-sm text-gray-600 mt-1">Monitor privileged actions taken by admins and the super admin, including route, status, and request details.</p>
            </div>
        </div>

        <form method="GET" class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search action, route, path, or URL" class="border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            <select name="role" class="border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                <option value="">All roles</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
            </select>
            <select name="method" class="border border-gray-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                <option value="">All methods</option>
                <option value="GET" {{ request('method') === 'GET' ? 'selected' : '' }}>GET</option>
                <option value="POST" {{ request('method') === 'POST' ? 'selected' : '' }}>POST</option>
                <option value="PUT" {{ request('method') === 'PUT' ? 'selected' : '' }}>PUT</option>
                <option value="DELETE" {{ request('method') === 'DELETE' ? 'selected' : '' }}>DELETE</option>
            </select>
            <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-orange-700">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Actor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Method</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Path</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-semibold">{{ 'Unknown user' }}</div>
                                <div class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $log->role)) }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium">{{ $log->action }}</div>
                                <div class="text-xs text-gray-500">{{ $log->route_name ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $log->method }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <div class="font-medium">{{ $log->path }}</div>
                                <div class="text-xs text-gray-500 break-all">{{ $log->url }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $log->response_status >= 400 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $log->response_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No admin activity logs yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-4 border-t border-gray-100">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
