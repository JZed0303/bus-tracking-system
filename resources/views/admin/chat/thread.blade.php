@extends('layouts.master')

@section('title', 'Chat')
@section('page-title', 'Chat')

@section('body')
    <body data-sidebar="colored">
@endsection

@section('content')
    <script>
        window.chatThreadId = @json($threadId);
        window.authUser = @json(auth()->user());
    </script>

    <div class="container-fluid">
        <div id="admin-chat-thread-root"></div>
    </div>
@endsection

@section('scripts')
    <!-- App js -->
    <script src="{{ URL::asset('build/js/app.js') }}"></script>

    <!-- React/Vite entry -->
    @vite('resources/js/admin-chat-threads.jsx')
@endsection
