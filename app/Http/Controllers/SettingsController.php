<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Support\AuditTrail;
use App\Support\ThemeOverride;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;

class SettingsController extends Controller
{
    public function themeOverride(): View
    {
        return view('settings.theme-override', [
            'partialOptions' => ThemeOverride::getPartialOptions(),
            'selectedPartial' => ThemeOverride::getSelectedPartial(),
        ]);
    }

    public function updateThemeOverride(Request $request): RedirectResponse
    {
        $oldSelectedPartial = ThemeOverride::getSelectedPartial();

        $validated = $request->validate([
            'theme_override_partial' => ['required', 'in:' . implode(',', array_keys(ThemeOverride::getPartialOptions()))],
        ]);

        SystemSetting::updateOrCreate(
            ['key' => ThemeOverride::PARTIAL_KEY],
            ['value' => $validated['theme_override_partial']]
        );

        ThemeOverride::clearPartialCache();

        AuditTrail::log(
            event: 'settings_theme_override_updated',
            auditable: $request->user(),
            oldValues: ['theme_override_partial' => $oldSelectedPartial],
            newValues: ['theme_override_partial' => $validated['theme_override_partial']],
            tags: 'settings'
        );

        return back()->with('success', 'Theme override file switched successfully.');
    }
}
