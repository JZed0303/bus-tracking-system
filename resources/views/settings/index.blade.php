@extends('layouts.master')
@section('title')
    Settings
@endsection
@section('page-title')
    Settings
@endsection
@section('body')
    <body data-sidebar="colored">
@endsection
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-1">Appearance Settings</h4>
                <p class="card-title-desc mb-4">Choose and persist your preferred theme mode.</p>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @php
                    $currentTheme = auth()->user()?->theme_mode ?: 'light';
                @endphp

                <h6 class="mb-3">Choose Theme Mode</h6>
                <form method="POST" action="{{ route('settings.preferences.update') }}">
                    @csrf
                    <div class="row g-4">
                        <div class="col-md-6 text-center">
                            <img src="{{ URL::asset('build/images/layouts/layout-1.jpg') }}"
                                 class="img-fluid img-thumbnail mb-2"
                                 style="max-width: 250px;"
                                 alt="Light layout preview">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="theme_mode"
                                    id="theme-light"
                                    value="light"
                                    {{ $currentTheme === 'light' ? 'checked' : '' }}>
                                <label class="form-check-label" for="theme-light">Light Mode</label>
                            </div>
                        </div>

                        <div class="col-md-6 text-center">
                            <img src="{{ URL::asset('build/images/layouts/layout-2.jpg') }}"
                                 class="img-fluid img-thumbnail mb-2"
                                 style="max-width: 250px;"
                                 alt="Dark layout preview">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    name="theme_mode"
                                    id="theme-dark"
                                    value="dark"
                                    {{ $currentTheme === 'dark' ? 'checked' : '' }}>
                                <label class="form-check-label" for="theme-dark">Dark Mode</label>
                            </div>
                        </div>
                    </div>

                    @error('theme_mode')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const radios = document.querySelectorAll('input[name="theme_mode"]');

        radios.forEach(function (radio) {
            radio.addEventListener('change', function (event) {
                const mode = event.target.value === 'dark' ? 'dark' : 'light';
                document.documentElement.setAttribute('data-bs-theme', mode);
                document.body.setAttribute('data-bs-theme', mode);

                try {
                    sessionStorage.setItem('is_visited', mode === 'dark' ? 'dark-mode-switch' : 'light-mode-switch');
                } catch (e) {
                    // Ignore storage failures.
                }
            });
        });
    });
</script>
@endsection
