@extends('layouts.master')

@section('title', 'Bus Chat')
@section('page-title', 'Bus Chat')

@section('body')
    <body data-sidebar="colored">
@endsection

@section('content')
    {{-- Pass authenticated user data to JS --}}
    <script>
        window.authUser  = @json(auth()->user());
        window.userRole  = @json(auth()->user()->role);
        window.companyId = @json(auth()->user()->company_id);
    </script>

    <div class="container-fluid">
        <div id="admin-bus-chat-root"></div>
    </div>
@endsection

@section('scripts')
    <!-- Core App JS -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>

    <!-- React / Vite Entry -->
    @vite('resources/js/admin-bus-chat.jsx')
@endsection
