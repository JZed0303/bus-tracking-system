<!-- Layout config Js -->
<script src="{{ URL::asset('build/js/layout.js') }}"></script>
@yield('css')
<!-- Bootstrap Css -->
<link href="{{ URL::asset('build/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
<!-- Icons Css -->
<link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
<!-- App Css-->
<link href="{{ URL::asset('build/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
<!-- @include('layouts.hm-theme-override') -->
@include('layouts.hm-theme-override-finance')

<style>
    .table thead th,
    table.dataTable thead th {
        text-align: center !important;
        vertical-align: middle;
    }

    .auto-center-column {
        text-align: center !important;
    }

    .table-action-cell {
        text-align: center !important;
        vertical-align: middle;
    }

    .table-action-cell .btn-group,
    .table-action-cell .btn-toolbar {
        justify-content: center;
        margin-left: auto;
        margin-right: auto;
    }
</style>
