@extends('layouts.app')

@section('title', 'Backup & Restore')
@section('page-title', 'Backup & Restore')
@section('page-subtitle', 'Encrypted database and document backups')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-5 text-sm text-blue-950 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-100">
        <h2 class="font-bold"><i class="fas fa-lock mr-2"></i>Protected backup storage</h2>
        <p class="mt-2">Backups are AES-256 encrypted, stored outside the public document disk, and contain database records plus research uploads. The current Laravel APP_KEY encrypts every archive entry; keep that key securely and separately or restores will not work.</p>
        <p class="mt-2">Daily automatic backups run at 02:00 server time when the deployment scheduler is active. Restores require the same database driver, schema, and migration history.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-lg font-bold">Create a backup</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Creates an encrypted archive of current database and stored document data. This may take a while on large archives.</p>
            <form action="{{ route('admin.backups.create') }}" method="POST" class="mt-5" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<i class=&quot;fas fa-spinner fa-spin mr-2&quot;></i>Creating encrypted backup...';">
                @csrf
                <button type="submit" class="rounded-xl bg-blue-700 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800">
                    <i class="fas fa-database mr-2"></i>Create Backup
                </button>
            </form>
        </section>

        <section class="rounded-2xl border border-red-200 bg-white p-6 shadow-sm dark:border-red-900 dark:bg-gray-800">
            <h2 class="text-lg font-bold text-red-800 dark:text-red-300">Restore a backup</h2>
            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">Restore replaces the current database records and backed-up research files. A safety backup is created first. Only restore a trusted archive made for this installation and APP_KEY.</p>
            <form action="{{ route('admin.backups.restore') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4" onsubmit="if (!confirm('This will replace current database records and backed-up research files. Continue only if you have selected the correct backup.')) return false; const button = this.querySelector('button[type=submit]'); button.disabled = true; button.innerHTML = '<i class=&quot;fas fa-spinner fa-spin mr-2&quot;></i>Validating and restoring...'; return true;">
                @csrf
                @if($backups)
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                    Restore an existing server backup
                    <select name="backup_name" class="mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                        <option value="">Choose a stored backup (or upload one below)</option>
                        @foreach($backups as $backup)
                        <option value="{{ $backup['name'] }}">{{ $backup['name'] }} — {{ $backup['created_at'] }}</option>
                        @endforeach
                    </select>
                </label>
                <p class="text-center text-xs font-semibold uppercase text-gray-500">Or upload a backup</p>
                @endif
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                    Encrypted ARCHIVES backup (.zip, up to 1 GB)
                    <input type="file" name="backup_file" accept=".zip,application/zip" {{ $backups ? '' : 'required' }} class="mt-2 block w-full rounded-lg border border-gray-300 p-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                </label>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                    Type <span class="font-bold text-red-700 dark:text-red-300">RESTORE</span> to confirm
                    <input type="text" name="confirmation" required autocomplete="off" class="mt-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900">
                </label>
                <button type="submit" class="rounded-xl bg-red-700 px-5 py-3 text-sm font-semibold text-white hover:bg-red-800">
                    <i class="fas fa-triangle-exclamation mr-2"></i>Validate and Restore
                </button>
            </form>
        </section>
    </div>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-2 border-b border-gray-100 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
            <div>
                <h2 class="text-lg font-bold">Available backups</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">Creation time and file size are recorded for each encrypted archive.</p>
            </div>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ count($backups) }} backup(s)</span>
        </div>
        @if($backups)
        <div class="overflow-x-auto">
            <table class="w-full min-w-[42rem] text-left text-sm">
                <thead><tr class="border-b border-gray-100 text-xs uppercase text-gray-500 dark:border-gray-700"><th class="px-5 py-3">Backup file</th><th class="px-5 py-3">Created</th><th class="px-5 py-3">Size</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Action</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($backups as $backup)
                    <tr>
                        <td class="px-5 py-3 font-medium">{{ $backup['name'] }}</td>
                        <td class="px-5 py-3">{{ $backup['created_at'] }}</td>
                        <td class="px-5 py-3">{{ number_format($backup['size'] / 1024 / 1024, 2) }} MB</td>
                        <td class="px-5 py-3"><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200">Encrypted</span></td>
                        <td class="px-5 py-3"><a href="{{ route('admin.backups.download', $backup['name']) }}" class="font-semibold text-blue-700 hover:underline dark:text-blue-300">Download</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="p-8 text-center text-sm text-gray-500">No backups have been created yet.</p>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-100 p-5 dark:border-gray-700">
            <h2 class="text-lg font-bold">Backup and restore activity</h2>
            <p class="text-sm text-gray-600 dark:text-gray-300">Recent outcomes, timestamps, and responsible accounts. Sensitive configuration values are not recorded.</p>
        </div>
        @if($activities->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead><tr class="border-b border-gray-100 text-xs uppercase text-gray-500 dark:border-gray-700"><th class="px-5 py-3">Date and time</th><th class="px-5 py-3">Operation</th><th class="px-5 py-3">User</th><th class="px-5 py-3">Status</th></tr></thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($activities as $activity)
                    <tr>
                        <td class="px-5 py-3">{{ $activity->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="px-5 py-3">{{ str_replace('.', ' ', $activity->action) }}</td>
                        <td class="px-5 py-3">{{ $activity->user?->name ?? ($activity->role === 'system' ? 'Scheduled task' : 'Unknown') }}</td>
                        <td class="px-5 py-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $activity->response_status >= 400 ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200' }}">
                                {{ $activity->response_status >= 400 ? 'Failed' : 'Completed' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="p-8 text-center text-sm text-gray-500">No backup or restore activity recorded yet.</p>
        @endif
    </section>
</div>
@endsection
