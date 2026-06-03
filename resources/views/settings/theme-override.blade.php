@extends('layouts.master')

@php
    $themePreviewMap = [
        'layouts.theme-color.hm-theme-override' => [
            'tag' => 'Default HM',
            'description' => 'Warm transport palette with red, orange, and yellow highlights.',
            'swatches' => ['#B32025', '#E85D04', '#F4C430', '#111827', '#F7F8FA'],
            'sidebar' => 'linear-gradient(180deg, #b4181e 0%, #940005 48%, #660509 100%)',
            'header' => 'linear-gradient(135deg, #B32025 0%, #E85D04 65%, #F4C430 120%)',
            'surface' => '#FFFFFF',
            'accent' => '#E85D04',
        ],
        'layouts.theme-color.hm-theme-override-finance' => [
            'tag' => 'Finance',
            'description' => 'Blue-led finance styling with a clean, structured enterprise feel.',
            'swatches' => ['#346BEC', '#1F7AE0', '#7CC4FF', '#111827', '#F5F8FC'],
            'sidebar' => 'linear-gradient(180deg, #003c89 0%, #094597 48%, #083375 100%)',
            'header' => 'linear-gradient(135deg, #346BEC 0%, #1F7AE0 65%, #7CC4FF 120%)',
            'surface' => '#FFFFFF',
            'accent' => '#1F7AE0',
        ],
        'layouts.theme-color.hm-blue-enterprise' => [
            'tag' => 'Enterprise',
            'description' => 'Bold blue enterprise palette with strong contrast and clean cards.',
            'swatches' => ['#2563EB', '#60A5FA', '#93C5FD', '#111827', '#F5F7FB'],
            'sidebar' => 'linear-gradient(180deg, #153E75 0%, #1D4ED8 60%, #2563EB 100%)',
            'header' => 'linear-gradient(135deg, #2563EB 0%, #60A5FA 72%)',
            'surface' => '#FFFFFF',
            'accent' => '#2563EB',
        ],
        'layouts.theme-color.hm-indigo-minimal' => [
            'tag' => 'Minimal',
            'description' => 'Light, minimal indigo styling with a softer modern dashboard mood.',
            'swatches' => ['#4F46E5', '#A5B4FC', '#C7D2FE', '#111827', '#F9FAFB'],
            'sidebar' => 'linear-gradient(180deg, #FFFFFF 0%, #F3F4F6 100%)',
            'header' => 'linear-gradient(135deg, #4F46E5 0%, #A5B4FC 75%)',
            'surface' => '#FFFFFF',
            'accent' => '#4F46E5',
        ],
        'layouts.theme-color.hm-theme-green-shade' => [
            'tag' => 'Green',
            'description' => 'Fresh green palette with strong success-state energy and bright accents.',
            'swatches' => ['#15803D', '#22C55E', '#4ADE80', '#111827', '#F7F8FA'],
            'sidebar' => 'linear-gradient(180deg, #0f5a2d 0%, #15803D 55%, #166534 100%)',
            'header' => 'linear-gradient(135deg, #15803D 0%, #22C55E 65%, #4ADE80 120%)',
            'surface' => '#FFFFFF',
            'accent' => '#22C55E',
        ],
        'layouts.theme-color.hm-theme-red-black-shade' => [
            'tag' => 'Corporate Red',
            'description' => 'High-contrast red and dark neutral theme for a sharper corporate look.',
            'swatches' => ['#E60012', '#FF4D5A', '#8C000B', '#111827', '#F5F6F8'],
            'sidebar' => 'linear-gradient(180deg, #111111 0%, #1f1f1f 55%, #2d2d2d 100%)',
            'header' => 'linear-gradient(135deg, #E60012 0%, #FF4D5A 70%)',
            'surface' => '#FFFFFF',
            'accent' => '#E60012',
        ],
    ];

    $resolvedThemeCards = collect($partialOptions)->map(function ($label, $path) use ($themePreviewMap) {
        $fallbackAccent = '#2563EB';

        return array_merge([
            'path' => $path,
            'label' => $label,
            'tag' => 'Theme',
            'description' => 'Theme preset for the global admin UI.',
            'swatches' => [$fallbackAccent, '#93C5FD', '#E5E7EB', '#111827', '#FFFFFF'],
            'sidebar' => 'linear-gradient(180deg, #1f2937 0%, #334155 100%)',
            'header' => 'linear-gradient(135deg, #2563EB 0%, #93C5FD 100%)',
            'surface' => '#FFFFFF',
            'accent' => $fallbackAccent,
        ], $themePreviewMap[$path] ?? [], [
            'path' => $path,
            'label' => $label,
        ]);
    })->values();

    $activeThemeCard = $resolvedThemeCards->firstWhere('path', old('theme_override_partial', $selectedPartial))
        ?? $resolvedThemeCards->first();
