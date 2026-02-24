<?php

return [
    'api' => [
        // Protected bus API default limiter (all authenticated bus endpoints).
        'bus_requests_per_minute' => (int) env('BUS_API_RATE_LIMIT_PER_MINUTE', 60),

        // Dedicated GPS ingest limiter (applies to /api/bus/gps only).
        'bus_gps_requests_per_minute' => (int) env('BUS_GPS_RATE_LIMIT_PER_MINUTE', 60),
    ],

    'dashboard' => [
        'analytics_cache_enabled' => env('COMPANY_DASHBOARD_ANALYTICS_CACHE_ENABLED', true),
        'analytics_cache_ttl_seconds' => (int) env('COMPANY_DASHBOARD_ANALYTICS_CACHE_TTL_SECONDS', 60),
    ],

    'telemetry' => [
        'pruning_enabled' => env('TRANSPORT_TELEMETRY_PRUNING_ENABLED', true),
        'prune_after_days' => (int) env('TRANSPORT_TELEMETRY_PRUNE_AFTER_DAYS', 14),
        'prune_chunk_size' => (int) env('TRANSPORT_TELEMETRY_PRUNE_CHUNK_SIZE', 5000),
        'prune_schedule' => env('TRANSPORT_TELEMETRY_PRUNE_SCHEDULE', '02:30'),
    ],
];
