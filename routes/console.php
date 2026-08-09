<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:send-digest', function () {
    $now = now();
    $users = User::whereIn('notification_digest_frequency', ['daily', 'weekly'])->get();
    $sent = 0;

    foreach ($users as $user) {
        $lastSent = $user->notification_digest_last_sent_at;
        $isDue = match ($user->notification_digest_frequency) {
            'daily' => ! $lastSent || $lastSent->lte($now->copy()->subDay()),
            'weekly' => ! $lastSent || $lastSent->lte($now->copy()->subDays(7)),
            default => false,
        };

        if (! $isDue) {
            continue;
        }

        $items = $user->unreadNotifications()->latest()->limit(12)->get();

        if ($items->isEmpty()) {
            continue;
        }

        $lines = $items->map(function ($notification) {
            $data = $notification->data;
            $title = $data['title'] ?? 'Notification';
            $message = $data['message'] ?? '';

            return '- '.$title.': '.$message;
        })->implode("\n");

        $body = "Hello {$user->name},\n\n".
            "Here is your {$user->notification_digest_frequency} ARCHIVES notification digest:\n\n".
            $lines.
            "\n\nPlease log in to ARCHIVES to review and mark items as read.\n";

        Mail::raw($body, function ($message) use ($user) {
            $message->to($user->email)
                ->subject('ARCHIVES Notification Digest');
        });

        $user->update([
            'notification_digest_last_sent_at' => $now,
        ]);

        $sent++;
    }

    $this->info('Digest emails sent: '.$sent);
})->purpose('Send unread notification digests based on user preferences');

Schedule::command('notifications:send-digest')->hourly();