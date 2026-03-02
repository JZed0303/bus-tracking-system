<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class ThemeOverride
{
    public const PARTIAL_KEY = 'theme_override_partial';
    public const PARTIAL_CACHE_KEY = 'theme_override_partial_cached';
    public const DEFAULT_PARTIAL = 'layouts.hm-theme-override';

    public const PARTIAL_OPTIONS = [
        'layouts.hm-theme-override' => 'HM Theme Override',
        'layouts.hm-theme-override-finance' => 'HM Theme Override Finance',
    ];

    public static function getPartialOptions(): array
    {
        return self::PARTIAL_OPTIONS;
    }

    public static function getSelectedPartial(): string
    {
        return Cache::rememberForever(self::PARTIAL_CACHE_KEY, function () {
            $setting = SystemSetting::query()
                ->where('key', self::PARTIAL_KEY)
                ->first();

            if (!$setting || !$setting->value) {
                return self::DEFAULT_PARTIAL;
            }

            $selected = (string) $setting->value;

            if (!array_key_exists($selected, self::PARTIAL_OPTIONS)) {
                return self::DEFAULT_PARTIAL;
            }

            return $selected;
        });
    }

    public static function clearPartialCache(): void
    {
        Cache::forget(self::PARTIAL_CACHE_KEY);
    }
}
