@extends('layouts.master')

@section('title', 'Live Bus Tracking')
@section('page-title', '')

@section('body')
<body data-sidebar="colored" class="map-only-page">
@endsection

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
/* =========================================================
   MAP ONLY / FULLSCREEN PAGE RESET
   ========================================================= */
.map-only-page,
.map-only-page body {
    height: 100vh !important;
    overflow: hidden !important;
}

/* Common wrappers (works across many admin templates) */
.map-only-page #layout-wrapper,
.map-only-page .main-content,
.map-only-page .page-content,
.map-only-page .container-fluid {
    height: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
    max-width: 100% !important;
}

/* If template reserves space for sidebar */
.map-only-page .main-content {
    margin-left: 0 !important;
}

/* Hide layout chrome */
.map-only-page .vertical-menu,
.map-only-page #sidebar-menu,
.map-only-page .navbar-header,
.map-only-page header,
.map-only-page .topbar,
.map-only-page .footer,
.map-only-page footer {
    display: none !important;
}

/* React mount fills entire viewport */
#admin-live-map {
    height: 100vh;
    width: 100vw;
}

/* Leaflet fills the mount */
#admin-live-map .leaflet-container {
    height: 100% !important;
    width: 100% !important;
}

/* =========================================================
   LEAFLET CONTROLS - CLEAN, CONSISTENT UI
   ========================================================= */

/* Control spacing */
.leaflet-top.leaflet-left {
    margin-top: 12px;
    margin-left: 12px;
}

/* Make ALL leaflet bars consistent (zoom + custom controls) */
.leaflet-bar {
    border: 0 !important;
    border-radius: 10px !important;
    overflow: hidden;
    box-shadow: 0 6px 18px rgba(0,0,0,.18);
    background: rgba(255,255,255,.95);
    backdrop-filter: blur(6px);
}

/* Zoom buttons */
.leaflet-control-zoom a {
    width: 38px !important;
    height: 38px !important;
    line-height: 38px !important;
    color: #1f2937 !important; /* slate-ish */
    background: transparent !important;
    border: 0 !important;
}

/* Divider between + and - */
.leaflet-control-zoom a:first-child {
    border-bottom: 1px solid rgba(0,0,0,.10) !important;
}

.leaflet-control-zoom a:hover {
    background: rgba(13,110,253,.12) !important;
}

/* =========================================================
   CUSTOM CONTROL (HOME + RESET) ABOVE ZOOM
   Uses .leaflet-bar too, so it matches perfectly
   ========================================================= */
.map-custom-controls {
    display: flex;
    flex-direction: column;
}

.map-custom-controls .map-control-btn {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    font-size: 16px;
    color: #1f2937;
    cursor: pointer;
    user-select: none;
    background: transparent;
}

/* Divider between custom buttons */
.map-custom-controls .map-control-btn:not(:last-child) {
    border-bottom: 1px solid rgba(0,0,0,.10);
}

.map-custom-controls .map-control-btn:hover {
    background: rgba(13,110,253,.12);
}

/* Add a small gap between custom control and zoom */
.leaflet-control.map-custom-controls {
    margin-bottom: 10px !important;
}
.bus-row {
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.bus-row:hover {
  background-color: #f8f9fa;
}

</style>
@endsection

@section('content')
<div id="admin-live-map"></div>
@endsection

@section('scripts')
<script>
  console.log('[globals]', window.userRole, window.companyId, !!window.Echo);
</script>

<script src="{{ URL::asset('build/js/app.js') }}"></script>

@vite('resources/js/app.jsx')
@endsection
