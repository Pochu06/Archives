@php
    $notificationPalette = [
        'info' => 'bg-blue-50 text-blue-700 border-blue-100',
        'success' => 'bg-green-50 text-green-700 border-green-100',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-100',
        'danger' => 'bg-red-50 text-red-700 border-red-100',
    ];
@endphp

<div class="relative" data-notification-center>
    <button type="button" data-notification-toggle class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50" aria-label="Open notifications">
        <i class="fas fa-bell"></i>
        @if(($notificationUnreadCount ?? 0) > 0)
        <span class="absolute -right-1 -top-1 min-w-[1.2rem] rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
            {{ $notificationUnreadCount > 9 ? '9+' : $notificationUnreadCount }}
        </span>
        @endif
    </button>

    <div data-notification-panel class="absolute right-0 z-50 mt-3 hidden w-[22rem] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">Notifications</h3>
                <p class="text-xs text-gray-500">Approval, revision, draft, and AI alerts</p>
            </div>
            @if(($notificationUnreadCount ?? 0) > 0)
            <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                @csrf
                <button type="submit" class="text-xs font-semibold text-orange-600 hover:text-orange-700">Mark all read</button>
            </form>
            @endif
        </div>

        <div class="max-h-[26rem] overflow-y-auto">
            @forelse(($notificationItems ?? []) as $item)
            <div class="border-b border-gray-100 px-4 py-3 last:border-b-0 {{ $item['is_read'] ? 'bg-white' : 'bg-orange-50/40' }}">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-xl border {{ $notificationPalette[$item['level']] ?? $notificationPalette['info'] }}">
                        <i class="fas {{ $item['icon'] }} text-sm"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ $item['title'] }}</p>
                                <p class="mt-1 text-xs leading-5 text-gray-600">{{ $item['message'] }}</p>
                            </div>
                            @if(! $item['is_read'])
                            <span class="mt-1 h-2.5 w-2.5 flex-shrink-0 rounded-full bg-orange-500"></span>
                            @endif
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <span class="text-[11px] text-gray-400">{{ $item['created_at_human'] }}</span>
                            <div class="flex items-center gap-3">
                                @if(!empty($item['action_url']))
                                <a href="{{ $item['action_url'] }}" class="text-xs font-semibold text-orange-600 hover:text-orange-700">{{ $item['action_label'] }}</a>
                                @endif
                                @if(! $item['is_read'])
                                <form action="{{ route('notifications.mark-read', $item['id']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-gray-500 hover:text-gray-700">Mark read</button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-4 py-10 text-center text-sm text-gray-500">
                <i class="fas fa-bell-slash mb-3 text-2xl text-gray-300"></i>
                <p>No notifications yet.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>