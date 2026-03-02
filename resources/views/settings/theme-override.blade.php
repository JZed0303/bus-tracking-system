@extends('layouts.master')
@section('title')
    Theme Override
@endsection
@section('page-title')
    Theme Override
@endsection
@section('body')
    <body data-sidebar="colored">
@endsection
@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-1">Theme Override File Switch</h4>
                <p class="card-title-desc mb-4">Choose which Blade include file to use in head CSS.</p>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('settings.theme-override.update') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="theme_override_partial">Theme Override Include Path</label>
                            <select class="form-select" id="theme_override_partial" name="theme_override_partial" required>
                                @foreach($partialOptions as $path => $label)
                                    <option
                                        value="{{ $path }}"
                                        {{ old('theme_override_partial', $selectedPartial) === $path ? 'selected' : '' }}>
                                        {{ $label }} ({{ $path }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">This controls which `@@include('...')` file is used by `head-css` globally.</small>
                        </div>
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

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Save File Switch</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
