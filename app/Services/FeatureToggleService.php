<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FeatureToggleService
{
    private const AI_FEATURES_KEY = 'ai_features_enabled';

    private const CACHE_KEY = 'settings.ai_features_enabled';

    private static ?bool $settingsTableExists = null;

    public function aiFeaturesEnabled(): bool
    {
        try {
            if (! $this->settingsTableExists()) {
                return true;
            }

            return Cache::rememberForever(self::CACHE_KEY, function () {
                $raw = SystemSetting::query()
                    ->where('key', self::AI_FEATURES_KEY)
                    ->value('value');

                if ($raw === null) {
                    return true;
                }

                return in_array(strtolower(trim((string) $raw)), ['1', 'true', 'yes', 'on'], true);
            });
        } catch (Throwable) {
            return true;
        }
    }

    public function setAiFeaturesEnabled(bool $enabled, ?int $updatedBy = null): void
    {
        if (! $this->settingsTableExists()) {
            return;
        }

        SystemSetting::query()->updateOrCreate(
            ['key' => self::AI_FEATURES_KEY],
            [
                'value' => $enabled ? '1' : '0',
                'updated_by' => $updatedBy,
            ]
        );

        Cache::forever(self::CACHE_KEY, $enabled);
    }

    private function settingsTableExists(): bool
    {
        if (self::$settingsTableExists !== null) {
            return self::$settingsTableExists;
        }

        self::$settingsTableExists = Schema::hasTable('system_settings');

        return self::$settingsTableExists;
    }
}
