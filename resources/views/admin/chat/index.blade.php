@extends('layouts.master')

@section('title', 'My Chats')
@section('page-title', 'My Chats')

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
 <script>
  window.authUser = @json(auth()->user());
  window.chatBase = @json('/admin/chat');
</script>

  <div class="container-fluid">
    <div id="admin-chat-root"></div>
  </div>
@endsection

@section('scripts')
  <script src="{{ URL::asset('build/js/app.js') }}"></script>
  @vite('resources/js/admin-chat-root.jsx')
@endsection