@endphp

@section('title')
    Theme Override
@endsection

@section('page-title')
    Theme Override
@endsection

@section('body')
    <body data-sidebar="colored">
@endsection

@section('css')
<style>
    .theme-picker-shell {
        display: grid;
        gap: 1.5rem;
    }

    .theme-hero {
        position: relative;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 24px;
        padding: 1.5rem;
        background:
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.16), transparent 26%),
            linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
    }

    .theme-hero::after {
        content: "";
        position: absolute;
        inset: auto -40px -60px auto;
        width: 180px;
        height: 180px;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.14);
        filter: blur(8px);
    }

    .theme-hero-copy {
        position: relative;
        z-index: 1;
        max-width: 760px;
    }

    .theme-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .theme-hero h4 {
        margin: 0.85rem 0 0.35rem;
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
    }

    .theme-hero p {
        margin: 0;
        color: #475569;
        max-width: 64ch;
    }

    .theme-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
    }

    .theme-card-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .theme-card {
        position: relative;
        display: block;
        height: 100%;
        padding: 1rem;
        border: 1px solid #dbe2ea;
        border-radius: 22px;
        background: #ffffff;
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.06);
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        cursor: pointer;
    }

    .theme-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.1);
        border-color: #cbd5e1;
    }

    .theme-card-input:checked + .theme-card {
        border-color: var(--theme-accent, #2563eb);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--theme-accent, #2563eb) 22%, white), 0 22px 40px rgba(15, 23, 42, 0.12);
        transform: translateY(-2px);
    }

    .theme-card-input:focus-visible + .theme-card {
        outline: 3px solid rgba(37, 99, 235, 0.2);
        outline-offset: 2px;
    }

    .theme-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .theme-card-tag {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #334155;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .theme-card-check {
        width: 1.9rem;
        height: 1.9rem;
        border-radius: 999px;
        border: 1px solid #cbd5e1;
        display: grid;
        place-items: center;
        color: transparent;
        background: #ffffff;
        transition: all 0.18s ease;
    }

    .theme-card-input:checked + .theme-card .theme-card-check {
        color: #ffffff;
        border-color: var(--theme-accent, #2563eb);
        background: var(--theme-accent, #2563eb);
    }

    .theme-card-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
    }

    .theme-card-path {
        margin-top: 0.25rem;
        font-size: 0.78rem;
        color: #64748b;
        word-break: break-word;
    }

    .theme-card-description {
        margin: 0.65rem 0 1rem;
        color: #475569;
        min-height: 42px;
    }

    .theme-swatches {
        display: flex;
        gap: 0.45rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .theme-swatch {
        width: 1.8rem;
        height: 1.8rem;
        border-radius: 999px;
        border: 2px solid rgba(255, 255, 255, 0.96);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.14);
    }

    .theme-preview {
        overflow: hidden;
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
    }

    .theme-preview-topbar {
        height: 14px;
        background: var(--theme-header, linear-gradient(135deg, #2563eb 0%, #93c5fd 100%));
    }

    .theme-preview-body {
        display: grid;
        grid-template-columns: 68px 1fr;
        min-height: 138px;
        background: #f8fafc;
    }

    .theme-preview-sidebar {
        padding: 0.6rem 0.45rem;
        background: var(--theme-sidebar, linear-gradient(180deg, #1f2937 0%, #334155 100%));
        display: grid;
        align-content: start;
        gap: 0.45rem;
    }

    .theme-preview-nav {
        height: 9px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.78);
    }

    .theme-preview-nav.is-soft {
        opacity: 0.55;
    }

    .theme-preview-main {
        padding: 0.75rem;
        background: #f8fafc;
    }

    .theme-preview-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }

    .theme-preview-title {
        width: 46%;
        height: 10px;
        border-radius: 999px;
        background: #cbd5e1;
    }

    .theme-preview-chip {
        width: 60px;
        height: 24px;
        border-radius: 999px;
        background: var(--theme-header, linear-gradient(135deg, #2563eb 0%, #93c5fd 100%));
    }

    .theme-preview-panels {
        display: grid;
        grid-template-columns: 1.1fr 0.9fr;
        gap: 0.65rem;
    }

    .theme-preview-panel {
        border-radius: 14px;
        background: var(--theme-surface, #ffffff);
        border: 1px solid #e2e8f0;
        padding: 0.7rem;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    .theme-preview-graph {
        height: 54px;
        border-radius: 12px;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.32), rgba(255, 255, 255, 0)),
            var(--theme-header, linear-gradient(135deg, #2563eb 0%, #93c5fd 100%));
        margin-bottom: 0.55rem;
    }

    .theme-preview-line {
        height: 8px;
        border-radius: 999px;
        background: #dbeafe;
        margin-top: 0.4rem;
    }

    .theme-preview-line.is-dark {
        background: #cbd5e1;
        width: 72%;
    }

    .theme-preview-pill {
        width: 100%;
        height: 18px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--theme-accent, #2563eb) 18%, white);
        margin-bottom: 0.45rem;
    }

    .theme-preview-pill.is-strong {
        background: color-mix(in srgb, var(--theme-accent, #2563eb) 85%, white);
    }

    .theme-selection-bar {
        display: grid;
        gap: 0.85rem;
        padding: 1rem 1.1rem;
        border-radius: 18px;
        border: 1px solid #dbe2ea;
        background: #ffffff;
    }

    .theme-selection-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .theme-selection-meta {
        display: grid;
        gap: 0.2rem;
    }

    .theme-selection-label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .theme-selection-value {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
    }

    .theme-selection-desc {
        color: #475569;
        margin: 0;
    }

    .theme-edit-hint {
        border-radius: 18px;
        padding: 1rem 1.1rem;
        border: 1px dashed #cbd5e1;
        background: #f8fafc;
        color: #475569;
    }

    @media (max-width: 768px) {
        .theme-hero,
        .theme-selection-bar {
            padding: 1rem;
        }

        .theme-preview-body {
            grid-template-columns: 58px 1fr;
        }

        .theme-preview-panels {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="theme-picker-shell">
    <div class="theme-hero">
        <div class="theme-hero-copy">
            <span class="theme-hero-badge">Visual Theme Picker</span>
            <h4>Choose a palette with a real preview, not just a file name</h4>
            <p>
                Pick the global theme override by looking at the palette, sidebar feel, and dashboard tone first.
                The selected preset still saves the same Blade include file behind the scenes.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('settings.theme-override.update') }}" id="theme-override-form">
                @csrf

                <div class="theme-grid">
                    @foreach($resolvedThemeCards as $theme)
                        @php($isSelected = old('theme_override_partial', $selectedPartial) === $theme['path'])
                        <div>
                            <input
                                class="theme-card-input"
                                type="radio"
                                name="theme_override_partial"
                                id="theme-{{ $loop->index }}"
                                value="{{ $theme['path'] }}"
                                data-label="{{ $theme['label'] }}"
                                data-description="{{ $theme['description'] }}"
                                {{ $isSelected ? 'checked' : '' }}
                                required
                            >
                            <label
                                class="theme-card"
                                for="theme-{{ $loop->index }}"
                                style="--theme-header: {{ $theme['header'] }}; --theme-sidebar: {{ $theme['sidebar'] }}; --theme-surface: {{ $theme['surface'] }}; --theme-accent: {{ $theme['accent'] }};"
                            >
                                <div class="theme-card-top">
                                    <span class="theme-card-tag">{{ $theme['tag'] }}</span>
                                    <span class="theme-card-check">
                                        <i class="mdi mdi-check"></i>
                                    </span>
                                </div>

                                <h5 class="theme-card-title">{{ $theme['label'] }}</h5>
                                <div class="theme-card-path">{{ $theme['path'] }}</div>
                                <p class="theme-card-description">{{ $theme['description'] }}</p>

                                <div class="theme-swatches" aria-label="Palette preview">
                                    @foreach($theme['swatches'] as $swatch)
                                        <span class="theme-swatch" style="background-color: {{ $swatch }};" title="{{ $swatch }}"></span>
                                    @endforeach
                                </div>

                                <div class="theme-preview" aria-hidden="true">
                                    <div class="theme-preview-topbar"></div>
                                    <div class="theme-preview-body">
                                        <div class="theme-preview-sidebar">
                                            <div class="theme-preview-nav"></div>
                                            <div class="theme-preview-nav is-soft"></div>
                                            <div class="theme-preview-nav"></div>
                                            <div class="theme-preview-nav is-soft"></div>
                                        </div>
                                        <div class="theme-preview-main">
                                            <div class="theme-preview-toolbar">
                                                <div class="theme-preview-title"></div>
                                                <div class="theme-preview-chip"></div>
                                            </div>
                                            <div class="theme-preview-panels">
                                                <div class="theme-preview-panel">
                                                    <div class="theme-preview-graph"></div>
                                                    <div class="theme-preview-line"></div>
                                                    <div class="theme-preview-line is-dark"></div>
                                                </div>
                                                <div class="theme-preview-panel">
                                                    <div class="theme-preview-pill is-strong"></div>
                                                    <div class="theme-preview-pill"></div>
                                                    <div class="theme-preview-pill"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>

                @if($errors->any())
                    <div class="alert alert-danger mt-3 mb-0">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="theme-selection-bar mt-4">
                    <div class="theme-selection-summary">
                        <div class="theme-selection-meta">
                            <span class="theme-selection-label">Selected Theme</span>
                            <span class="theme-selection-value" id="selected-theme-label">{{ $activeThemeCard['label'] ?? 'Theme' }}</span>
                            <p class="theme-selection-desc" id="selected-theme-description">{{ $activeThemeCard['description'] ?? 'Theme preset for the global admin UI.' }}</p>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Save Theme Override
                        </button>
                    </div>

                    <div class="text-muted" style="font-size: 0.9rem;">
                        Active include path:
                        <code id="selected-theme-path">{{ old('theme_override_partial', $selectedPartial) }}</code>
                    </div>
                </div>

                <div class="theme-edit-hint mt-3">
                    Need deeper customization than preset switching?
                    Edit the matching file under
                    <code>resources/views/layouts/theme-color/</code>
                    to change the actual CSS variables, gradients, and component styling for that preset.
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const inputs = Array.from(document.querySelectorAll('.theme-card-input'));
            const labelEl = document.getElementById('selected-theme-label');
            const descEl = document.getElementById('selected-theme-description');
            const pathEl = document.getElementById('selected-theme-path');

            const syncSelection = (input) => {
                if (!input) return;

                if (labelEl) labelEl.textContent = input.dataset.label || input.value;
                if (descEl) descEl.textContent = input.dataset.description || '';
                if (pathEl) pathEl.textContent = input.value;
            };

            inputs.forEach((input) => {
                input.addEventListener('change', function () {
                    syncSelection(this);
                });
            });

            syncSelection(inputs.find((input) => input.checked));
        });
    </script>
@endsection
