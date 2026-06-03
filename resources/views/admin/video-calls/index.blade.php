@extends('layouts.master')

@section('title', 'Video Call')
@section('page-title', 'Video Call')

@section('content')
<div
    id="admin-video-call-root"
    data-bus-id="{{ request('bus') }}"
    data-bus-label="{{ request('label') }}"
></div>
@endsection
