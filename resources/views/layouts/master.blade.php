<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <title>@yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesdesign" name="author" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $resolvedThemeMode = auth()->check() ? (auth()->user()->theme_mode ?: 'light') : 'light';
        $resolvedThemeMode = $resolvedThemeMode === 'dark' ? 'dark' : 'light';
    @endphp

    <script>
        (function () {
            var themeMode = @json($resolvedThemeMode);
            document.documentElement.setAttribute('data-bs-theme', themeMode);

            try {
                if (window.sessionStorage) {
                    sessionStorage.setItem('is_visited', themeMode === 'dark' ? 'dark-mode-switch' : 'light-mode-switch');
                }
            } catch (e) {
                // Intentionally ignored; theme still applies via server-rendered attribute.
            }
        })();
    </script>

    <script>
        window.companyId = @json(auth()->user()?->company_id);
        window.userRole  = @json(auth()->user()?->role);
        window.themeMode = @json($resolvedThemeMode);
    </script>

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('images/logo-header.ico') }}">

    {{-- Vite (React + assets) --}}
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])

    {{-- Default template head CSS (bootstrap, icons, etc.) --}}
    @include('layouts.head-css')
    <link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />

    {{-- NOTE:
         `worthy-global-override.css` is now handled by Vite.
         Make sure you have:
     import '../css/hm-global-override.css';
         inside resources/js/app.jsx
    --}}

    {{-- jQuery (if still needed globally) --}}
    <script
        src="https://code.jquery.com/jquery-3.7.1.js"
        integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous">
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

{{-- In Tocly templates, child views usually define the <body> tag --}}
@yield('body')

<script>
    if (document.body && window.themeMode) {
        document.body.setAttribute('data-bs-theme', window.themeMode);
    }
</script>

@if(auth()->check())
    <script>
        window.authUser = @json([
            'id' => auth()->id(),
        ]);
    </script>
@endif

@if(auth()->check())
    <audio id="chat-sound"
           src="{{ asset('sounds/chat.mp3') }}"
           preload="auto"></audio>
@endif

<!-- Begin page -->
<div id="layout-wrapper">

    <!-- topbar -->
    @include('layouts.topbar')

    <!-- sidebar components -->
    @include('layouts.sidebar')

    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->
    <div class="main-content">

        <div class="page-content">
            <div class="container-fluid">
                @yield('content')
            </div>
            <!-- container-fluid -->
        </div>
        <!-- End Page-content -->

        <!-- footer -->
        @include('layouts.footer')

    </div>
    <!-- end main content-->

</div>
<!-- END layout-wrapper -->

<!-- vendor-scripts (bootstrap.bundle, waves, simplebar, etc.) -->
@include('layouts.vendor-scripts')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Swal === 'undefined') {
            return;
        }

        const toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3200,
            timerProgressBar: true,
        });

        @if(session('success'))
            toast.fire({
                icon: 'success',
                title: @json(session('success')),
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Action Failed',
                text: @json(session('error')),
                confirmButtonColor: '#d33',
            });
        @endif

        @if(session('warning'))
            Swal.fire({
                icon: 'warning',
                title: 'Please Check',
                text: @json(session('warning')),
                confirmButtonColor: '#f59e0b',
            });
        @endif

        @if(session('info'))
            toast.fire({
                icon: 'info',
                title: @json(session('info')),
            });
        @endif

        @if($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: '<ul style="text-align:left; margin:0; padding-left:1.25rem;">' +
                    @json(collect($errors->all())->map(fn ($message) => '<li>' . e($message) . '</li>')->implode('')) +
                    '</ul>',
                confirmButtonColor: '#d33',
            });
        @endif
    });
</script>

</body>
</html>
