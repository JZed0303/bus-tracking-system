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
                <h4 class="card-title mb-1">Settings</h4>
                <p class="card-title-desc mb-4">Manage your interface preferences.</p>

<h6 class="mb-3">Choose Layouts</h6>

<div class="row">
    <!-- Light Mode -->
    <div class="col-md-6 text-center">
        <img src="{{ URL::asset('build/images/layouts/layout-1.jpg') }}" 
             class="img-fluid img-thumbnail mb-2" 
             style="max-width: 250px;" 
             alt="layout-1">

        <div class="form-check form-switch">
            <input class="form-check-input theme-choice" type="checkbox" id="light-mode-switch" checked>
            <label class="form-check-label" for="light-mode-switch">Light Mode</label>
        </div>
    </div>

    <!-- Dark Mode -->
    <div class="col-md-6 text-center">
        <img src="{{ URL::asset('build/images/layouts/layout-2.jpg') }}" 
             class="img-fluid img-thumbnail mb-2" 
             style="max-width: 250px;" 
             alt="layout-2">

        <div class="form-check form-switch">
            <input class="form-check-input theme-choice" type="checkbox" id="dark-mode-switch" 
                   data-bsStyle="build/css/bootstrap-dark.min.css" 
                   data-appStyle="build/css/app-dark.min.css">
            <label class="form-check-label" for="dark-mode-switch">Dark Mode</label>
        </div>
    </div>
</div>
    </div>
</div>
@endsection
