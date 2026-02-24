@extends('layouts.master')

@section('title', 'Group Chats')
@section('page-title', 'Group Chats')

@section('body')
    <body data-sidebar="colored">
@endsection

@section('content')
    <div class="container-fluid">
        <div id="admin-group-chats-root"></div>
    </div>
@endsection

@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
    @vite('resources/js/admin-group-chat.jsx')
@endsection
