<?php

namespace App\Services;

use App\Models\Research;
use App\Models\ResearchDraft;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NotificationCenterService
{
    private const HEADER_LIMIT = 8;

    private const DRAFT_WARNING_DAYS = 5;

    public function getCurrentUser(): ?User
    {
        $userId = session('user_id');

        if (! $userId) {
            return null;
        }

        return User::find($userId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSharedData(): array
    {
        $user = $this->getCurrentUser();
        $draftNotification = $user ? $this->getDraftAlert($user) : null;
        $databaseNotifications = $user
            ? $this->mapDatabaseNotifications($user->notifications()->latest()->limit(self::HEADER_LIMIT)->get())
            : collect();

        $items = $databaseNotifications;

        if ($draftNotification) {
            $items = $items->prepend($draftNotification);
        }

        $items = $items
            ->sortByDesc(fn (array $item) => $item['created_at_sort'])
            ->take(self::HEADER_LIMIT)
            ->values();

        return [
            'notificationItems' => $items,
            'notificationUnreadCount' => ($user ? $user->unreadNotifications()->count() : 0) + ($draftNotification && ! $draftNotification['is_read'] ? 1 : 0),
            'collegeApprovalCount' => $this->getCollegeApprovalCount(),
            'rdeApprovalCount' => $this->getRdeApprovalCount(),
        ];
    }

    public function markAsRead(string $id): void
    {
        $user = $this->getCurrentUser();

        if (! $user) {
            return;
        }

        if (str_starts_with($id, 'draft-expiry:')) {
            $draft = ResearchDraft::where('user_id', $user->id)->first();

            if ($draft) {
                Cache::forever($this->draftReadCacheKey($user->id, $draft->last_saved_at), true);
            }

            return;
        }

        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification && ! $notification->read_at) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        $user = $this->getCurrentUser();

        if (! $user) {
            return;
        }

        $user->unreadNotifications->markAsRead();

        $draft = ResearchDraft::where('user_id', $user->id)->first();

        if ($draft && $this->shouldShowDraftAlert($draft)) {
            Cache::forever($this->draftReadCacheKey($user->id, $draft->last_saved_at), true);
        }
    }

    /**
     * @param Collection<int, DatabaseNotification> $notifications
     * @return Collection<int, array<string, mixed>>
     */
    private function mapDatabaseNotifications(Collection $notifications): Collection
    {
        return $notifications->map(function (DatabaseNotification $notification) {
            $data = $notification->data;
            $createdAt = $notification->created_at ?? now();

            return [
                'id' => $notification->id,
                'title' => $data['title'] ?? 'Notification',
                'message' => $data['message'] ?? '',
                'action_url' => $data['action_url'] ?? null,
                'action_label' => $data['action_label'] ?? 'Open',
                'icon' => $data['icon'] ?? 'fa-bell',
                'level' => $data['level'] ?? 'info',
                'type' => $data['type'] ?? 'general',
                'source' => 'database',
                'is_read' => $notification->read_at !== null,
                'created_at_human' => $createdAt->diffForHumans(),
                'created_at_sort' => $createdAt->timestamp,
            ];
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getDraftAlert(User $user): ?array
    {
        $draft = ResearchDraft::where('user_id', $user->id)->first();

        if (! $draft || ! $this->shouldShowDraftAlert($draft)) {
            return null;
        }

        $lastSavedAt = $draft->last_saved_at ?? $draft->updated_at ?? now();
        $daysInactive = max(1, $lastSavedAt->diffInDays(now()));
        $isRead = Cache::get($this->draftReadCacheKey($user->id, $lastSavedAt), false);

        return [
            'id' => 'draft-expiry:'.$user->id,
            'title' => 'Draft needs attention',
            'message' => 'Your saved draft has been inactive for '.$daysInactive.' day(s). Review and save it again so it stays current.',
            'action_url' => route('research.create'),
            'action_label' => 'Open Draft',
            'icon' => 'fa-file-pen',
            'level' => 'warning',
            'type' => 'draft_expiring',
            'source' => 'draft',
            'is_read' => (bool) $isRead,
            'created_at_human' => $lastSavedAt->diffForHumans(),
            'created_at_sort' => $lastSavedAt->timestamp,
        ];
    }

    private function shouldShowDraftAlert(ResearchDraft $draft): bool
    {
        $lastSavedAt = $draft->last_saved_at ?? $draft->updated_at;

        if (! $lastSavedAt) {
            return false;
        }

        return $lastSavedAt->lte(now()->subDays(self::DRAFT_WARNING_DAYS));
    }

    private function draftReadCacheKey(int $userId, ?Carbon $lastSavedAt): string
    {
        return 'notification_center:draft_read:'.$userId.':'.($lastSavedAt?->timestamp ?? 'none');
    }

    private function getCollegeApprovalCount(): int
    {
        if (!(session('user_role') === 'admin' && session('user_college_id'))) {
            return 0;
        }

        return Research::where('college_id', session('user_college_id'))
            ->where('status', Research::STATUS_PENDING_COLLEGE)
            ->count();
    }

    private function getRdeApprovalCount(): int
    {
        if (!(session('user_role') === 'super_admin' || (session('user_role') === 'admin' && ! session('user_college_id')))) {
            return 0;
        }

        return Research::where('status', Research::STATUS_PENDING_RDE)->count();
    }
}