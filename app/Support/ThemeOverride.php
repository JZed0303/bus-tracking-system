<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ThemeOverride
{
    public const PARTIAL_KEY = 'theme_override_partial';
    public const PARTIAL_CACHE_KEY = 'theme_override_partial_cached';
    public const DEFAULT_PARTIAL = 'layouts.theme-color.hm-theme-override';
    private const THEME_VIEW_PATH = 'views/layouts/theme-color';
    private const THEME_PARTIAL_PREFIX = 'layouts.theme-color.';

    public static function getPartialOptions(): array
    {
        $directory = resource_path(self::THEME_VIEW_PATH);
        $options = [];

        if (File::isDirectory($directory)) {
            foreach (File::files($directory) as $file) {
                $filename = $file->getFilename();

                if (Str::endsWith($filename, '.blade.php')) {
                    $partialName = Str::beforeLast($filename, '.blade.php');
                } elseif (Str::endsWith($filename, '.php')) {
                    $partialName = Str::beforeLast($filename, '.php');
                } else {
                    continue;
                }

                $partial = self::THEME_PARTIAL_PREFIX . $partialName;
                $options[$partial] = self::buildLabelFromPartialName($partialName);
            }
        }

        if (empty($options)) {
            return [self::DEFAULT_PARTIAL => self::buildLabelFromPartialName('hm-theme-override')];
        }

        ksort($options);

        if (array_key_exists(self::DEFAULT_PARTIAL, $options)) {
            $defaultLabel = $options[self::DEFAULT_PARTIAL];
            unset($options[self::DEFAULT_PARTIAL]);
            $options = [self::DEFAULT_PARTIAL => $defaultLabel] + $options;
        }

        return $options;
    }

    public static function getSelectedPartial(): string
    {
        $partialOptions = self::getPartialOptions();
        $defaultPartial = self::resolveDefaultPartial($partialOptions);
        $cached = Cache::get(self::PARTIAL_CACHE_KEY);

        if (is_string($cached) && array_key_exists($cached, $partialOptions)) {
            return $cached;
        }

        if (is_string($cached)) {
            Cache::forget(self::PARTIAL_CACHE_KEY);
        }

        return Cache::rememberForever(self::PARTIAL_CACHE_KEY, function () use ($partialOptions, $defaultPartial) {
            $setting = SystemSetting::query()
                ->where('key', self::PARTIAL_KEY)
                ->first();

            if (!$setting || !$setting->value) {
                return $defaultPartial;
            }

            $selected = (string) $setting->value;

            if (!array_key_exists($selected, $partialOptions)) {
                return $defaultPartial;
            }

            return $selected;
        });
    }

    public static function clearPartialCache(): void
    {
        Cache::forget(self::PARTIAL_CACHE_KEY);
    }

    private static function resolveDefaultPartial(array $partialOptions): string
    {
        if (array_key_exists(self::DEFAULT_PARTIAL, $partialOptions)) {
            return self::DEFAULT_PARTIAL;
        }

        return array_key_first($partialOptions) ?? self::DEFAULT_PARTIAL;
    }

    private static function buildLabelFromPartialName(string $partialName): string
    {
        return (string) Str::of($partialName)
            ->replace(['-', '_'], ' ')
            ->title()
            ->replace('Hm', 'HM');
    }
}
