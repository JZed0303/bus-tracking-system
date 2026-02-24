<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <title>@yield('title') | Tocly - Admin & Dashboard Template</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesdesign" name="author" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        window.companyId = @json(auth()->user()?->company_id);
        window.userRole  = @json(auth()->user()?->role);
    </script>

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">

    {{-- Vite (React + assets) --}}
    @viteReactRefresh
    @vite(['resources/js/app.jsx'])

    {{-- Default template head CSS (bootstrap, icons, etc.) --}}
    @include('layouts.head-css')

    {{-- NOTE:
         `worthy-global-override.css` is now handled by Vite.
         Make sure you have:
         import '../css/worthy-global-override.css';
         inside resources/js/app.jsx
    --}}

    {{-- jQuery (if still needed globally) --}}
    <script
        src="https://code.jquery.com/jquery-3.7.1.js"
        integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous">
    </script>
</head>

{{-- In Tocly templates, child views usually define the <body> tag --}}
@yield('body')

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

<!-- customizer -->
@include('layouts.right-sidebar')

<!-- vendor-scripts (bootstrap.bundle, waves, simplebar, etc.) -->
@include('layouts.vendor-scripts')

</body>
</html>
