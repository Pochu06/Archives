<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InAppAlertNotification extends Notification
{
    use Queueable;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(private readonly array $payload)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->payload['type'] ?? 'general',
            'title' => $this->payload['title'] ?? 'Notification',
            'message' => $this->payload['message'] ?? '',
            'action_url' => $this->payload['action_url'] ?? null,
            'action_label' => $this->payload['action_label'] ?? 'Open',
            'icon' => $this->payload['icon'] ?? 'fa-bell',
            'level' => $this->payload['level'] ?? 'info',
            'meta' => $this->payload['meta'] ?? [],
        ];
    }
}