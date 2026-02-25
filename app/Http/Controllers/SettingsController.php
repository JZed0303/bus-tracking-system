<?php

namespace App\Http\Controllers;

use App\Support\AuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.index');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme_mode' => ['required', 'in:light,dark'],
        ]);

        $user = $request->user();
        $oldThemeMode = $user?->theme_mode ?: 'light';

        if ($user) {
            $user->forceFill([
                'theme_mode' => $validated['theme_mode'],
            ])->saveQuietly();
        }

        AuditTrail::log(
            event: 'settings_updated',
            auditable: $user,
            oldValues: ['theme_mode' => $oldThemeMode],
            newValues: ['theme_mode' => $validated['theme_mode']],
            tags: 'settings'
        );

        return back()->with('success', 'Settings updated successfully.');
    }
}
