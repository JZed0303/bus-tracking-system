<!-- Layout config Js -->
<script src="{{ URL::asset('build/js/layout.js') }}"></script>
@yield('css')
<!-- Bootstrap Css -->
<link href="{{ URL::asset('build/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
<!-- Icons Css -->
<link href="{{ URL::asset('build/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
<!-- App Css-->
<link href="{{ URL::asset('build/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
@php($selectedThemeOverridePartial = $selectedThemeOverridePartial ?? 'layouts.theme-color.hm-theme-override')
@includeIf($selectedThemeOverridePartial)

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

    .table-action-cell .action-btn-group,
    .table-action-cell .btn-group,
    .table-action-cell .btn-toolbar,
    .table-action-cell .module-actions,
    .table-action-cell .user-actions,
    .table-action-cell .employee-actions,
    .table-action-cell .driver-actions,
    .table-action-cell .route-actions,
    .table-action-cell .action-icons {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .table-action-cell form {
        display: inline-flex;
        margin: 0;
    }

    .table-action-cell .btn.action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        min-height: 34px;
        padding: 0.4rem 0.6rem;
        border-radius: 0.45rem;
        border: 1px solid transparent;
        font-weight: 600;
        line-height: 1;
        transition: all 0.18s ease-in-out;
    }

    .table-action-cell .btn.action-btn i {
        font-size: 1rem;
        line-height: 1;
    }

    .table-action-cell .btn.action-btn:not(:last-child) {
        margin-right: 0.5rem;
    }

    .table-action-cell .action-btn-group .btn.action-btn:not(:last-child),
    .table-action-cell .btn-group .btn.action-btn:not(:last-child),
    .table-action-cell .btn-toolbar .btn.action-btn:not(:last-child),
    .table-action-cell .module-actions .btn.action-btn:not(:last-child),
    .table-action-cell .user-actions .btn.action-btn:not(:last-child),
    .table-action-cell .employee-actions .btn.action-btn:not(:last-child),
    .table-action-cell .driver-actions .btn.action-btn:not(:last-child),
    .table-action-cell .route-actions .btn.action-btn:not(:last-child),
    .table-action-cell .action-icons .btn.action-btn:not(:last-child) {
        margin-right: 0;
    }

    .table-action-cell .btn.action-btn:hover:not(:disabled),
    .table-action-cell .btn.action-btn:focus-visible:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(37, 46, 75, 0.16);
    }

    .table-action-cell .btn.action-btn:disabled {
        opacity: 0.6;
        transform: none;
        box-shadow: none;
    }

    .table-action-cell .btn.action-btn.action-btn--view {
        color: #fff !important;
        background-color: #1d6fdc !important;
        border-color: #1d6fdc !important;
    }

    .table-action-cell .btn.action-btn.action-btn--edit {
        color: #fff !important;
        background-color: #f59f00 !important;
        border-color: #f59f00 !important;
    }

    .table-action-cell .btn.action-btn.action-btn--delete {
        color: #fff !important;
        background-color: #d63348 !important;
        border-color: #d63348 !important;
    }

    .table-action-cell .btn.action-btn.action-btn--timeline,
    .table-action-cell .btn.action-btn.action-btn--map,
    .table-action-cell .btn.action-btn.action-btn--default {
        color: #fff !important;
        background-color: #495057 !important;
        border-color: #495057 !important;
    }
</style>
